<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2019, Webgroup Media LLC
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
		if(null == (CerberusApplication::getActiveWorker()))
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
		
		$is_download = isset($request->query['download']) ? true : false;
		$handled = false;
		
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
		$mime_type = DevblocksPlatform::strLower($file->mime_type);
		$size = $file_stats['size'];
		
		// Set headers
		header('Pragma: cache');
		header('Cache-control: max-age=604800', true); // 1 wk // , must-revalidate
		header('Expires: ' . gmdate('D, d M Y H:i:s',time()+604800) . ' GMT'); // 1 wk
		header('Accept-Ranges: bytes');
// 		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");

		if($is_download) {
			header("Content-Disposition: attachment; filename=\"" . $file->name . "\"");
			
		} else {
			@$range = DevblocksPlatform::importGPC($_SERVER['HTTP_RANGE'], 'string', null);
			
			if($range) {
				@list($range_unit, $value) = explode('=', $range, 2);
				
				if($range_unit != 'bytes')
					DevblocksPlatform::dieWithHttpError('Bad Request', 400);
				
				@list($range_from, $range_to) = explode('-', $value, 2);
				
				if(!$range_to)
					$range_to = $size - 1;
				
				$length = ($range_to - $range_from) + 1;
				
				header('HTTP/1.1 206 Partial Content');
				header("Content-Type: " . $mime_type);
				header("Content-Length: " . $length);
				header(sprintf("Content-Range: bytes %d-%d/%d", $range_from, $range_to, $size));
				
				flush();
				
				$remaining = $length;
				$block_size = 8192;
				
				fseek($fp, $range_from);
				
				while(true) {
					$read_size = min($remaining, $block_size);
					
					if($read_size <= 0)
						break;
					
					echo fread($fp, $read_size);
					$remaining -= $read_size;
					flush();
				}
				
				$handled = true;
				fclose($fp);
			}
		}
		
		switch($mime_type) {
			case 'application/pdf':
			case 'audio/mpeg':
			case 'audio/ogg':
			case 'audio/wav':
			case 'audio/x-wav':
			case 'image/gif':
			case 'image/jpeg':
			case 'image/png':
			case 'video/mp4':
			case 'video/mpeg':
			case 'video/quicktime':
				break;
			
			case 'application/json':
			case 'application/pgp-signature':
			case 'application/xml':
			case 'image/svg+xml':
			case 'message/feedback-report':
			case 'message/rfc822':
			case 'multipart/encrypted':
			case 'multipart/signed':
			case 'text/css':
			case 'text/csv':
			case 'text/javascript':
			case 'text/plain':
			case 'text/xml':
				// Render to the browser as text
				if(!$is_download)
					$mime_type = 'text/plain';
				break;
				
			case 'application/xhtml+xml':
			case 'text/html':
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
					
					$clean_html = DevblocksPlatform::purifyHTML($fp, true, true);
					
					header("Content-Length: " . strlen($clean_html));
					echo $clean_html;
				}
				
				$handled = true;
				fclose($fp);
				break;
				
			default:
				$mime_type = 'application/octet-stream';
				break;
		}
		
		if(!$handled) {
			header("Content-Type: " . $mime_type);
			header("Content-Length: " . $file_stats['size']);
			fpassthru($fp);
			fclose($fp);
		}
		exit;
	}
};
