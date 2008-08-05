<?php
/***********************************************************************
 | Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
 |-----------------------------------------------------------------------
 | All source code & content (c) Copyright 2007, WebGroup Media LLC
 |   unless specifically noted otherwise.
 |
 | This source code is released under the Cerberus Public License.
 | The latest version of this license can be found here:
 | http://www.cerberusweb.com/license.php
 |
 | By using this software, you acknowledge having read this license
 | and agree to be bound thereby.
 | ______________________________________________________________________
 |	http://www.cerberusweb.com	  http://www.webgroupmedia.com/
 ***********************************************************************/
/*
 * IMPORTANT LICENSING NOTE from your friends on the Cerberus Helpdesk Team
 *
 * Sure, it would be so easy to just cheat and edit this file to use the
 * software without paying for it.  But we trust you anyway.  In fact, we're
 * writing this software for you!
 *
 * Quality software backed by a dedicated team takes money to develop.  We
 * don't want to be out of the office bagging groceries when you call up
 * needing a helping hand.  We'd rather spend our free time coding your
 * feature requests than mowing the neighbors' lawns for rent money.
 *
 * We've never believed in encoding our source code out of paranoia over not
 * getting paid.  We want you to have the full source code and be able to
 * make the tweaks your organization requires to get more done -- despite
 * having less of everything than you might need (time, people, money,
 * energy).  We shouldn't be your bottleneck.
 *
 * We've been building our expertise with this project since January 2002.  We
 * promise spending a couple bucks [Euro, Yuan, Rupees, Galactic Credits] to
 * let us take over your shared e-mail headache is a worthwhile investment.
 * It will give you a sense of control over your in-box that you probably
 * haven't had since spammers found you in a game of "E-mail Address
 * Battleship".  Miss. Miss. You sunk my in-box!
 *
 * A legitimate license entitles you to support, access to the developer
 * mailing list, the ability to participate in betas and the warm fuzzy
 * feeling of feeding a couple obsessed developers who want to help you get
 * more done than 'the other guy'.
 *
 * - Jeff Standen, Mike Fogg, Brenan Cavish, Darren Sugita, Dan Hildebrandt
 * 		and Joe Geck.
 *   WEBGROUP MEDIA LLC. - Developers of Cerberus Helpdesk
 */
/*
 * PARAMS (overloads):
 * parse_max=n (max tickets to parse)
 *
 */
class ParseCron extends CerberusCronPageExtension {
	function scanDirMessages($dir) {
		if(substr($dir,-1,1) != DIRECTORY_SEPARATOR) $dir .= DIRECTORY_SEPARATOR;
		$files = glob($dir . '*.msg');
		if ($files === false) return array();
		return $files;
	}

	function run() {
		$logger = DevblocksPlatform::getConsoleLog();
		
		$logger->info("[Parser] Starting Parser Task");
		
		if (!extension_loaded("imap")) die("IMAP Extension not loaded!");
		@ini_set('memory_limit','64M');

		$timeout = ini_get('max_execution_time');
		$runtime = microtime(true);
		 
		// Allow runtime overloads (by host, etc.)
		@$gpc_parse_max = DevblocksPlatform::importGPC($_REQUEST['parse_max'],'integer');
		
		$total = !empty($gpc_parse_max) ? $gpc_parse_max : $this->getParam('max_messages', 500);

		$mailDir = APP_MAIL_PATH . 'new' . DIRECTORY_SEPARATOR;
		$subdirs = glob($mailDir . '*', GLOB_ONLYDIR);
		if ($subdirs === false) $subdirs = array();
		$subdirs[] = $mailDir; // Add our root directory last

		foreach($subdirs as $subdir) {
			if(!is_writable($subdir)) {
				$logger->err('[Parser] Write permission error, unable parse messages inside: '. $subdir. "...skipping");
				continue;
			}

			$files = $this->scanDirMessages($subdir);
			 
			foreach($files as $file) {
				$filePart = basename($file);
				$parseFile = APP_MAIL_PATH . 'fail' . DIRECTORY_SEPARATOR . $filePart;
				rename($file, $parseFile);
				$this->_parseFile($parseFile);
				//				flush();
				if(--$total <= 0) break;
			}
			if($total <= 0) break;
		}
	  
		unset($files);
		unset($subdirs);
	  
		$logger->info("[Parser] Total Runtime: ".((microtime(true)-$runtime)*1000)." ms");
	}

