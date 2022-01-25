<?php
namespace Cerb\Automation\Builder\Trigger\InteractionWebsite\Awaits;

use _DevblocksValidationService;
use DevblocksPlatform;
use Model_AutomationContinuation;

class SayAwait extends AbstractAwait {
	function invoke(string $prompt_key, string $action, Model_AutomationContinuation $continuation) {
		return false;
	}

	function formatValue() {
		return $this->_value;
	}
	
	function validate(_DevblocksValidationService $validation) {
	}
	
	function render(Model_AutomationContinuation $continuation) {
		$tpl = DevblocksPlatform::services()->templateSandbox();
		
		$msg = '';
		$format = 'text';
		$style = is_array($this->_data) && array_key_exists('style', $this->_data) ? $this->_data['style'] : null;
		
		if(is_string($this->_data)) {
			$msg = $this->_data;
			
		} else if(array_key_exists('content', $this->_data)) {
			$msg = \Portal_WebsiteInteractions::parseMarkdown($this->_data['content']);
			$format = 'markdown';
			
			// If we have references to replace and a secret, extract fragments from
			// image URLs and generate signatures
			if(array_key_exists('references', $this->_data)) {
				$secret = $this->_schema->getImageRequestsSecret() ?? sha1(DevblocksPlatform::services()->encryption()->getSystemKey());
				$portal_code = \ChPortalHelper::getCode();
				
				$references = array_combine(
					array_map(function($key) {
						return DevblocksPlatform::services()->string()->strAfter($key, '/') ?? uniqid();
					}, array_keys($this->_data['references'])),
					array_map(function($ref) {
						$uri_parts = DevblocksPlatform::services()->ui()->parseURI($ref['uri'] ?? null);
						return $uri_parts['context_id'] ?? null;
					}, $this->_data['references'])
				);
				
				$filter = new \Cerb_HTMLPurifier_URIFilter_Extract();
				
				$msg = DevblocksPlatform::purifyHTML($msg, true, true, [$filter]);
				
				$results = $filter->flush();
				
				if (
					is_array($results)
					&& array_key_exists('tokens', $results)
					&& array_key_exists('context', $results)
				) {
					$url_writer = DevblocksPlatform::services()->url();
					
					foreach ($results['tokens'] as $token => $ref) {
						$context = $results['context'][$token];
						
						if (
							($context['is_tag'] ?? false)
							&& ($context['name'] ?? null) == 'img'
							&& ($context['attr'] ?? null) == 'src'
							&& ($context['uri_parts']['scheme'] ?? null) == null
							&& ($context['uri_parts']['host'] ?? null) == null
							&& ($context['uri_parts']['fragment'] ?? null) != null
						) {
							$uri = DevblocksPlatform::strAlphaNum($context['uri_parts']['fragment'], '._');
							
							if(array_key_exists($uri, $references)) {
								if(null != ($resource_name = $references[$uri])) {
									$hash_calc = hash_hmac('sha256', implode('/',[$resource_name,$portal_code]), $secret);
									$results['tokens'][$token] = $url_writer->write('c=assets&a=image&hash=' . $hash_calc . '&f=' . $resource_name, true);
								}
							}
						}
					}
					
					$msg = str_replace(array_keys($results['tokens']), $results['tokens'], $msg);
				}
				
			} else {
				$msg = DevblocksPlatform::purifyHTML($msg, true, true);
			}
			
		} else if(array_key_exists('message', $this->_data)) {
			$msg = @$this->_data['message'];
		}
		
		$tpl->assign('message', $msg);
		$tpl->assign('format', $format);
		$tpl->assign('style', $style);
		$tpl->display('devblocks:cerberusweb.core::automations/triggers/interaction.website/await/say.tpl');
	}
}