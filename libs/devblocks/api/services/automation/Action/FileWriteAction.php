<?php
namespace Cerb\AutomationBuilder\Action;

use DevblocksDictionaryDelegate;
use DevblocksPlatform;
use Exception_DevblocksAutomationError;
use Model_Automation;

class FileWriteAction extends AbstractAction {
	const ID = 'file.write';
	
	function activate(Model_Automation $automation, DevblocksDictionaryDelegate $dict, array &$node_memory, string &$error=null) {
		$validation = DevblocksPlatform::services()->validation();
		
		$params = $automation->getParams($this->node, $dict);
		$policy = $automation->getPolicy();
		
		$inputs = $params['inputs'] ?? [];
		$output = $params['output'] ?? null;
		
		$zip_name = null;
		
		try {
			// Validate params
			
			$validation->addField('inputs', 'inputs:')
				->array()
				->setRequired(true);
			
			$validation->addField('output', 'output:')
				->string()
				->setRequired(true)
				;
			
			if (false === ($validation->validateAll($params, $error)))
				throw new Exception_DevblocksAutomationError($error);
			
			// Validate inputs
			
			$validation->reset();
			
			$validation->addField('content', 'inputs:content:')
				->stringOrArray()
				->setRequired(true)
				->setMaxLength('24 bits')
			;
			
			$validation->addField('mime_type', 'inputs:mime_type:')
				->string()
			;
			
			$validation->addField('name', 'inputs:name:')
				->string()
			;

			$validation->addField('expires', 'inputs:expires:')
				->timestamp()
			;
			
			$validation->addField('uri', 'inputs:uri:')
				->string()
			;
			
			/** @noinspection DuplicatedCode */
			if (false === ($validation->validateAll($inputs, $error)))
				throw new Exception_DevblocksAutomationError($error);
			
			$action_dict = DevblocksDictionaryDelegate::instance([
				'node' => [
					'id' => $this->node->getId(),
					'type' => self::ID,
				],
				'inputs' => $inputs,
				'output' => $output,
			]);
			
			if (!$policy->isCommandAllowed(self::ID, $action_dict)) {
				$error = sprintf(
					"The automation policy does not allow this command (%s).",
					self::ID
				);
				throw new Exception_DevblocksAutomationError($error);
			}
			
			$file_name = DevblocksPlatform::strLower($inputs['name'] ?? null);
			$mime_type = DevblocksPlatform::strLower($inputs['mime_type'] ?? 'application/octet-stream');
			$content = $inputs['content'] ?? null;
			$expires_at = $inputs['expires'] ?? (time() + 900);
			$resource_token = DevblocksPlatform::services()->string()->uuid();
			
			$possible_content_keys = [
				'bytes',
				'text',
				'zip',
			];
			
			if(is_scalar($content)) {
				$content = [
					'text' => strval($content),
				];
			}
			
			if(!is_array($content)) {
				$error = sprintf(
					"`%s:inputs:content:` must be a string or an object",
					$this->node->getId()
				);
				throw new Exception_DevblocksAutomationError($error);
			}
			
			$content_key = array_key_first($content);
			
			if(
				1 != count(array_keys($content)) 
				|| !in_array($content_key, $possible_content_keys)
			) {
				$error = sprintf(
					"`%s:inputs:content:` must be one of: %s",
					$this->node->getId(),
					implode(', ', $possible_content_keys)
				);
				throw new Exception_DevblocksAutomationError($error);
			}
			
			$results = [];
			
			if('zip' == $content_key) {
				try {
					$zip_name = $this->_createZip($content['zip']);
					$mime_type = 'application/zip';
					
					$fields = [
						\DAO_AutomationResource::MIME_TYPE => $mime_type,
						\DAO_AutomationResource::TOKEN => $resource_token,
						\DAO_AutomationResource::EXPIRES_AT => $expires_at,
					];
					
					if($file_name)
						$fields[\DAO_AutomationResource::NAME] = $file_name;
					
					$resource_id = \DAO_AutomationResource::create($fields);
					
					if(!($fp = fopen($zip_name, 'r+b')))
						throw new Exception_DevblocksAutomationError('Failed to read ZIP archive.');
					
					$fstat = fstat($fp);
					
					$results = [
						'uri' => 'cerb:automation_resource:' . $resource_token,
						'mime_type' => $mime_type,
						'expires_at' => $expires_at,
						'size' => $fstat['size'] ?? 0,
						'id' => $resource_id,
					];
					
					if($file_name)
						$results['name'] = $file_name;
					
					\Storage_AutomationResource::put($resource_id, $fp);
					
					fclose($fp);
					
				} finally {
					if($zip_name && file_exists($zip_name))
						@unlink($zip_name);
				}
				
			} else if ('text' == $content_key) {
				if(!is_scalar($content['text']))
					throw new Exception_DevblocksAutomationError('`file.write:inputs:content:text:` must be a string.');
				
				$fields = [
					\DAO_AutomationResource::MIME_TYPE => $mime_type,
					\DAO_AutomationResource::TOKEN => $resource_token,
					\DAO_AutomationResource::EXPIRES_AT => $expires_at,
				];
				
				if($file_name)
					$fields[\DAO_AutomationResource::NAME] = $file_name;
				
				$resource_id = \DAO_AutomationResource::create($fields);
				
				$results = [
					'uri' => 'cerb:automation_resource:' . $resource_token,
					'mime_type' => $mime_type,
					'expires_at' => $expires_at,
					'size' => strlen($content['text']),
					'id' => $resource_id,
				];
				
				if($file_name)
					$results['name'] = $file_name;
				
				\Storage_AutomationResource::put($resource_id, $content['text']);
			}
			
			if($output)
				$dict->set($output, $results);
			
		} catch (Exception_DevblocksAutomationError $e) {
			$error = $e->getMessage();
			
			if (null != ($event_error = $this->node->getChildBySuffix(':on_error'))) {
				if ($output) {
					$dict->set($output, [
						'error' => $error,
					]);
				}
				
				return $event_error->getId();
			}
			
			return false;
		}
		
		if (null != ($event_success = $this->node->getChildBySuffix(':on_success'))) {
			return $event_success->getId();
		}
		
		return $this->node->getParent()->getId();
	}
	