	function _parseFile($full_filename) {
		$logger = DevblocksPlatform::getConsoleLog();
		
		$fileparts = pathinfo($full_filename);
		$logger->info("[Parser] Reading ".$fileparts['basename']."...");

		$time = microtime(true);

		$mime = mailparse_msg_parse_file($full_filename);
		$message = CerberusParser::parseMime($mime, $full_filename);

		$time = microtime(true) - $time;
		$logger->info("[Parser] Decoded! (".sprintf("%d",($time*1000))." ms)");

		//	    echo "<b>Plaintext:</b> ", $message->body,"<BR>";
		//	    echo "<BR>";
		//	    echo "<b>HTML:</b> ", htmlentities($message->htmlbody), "<BR>";
		//	    echo "<BR>";
		//	    echo "<b>Files:</b> "; print_r($message->files); echo "<BR>";
		//	    echo "<HR>";

		$time = microtime(true);
		$ticket_id = CerberusParser::parseMessage($message);
		$time = microtime(true) - $time;
		
		$logger->info("[Parser] Parsed! (".sprintf("%d",($time*1000))." ms) " .
			(!empty($ticket_id) ? ("(Ticket ID: ".$ticket_id.")") : ("(Local Delivery Rejected.)")));

		@unlink($full_filename);
		mailparse_msg_free($mime);

		//		flush();
	}

	function configure($instance) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl_path = dirname(__FILE__) . '/templates/';
		$tpl->assign('path', $tpl_path);

		$tpl->assign('max_messages', $this->getParam('max_messages', 500));

		$tpl->display($tpl_path . 'cron/parser/config.tpl.php');
	}

	function saveConfigurationAction() {
		@$max_messages = DevblocksPlatform::importGPC($_POST['max_messages'],'integer');
		$this->setParam('max_messages', $max_messages);
	}
};

/*
 * PARAMS (overloads):
 * maint_max_deletes=n (max tickets to purge)
 *
 */
// [TODO] Clear idle temp files (fileatime())
class MaintCron extends CerberusCronPageExtension {
	function run() {
		$logger = DevblocksPlatform::getConsoleLog();
		
		$logger->info("[Maint] Starting Maintenance Task");
		
		@ini_set('memory_limit','64M');

		$db = DevblocksPlatform::getDatabaseService();

		// Purge Deleted Content
		$purge_waitdays = intval($this->getParam('purge_waitdays', 7));

		$sql = sprintf("DELETE QUICK FROM ticket ".
			"WHERE is_deleted = 1 ".
			"AND updated_date < unix_timestamp(date_sub(NOW(),INTERVAL \"%d\" DAY))",
			$purge_waitdays
		);
		$db->Execute($sql);
		
		$logger->info("[Maint] Purged deleted tickets!");

		// Give plugins a chance to run maintenance (nuke NULL rows, etc.)
	    $eventMgr = DevblocksPlatform::getEventService();
	    $eventMgr->trigger(
	        new Model_DevblocksEvent(
	            'cron.maint',
                array()
            )
	    );
		
		// Nuke orphaned words from the Bayes index
		// [TODO] Make this configurable from job
		$sql = "DELETE FROM bayes_words WHERE nonspam + spam < 2"; // only 1 occurrence
		$db->Execute($sql);

		// [mdf] Remove any empty directories inside storage/mail/new
		$mailDir = APP_MAIL_PATH . 'new' . DIRECTORY_SEPARATOR;
		$subdirs = glob($mailDir . '*', GLOB_ONLYDIR);
		if ($subdirs !== false) {
			foreach($subdirs as $subdir) {
				$directory_empty = count(glob($subdir. DIRECTORY_SEPARATOR . '*')) === 0;
				if($directory_empty && is_writeable($subdir)) {
					rmdir($subdir);
				}
			}
		}
	  
		// [JAS] Remove any empty directories inside storage/import/new
		$importNewDir = APP_PATH . '/storage/import/new' . DIRECTORY_SEPARATOR;
		$subdirs = glob($importNewDir . '*', GLOB_ONLYDIR);
		if ($subdirs !== false) {
			foreach($subdirs as $subdir) {
				$directory_empty = count(glob($subdir. DIRECTORY_SEPARATOR . '*')) === 0;
				if($directory_empty && is_writeable($subdir)) {
					rmdir($subdir);
				}
			}
		}
		
	}

