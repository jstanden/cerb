<?php

class CerberusDashboardViewColumn {
	public $column;
	public $name;
	
	public function CerberusDashboardViewColumn($column, $name) {
		$this->column = $column;
		$this->name = $name;
	}
}

class CerberusDashboardView {
	public $id = 0;
	public $name = "";
	public $dashboard_id = 0;
	public $params = array();
	
	public $renderPage = 0;
	public $renderLimit = 10;
	public $renderSortBy = 't.subject';
	public $renderSortAsc = 1;
	
	function getTickets() {
		$tickets = CerberusTicketDAO::searchTickets(
			$this->params,
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc
		);
		return $tickets;	
	}
};

class CerberusSearchCriteria {
	public $field;
	public $operator;
	public $value;
	
	/**
	 * Enter description here...
	 *
	 * @param string $field
	 * @param string $oper
	 * @param mixed $value
	 * @return CerberusSearchCriteria
	 */
	 public function CerberusSearchCriteria($field,$oper,$value) {
		$this->field = $field;
		$this->operator = $oper;
		$this->value = $value;
	}
};

class CerberusMessageType {
	const EMAIL = 'E';
	const FORWARD = 'F';
	const COMMENT = 'C';
};

class CerberusTicketBits {
	const CREATED_FROM_WEB = 1;
};

class CerberusTicketStatus {
	const OPEN = 'O';
	const WAITING = 'W';
	const CLOSED = 'C';
	const DELETED = 'D';
};

class CerberusAddressBits {
	const AGENT = 1;
	const BANNED = 2;
	const QUEUE = 4;
};

class CerberusParser {
	
	/**
	 * Enter description here...
	 * @param object $rfcMessage
	 * @return integer ticket id
	 */
	static public function parseMessage($rfcMessage) {
//		print_r($rfcMessage);

		$headers =& $rfcMessage->headers;

		// To/From/Cc/Bcc
		$sReturnPath = @$headers['return-path'];
		$sReplyTo = @$headers['reply-to'];
		$sFrom = @$headers['from'];
		$sTo = @$headers['to'];
		$sMask = CerberusApplication::generateTicketMask();
		
		$from = array();
		$to = array();
		
		if(!empty($sReplyTo)) {
			$from = CerberusParser::parseRfcAddress($sReplyTo);
		} elseif(!empty($sFrom)) {
			$from = CerberusParser::parseRfcAddress($sFrom);
		} elseif(!empty($sReturnPath)) {
			$from = CerberusParser::parseRfcAddress($sReturnPath);
		}
		
		if(!empty($sTo)) {
			$to = CerberusParser::parseRfcAddress($sTo);
		}
		
		// Subject
		$sSubject = @$headers['subject'];
		
		// Date
		$iDate = strtotime(@$headers['date']);
		if(empty($iDate)) $iDate = gmmktime();
		
		// Message Id / References / In-Reply-To
//		echo "Parsing message-id: ",@$headers['message-id'],"<BR>\r\n";

		if(empty($from) || !is_array($from))
			return false;
		
		$fromAddress = $from[0]->mailbox.'@'.$from[0]->host;
		$fromPersonal = $from[0]->personal;
		$fromAddressId = CerberusContactDAO::createAddress($fromAddress, $fromPersonal);

		if(is_array($to))
		foreach($to as $recipient) {
			$toAddress = $recipient->mailbox.'@'.$recipient->host;
			$toPersonal = $recipient->personal;
			$toAddressId = CerberusContactDAO::createAddress($toAddress,$toPersonal);
		}
		
		$sReferences = @$headers['references'];
		$sInReplyTo = @$headers['in-reply-to'];
		
		// [JAS] [TODO] References header may contain multiple message-ids to find
//		if(!empty($sReferences) || !empty($sInReplyTo)) {
		if(!empty($sInReplyTo)) {
//			$findMessageId = (!empty($sInReplyTo)) ? $sInReplyTo : $sReferences;
			$findMessageId = $sInReplyTo;
			$id = CerberusTicketDAO::getTicketByMessageId($findMessageId);
		}
		
		if(empty($id)) {
			$mailbox_id = CerberusParser::parseDestination($headers);
			$id = CerberusTicketDAO::createTicket($sMask,$sSubject,CerberusTicketStatus::OPEN,$mailbox_id,$fromAddress,$iDate);
		}
		
		// [JAS]: Add requesters to the ticket
		CerberusTicketDAO::createRequester($fromAddressId,$id);
		
		$attachments = array();
		$attachments['plaintext'] = '';
		$attachments['html'] = '';
		$attachments['files'] = array();
		
		if(is_array($rfcMessage->parts)) {
			CerberusParser::parseMimeParts($rfcMessage->parts,$attachments);
		} else {
			CerberusParser::parseMimePart($rfcMessage,$attachments);			
		}

		if(!empty($attachments)) {
			$message_id = CerberusTicketDAO::createMessage($id,CerberusMessageType::EMAIL,$iDate,$fromAddressId,$headers,$attachments['plaintext']);
		}
		foreach ($attachments['files'] as $filepath => $filename) {
			CerberusTicketDAO::createAttachment($message_id, $filename, $filepath);
		}
			
		$ticket = CerberusTicketDAO::getTicket($id);
		return $ticket;
	}
	