	/**
	 * @throws Exception_DevblocksAutomationError
	 */
	private function _createZip($data) : string {
		$zip_name = tempnam(APP_TEMP_PATH, 'tmp');
		
		$zip = new \ZipArchive();
		$zip->open($zip_name, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
		
		if(!array_key_exists('files', $data) || !is_array($data['files']))
			throw new Exception_DevblocksAutomationError('`file.write:inputs:content:zip:files:` must be a list');
		
		if(array_key_exists('password', $data)) {
			if(!is_string($data['password']))
				throw new Exception_DevblocksAutomationError('`file.write:inputs:content:zip:password:` must be a string');
			
			$zip->setPassword($data['password']);
		}
		
		foreach($data['files'] as $file_key => $file) {
			$file_key_path = 'file.write:inputs:content:zip:files:' . $file_key;
			
			if(!is_array($file))
				throw new Exception_DevblocksAutomationError(sprintf('`%s:` must be an object', $file_key_path));
			
			if(!array_key_exists('path', $file))
				throw new Exception_DevblocksAutomationError(sprintf('`%s:path:` is required', $file_key_path));
			
			if(array_key_exists('uri', $file)) {
				if(!($uri_parts = DevblocksPlatform::services()->ui()->parseURI($file['uri'])))
					throw new Exception_DevblocksAutomationError(sprintf('`%s:uri:` is invalid', $file_key_path));
				
				if(\CerberusContexts::isSameContext($uri_parts['context'], \CerberusContexts::CONTEXT_AUTOMATION_RESOURCE)) {
					if(!($automation_resource = \DAO_AutomationResource::getByToken($uri_parts['context_id'])))
						throw new Exception_DevblocksAutomationError(sprintf('`%s:uri:` is an invalid automation resource', $file_key_path));
					
					$fp = DevblocksPlatform::getTempFile();
					$fp_name = DevblocksPlatform::getTempFileInfo($fp);
					
					if(!$automation_resource->getFileContents($fp))
						throw new Exception_DevblocksAutomationError(sprintf('`%s:uri:` is unreadable', $file_key_path));
					
					$zip->addFile($fp_name, $file['path']);
					
					if(array_key_exists('password', $data))
						$zip->setEncryptionName($file['path'], \ZipArchive::EM_AES_192);					
					
					fclose($fp);
					
				} elseif (\CerberusContexts::isSameContext($uri_parts['context'], \CerberusContexts::CONTEXT_ATTACHMENT)) {
					if(!($attachment = \DAO_Attachment::get($uri_parts['context_id'])))
						throw new Exception_DevblocksAutomationError(sprintf('`%s:uri:` is an invalid attachment', $file_key_path));
					
					$fp = DevblocksPlatform::getTempFile();
					$fp_name = DevblocksPlatform::getTempFileInfo($fp);
					
					DevblocksPlatform::logError($fp_name);
					
					if(!$attachment->getFileContents($fp))
						throw new Exception_DevblocksAutomationError(sprintf('`%s:uri:` is unreadable', $file_key_path));
					
					$zip->addFile($fp_name, $file['path']);
					
					if(array_key_exists('password', $data))
						$zip->setEncryptionName($file['path'], \ZipArchive::EM_AES_192);
					
					fclose($fp);
					
				} else {
					throw new Exception_DevblocksAutomationError(sprintf('`%s:uri:` must be an attachment or automation resource', $file_key_path));
				}
				
			} else if(array_key_exists('bytes', $file)) {
				$zip->addFromString($file['path'], $file['bytes']);
				
			} else {
				throw new Exception_DevblocksAutomationError(sprintf('`%s:` must provide either `uri:` or `bytes:`', $file_key_path));
			}
		}
		
		$zip->close();
		
		return $zip_name;
	}
}