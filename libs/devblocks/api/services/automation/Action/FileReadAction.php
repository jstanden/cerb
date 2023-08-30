<?php
namespace Cerb\AutomationBuilder\Action;

use DevblocksDictionaryDelegate;
use DevblocksPlatform;
use Exception_DevblocksAutomationError;
use Model_Automation;

class FileReadAction extends AbstractAction {
	const ID = 'file.read';
	
	function activate(Model_Automation $automation, DevblocksDictionaryDelegate $dict, array &$node_memory, string &$error=null) : string|false {
		$validation = DevblocksPlatform::services()->validation();
		
		$params = $automation->getParams($this->node, $dict);
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
			
			$validation->addField('length_split', 'inputs:length_split:')
				->string()
			;
			
			$validation->addField('extract', 'inputs:extract:')
				->string()
			;
			
			$validation->addField('offset', 'inputs:offset:')
				->number()
			;
			
			$validation->addField('password', 'inputs:password:')
				->string()
			;
			
			$validation->addField('filters', 'inputs:filters:')
				->array()
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
			
			if(!($uri_parts = DevblocksPlatform::services()->ui()->parseURI($inputs['uri']))) {
				$error = sprintf("Failed to parse the URI (`%s`)", $inputs['uri']);
				throw new Exception_DevblocksAutomationError($error);
			}
			
			$fp_offset = intval($inputs['offset'] ?? 0);
			$fp_max_size = intval($inputs['length'] ?? 1024000);
			$length_split = strval($inputs['length_split'] ?? '');
			
			if($uri_parts['context'] == \CerberusContexts::CONTEXT_AUTOMATION_RESOURCE) {
				if(!($resource = \DAO_AutomationResource::getByToken($uri_parts['context_id']))) {
					$error = sprintf("Failed to load the automation resource (`%s`)", $inputs['uri']);
					throw new Exception_DevblocksAutomationError($error);
				}
				
				// Do we have a manifest key?
				$filters = $inputs['filters'] ?? [];
				
				if(array_key_exists('extract', $inputs) && $inputs['extract']) {
					$error = null;
					if(!($results = $this->_getFileFromManifestKey($resource, $inputs, $error)))
						throw new Exception_DevblocksAutomationError($error);
					
				} else {
					$fp = DevblocksPlatform::getTempFile();
					$stream_mime_type = $resource->mime_type;
					
					$resource->getFileContents($fp);
					
					$fp_filters = $this->_registerStreamFilters($fp, $filters, $stream_mime_type);
					
					$bytes = stream_get_contents($fp, $fp_max_size, $fp_offset);
					$bytes = $this->_lengthSplit($bytes, $length_split);
					$length = strlen($bytes);
					
					$this->_deregisterStreamFilters($fp_filters);
					
					$is_printable = DevblocksPlatform::services()->string()->isPrintable($bytes);
					
					if(!$is_printable)
						$bytes = sprintf('data:%s;base64,%s', $stream_mime_type, base64_encode($bytes));
					
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
				
			} else if($uri_parts['context'] == \CerberusContexts::CONTEXT_RESOURCE) {
				if(!($resource = \DAO_Resource::getByName($uri_parts['context_id']))) {
					$error = sprintf("Failed to load the resource (`%s`)", $inputs['uri']);
					throw new Exception_DevblocksAutomationError($error);
				}
				
				// Do we have a manifest key?
				$filters = $inputs['filters'] ?? [];
				
				if(array_key_exists('extract', $inputs) && $inputs['extract']) {
					$error = null;
					if(!($results = $this->_getFileFromManifestKey($resource, $inputs, $error)))
						throw new Exception_DevblocksAutomationError($error);
					
				} else {
					$fp = DevblocksPlatform::getTempFile();
					$stream_mime_type = $resource->mime_type ?? 'application/octet-stream';
					
					$resource->getFileContents($fp);
					
					$fp_filters = $this->_registerStreamFilters($fp, $filters, $stream_mime_type);
					
					$bytes = stream_get_contents($fp, $fp_max_size, $fp_offset);
					$bytes = $this->_lengthSplit($bytes, $length_split);
					$length = strlen($bytes);
					
					$this->_deregisterStreamFilters($fp_filters);
					
					$is_printable = DevblocksPlatform::services()->string()->isPrintable($bytes);
					
					if(!$is_printable)
						$bytes = sprintf('data:%s;base64,%s', $stream_mime_type, base64_encode($bytes));
					
					$results = [
						'bytes' => $bytes,
						'uri' => $inputs['uri'],
						'name' => $resource->name,
						'offset_from' => $fp_offset,
						'offset_to' => $fp_offset + $length,
						//'mime_type' => $resource->mime_type,
						'mime_type' => 'application/octet-stream',
						'size' => $resource->storage_size,
					];
				}
				
				if($output)
					$dict->set($output, $results);
				
			} else if($uri_parts['context'] == \CerberusContexts::CONTEXT_ATTACHMENT) {
				if(!($file = \DAO_Attachment::get($uri_parts['context_id']))) {
					$error = sprintf("Failed to load the attachment (`%s`)", $inputs['uri']);
					throw new Exception_DevblocksAutomationError($error);
				}
				
				// Do we have a manifest key?
				$filters = $inputs['filters'] ?? [];
				
				if(array_key_exists('extract', $inputs) && $inputs['extract']) {
					$error = null;
					if(!($results = $this->_getFileFromManifestKey($file, $inputs, $error)))
						throw new Exception_DevblocksAutomationError($error);
					
				} else {
					$fp = DevblocksPlatform::getTempFile();
					$stream_mime_type = $file->mime_type;
					
					$file->getFileContents($fp);
					
					$fp_filters = $this->_registerStreamFilters($fp, $filters, $stream_mime_type);
					
					$bytes = stream_get_contents($fp, $fp_max_size, $fp_offset);
					$bytes = $this->_lengthSplit($bytes, $length_split);
					$length = strlen($bytes);
					
					$this->_deregisterStreamFilters($fp_filters);
					
					$is_printable = DevblocksPlatform::services()->string()->isPrintable($bytes);
					
					if (!$is_printable)
						$bytes = sprintf('data:%s;base64,%s', $stream_mime_type, base64_encode($bytes));
					
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
				$error = "Only these URIs are supported: attachment, automation_resource, resource";
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
	
	private function _getFileFromManifestKey($file, array $inputs, &$error=null) {
		if(!extension_loaded('zip')) {
			$error = 'The `zip` extension is not loaded';
			return false;
		}
		
		$zip = new \ZipArchive();
		
		$fp = DevblocksPlatform::getTempFile();
		$fp_name = DevblocksPlatform::getTempFileInfo($fp);
		
		$extract = $inputs['extract'] ?? null;
		$password = $inputs['password'] ?? null;
		
		if(false === ($file->getFileContents($fp))) {
			$error = 'Failed to read file data';
			return false;
		}
		
		if(false === ($zip->open($fp_name))) {
			$error = 'The file is not a valid ZIP archive.';
			return false;	
		}
		
		if($password) {
			try {
				if (false === $zip->setPassword($password))
					throw new Exception_DevblocksAutomationError("Invalid ZIP archive");
			} catch (\Throwable $e) {
				$error = 'Failed to decode the ZIP archive.';
				return false;
			}
		}
		
		try {
			if(false === ($index = $zip->locateName($extract))) {
				$error = sprintf('Path (%s) not found in archive manifest.', $extract);
				return false;
			}
		} catch (\Throwable $e) {
			$error = 'Invalid ZIP file.';
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
	
	private function _lengthSplit($bytes, string $length_split) : string {
		if($length_split && false !== ($pos = strrpos($bytes, $length_split))) {
			$bytes = substr($bytes, 0, $pos+1);
		}
		return $bytes;
	}
	
	/**
	 * @throws Exception_DevblocksAutomationError
	 */
	private function _registerStreamFilters($fp, $filters, &$stream_mime_type=null) {
		$fp_filters = [];
		
		foreach($filters as $filter_key => $filter_data) {
			list($filter_type,) = array_pad(explode('/', $filter_key), 2, null);
			
			if('gzip.decompress' == $filter_type) {
				if(!extension_loaded('zlib')) {
					$error = "The `zlib` PHP extension is not enabled";
					throw new Exception_DevblocksAutomationError($error);
				}
				
				if(!($fp_filter = stream_filter_append($fp, 'zlib.inflate', STREAM_FILTER_READ, [
					'window' => 30,
				]))) {
					$error = 'Failed to add the zlib.inflate filter to file.read';
					throw new Exception_DevblocksAutomationError($error);
				}
				
				$fp_filters[] = $fp_filter;
				$stream_mime_type = 'application/octet-stream';
			}
		}
		
		return $fp_filters;
	}
	
	private function _deregisterStreamFilters(array $fp_filters) {
		foreach($fp_filters as $fp_filter)
			stream_filter_remove($fp_filter);
	}
}