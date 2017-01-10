<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2017, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerb.ai/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://cerb.ai	    http://webgroup.media
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
		$file_id = array_shift($stack); // 123
		$file_name = array_shift($stack); // plaintext.txt
		
		if(40 == strlen($file_id))
			$file_id = DAO_Attachment::getBySha1Hash($file_id);
		
		// Security
		if(null == ($active_worker = CerberusApplication::getActiveWorker()))
			DevblocksPlatform::dieWithHttpError($translate->_('common.access_denied'), 403);
		
		if(empty($file_id) || empty($file_name))
			DevblocksPlatform::dieWithHttpError($translate->_('files.not_found'), 404);
		
		if(false == ($file = DAO_Attachment::get($file_id)))
			DevblocksPlatform::dieWithHttpError($translate->_('files.not_found'), 404);
		
		if(!Context_Attachment::isDownloadableByActor($file, $active_worker))
			DevblocksPlatform::dieWithHttpError($translate->_('common.access_denied'), 403);
		
		if(false === ($fp = DevblocksPlatform::getTempFile()))
			DevblocksPlatform::dieWithHttpError($translate->_('files.error_temp_open'), 500);
		
		if(false === $file->getFileContents($fp))
			DevblocksPlatform::dieWithHttpError($translate->_('files.error_resource_read'), 500);
			
		$file_stats = fstat($fp);
		
		$is_download = isset($request->query['download']) ? true : false;

		// Set headers
		header('Pragma: cache');
		header('Cache-control: max-age=604800', true); // 1 wk // , must-revalidate
		header('Expires: ' . gmdate('D, d M Y H:i:s',time()+604800) . ' GMT'); // 1 wk
		header('Accept-Ranges: bytes');
// 		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");

		if($is_download) {
			header('Content-Disposition: attachment; filename=' . urlencode($file->name));
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
