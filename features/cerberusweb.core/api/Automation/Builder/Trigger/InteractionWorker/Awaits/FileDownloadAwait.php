<?php
namespace Cerb\Automation\Builder\Trigger\InteractionWorker\Awaits;

use _DevblocksValidationService;
use CerberusApplication;
use CerberusContexts;
use DevblocksPlatform;
use Model_AutomationContinuation;

class FileDownloadAwait extends AbstractAwait {
	function invoke(string $prompt_key, string $action, Model_AutomationContinuation $continuation) {
		if ($action == 'download') {
			return $this->_promptAction_download();
		}
		
		return false;
	}

	function formatValue() {
		return $this->_value;
	}
	
	function validate(_DevblocksValidationService $validation) {
	}
	
	private function _promptAction_download() {
		$uri = $this->_data['uri'] ?? null;
		
		if(!$uri)
			DevblocksPlatform::dieWithHttpError(null, 404);
	
		if(!($uri_parts = DevblocksPlatform::services()->ui()->parseURI($uri)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(CerberusContexts::CONTEXT_ATTACHMENT == $uri_parts['context']) {
			$active_worker = CerberusApplication::getActiveWorker();
			
			if(!($attachment = \DAO_Attachment::get($uri_parts['context_id'])))
				return;
			
			if(!\Context_Attachment::isDownloadableByActor($attachment, $active_worker))
				return;
			
			$content_type = $attachment->mime_type ?? 'application/octet-stream';
			
			header('Content-Type: ' . $content_type);
			
			$fp = DevblocksPlatform::getTempFile();
			
			if(!$attachment->getFileContents($fp))
				return;
			
			fpassthru($fp);
			fclose($fp);
			
		} else if(CerberusContexts::CONTEXT_AUTOMATION_RESOURCE == $uri_parts['context']) {
			if(!($automation_resource = \DAO_AutomationResource::getByToken($uri_parts['context_id'])))
				return;
			
			$content_type = $automation_resource->mime_type ?? 'application/octet-stream';
			
			header('Content-Type: ' . $content_type);
			
			$fp = DevblocksPlatform::getTempFile();
			
			if(!$automation_resource->getFileContents($fp))
				return;
			
			fpassthru($fp);
			fclose($fp);
			
		} else if(CerberusContexts::CONTEXT_RESOURCE == $uri_parts['context']) {
			if(is_numeric($uri_parts['context_id'])) {
				if(!($resource = \DAO_Resource::get($uri_parts['context_id'])))
					return;
			} else {
				if(!($resource = \DAO_Resource::getByName($uri_parts['context_id'])))
					return;
			}
			
			$content_data = $resource->getExtension()->getContentData($resource);
			
			$content_data->writeHeaders();
			$content_data->writeBody();
		}
	}
	
	function render(Model_AutomationContinuation $continuation) {
		$tpl = DevblocksPlatform::services()->template();
		
		$label = $this->_data['label'] ?? null;
		$filename = $this->_data['filename'] ?? null;
		
		if(!$filename)
			return;
		
		$tpl->assign('var', $this->_key);
		$tpl->assign('label', $label);
		$tpl->assign('filename', $filename);
		$tpl->assign('continuation_token', $continuation->token);
		$tpl->display('devblocks:cerberusweb.core::automations/triggers/interaction.worker/await/file_download.tpl');
	}
}