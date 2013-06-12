<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2013, Webgroup Media LLC
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
		
		$is_download = isset($request->query['download']) ? true : false;

		// Set headers
		header("Expires: Mon, 26 Nov 1979 00:00:00 GMT");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Accept-Ranges: bytes");
//		header("Keep-Alive: timeout=5, max=100");
//		header("Connection: Keep-Alive");

		if($is_download) {
			header('Content-Disposition: attachment; filename=' . urlencode($file->display_name));
		}
		
		switch(strtolower($file->mime_type)) {
			case 'text/html':
				header("Content-Type: " . $file->mime_type . '; charset=UTF-8');
				
				// If we're downloading the HTML, just pass the raw bytes
				if($is_download) {
					header("Content-Length: " . $file_stats['size']);
					fpassthru($fp);
					
				// If we're displaying the HTML inline, tidy and purify it first
				} else {
					// If the 'tidy' extension exists, and the file size is less than 5MB
					if(extension_loaded('tidy') && $file_stats['size'] < 1024000 * 5) {
						$tidy = new tidy();
						if(null != ($fp_filename = DevblocksPlatform::getTempFileInfo($fp))) {
							file_put_contents($fp_filename, $tidy->repairFile($fp_filename));
							fseek($fp, 0);
						}
					}
					
					$purify_config = array(
						'Attr.EnableID' => true,
					);
					
					$clean_html = DevblocksPlatform::purifyHTML($fp, $purify_config);
					
					header("Content-Length: " . strlen($clean_html));
					echo $clean_html;
				}
				break;
				
			default:
				header("Content-Type: " . $file->mime_type);
				header("Content-Length: " . $file_stats['size']);
				fpassthru($fp);
				break;
		}
		
		fclose($fp);
		
		exit;
	}
};
