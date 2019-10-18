<?php
class ProfileScript_ProfileAttachmentsZip extends Extension_ContextProfileScript {
	const ID = 'cerb.legacy.profile.attachments.script';
	
	function renderScript($context, $context_id) {
		$tpl = DevblocksPlatform::services()->template();
		
		$tpl->assign('page_context', $context);
		$tpl->assign('page_context_id', $context_id);
		
		$tpl->display('devblocks:cerb.legacy.profile.attachments::profile/script.tpl');
	}
};

class Controller_AttachmentsAsZip extends DevblocksControllerExtension {
	const ID = 'cerb.legacy.profile.attachments.controller';
	
	/*
	 * Request Overload
	 */
	function handleRequest(DevblocksHttpRequest $request) {
		if(false == ($active_worker = CerberusApplication::getActiveWorker()))
			return;
		
		$translate = DevblocksPlatform::getTranslationService();
		
		$stack = $request->path;
		array_shift($stack); // attachments.zip
		@$context_alias = array_shift($stack);
		@$context_id = intval(array_shift($stack));
		
		if(!extension_loaded('zip'))
			return;
		
		if(false == ($context_ext = Extension_DevblocksContext::getByAlias($context_alias, true)))
			DevblocksPlatform::dieWithHttpError($translate->_('files.not_found'), 404);
		
		if($context_ext->id != CerberusContexts::CONTEXT_TICKET)
			DevblocksPlatform::dieWithHttpError($translate->_('files.not_found'), 404);
		
		@$model = $context_ext->getModelObjects([$context_id])[$context_id];
		
		if(!$model)
			DevblocksPlatform::dieWithHttpError($translate->_('files.not_found'), 404);
		
		if(!CerberusContexts::isReadableByActor($context_ext->id, $model, $active_worker))
			DevblocksPlatform::dieWithHttpError($translate->_('common.access_denied'), 403);
		
		$attachments_context = Extension_DevblocksContext::get(CerberusContexts::CONTEXT_ATTACHMENT, true);
		
		$view = $attachments_context->getTempView();
		$view->renderLimit = 100;
		$view->renderTotal = false;
		
		$query = sprintf('name:!"original_message.html" AND (on.msgs:(ticket.id:%d) OR on.comments:(on.ticket:(id:%d)))',
			$context_id,
			$context_id
		);
		
		$view->addParamsWithQuickSearch($query, true);
		
		list($results,) = $view->getData();
		
		$attachments = DAO_Attachment::getIds(array_keys($results));
		
		if(empty($attachments))
			DevblocksPlatform::dieWithHttpError($translate->_('files.not_found'), 404);
		
		if(!Context_Attachment::isDownloadableByActor($attachments, $active_worker))
			DevblocksPlatform::dieWithHttpError($translate->_('common.access_denied'), 403);
		
		$zip_fp = DevblocksPlatform::getTempFile();
		$zip_filename = DevblocksPlatform::getTempFileInfo($zip_fp);
		
		$download_filename = sprintf("ticket-%s-attachments.zip",
			DevblocksPlatform::strAlphaNum($model->mask, '-_')
		);
		$zip = new ZipArchive();
		$zip->open($zip_filename, ZipArchive::OVERWRITE);
		
		foreach($attachments as $attachment) {
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
		exit;
	}
};