	function configure($instance) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl_path = dirname(__FILE__) . '/templates/';
		$tpl->assign('path', $tpl_path);

		$tpl->assign('purge_waitdays', $this->getParam('purge_waitdays', 7));

		$tpl->display($tpl_path . 'cron/maint/config.tpl.php');
	}

	function saveConfigurationAction() {
		@$purge_waitdays = DevblocksPlatform::importGPC($_POST['purge_waitdays'],'integer');
		$this->setParam('purge_waitdays', $purge_waitdays);
	}
};

/**
 * Plugins can implement an event listener on the heartbeat to do any kind of
 * time-dependent or interval-based events.  For example, doing a workflow
 * action every 5 minutes.
 */
class HeartbeatCron extends CerberusCronPageExtension {
	function run() {
		// Heartbeat Event
		$eventMgr = DevblocksPlatform::getEventService();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
	            'cron.heartbeat',
				array(
				)
			)
		);
	}

	function configure($instance) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl_path = dirname(__FILE__) . '/templates/';
		$tpl->assign('path', $tpl_path);

		$tpl->display($tpl_path . 'cron/heartbeat/config.tpl.php');
	}
};

class ImportCron extends CerberusCronPageExtension {
	function run() {
		$logger = DevblocksPlatform::getConsoleLog();
		
		$logger->info("[Importer] Starting Import Task");
		
		@set_time_limit(0); // Unlimited (if possible)
		@ini_set('memory_limit','64M');
		 
		$importNewDir = APP_PATH . '/storage/import/new/';
		$importFailDir = APP_PATH . '/storage/import/fail/';

		if(!is_writable($importNewDir)) {
			$logger->err("[Importer] Unable to write in '$importNewDir'.  Please check permissions.");
			return;
		}

		if(!is_writable($importFailDir)) {
			$logger->err("[Importer] Unable to write in '$importFailDir'.  Please check permissions.");
			return;
		}

		$limit = 500; // [TODO] Set from config

		$runtime = microtime(true);

		$subdirs = glob($importNewDir . '*', GLOB_ONLYDIR);
		if ($subdirs === false) $subdirs = array();
		$subdirs[] = $importNewDir; // Add our root directory last

		foreach($subdirs as $subdir) {
			if(!is_writable($subdir)) {
				$logger->err('[Importer] Write permission error, unable parse imports inside: '. $subdir. "...skipping");
				continue;
			}

			$files = $this->scanDirMessages($subdir);
			 
			foreach($files as $file) {
				// If we can't nuke the file, there's no sense in trying to import it
				if(!is_writeable($file))
					continue;

				$file_part = basename($file);
				 
				$xml_root = simplexml_load_file($file); /* @var $xml_root SimpleXMLElement */
				$object_type = $xml_root->getName();

				$logger->info("[Importer] Reading ".$file_part." ... ($object_type)");

				if($this->_handleImport($object_type, $xml_root)) { // Success
					@unlink($file);

				} else { // Missed (move to fail) 
					$move_to_dir = $importFailDir . basename($subdir) . '/';

					if(!file_exists($move_to_dir))
					mkdir($move_to_dir,0744,true);

					@rename($file, $move_to_dir . basename($file));
				}
				 
				if(--$limit <= 0)
				break;
			}
				
			if($limit <= 0)
			break;
		}
	  
		unset($files);
		unset($subdirs);

		$logger->info("[Importer] Total Runtime: ".((microtime(true)-$runtime)*1000)." ms");
	}

	private function _handleImport($object_type, $xml) {
		// [TODO] Import extensions (delegate to plugins)
		switch($object_type) {
		 	case 'ticket':
		 		return $this->_handleImportTicket($xml);
		 		break;
		 	case 'comment':
		 		return $this->_handleImportComment($xml);
		 		break;
		 	default:
		 		break;
		 }
	}
	
