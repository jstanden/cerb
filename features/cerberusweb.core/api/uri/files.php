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
	/*
	 * Request Overload
	 */
	function handleRequest(DevblocksHttpRequest $request) {
		$stack = $request->path; // URLS like: /files/10000/plaintext.txt
		array_shift($stack); // files
		
		if('message' == reset($stack)) {
			$this->_downloadAllOnMessage($stack);
		} else {
			$this->_downloadFile($request, $stack);
		}
	}
	
	private function _downloadFile(DevblocksHttpRequest $request, array $stack) {
		$file_id = array_shift($stack); // 123
		$file_name = array_shift($stack); // plaintext.txt
		
		$is_download = isset($request->query['download']);
		$handled = false;
		
		// Security
		if(null == ($active_worker = CerberusApplication::getActiveWorker()))
			DevblocksPlatform::dieWithHttpError(DevblocksPlatform::translate('common.access_denied'), 403);
		
		if(40 == strlen($file_id))
			$file_id = DAO_Attachment::getBySha1Hash($file_id);
		
		if(empty($file_id) || empty($file_name))
			DevblocksPlatform::dieWithHttpError(DevblocksPlatform::translate('files.not_found'), 404);
		
		if(false == ($file = DAO_Attachment::get($file_id)))
			DevblocksPlatform::dieWithHttpError(DevblocksPlatform::translate('files.not_found'), 404);
		
		if(!Context_Attachment::isDownloadableByActor($file, $active_worker))
			DevblocksPlatform::dieWithHttpError(DevblocksPlatform::translate('common.access_denied'), 403);
		
		if(false == ($fp = DevblocksPlatform::getTempFile()))
			DevblocksPlatform::dieWithHttpError(DevblocksPlatform::translate('files.error_temp_open'), 500);
		
		if(false === $file->getFileContents($fp))
			DevblocksPlatform::dieWithHttpError(DevblocksPlatform::translate('files.error_resource_read'), 500);
		
		$file_stats = fstat($fp);
		$mime_type = DevblocksPlatform::strLower($file->mime_type);
		$size = $file_stats['size'];
		
		// Set headers
		header('Pragma: cache');
		header('Cache-control: max-age=604800', true); // 1 wk // , must-revalidate
		header('Expires: ' . gmdate('D, d M Y H:i:s',time()+604800) . ' GMT'); // 1 wk
		header('Accept-Ranges: bytes');
		
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
							'output-xhtml' => false,
							'output-html' => true,
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
					
					$filter = new Cerb_HTMLPurifier_URIFilter_Email(false);
					$clean_html = DevblocksPlatform::purifyHTML($fp, true, true, [$filter]);
					
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
	
	private function _downloadAllOnMessage(array $stack) {
		array_shift($stack); // message
		
		$message_id = array_shift($stack); // 123
		
		$message = null;
		
		if(!extension_loaded('zip') || !class_exists('ZipArchive'))
			DevblocksPlatform::dieWithHttpError('The `zip` PHP extension is required.');
		
		if(false == ($active_worker = CerberusApplication::getActiveWorker()))
			DevblocksPlatform::dieWithHttpError(DevblocksPlatform::translate('common.access_denied'), 403);
		
		if(!$message_id || false == ($message = DAO_Message::get($message_id)))
			DevblocksPlatform::dieWithHttpError(DevblocksPlatform::translate('error.core.record.not_found'), 404);
		
		if(false == ($ticket = DAO_Ticket::getTicketByMessageId($message_id)))
			DevblocksPlatform::dieWithHttpError(DevblocksPlatform::translate('error.core.record.not_found'), 404);
		
		if(!Context_Ticket::isReadableByActor($ticket, $active_worker))
			DevblocksPlatform::dieWithHttpError(DevblocksPlatform::translate('common.access_denied'), 403);
		
		$attachments = [];
		
		// Attachments on messages
		$attachments += DAO_Attachment::getByContextIds(CerberusContexts::CONTEXT_MESSAGE, $message->id);
		
		if(!$attachments)
			return true;
		
		$zip_fp = DevblocksPlatform::getTempFile();
		$zip_filename = DevblocksPlatform::getTempFileInfo($zip_fp);
		
		$download_filename = sprintf("ticket-%s--%d-attachments.zip",
			DevblocksPlatform::strAlphaNum($ticket->mask, '-_'),
			$message->id
		);
		
		$zip = new ZipArchive();
		$zip->open($zip_filename, ZipArchive::OVERWRITE);
		
		foreach($attachments as $attachment) {
			if(false == ($fp = DevblocksPlatform::getTempFile()))
				continue;
			
			if(false == ($attachment->getFileContents($fp)))
				continue;
			
			$fp_filename = DevblocksPlatform::getTempFileInfo($fp);
			
			$zip->addFile($fp_filename, $attachment->name);
			
			fclose($fp);
		}
		
		$zip->close();
		fclose($zip_fp);
		
		$zip_fp = fopen($zip_filename, 'rb');
		$file_stats = fstat($zip_fp);
		
		// Set headers
		header("Expires: Mon, 26 Nov 1979 00:00:00 GMT");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Accept-Ranges: bytes");
		header("Content-disposition: attachment; filename=" . $download_filename);
		header("Content-Type: application/zip");
		header("Content-Length: " . $file_stats['size']);
		fpassthru($zip_fp);
		fclose($zip_fp);
		
		return true;		
	}
	
	/*
	private function _downloadAllOnTicket(array $stack) {
		array_shift($stack); // ticket
		$ticket_id = array_shift($stack); // 123
		
		if(!extension_loaded('zip') || !class_exists('ZipArchive'))
			DevblocksPlatform::dieWithHttpError('The `zip` PHP extension is required.');
		
		if(false == ($active_worker = CerberusApplication::getActiveWorker()))
			DevblocksPlatform::dieWithHttpError(DevblocksPlatform::translate('common.access_denied'), 403);
		
		if(!$ticket_id || false == ($ticket = DAO_Ticket::get($ticket_id)))
			DevblocksPlatform::dieWithHttpError(DevblocksPlatform::translate('error.core.record.not_found'), 404);
		
		if(!Context_Ticket::isReadableByActor($ticket, $active_worker))
			DevblocksPlatform::dieWithHttpError(DevblocksPlatform::translate('common.access_denied'), 403);
		
		$attachments = [];
		
		// Attachments on messages
		if(false != ($messages = DAO_Message::getMessagesByTicket($ticket_id)))
			$attachments += DAO_Attachment::getByContextIds(CerberusContexts::CONTEXT_MESSAGE, array_keys($messages));
		
		// Attachments on ticket comments
		if(false != ($comments = DAO_Comment::getByContext(CerberusContexts::CONTEXT_TICKET, $ticket->id)))
			$attachments += DAO_Attachment::getByContextIds(CerberusContexts::CONTEXT_COMMENT, array_keys($comments));
		
		// Attachments on messages comments
		if(false != ($comments = DAO_Comment::getByContext(CerberusContexts::CONTEXT_MESSAGE, array_keys($messages))))
			$attachments += DAO_Attachment::getByContextIds(CerberusContexts::CONTEXT_COMMENT, array_keys($comments));
		
		if(!$attachments)
			return true;
		
		$zip_fp = DevblocksPlatform::getTempFile();
		$zip_filename = DevblocksPlatform::getTempFileInfo($zip_fp);
		
		$download_filename = sprintf("ticket-%s-attachments.zip",
			DevblocksPlatform::strAlphaNum($ticket->mask, '-_')
		);
		
		$zip = new ZipArchive();
		$zip->open($zip_filename, ZipArchive::OVERWRITE);
		
		foreach($attachments as $attachment) {
			// Not `original_message.html`
			if('original_message.html' == $attachment->name)
				continue;
			
			if(false == ($fp = DevblocksPlatform::getTempFile()))
				continue;
			
			if(false == ($attachment->getFileContents($fp)))
				continue;
			
			$fp_filename = DevblocksPlatform::getTempFileInfo($fp);
			
			$zip->addFile($fp_filename, sprintf('%d-%s', $attachment->id, $attachment->name));
			
			fclose($fp);
		}
		
		$zip->close();
		fclose($zip_fp);
		
		$zip_fp = fopen($zip_filename, 'rb');
		$file_stats = fstat($zip_fp);
		
		// Set headers
		header("Expires: Mon, 26 Nov 1979 00:00:00 GMT");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Accept-Ranges: bytes");
		header("Content-disposition: attachment; filename=" . $download_filename);
		header("Content-Type: application/zip");
		header("Content-Length: " . $file_stats['size']);
		fpassthru($zip_fp);
		fclose($zip_fp);
		
		return true;		
	}
	*/
};