	/**
	 * Enter description here...
	 *
	 * @todo
	 * @param array $headers
	 * @return integer
	 */
	static private function parseDestination($headers) {
		$addresses = array();
		
		// [TODO] The split could be handled by Mail_RFC822:parseAddressList (commas, semi-colons, etc.)

		$aTo = split(',', @$headers['to']);
		$aCc = split(',', @$headers['cc']);
		
		$destinations = $aTo + $aCc;
		
		foreach($destinations as $destination) {
			$structure = CerberusParser::parseRfcAddress($destination);
			
			if(empty($structure[0]->mailbox) || empty($structure[0]->host))
				continue;
			
			$address = $structure[0]->mailbox.'@'.$structure[0]->host;
				
			if(null != ($mailbox_id = CerberusContactDAO::getMailboxIdByAddress($address)))
				return $mailbox_id;
		}
		
		// envelope + delivered 'Delivered-To'
		// received
		
		// [TODO] catchall?
		
		return null;
	}
	
	static private function parseMimeParts($parts,&$attachments) {
		
		foreach($parts as $part) {
			CerberusParser::parseMimePart($part,$attachments);
		}
		
		return $attachments;
	}
	
	static private function parseMimePart($part,&$attachments) {
		$contentType = @$part->ctype_primary.'/'.@$part->ctype_secondary;
		$fileName = @$part->d_parameters['filename'];
		if (empty($fileName)) $fileName = @$part->ctype_parameters['name'];
		
		if(0 == strcasecmp($contentType,'text/plain') && empty($fileName)) {
			$attachments['plaintext'] .= $part->body;
			
		} elseif(0 == strcasecmp($contentType,'text/html') && empty($fileName)) {
			$attachments['html'] .= $part->body;
			
		} elseif(0 == strcasecmp(@$part->ctype_primary,'multipart')) {
			CerberusParser::parseMimeParts($part);
			
		} else {
			// valid primary types are found at http://www.iana.org/assignments/media-types/
			$timestamp = gmdate('Y.m.d.H.i.s.', gmmktime());
			list($usec, $sec) = explode(' ', microtime());
			$timestamp .= $usec . '.';
			if (false !== file_put_contents(UM_ATTACHMENT_SAVE_PATH . $timestamp . $fileName, $part->body)) {
				$attachments['files'][$timestamp.$fileName] = $fileName;
//				$attachments['plaintext'] .= ' Saved file <a href="' . UM_ATTACHMENT_ACCESS_PATH . $timestamp . $fileName . '">'
//											. (empty($fileName) ? 'Unnamed file' : $fileName) . '</a>. ';
			}
		}
	}
	
	static private function parseRfcAddress($address_string) {
		require_once(UM_PATH . '/libs/pear/Mail/RFC822.php');
		$structure = Mail_RFC822::parseAddressList($address_string, null, false);
		return $structure;
	}
	
};

class CerberusTicket {
	public $id;
	public $mask;
	public $subject;
	public $bitflags;
	public $status;
	public $priority;
	public $mailbox_id;
	public $first_wrote;
	public $last_wrote;
	public $created_date;
	public $updated_date;
	
	function CerberusTicket() {}
	
	function getMessages() {
		$messages = CerberusTicketDAO::getMessagesByTicket($this->id);
		return $messages[0];
	}
	
	function getRequesters() {
		$requesters = CerberusTicketDAO::getRequestersByTicket($this->id);
		return $requesters;
	}
};

class CerberusMessage {
	public $id;
	public $ticket_id;
	public $message_type;
	public $created_date;
	public $address_id;
	public $message_id;
	public $headers;
	private $content; // use getter
	
	function CerberusMessage() {}
	
	function getContent() {
		return CerberusTicketDAO::getMessageContent($this->id);
	}

	/**
	 * returns an array of the message's attachments
	 *
	 * @return CerberusAttachment[]
	 */
	function getAttachments() {
		$attachments = CerberusTicketDAO::getAttachmentsByMessage($this->id);
		return $attachments;
	}

};

class CerberusAddress {
	public $id;
	public $email;
	public $personal;
	public $bitflags;
	
	function CerberusAddress() {}
};

class CerberusAttachment {
	public $id;
	public $message_id;
	public $display_name;
	public $filepath;
	
	function CerberusAttachment() {}
};

class CerberusMailbox {
	public $id;
	public $name;
	public $reply_address_id;
	public $display_name;
	
	function CerberusMailbox() {}
};

?>