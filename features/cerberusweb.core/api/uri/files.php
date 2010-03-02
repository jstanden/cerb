<?php
class ChFilesController extends DevblocksControllerExtension {
	function __construct($manifest) {
		parent::__construct($manifest);
	}
	
	function isVisible() {
		// check login
		$session = DevblocksPlatform::getSessionService();
		$visit = $session->getVisit();
		
		if(empty($visit)) {
			return false;
		} else {
			return true;
		}
	}
	
	/*
	 * Request Overload
	 */
	function handleRequest(DevblocksHttpRequest $request) {
		$translate = DevblocksPlatform::getTranslationService();
		
		$stack = $request->path;				// URLS like: /files/10000/plaintext.txt
		array_shift($stack);					// files	
		$file_id = array_shift($stack); 		// 10000
		$file_name = array_shift($stack); 		// plaintext.txt

		// Security
		if(null == ($active_worker = CerberusApplication::getActiveWorker()))
			die($translate->_('common.access_denied'));
		
		if(empty($file_id) || empty($file_name) || null == ($file = DAO_Attachment::get($file_id)))
			die($translate->_('files.not_found'));
			
		// Security
			$message = DAO_Ticket::getMessage($file->message_id);
		if(null == ($ticket = DAO_Ticket::getTicket($message->ticket_id)))
			die($translate->_('common.access_denied'));
			
		// Security
		$active_worker_memberships = $active_worker->getMemberships();
		if(null == ($active_worker_memberships[$ticket->team_id]))
			die($translate->_('common.access_denied'));
			
		$contents = $file->getFileContents();
			
		// Set headers
		header("Expires: Mon, 26 Nov 1962 00:00:00 GMT\n");
		header("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT\n");
		header("Cache-control: private\n");
		header("Pragma: no-cache\n");
		header("Content-Type: " . $file->mime_type . "\n");
		header("Content-transfer-encoding: binary\n"); 
		header("Content-Length: " . strlen($contents) . "\n");
		
		echo $contents;
		unset($contents);
		
		exit;
	}
};
