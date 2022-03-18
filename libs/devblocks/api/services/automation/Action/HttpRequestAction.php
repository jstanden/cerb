<?php
namespace Cerb\AutomationBuilder\Action;

use CerberusContexts;
use DAO_Attachment;
use DAO_AutomationResource;
use DAO_ConnectedAccount;
use DevblocksDictionaryDelegate;
use DevblocksPlatform;
use Exception_DevblocksAutomationError;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Utils;
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
		$output = $params['output'] ?? null;
		
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
			$url = $inputs['url'] ?? null;
			$method = $inputs['method'] ?? null;
			$body = $inputs['body'] ?? null;
			
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
			
			if(is_string($body) && array_key_exists('content-type', $headers) && 'application/vnd.cerb.uri' == $headers['content-type']) {
				if(false == ($uri_parts = DevblocksPlatform::services()->ui()->parseURI($body)))
					throw new Exception_DevblocksAutomationError('Failed to parse the `cerb:` URI body');
			
				if(CerberusContexts::isSameContext(CerberusContexts::CONTEXT_AUTOMATION_RESOURCE, $uri_parts['context'])) {
					if(false == ($resource = DAO_AutomationResource::get($uri_parts['context_id'])))
						throw new Exception_DevblocksAutomationError("Failed to load automation resource id #" . $uri_parts['context_id']);
					
					$headers['content-type'] = $resource->mime_type;
					
					$fp = DevblocksPlatform::getTempFile();
					
					if(false == ($resource->getFileContents($fp)))
						throw new Exception_DevblocksAutomationError("Failed to load content for automation resource id #" . $uri_parts['context_id']);
					
					$body = Utils::streamFor($fp);
					
				} else if(CerberusContexts::isSameContext(CerberusContexts::CONTEXT_ATTACHMENT, $uri_parts['context'])) {
					if(false == ($attachment = DAO_Attachment::get($uri_parts['context_id'])))
						throw new Exception_DevblocksAutomationError("Failed to load attachment id #" . $uri_parts['context_id']);
					
					$headers['content-type'] = $attachment->mime_type;
					
					$fp = DevblocksPlatform::getTempFile();
					
					if(false == ($attachment->getFileContents($fp)))
						throw new Exception_DevblocksAutomationError("Failed to load content for attachment id #" . $uri_parts['context_id']);
					
					$body = Utils::streamFor($fp);
				}
				
			} else if(is_string($body)) {
				if(!array_key_exists('content-type', $headers))
					$headers['content-type'] = 'application/x-www-form-urlencoded';
				
			} else if(is_array($body)) {
				if(!array_key_exists('content-type', $headers))
					$headers['content-type'] = 'application/x-www-form-urlencoded';
				
				$request_content_type = $headers['content-type'];
				list($request_content_type,) = array_pad(explode(';', $request_content_type, 2), 2, '');
				$request_content_type = trim(DevblocksPlatform::strLower($request_content_type));
				
				switch($request_content_type) {
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
			$error_response = null;
			
			$response = $http->sendRequest($request, $request_options, $error, $error_response);
			
			if(false === $response) {
				if(null != ($event_error = $this->node->getChildBySuffix(':on_error'))) {
					$error_dict = [];
					
					if($error_response instanceof \Psr\Http\Message\ResponseInterface) {
						if(false === ($error_dict = $this->_buildResults($error_response, $error)))
							return false;
					}
					
					$error_dict['url'] = $url;
					$error_dict['error'] = $error;
					
					if($output) {
						$dict->set($output, $error_dict);
					}
					
					return $event_error->getId();
					
				} else {
					return false;
				}
				
			} else {
				if ($output) {
					if(false === ($results = $this->_buildResults($response, $error)))
						return false;
					
					$results['url'] = $url;
					
					$dict->set($output, $results);
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
	
	private function _buildResults(\Psr\Http\Message\ResponseInterface $response, &$error=null) {
		$results = [
			'status_code' => $response->getStatusCode(),
		];
		
		$content_type = $response->getHeaderLine('Content-Type');
		list($content_type, $content_attributes) = array_pad(explode(';', $content_type, 2), 2, '');
		
		$content_type = trim(DevblocksPlatform::strLower($content_type));
		$content_attributes = DevblocksPlatform::parseHttpHeaderAttributes($content_attributes);
		
		$response_headers = [];
		foreach($response->getHeaders() as $k => $v) {
			$response_headers[DevblocksPlatform::strLower($k)] = implode(', ', $v);
		}
		
		$response_size = $response->getBody()->getSize() ?? 0;
		
		// If larger than 1MB, stream to an automation resource
		if($response_size > 1_024_000) {
			$resource_token = DevblocksPlatform::services()->string()->uuid();
			
			$resource_id = DAO_AutomationResource::create([
				DAO_AutomationResource::EXPIRES_AT => time() + 900, // +15 mins
				DAO_AutomationResource::TOKEN => $resource_token,
				DAO_AutomationResource::MIME_TYPE => $content_type,
			]);
			
			if(false == $resource_id) {
				$error = 'Failed to create automation resource';
				return false;
			}
			
			$fp = DevblocksPlatform::getTempFile();
			
			while(!$response->getBody()->eof()) {
				$buffer = $response->getBody()->read(512_000);
				fwrite($fp, $buffer);
			}
			
			$response->getBody()->close();
			
			\Storage_AutomationResource::put($resource_id, $fp);
			
			$results['is_cerb_uri'] = true;
			$results['content_type_original'] = $content_type;
			$content_type = 'application/vnd.cerb.uri';
			$response_body = sprintf("cerb:automation_resource:%s", $resource_token);
			
		} else {
			$response_body = $response->getBody()->getContents();
			
			// Fix bad encodings
			if(isset($content_attributes['charset'])) {
				@$response_body = mb_convert_encoding($response_body, $content_attributes['charset']);
			}
			
			// If binary, base64 encode
			if(!DevblocksPlatform::services()->string()->isPrintable($response_body)) {
				$results['is_data_uri'] = true;
				
				$response_body = sprintf('data:%s;base64,%s',
					$content_type,
					base64_encode($response_body)
				);
			}
		}
		
		$results['content_type'] = $content_type;
		$results['headers'] = $response_headers;
		$results['body'] = $response_body;
		
		return $results;
	}
}