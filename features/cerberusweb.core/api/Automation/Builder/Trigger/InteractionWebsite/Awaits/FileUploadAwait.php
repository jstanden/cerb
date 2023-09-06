<?php
namespace Cerb\Automation\Builder\Trigger\InteractionWebsite\Awaits;

use _DevblocksValidationService;
use DevblocksPlatform;
use Exception_DevblocksValidationError;
use Model_AutomationContinuation;

class FileUploadAwait extends AbstractAwait {
	function invoke(string $prompt_key, string $action, Model_AutomationContinuation $continuation) : bool {
		if($action == 'uploadFile') {
			return $this->_promptAction_uploadFile($continuation);
		}
		
		return false;
	}

	function validate(_DevblocksValidationService $validation) {
		$prompt_label = $this->_data['label'] ?? null;
		
		$is_required = array_key_exists('required', $this->_data) && $this->_data['required'];
		
		$input_field = $validation->addField($this->_key, $prompt_label);
		
		$input_field_type = $input_field->stringOrArray();
		
		if($is_required)
			$input_field_type->setRequired(true);
	}
	
	function formatValue() {
		return $this->_value;
	}
	
	function setValue($key, $value, $dict) {
		if(is_array($value)) {
			return $this->_setValues($key, $value, $dict);
		} else {
			return $this->_setValue($key, $value, $dict);
		}
	}
	
	private function _setValue(string $key, string $value, $dict) {
		if(!($automation_resource = \DAO_AutomationResource::getByToken($value)))
			return $dict;
		
		$resource_dict = \DevblocksDictionaryDelegate::getDictionaryFromModel($automation_resource, \CerberusContexts::CONTEXT_AUTOMATION_RESOURCE);
		
		if(is_array($dict)) {
			$dict[$key] = $value;
			
			foreach($resource_dict as $kk => $vv)
				$dict[$key . '_' . $kk] = $vv;
			
		} elseif ($dict instanceof \DevblocksDictionaryDelegate) {
			$dict->set($key, $value);
			$dict->mergeKeys($key . '_', $resource_dict);
		}
		
		return $dict;
	}
	
	private function _setValues(string $key, array $value, $dict) {
		if(!($automation_resources = \DAO_AutomationResource::getByTokens($value)))
			return $dict;
		
		if(!($resource_dicts = \DevblocksDictionaryDelegate::getDictionariesFromModels($automation_resources, \CerberusContexts::CONTEXT_AUTOMATION_RESOURCE)))
			return $dict;
		
		$resource_dicts = array_combine(
			array_column($resource_dicts, 'token'),
			$resource_dicts
		);
		
		if(is_array($dict)) {
			$dict[$key] = $value;
			$dict[$key . '__records'] = $resource_dicts;
			
		} elseif ($dict instanceof \DevblocksDictionaryDelegate) {
			$dict->set($key, $value);
			$dict->set($key . '__records', $resource_dicts);
		}
		
		return $dict;
	}
	
	function render(Model_AutomationContinuation $continuation) {
		$tpl = DevblocksPlatform::services()->templateSandbox();
		$session = \ChPortalHelper::getSession();
		
		$label = $this->_data['label'] ?? null;
		$is_required = array_key_exists('required', $this->_data) && $this->_data['required'];
		$is_multiple = array_key_exists('multiple', $this->_data) && $this->_data['multiple'];
		$accept = $this->_data['accept'] ?? null;
		
		$tpl->assign('continuation_token', $continuation->token);
		$tpl->assign('session', $session);
		$tpl->assign('label', $label);
		$tpl->assign('var', $this->_key);
		$tpl->assign('is_required', $is_required);
		$tpl->assign('is_multiple', $is_multiple);
		$tpl->assign('accept', $accept);
		$tpl->display('devblocks:cerberusweb.core::automations/triggers/interaction.website/await/file_upload.tpl');
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
			
			$expires_at = $inputs['expires'] ?? (time() + 900);
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
			
			DevblocksPlatform::logError($e->getMessage());
			
			return false;
		}
	}
}