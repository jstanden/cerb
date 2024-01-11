<?php
namespace Cerb\Automation\Builder\Trigger\InteractionWorker\Awaits;

use _DevblocksValidationService;
use DevblocksPlatform;
use Model_AutomationContinuation;

class AudioAwait extends AbstractAwait {
	function invoke(string $prompt_key, string $action, Model_AutomationContinuation $continuation) {
		if ($action == 'play') {
			return $this->_promptAction_play($prompt_key, $continuation);
		}
		return false;
	}

	function formatValue() {
		return $this->_value;
	}
	
	function validate(_DevblocksValidationService $validation) {
	}
	
	private function _promptAction_play(string $prompt_key, Model_AutomationContinuation $continuation) : void {
		$uri = $this->_data['source']['uri'] ?? null;
		$blob = $this->_data['source']['blob'] ?? null;
		
		if($blob) {
			$url_parts = parse_url($blob);
			unset($blob);
			
			$scheme = $url_parts['scheme'] ?? null;
			
			if($scheme != 'data')
				DevblocksPlatform::dieWithHttpError(null, 404);
			
			list($content_type, $bytes) = array_pad(explode(';base64,', $url_parts['path']), 2, null);
			
			if(!DevblocksPlatform::strStartsWith($content_type, 'audio/'))
				DevblocksPlatform::dieWithHttpError(null, 404);
			
			$bytes = base64_decode($bytes);
			
			DevblocksPlatform::services()->http()
				->setHeader('Content-Type', $content_type)
				->setHeader('Content-Length', strlen($bytes))
			;
			
			echo $bytes;
			
		} elseif($uri) {
			if(!($uri_parts = DevblocksPlatform::services()->ui()->parseURI($uri)))
				DevblocksPlatform::dieWithHttpError(null, 404);
			
			if(!($automation_resource = \DAO_AutomationResource::getByToken($uri_parts['context_id'])))
				DevblocksPlatform::dieWithHttpError(null, 404);
			
			$content_type = $automation_resource->mime_type ?? 'audio/mpeg';
			
			DevblocksPlatform::services()->http()
				->setHeader('Content-Type', $content_type)
			;
			
			// Use memory if less than 32MB
			$fp = fopen('php://temp/maxmemory:32768000', 'rw');
			
			if(!$automation_resource->getFileContents($fp))
				return;
			
			$fstat = fstat($fp);
			
			DevblocksPlatform::services()->http()
				->setHeader('Content-Length', intval($fstat['size']))
			;
			
			fpassthru($fp);
			fclose($fp);
			
		} else {
			DevblocksPlatform::dieWithHttpError(null, 404);
		}
	}
	
	function render(Model_AutomationContinuation $continuation) {
		$tpl = DevblocksPlatform::services()->template();
		
		$label = $this->_data['label'] ?? null;
		
		$autoplay = $this->_data['autoplay'] ?? null;
		$controls = $this->_data['controls'] ?? null;
		$loop = $this->_data['loop'] ?? null;
		
		if(is_null($autoplay)) $autoplay = true;
		if(is_null($controls)) $controls = true;
		if(is_null($loop)) $loop = false;
		
		$tpl->assign('var', $this->_key);
		$tpl->assign('label', $label);
		$tpl->assign('autoplay', $autoplay);
		$tpl->assign('controls', $controls);
		$tpl->assign('loop', $loop);
		$tpl->assign('continuation_token', $continuation->token);
		$tpl->display('devblocks:cerberusweb.core::automations/triggers/interaction.worker/await/audio.tpl');
	}
}