	// [TODO] Move to an extension
	private function _handleImportTicket($xml) {
		$settings = CerberusSettings::getInstance();

		$sMask = (string) $xml->mask;
		$sSubject = (string) $xml->subject;
		$sGroup = (string) $xml->group;
		$sBucket = (string) $xml->bucket;
		$iCreatedDate = (integer) $xml->created_date;
		$iUpdatedDate = (integer) $xml->updated_date;
		$isWaiting = (integer) $xml->is_waiting;
		$isClosed = (integer) $xml->is_closed;
		
		// [TODO] Find the destination Group + Bucket (or create them)
		// [TODO] Hash these so we know which group+bucket combos exist already
		$groups = DAO_Group::getAll();
		$buckets = DAO_Bucket::getAll();
		$iGroupId = 1;
		$iBucketId = 0;
		
		// Xpath the first and last "from" out of "/ticket/messages/message/headers/from"
		$aMessageNodes = $xml->xpath("/ticket/messages/message");
		$iNumMessages = count($aMessageNodes);
		
		$eFirstMessage = reset($aMessageNodes);
		$sFirstWrote = (string) $eFirstMessage->headers->from; // [TODO] RFC822 validate
		$firstWroteInst = CerberusApplication::hashLookupAddress($sFirstWrote, true);
		
		$eLastMessage = end($aMessageNodes);
		$sLastWrote = (string) $eLastMessage->headers->from; // [TODO] RFC822 validate
		$lastWroteInst = CerberusApplication::hashLookupAddress($sLastWrote, true);

		// Create ticket
		$fields = array(
			DAO_Ticket::MASK => $sMask, // [TODO] Dupe check
			DAO_Ticket::SUBJECT => $sSubject,
			DAO_Ticket::IS_WAITING => $isWaiting,
			DAO_Ticket::IS_CLOSED => $isClosed,
			DAO_Ticket::FIRST_WROTE_ID => intval($firstWroteInst->id),
			DAO_Ticket::LAST_WROTE_ID => intval($lastWroteInst->id),
			DAO_Ticket::CREATED_DATE => $iCreatedDate,
			DAO_Ticket::UPDATED_DATE => $iUpdatedDate,
			DAO_Ticket::TEAM_ID => intval($iGroupId),
			DAO_Ticket::CATEGORY_ID => intval($iBucketId),
			DAO_Ticket::LAST_ACTION_CODE => CerberusTicketActionCode::TICKET_OPENED,
		);
		$ticket_id = DAO_Ticket::createTicket($fields);
		echo $ticket_id;
		
		print_r($fields);
		
		// Create requesters
		if(!is_null($xml->requesters))
		foreach($xml->requesters->address as $eAddress) { /* @var $eAddress SimpleXMLElement */
			$sRequesterAddy = (string) $eAddress; // [TODO] RFC822
			// Insert requesters
			$requesterAddyInst = CerberusApplication::hashLookupAddress($sRequesterAddy, true);
			DAO_Ticket::createRequester($requesterAddyInst->id, $ticket_id);
		}
		
		// Create messages
		if(!is_null($xml->messages))
		foreach($xml->messages->message as $eMessage) { /* @var $eMessage SimpleXMLElement */
			$eHeaders =& $eMessage->headers; /* @var $eHeaders SimpleXMLElement */
//			echo "From: ",(string) $eHeaders->from,"<BR>";
//			echo "To: ",(string) $eHeaders->to,"<BR>";
//			echo "Date: ",(string) $eHeaders->date,"<BR>";
			$sMsgFrom = (string) $eHeaders->from;
			$sMsgDate = (string) $eHeaders->date;
			
			// [TODO] RFC822 address parse 'From'.  Hash lookup.
			$msgFromInst = CerberusApplication::hashLookupAddress($sMsgFrom, true);

	        $fields = array(
	            DAO_Message::TICKET_ID => $ticket_id,
//	            DAO_Message::CREATED_DATE => $iDate, // [TODO] RFC822->epochal
	            DAO_Message::ADDRESS_ID => $msgFromInst->id
	        );
			$email_id = DAO_Message::create($fields);
			
			// Create message content
			$sMessageContent = (string) $eMessage->content;
//			echo "CONTENT: ",nl2br($sMessageContent),"<BR>";
			DAO_MessageContent::update($email_id, $sMessageContent);

			// Headers
			foreach($eHeaders->children() as $eHeader) { /* @var $eHeader SimpleXMLElement */
//				echo "Header Name: " . $eHeader->getName() . "Value: " . (string) $eHeader . "<BR>";
			    DAO_MessageHeader::update($email_id, $ticket_id, $eHeader->getName(), (string) $eHeader);
			}
		}
		
		// Create attachments
		// [TODO]
		
		// Create comments
		if(!is_null($xml->comments))
		foreach($xml->comments->comment as $eComment) { /* @var $eMessage SimpleXMLElement */
			$iCommentDate = (integer) $eComment->created_date;
			$sCommentAuthor = (string) $eComment->author; // [TODO] Address Hash Lookup
			$sCommentText = (string) $eComment->text;
			
			$commentAuthorInst = CerberusApplication::hashLookupAddress($sCommentAuthor, true);
			
			// [TODO] Sanity checking
			
			$fields = array(
				DAO_TicketComment::TICKET_ID => intval($ticket_id),
				DAO_TicketComment::CREATED => intval($iCommentDate),
				DAO_TicketComment::ADDRESS_ID => intval($commentAuthorInst->id),
				DAO_TicketComment::COMMENT => $sCommentText,
			);
			$comment_id = DAO_TicketComment::create($fields);
		}
		
		return true;
	}

