<?php
/***********************************************************************
| Cerb(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2012, WebGroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerberusweb.com/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerberusweb.com	  http://www.webgroupmedia.com/
***********************************************************************/

class ChFilesController extends DevblocksControllerExtension {
	function isVisible() {
		// The current session must be a logged-in worker to use this page.
		if(null == ($worker = CerberusApplication::getActiveWorker()))
			return false;
		return true;
	}
	
	/*
	 * Request Overload
	 */
	function handleRequest(DevblocksHttpRequest $request) {
		$translate = DevblocksPlatform::getTranslationService();
		
		$stack = $request->path;				// URLS like: /files/10000/plaintext.txt
		array_shift($stack);					// files
		$file_guid = array_shift($stack);		// GUID
		$file_name = array_shift($stack);		// plaintext.txt

		// Security
		if(null == ($active_worker = CerberusApplication::getActiveWorker()))
			die($translate->_('common.access_denied'));
		
		if(empty($file_guid) || empty($file_name))
			die($translate->_('files.not_found'));
		
		$link = DAO_AttachmentLink::getByGUID($file_guid);
		
		if(empty($link))
			die("Error reading link.");
		
		if(null == ($context = $link->getContext()))
			die($translate->_('common.access_denied'));
		
		// Security
		if(!$context->authorize($link->context_id, $active_worker))
			die($translate->_('common.access_denied'));
			
		$file = $link->getAttachment();
		
		
		if(false === ($fp = DevblocksPlatform::getTempFile()))
			die("Could not open a temporary file.");
		if(false === $file->getFileContents($fp))
			die("Error reading resource.");
			
		$file_stats = fstat($fp);

		// Set headers
		header("Expires: Mon, 26 Nov 1979 00:00:00 GMT");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Accept-Ranges: bytes");
//		header("Keep-Alive: timeout=5, max=100");
//		header("Connection: Keep-Alive");
		header("Content-Type: " . $file->mime_type);

		switch(strtolower($file->mime_type)) {
			case 'text/html':
				$clean_html = DevblocksPlatform::purifyHTML($fp);
				header("Content-Length: " . strlen($clean_html));
				echo $clean_html;
				break;
				
			default:
				header("Content-Length: " . $file_stats['size']);
				fpassthru($fp);
				break;
		}
		
		fclose($fp);
		
		exit;
	}
};
