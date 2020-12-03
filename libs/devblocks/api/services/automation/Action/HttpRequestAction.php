<?php
namespace Cerb\AutomationBuilder\Action;

use DevblocksDictionaryDelegate;
use DevblocksPlatform;
use Exception_DevblocksAutomationError;
use GuzzleHttp\Psr7\Request;
use function GuzzleHttp\headers_from_lines;

class HttpRequestAction extends AbstractAction {
	const ID = 'http.request';
	
	function activate(\DevblocksDictionaryDelegate $dict, array &$node_memory, \CerbAutomationPolicy $policy, string &$error=null) {
		$http = DevblocksPlatform::services()->http();
		$validation = DevblocksPlatform::services()->validation();
		
		// [TODO] Timeout
		// [TODO] SSL certs
		// [TODO] Sign request with connected accounts
		// [TODO] User-level option to follow redirects
		
		$params = $this->node->getParams($dict);
		
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
			
			@$inputs = $params['inputs'] ?? [];
			@$output = $params['output'];
			
			if(!array_key_exists('method', $inputs))
				$inputs['method'] = 'GET';
			
			$validation->addField('url', 'inputs:url:')
				->url()
				->setRequired(true)
			;
			
			$validation->addField('method', 'inputs:method:')
				->string()
				->addFormatter($validation->formatters()->stringUpper())
				->setPossibleValues(['GET', 'PUT', 'POST', 'PATCH', 'DELETE', 'OPTIONS', 'HEAD'])
			;
			
			$validation->addField('headers', 'inputs:headers:')
				->stringOrArray()
			;
			
//			$validation->addField('query', 'inputs:query:')
//				->stringOrArray()
//			;
			
			$validation->addField('body', 'inputs:body:')
				->string()
				->setMaxLength('24 bits')
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
					"The automation policy does not permit this action (%s).",
					self::ID
				);
				throw new Exception_DevblocksAutomationError($error);
			}
			
			$headers = [];
			@$url = $inputs['url'];
			@$method = $inputs['method'];
			@$body = $inputs['body'];
			
			if(array_key_exists('headers', $inputs))
				@$headers = headers_from_lines(DevblocksPlatform::parseCrlfString($inputs['headers']));
			
			$request = new Request($method, $url, $headers, $body);
			$request_options = [];
			//$connected_account = null;
			
			switch($method) {
				case 'POST':
				case 'PUT':
				case 'PATCH':
					if(!$request->hasHeader('content-type'))
						$request = $request->withHeader('Content-Type', 'application/x-www-form-urlencoded');
					break;
			}
			
			/*
			if(isset($options['ignore_ssl_validation']) && $options['ignore_ssl_validation']) {
				$request_options['verify'] = false;
			}
			
			if(isset($options['connected_account']) && $options['connected_account']) {
				if(false == ($connected_account = DAO_ConnectedAccount::get($options['connected_account'])))
					return false;
				
				if(false == $connected_account->authenticateHttpRequest($request, $request_options, CerberusContexts::getCurrentActor()))
					return false;
			}
			*/
			
			$error = null;
			$status_code = null;
			
			$response = $http->sendRequest($request, $request_options, $error, $status_code);
			
			if(false === $response) {
				if($output) {
					$dict->set($output, [
						'status_code' => $status_code,
						'url' => $url,
						'error' => $error,
					]);
				}
				
				if(null != ($event_error = $this->node->getChildBySuffix(':on_error'))) {
					return $event_error->getId();
				}
				
			} else {
				$response_body = $response->getBody()->getContents();
				
				$content_type = $response->getHeaderLine('Content-Type');
				@list($content_type, $content_attributes) = explode(';', $content_type, 2);
				
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