	// [TODO] Move to an extension
	private function _handleImportComment($xml) {
		$mask = (string) $xml->mask;
		$author_email = (string) $xml->author_email;
		$note = trim((string) $xml->note);
		$created = intval((string) $xml->created_date);

		$author_address = CerberusApplication::hashLookupAddress($author_email,true);

		// Bad file
		if(empty($note) || empty($author_address) || empty($mask)) {
			return false;
		}

//		echo "MASK: ",$mask,"<BR>";
//		echo " -- Author: ",$author_address->email,"<BR>";
//		echo " -- Note: ",$note,"<BR>";

		if(null !== ($ticket = DAO_Ticket::getTicketByMask($mask))) {
			$fields = array(
				DAO_TicketComment::CREATED => $created,
				DAO_TicketComment::TICKET_ID => $ticket->id,
				DAO_TicketComment::COMMENT => $note,
				DAO_TicketComment::ADDRESS_ID => $author_address->id,
			);
			
			if(null !== ($comment_id = DAO_TicketComment::create($fields)))
				return true;
		}
		
		return false;
	}

	function scanDirMessages($dir) {
		if(substr($dir,-1,1) != DIRECTORY_SEPARATOR) $dir .= DIRECTORY_SEPARATOR;
		$files = glob($dir . '*.xml');
		if ($files === false) return array();
		return $files;
	}

	function configure($instance) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl_path = dirname(__FILE__) . '/templates/';
		$tpl->assign('path', $tpl_path);

		$tpl->display($tpl_path . 'cron/import/config.tpl.php');
	}
};

/*
 * PARAMS (overloads):
 * pop3_max=n (max messages to download at once)
 *
 */
