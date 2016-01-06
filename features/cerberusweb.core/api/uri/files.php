<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2015, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerberusweb.com/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerbweb.com	    http://www.webgroupmedia.com/
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
		
		$stack = $request->path; // URLS like: /files/10000/plaintext.txt
		array_shift($stack); // files
		$file_guid = array_shift($stack); // GUID
		$file_name = array_shift($stack); // plaintext.txt
		
		// Security
		if(null == ($active_worker = CerberusApplication::getActiveWorker()))
			die($translate->_('common.access_denied'));
		
		if(empty($file_guid) || empty($file_name))
			die($translate->_('files.not_found'));
		
		// Are we being asked for the direct SHA1 hash of a file?
		if(strlen($file_guid) == 40) {
			if(null == ($file_id = DAO_Attachment::getBySha1Hash($file_guid)))
				die($translate->_('common.access_denied'));
			
			$file = DAO_Attachment::get($file_id);
			
		// If not SHA1, then look for a link with this GUID
		} else {
			$link = DAO_AttachmentLink::getByGUID($file_guid);
			
			if(empty($link))
				die($translate->_('files.error_link_read'));
			
			if(null == ($context = $link->getContext()))
				die($translate->_('common.access_denied'));
			
			// Security
			if(!$context->authorize($link->context_id, $active_worker))
				die($translate->_('common.access_denied'));
			
			$file = $link->getAttachment();
		}
		
		if(empty($file))
			die($translate->_('files.not_found'));
		if(false === ($fp = DevblocksPlatform::getTempFile()))
			die($translate->_('files.error_temp_open'));
		if(false === $file->getFileContents($fp))
			die($translate->_('files.error_resource_read'));
			
		$file_stats = fstat($fp);
		
		$is_download = isset($request->query['download']) ? true : false;

		// Set headers
		header('Pragma: cache');
		header('Cache-control: max-age=604800', true); // 1 wk // , must-revalidate
		header('Expires: ' . gmdate('D, d M Y H:i:s',time()+604800) . ' GMT'); // 1 wk
		header('Accept-Ranges: bytes');
// 		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");

		if($is_download) {
			header('Content-Disposition: attachment; filename=' . urlencode($file->display_name));
		}
		
		$handled = false;
		
		switch(strtolower($file->mime_type)) {
			case 'message/feedback-report':
			case 'message/rfc822':
				// Render to the browser as text
				if(!$is_download)
					$file->mime_type = 'text/plain';
				break;
			
			case 'text/html':
				$handled = true;
				header("Content-Type: text/html; charset=" . LANG_CHARSET_CODE);
				
				// If we're downloading the HTML, just pass the raw bytes
				if($is_download) {
					header("Content-Length: " . $file_stats['size']);
					fpassthru($fp);
					
				// If we're displaying the HTML inline, tidy and purify it first
				} else {
					// If the 'tidy' extension exists, and the file size is less than 5MB
					if(extension_loaded('tidy') && $file_stats['size'] < 5120000) {
						$tidy = new tidy();
						
						$config = array (
							'bare' => true,
							'clean' => true,
							'drop-proprietary-attributes' => true,
							'indent' => false,
							'output-xhtml' => true,
							'wrap' => 0,
						);
						
						// If we're not stripping Microsoft Office formatting
						if(DevblocksPlatform::getPluginSetting('cerberusweb.core', CerberusSettings::HTML_NO_STRIP_MICROSOFT, CerberusSettingsDefaults::HTML_NO_STRIP_MICROSOFT)) {
							unset($config['bare']);
							unset($config['drop-proprietary-attributes']);
						}
						
						if(null != ($fp_filename = DevblocksPlatform::getTempFileInfo($fp))) {
							file_put_contents($fp_filename, $tidy->repairFile($fp_filename, $config, DB_CHARSET_CODE));
							fseek($fp, 0);
						}
					}
					
					$purify_config = array(
						'HTML.TargetBlank' => true,
						'Attr.EnableID' => true,
					);
					
					$clean_html = DevblocksPlatform::purifyHTML($fp, true, $purify_config);
					
					header("Content-Length: " . strlen($clean_html));
					echo $clean_html;
				}
				break;
				
			default:
				break;
		}
		
		if(!$handled) {
			header("Content-Type: " . $file->mime_type);
			header("Content-Length: " . $file_stats['size']);
			fpassthru($fp);
		}
		
		fclose($fp);
		
		exit;
	}
};
