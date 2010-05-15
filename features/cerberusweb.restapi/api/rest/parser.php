<?php
class ChRest_Parser extends Extension_RestController { //implements IExtensionRestController
	function __construct($manifest) {
		parent::__construct($manifest);
	}
	
	function getAction($stack) {
		$this->error(self::ERRNO_NOT_IMPLEMENTED);
	}
	
	function putAction($stack) {
		$this->error(self::ERRNO_NOT_IMPLEMENTED);
	}
	
	function postAction($stack) {
		@$action = array_shift($stack);
		
		switch($action) {
			case 'parse':
				$this->postParse();
				break;
		}
		
		$this->error(self::ERRNO_NOT_IMPLEMENTED);
	}
	
	function deleteAction($stack) {
		$this->error(self::ERRNO_NOT_IMPLEMENTED);
	}
	
	private function postParse() {
		$worker = $this->getActiveWorker();
		
		if(!$worker->hasPriv('core.mail.log_ticket'))
			$this->error(self::ERRNO_ACL);
		
		@$content = DevblocksPlatform::importGPC($_POST['message'],'string','');
		
		if(empty($content))
			$this->error(self::ERRNO_CUSTOM, 'The MIME content of your message cannot be blank.');
		
		$content .= PHP_EOL;
			
		if(null == ($file = CerberusParser::saveMimeToFile($content))) {
			@unlink($file);
			$this->error(self::ERRNO_CUSTOM, 'Your MIME file could not be saved.');
		}
		
		if(null == ($mime = mailparse_msg_parse_file($file))) {
			@unlink($file);			
			$this->error(self::ERRNO_CUSTOM, "Your message mime could not be decoded (it's probably malformed).");
		}
			
		if(null == ($parser_msg = CerberusParser::parseMime($mime, $file))) {
			@unlink($file);
			$this->error(self::ERRNO_CUSTOM, "Your message mime could not be parsed (it's probably malformed).");
		}
		
		if(null == ($ticket_id = CerberusParser::parseMessage($parser_msg))) {
			@unlink($file);
			$this->error(self::ERRNO_CUSTOM, "Your message content could not be parsed (it's probably malformed).");
		}
			
		if(null == ($ticket = DAO_Ticket::getTicket($ticket_id))) {
			@unlink($file);
			$this->error(self::ERRNO_CUSTOM, "Could not return a ticket object.");
		}

		$container = array(
			'id' => $ticket->id,
			'mask' => $ticket->mask,
			'last_message_id' => $ticket->last_message_id,
		);
			
		@unlink($file);
		
		$this->success($container);
	} 
};