class Pop3Cron extends CerberusCronPageExtension {
	function run() {
		$logger = DevblocksPlatform::getConsoleLog();
		
		$logger->info("[POP3] Starting POP3 Task");
		
		if (!extension_loaded("imap")) die("IMAP Extension not loaded!");
		@set_time_limit(0); // Unlimited (if possible)
		@ini_set('memory_limit','64M');

		$accounts = DAO_Mail::getPop3Accounts(); /* @var $accounts CerberusPop3Account[] */

		$timeout = ini_get('max_execution_time');
		
		// Allow runtime overloads (by host, etc.)
		@$gpc_pop3_max = DevblocksPlatform::importGPC($_REQUEST['pop3_max'],'integer');
		
		$max_downloads = !empty($gpc_pop3_max) ? $gpc_pop3_max : $this->getParam('max_messages', (($timeout) ? 20 : 50));
		
		// [JAS]: Make sure our output directory is writeable
		if(!is_writable(APP_MAIL_PATH . 'new' . DIRECTORY_SEPARATOR)) {
			$logger->err("[POP3] The mail storage directory is not writeable.  Skipping POP3 download.");
			return;
		}

		foreach ($accounts as $account) { /* @var $account CerberusPop3Account */
			if(!$account->enabled)
			continue;

			$logger->info('[POP3] Account being parsed is '. $account->nickname);
			 
			switch($account->protocol) {
				default:
				case 'pop3': // 110
					$connect = sprintf("{%s:%d/pop3/notls}INBOX",
					$account->host,
					$account->port
					);
					break;
					 
				case 'pop3-ssl': // 995
					$connect = sprintf("{%s:%d/pop3/ssl/novalidate-cert}INBOX",
					$account->host,
					$account->port
					);
					break;
					 
				case 'imap': // 143
					$connect = sprintf("{%s:%d/notls}INBOX",
					$account->host,
					$account->port
					);
					break;

				case 'imap-ssl': // 993
					$connect = sprintf("{%s:%d/imap/ssl/novalidate-cert}INBOX",
					$account->host,
					$account->port
					);
					break;
			}

			$runtime = microtime(true);
			 
			if(false === ($mailbox = @imap_open($connect,
			!empty($account->username)?$account->username:"",
			!empty($account->password)?$account->password:""))) {
				$logger->err("[POP3] Failed with error: ".imap_last_error());
				continue;
			}
			 
			$messages = array();
			$check = imap_check($mailbox);
			 
			// [TODO] Make this an account setting?
			$total = min($max_downloads,$check->Nmsgs);
			 
			$logger->info('[POP3] Init time: '.((microtime(true)-$runtime)*1000)," ms");

			$runtime = microtime(true);

			for($i=1;$i<=$total;$i++) {
				/*
				 * [TODO] Logic for max message size (>1MB, etc.) handling.  If over a
				 * threshold then use the attachment parser (imap_fetchstructure) to toss
				 * non-plaintext until the message fits.
				 */
				 
				$msgno = $i;
				 
				$time = microtime(true);
				 
				$headers = imap_fetchheader($mailbox, $msgno);
				$body = imap_body($mailbox, $msgno);

				do {
					$unique = sprintf("%s.%04d",
					time(),
					rand(0,9999)
					);
					$filename = APP_MAIL_PATH . 'new' . DIRECTORY_SEPARATOR . $unique;
				} while(file_exists($filename));

				$fp = fopen($filename,'w');

				if($fp) {
					fwrite($fp,$headers,strlen($headers));
					fwrite($fp,"\r\n\r\n");
					fwrite($fp,$body,strlen($body));
					@fclose($fp);
				}

				/*
				 * [JAS]: We don't add the .msg extension until we're done with the file,
				 * since this will safely be ignored by the parser until we're ready
				 * for it.
				 */
				rename($filename, dirname($filename) .DIRECTORY_SEPARATOR . basename($filename) . '.msg');

				unset($headers);
				unset($body);

				$time = microtime(true) - $time;
				$logger->info("[POP3] Downloaded message ".$msgno." (".sprintf("%d",($time*1000))." ms)");
				
				imap_delete($mailbox, $msgno);
				continue;
			}
			 
			imap_expunge($mailbox);
			imap_close($mailbox);
			imap_errors();
			 
			$logger->info("[POP3] Total Runtime: ".((microtime(true)-$runtime)*1000)." ms");
		}
	}

	function configure($instance) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl_path = dirname(__FILE__) . '/templates/';
		$tpl->assign('path', $tpl_path);

		$timeout = ini_get('max_execution_time');
		$tpl->assign('max_messages', $this->getParam('max_messages', (($timeout) ? 20 : 50)));

		$tpl->display($tpl_path . 'cron/pop3/config.tpl.php');
	}

	function saveConfigurationAction() {

		@$max_messages = DevblocksPlatform::importGPC($_POST['max_messages'],'integer');
		$this->setParam('max_messages', $max_messages);

		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','jobs')));
	}
};

class ParserFile {
	public $tmpname = null;
	public $mime_type = '';
	public $file_size = 0;

	function __destruct() {
		if(file_exists($this->tmpname)) {
			@unlink($this->tmpname);
		}
	}

	public function setTempFile($tmpname,$mimetype='application/octet-stream') {
		$this->mime_type = $mimetype;

		if(!empty($tmpname) && file_exists($tmpname)) {
			$this->tmpname = $tmpname;
		}
	}

	public function getTempFile() {
		return $this->tmpname;
	}

	static public function makeTempFilename() {
		$path = DEVBLOCKS_PATH . 'tmp' . DIRECTORY_SEPARATOR;
		return tempnam($path,'mime');
	}
};

class ParseCronFileBuffer extends ParserFile {
	private $mime_filename = '';
	private $section = null;
	private $info = array();
	private $fp = null;

	function __construct($section, $info, $mime_filename) {
		$this->mime_filename = $mime_filename;
		$this->section = $section;
		$this->info = $info;

		$this->setTempFile(ParserFile::makeTempFilename(),@$info['content-type']);
		$this->fp = fopen($this->getTempFile(),'wb');

		if($this->fp && !empty($this->section) && !empty($this->mime_filename)) {
			mailparse_msg_extract_part_file($this->section, $this->mime_filename, array($this, "writeCallback"));
		}

		@fclose($this->fp);
	}

	function writeCallback($chunk) {
		$this->file_size += fwrite($this->fp, $chunk);
		//        echo $chunk;
	}

}

?>