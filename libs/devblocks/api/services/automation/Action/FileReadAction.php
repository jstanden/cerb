<?php
namespace Cerb\AutomationBuilder\Action;

use DevblocksDictionaryDelegate;
use DevblocksPlatform;
use Exception_DevblocksAutomationError;
use Model_Automation;

class FileReadAction extends AbstractAction {
	const ID = 'file.read';
	
	function activate(Model_Automation $automation, DevblocksDictionaryDelegate $dict, array &$node_memory, string &$error=null) {
		$validation = DevblocksPlatform::services()->validation();
		
		$params = $this->node->getParams($dict);
		$policy = $automation->getPolicy();
		
		$inputs = $params['inputs'] ?? [];
		$output = $params['output'] ?? null;
		
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
			
			$validation->addField('length', 'inputs:length:')
				->number()
			;
			
			$validation->addField('extract', 'inputs:extract:')
				->string()
			;
			
			$validation->addField('offset', 'inputs:offset:')
				->number()
			;
			
			$validation->addField('uri', 'inputs:uri:')
				->string()
				->setRequired(true)
			;
			
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
			
			if(false == ($uri_parts = DevblocksPlatform::services()->ui()->parseURI($inputs['uri']))) {
				$error = sprintf("Failed to parse the URI (`%s`)", $inputs['uri']);
				throw new Exception_DevblocksAutomationError($error);
			}
			
			$fp_offset = intval($inputs['offset'] ?? 0);
			$fp_max_size = intval($inputs['length'] ?? 1024000);
			
			if($uri_parts['context'] == \CerberusContexts::CONTEXT_AUTOMATION_RESOURCE) {
				if(false == ($resource = \DAO_AutomationResource::getByToken($uri_parts['context_id']))) {
					$error = sprintf("Failed to load the automation resource (`%s`)", $inputs['uri']);
					throw new Exception_DevblocksAutomationError($error);
				}
				
				// Do we have a manifest key?
				$extract = $inputs['extract'] ?? null;
				
				if($extract) {
					$error = null;
					if(!($results = $this->_getFileFromManifestKey($resource, $extract, $error)))
						throw new Exception_DevblocksAutomationError($error);
					
				} else {
					$fp = DevblocksPlatform::getTempFile();
					
					$resource->getFileContents($fp);
					
					fseek($fp, $fp_offset);
					
					$bytes = fread($fp, $fp_max_size);
					$length = strlen($bytes);
					
					$is_printable = DevblocksPlatform::services()->string()->isPrintable($bytes);
					
					if(!$is_printable)
						$bytes = sprintf('data:%s;base64,%s', $resource->mime_type, base64_encode($bytes));
					
					$results = [
						'bytes' => $bytes,
						'uri' => $inputs['uri'],
						'name' => $resource->token,
						'offset_from' => $fp_offset,
						'offset_to' => $fp_offset + $length,
						'mime_type' => $resource->mime_type,
						'size' => $resource->storage_size,
					];
				}
				
				if($output)
					$dict->set($output, $results);
				
			} else if($uri_parts['context'] == \CerberusContexts::CONTEXT_ATTACHMENT) {
				if(false == ($file = \DAO_Attachment::get($uri_parts['context_id']))) {
					$error = sprintf("Failed to load the attachment (`%s`)", $inputs['uri']);
					throw new Exception_DevblocksAutomationError($error);
				}
				
				// Do we have a manifest key?
				$extract = $inputs['extract'] ?? null;
				
				if($extract) {
					$error = null;
					if(!($results = $this->_getFileFromManifestKey($file, $extract, $error)))
						throw new Exception_DevblocksAutomationError($error);
					
				} else {
					$fp = DevblocksPlatform::getTempFile();
					
					$file->getFileContents($fp);
					
					fseek($fp, $fp_offset);
					
					$bytes = fread($fp, $fp_max_size);
					$length = strlen($bytes);
					
					$is_printable = DevblocksPlatform::services()->string()->isPrintable($bytes);
					
					if (!$is_printable)
						$bytes = sprintf('data:%s;base64,%s', $file->mime_type, base64_encode($bytes));
					
					$results = [
						'bytes' => $bytes,
						'uri' => $inputs['uri'],
						'name' => $file->name,
						'offset_from' => $fp_offset,
						'offset_to' => $fp_offset + $length,
						'mime_type' => $file->mime_type,
						'size' => $file->storage_size,
					];
				}
				
				if($output)
					$dict->set($output, $results);
				
			} else {
				$error = "Only these URIs are supported: attachment, automation_resource";
				throw new Exception_DevblocksAutomationError($error);
			}
			
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
	
	private function _getFileFromManifestKey($file, $extract, &$error=null) {
		if(!extension_loaded('zip')) {
			$error = 'The `zip` extension is not loaded';
			return false;
		}
		
		$zip = new \ZipArchive();
		
		$fp = DevblocksPlatform::getTempFile();
		$fp_name = DevblocksPlatform::getTempFileInfo($fp);
		
		if(false === ($file->getFileContents($fp))) {
			$error = 'Failed to read file data';
			return false;
		}
		
		if(false === ($zip->open($fp_name))) {
			$error = 'The file is not a valid ZIP archive.';
			return false;	
		}
		
		if(false === ($index = $zip->locateName($extract))) {
			$error = sprintf('Path (%s) not found in archive manifest.', $extract);
			return false;
		}
		
		if(false === ($stat = $zip->statIndex($index))) {
			$error = sprintf('Failed to stat path (%s) in archive manifest.', $extract);
			return false;
		}
		
		if(false === ($bytes = $zip->getFromIndex($index))) {
			$error = sprintf('Failed to read bytes from path (%s) in archive manifest. Encrypted?', $extract);
			return false;
		}
		
		$is_printable = DevblocksPlatform::services()->string()->isPrintable($bytes);
		
		$mime_type = 'application/octet-stream';
		
		// Detect MIME type from magic bytes
		if(extension_loaded('fileinfo')) {
			$finfo = new \finfo(\FILEINFO_MIME_TYPE);
			$mime_type = $finfo->buffer($bytes);
		}
		
		if (!$is_printable)
			$bytes = sprintf('data:%s;base64,%s', $mime_type, base64_encode($bytes));
		
		return [
			'bytes' => $bytes,
			'name' => $stat['name'],
			'size' => $stat['size'],
			'mime_type' => $mime_type,
		];
	}
}