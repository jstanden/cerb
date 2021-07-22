<?php
namespace Cerb\AutomationBuilder\Action;

use CerberusContexts;
use DAO_ConnectedAccount;
use DevblocksDictionaryDelegate;
use DevblocksPlatform;
use Exception_DevblocksAutomationError;
use GuzzleHttp\Psr7\Request;
use Model_Automation;
use function GuzzleHttp\headers_from_lines;

class HttpRequestAction extends AbstractAction {
	const ID = 'http.request';
	
	function activate(Model_Automation $automation, DevblocksDictionaryDelegate $dict, array &$node_memory, string &$error=null) {
		$http = DevblocksPlatform::services()->http();
		$validation = DevblocksPlatform::services()->validation();
		
		// [TODO] SSL certs
		// [TODO] User-level option to follow redirects
		
		$params = $this->node->getParams($dict);
		$policy = $automation->getPolicy();
		
		$inputs = $params['inputs'] ?? [];
		$output = @$params['output'];
		
		try {
			// Params validation
			
			$validation->addField('inputs', 'inputs:')
				->array()
			;
			
			$validation->addField('output', 'output:')
				->string()
				->setRequired(true)
			;
			
			if(false === ($validation->validateAll($params, $error)))
				throw new Exception_DevblocksAutomationError($error);
			
			$validation->reset();
			
			// Inputs validation
			
			$validation->addField('authentication', 'inputs:authentication:')
				->string()
			;
			
			if(!array_key_exists('method', $inputs))
				$inputs['method'] = 'GET';
			
			$validation->addField('url', 'inputs:url:')
				->url()
				->setMaxLength(2048)
				->setRequired(true)
			;
			
			$validation->addField('method', 'inputs:method:')
				->string()
				->addFormatter($validation->formatters()->stringUpper())
				->setPossibleValues(['GET', 'PUT', 'POST', 'PATCH', 'DELETE', 'OPTIONS', 'HEAD'])
			;
			
			$validation->addField('headers', 'inputs:headers:')
				->stringOrArray()
				->setMaxLength(8192)
			;
			
			$validation->addField('body', 'inputs:body:')
				->stringOrArray()
				->setMaxLength(16_777_216)
			;
			
			$validation->addField('timeout', 'inputs:timeout:')
				->float()
				->setMin(0)
				->setMax(60)
			;
			
			if(false === ($validation->validateAll($inputs, $error)))
				throw new Exception_DevblocksAutomationError($error);
			
			$action_dict = DevblocksDictionaryDelegate::instance([
				'node' => [
					'id' => $this->node->getId(),
					'type' => self::ID,
				],
				'inputs' => $inputs,
				'output' => $output,
			]);
			
			if(!$policy->isCommandAllowed(self::ID, $action_dict)) {
				$error = sprintf(
					"The automation policy does not allow this command (%s).",
					self::ID
				);
				throw new Exception_DevblocksAutomationError($error);
			}
			
			$headers = [];
			@$url = $inputs['url'];
			@$method = $inputs['method'];
			@$body = $inputs['body'];
			
			if(array_key_exists('headers', $inputs)) {
				if(is_string($inputs['headers'])) {
					$headers = @headers_from_lines(DevblocksPlatform::parseCrlfString($inputs['headers']));
					
					$headers = array_combine(
						array_map(fn($k) => DevblocksPlatform::strLower($k), array_keys($headers)),
						$headers
					);
					
				} else if(is_array($inputs['headers'])) {
					$headers = array_combine(
						array_map(fn($k) => DevblocksPlatform::strLower($k), array_keys($inputs['headers'])),
						$inputs['headers']
					);
				}
			}
			
			if(is_string($body)) {
				if(!array_key_exists('content-type', $headers))
					$headers['content-type'] = 'application/x-www-form-urlencoded';
				
			} else if(is_array($body)) {
				if(!array_key_exists('content-type', $headers))
					$headers['content-type'] = 'application/x-www-form-urlencoded';
				
				switch($headers['content-type']) {
					case 'application/json':
					case 'application/x-amz-json-1.0':
					case 'application/x-amz-json-1.1':
						$body = json_encode($body,JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
						break;
						
					case 'application/x-yaml':
					case 'text/yaml':
						$body = DevblocksPlatform::services()->string()->yamlEmit($body, false);
						break;
						
					default:
						$body = DevblocksPlatform::services()->url()->arrayToQueryString($body);
						break;
				}
			}
			
			$request = new Request($method, $url, $headers, $body);
			$request_options = [];
			
			/*
			if(isset($options['ignore_ssl_validation']) && $options['ignore_ssl_validation']) {
				$request_options['verify'] = false;
			}
			*/
			
			if(array_key_exists('timeout', $inputs) && $inputs['timeout']) {
				$request_options['timeout'] = $inputs['timeout'];
			}
			
			if(array_key_exists('authentication', $inputs) && $inputs['authentication']) {
				$uri_parts = DevblocksPlatform::services()->ui()->parseURI($inputs['authentication']);
				
				if(is_numeric($uri_parts['context_id'])) {
					$connected_account = DAO_ConnectedAccount::get($uri_parts['context_id']);
				} else {
					$connected_account = DAO_ConnectedAccount::getByUri($uri_parts['context_id']);
				}
				
				if(!$connected_account) {
					$error = sprintf('Unknown account for authentication (%s)',
						$inputs['authentication']
					);
					return false;
				}
				
				if(false == $connected_account->authenticateHttpRequest($request, $request_options, [CerberusContexts::CONTEXT_APPLICATION, 0])) {
					$error = sprintf('Failed to authenticate with account (%s)',
						$inputs['authentication']
					);
					return false;
				}
			}
			
			$error = null;
			$status_code = null;
			
			$response = $http->sendRequest($request, $request_options, $error, $status_code);
			
			if(false === $response) {
				if(null != ($event_error = $this->node->getChildBySuffix(':on_error'))) {
					if($output) {
						$dict->set($output, [
							'status_code' => $status_code,
							'url' => $url,
							'error' => $error,
						]);
					}
					return $event_error->getId();
					
				} else {
					return false;
				}
				
			} else {
				$response_body = $response->getBody()->getContents();
				
				$content_type = $response->getHeaderLine('Content-Type');
				list($content_type, $content_attributes) = array_pad(explode(';', $content_type, 2), 2, '');
				
				$content_type = trim(DevblocksPlatform::strLower($content_type));
				$content_attributes = DevblocksPlatform::parseHttpHeaderAttributes($content_attributes);
				
				$response_headers = [];
				foreach($response->getHeaders() as $k => $v) {
					$response_headers[DevblocksPlatform::strLower($k)] = implode(', ', $v);
				}
				
				// Fix bad encodings
				if(isset($content_attributes['charset'])) {
					@$response_body = mb_convert_encoding($response_body, $content_attributes['charset']);
				}
				
				if ($output) {
					$dict->set($output, [
						'url' => $url,
						'status_code' => $status_code,
						'content_type' => $content_type,
						'headers' => $response_headers,
						'body' => $response_body,
					]);
				}
				
			}
		} catch (Exception_DevblocksAutomationError $e) {
			$error = $e->getMessage();
			
			if(null != ($event_error = $this->node->getChildBySuffix(':on_error'))) {
				if ($output) {
					$dict->set($output, [
						'error' => $error,
					]);
				}
				
				return $event_error->getId();
			}
			
			return false;
		}
		
		if(null != ($event_success = $this->node->getChildBySuffix(':on_success'))) {
			return $event_success->getId();
		}
		
		return $this->node->getParent()->getId();
	}
}