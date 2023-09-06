<?php
namespace Cerb\Automation\Builder\Trigger\InteractionWorker\Awaits;

use _DevblocksValidationService;
use DevblocksPlatform;
use Exception_DevblocksValidationError;
use Model_AutomationContinuation;

class FileUploadAwait extends AbstractAwait {
	function invoke(string $prompt_key, string $action, Model_AutomationContinuation $continuation) {
		return match ($action) {
			'uploadFile' => $this->_promptAction_uploadFile($continuation),
			default => false,
		};
	}

	function validate(_DevblocksValidationService $validation) {
		$prompt_label = $this->_data['label'] ?? null;
		$as = $this->_data['as'] ?? null;
		
		$is_required = array_key_exists('required', $this->_data) && $this->_data['required'];
		
		$input_field = $validation->addField($this->_key, $prompt_label);
		
		// automation resource or attachment
		if('automation_resource' == $as) {
			$input_field_type = $input_field->string()
				->setMinLength(0)
				->setMaxLength(64)
			;
			
		} else {
			$input_field_type = $input_field->id()
				->addValidator($validation->validators()->contextId(\CerberusContexts::CONTEXT_ATTACHMENT, !$is_required))
			;
		}
		
		if($is_required)
			$input_field_type->setRequired(true);
	}
	
	function formatValue() {
		return $this->_value;
	}
	
	function setValue($key, $value, $dict) {
		$as = $this->_data['as'] ?? null;
		
		// Automation resource vs attachment
		if('automation_resource' == $as) {
			if (is_array($dict)) {
				$dict[$key] = $value;
			} else {
				$dict->set($key, $value);
			}
		
		} else {
			if (DevblocksPlatform::strEndsWith($key, '_id')) {
				$id_key_prefix = substr($key, 0, -3);
			} else {
				$id_key_prefix = $key;
			}
			
			if (is_array($dict)) {
				$dict[$key] = $value;
				$dict[$id_key_prefix . '__context'] = \CerberusContexts::CONTEXT_ATTACHMENT;
				$dict[$id_key_prefix . '_id'] = $value;
				
			} elseif ($dict instanceof \DevblocksDictionaryDelegate) {
				$dict->set($key, $value);
				$dict->set($id_key_prefix . '__context', \CerberusContexts::CONTEXT_ATTACHMENT);
				$dict->set($id_key_prefix . '_id', $value);
			}
		}
		
		return $dict;
	}
	
	function render(Model_AutomationContinuation $continuation) {
		$tpl = DevblocksPlatform::services()->template();
		
		$label = $this->_data['label'] ?? null;
		$placeholder = $this->_data['placeholder'] ?? null;
		$default = $this->_data['default'] ?? null;
		$as = $this->_data['as'] ?? null;
		$is_required = array_key_exists('required', $this->_data) && $this->_data['required'];
	
		$tpl->assign('label', $label);
		$tpl->assign('placeholder', $placeholder);
		$tpl->assign('default', $default);
		$tpl->assign('var', $this->_key);
		$tpl->assign('value', $this->_value);
		$tpl->assign('is_required', $is_required);
		
		if('automation_resource' == $as) {
			$tpl->assign('continuation_token', $continuation->token);
			$tpl->display('devblocks:cerberusweb.core::automations/triggers/interaction.worker/await/file_upload/automation_resource.tpl');
		} else {
			$tpl->display('devblocks:cerberusweb.core::automations/triggers/interaction.worker/await/file_upload/attachment.tpl');
		}
	}
	
	private function _promptAction_uploadFile(Model_AutomationContinuation $continuation) : bool {
		$file = DevblocksPlatform::importGPC($_FILES['file'] ?? null, 'array', []);
		
		header('Content-Type: application/json; charset=utf-8');
		
		$error_codes = [
			1 => 'The uploaded file is too large',
			2 => 'The uploaded file is too large',
			3 => 'The uploaded file was only partially uploaded',
			4 => 'No file was uploaded',
			6 => 'Missing a temporary folder',
			7 => 'Failed to write file to disk',
			8 => 'An extension stopped the file upload',
		];
		
		try {
			$file_name = $file['name'] ?? null;
			$file_type = $file['type'] ?? null;
			$file_size = $file['size'] ?? null;
			$file_tmp_name = $file['tmp_name'] ?? null;
			$file_error = $file['error'] ?? null;
			
			// Check the error
			if($file_error)
				throw new Exception_DevblocksValidationError($error_codes[$file_error] ?? 'An unexpected error occurred');
			
			$expires_at = $inputs['expires'] ?? (time() + 3600);
			$resource_token = DevblocksPlatform::services()->string()->uuid();
			
			$resource_id = \DAO_AutomationResource::create([
				\DAO_AutomationResource::NAME => $file_name,
				\DAO_AutomationResource::MIME_TYPE => $file_type,
				\DAO_AutomationResource::TOKEN => $resource_token,
				\DAO_AutomationResource::EXPIRES_AT => $expires_at,
			]);
			
			if(!$file_tmp_name || !($fp = fopen($file_tmp_name, 'r+b')))
				throw new Exception_DevblocksValidationError('Failed to read uploaded file.');
			
			$results = [
				'uri' => 'cerb:automation_resource:' . $resource_token,
				'token' => $resource_token,
				'name' => $file_name,
				'mime_type' => $file_type,
				'expires_at' => $expires_at,
				'size' => $file_size,
			];
			
			\Storage_AutomationResource::put($resource_id, $fp);
			
			fclose($fp);
			
			echo DevblocksPlatform::strFormatJson($results);
			
			return true;
			
		} catch(Exception_DevblocksValidationError $e) {
			echo DevblocksPlatform::strFormatJson([
				'error' => $e->getMessage(),
			]);
			
			return false;
			
		} catch(\Exception $e) {
			echo DevblocksPlatform::strFormatJson([
				'error' => 'An unexpected error occurred.',
			]);
			
			DevblocksPlatform::logException($e);
			return false;
		}
	}
}