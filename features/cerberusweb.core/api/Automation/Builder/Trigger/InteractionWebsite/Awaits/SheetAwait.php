<?php
namespace Cerb\Automation\Builder\Trigger\InteractionWebsite\Awaits;

use _DevblocksValidationService;
use AutomationTrigger_UiSheetData;
use DAO_Automation;
use DAO_AutomationContinuation;
use DevblocksPlatform;
use Model_AutomationContinuation;

class SheetAwait extends AbstractAwait {
	function invoke(string $prompt_key, string $action, Model_AutomationContinuation $continuation) {
		switch($action) {
			case 'refresh':
				return $this->_promptAction_refresh($prompt_key, $continuation);
		}
		return false;
	}
	
	function validate(_DevblocksValidationService $validation) {
		$prompt_label = $this->_data['label'] ?? null;
		
		$field = $validation->addField($this->_key, $prompt_label);
		$field_type = $field->stringOrArray();
		
		if(array_key_exists('required', $this->_data) && $this->_data['required'])
			$field_type->setRequired(true);
	}
	
	function formatValue() {
		return $this->_value;
	}
	
	private function _render(string $prompt_key, Model_AutomationContinuation $continuation) {
		$sheets = DevblocksPlatform::services()->sheet();
		$tpl = DevblocksPlatform::services()->template();
		$session = \ChPortalHelper::getSession();
		
		$error = null;
		
		@$prompt = $continuation->state_data['dict']['__return']['form']['elements'][$prompt_key];
		
		if(is_null($prompt))
			return;
		
		$label = $prompt['label'] ?? '';
		$sheet_data = $prompt['data'] ?? [];
		$sheet_schema = $prompt['schema'] ?? [];
		$sheet_page = $prompt['page'] ?? 0;
		$sheet_filter = $prompt['filter'] ?? null;
		$sheet_limit = $prompt['limit'] ?? 10;
		
		@$default = $continuation->state_data['dict'][$this->_key] ?? $prompt['default'];
		
		$sheet_paging = [];
		
		// If an assoc/object and not indexed
		if(is_array($sheet_data) && !DevblocksPlatform::arrayIsIndexed($sheet_data) && array_key_exists('automation', $sheet_data)) {
			$automation_uri = $sheet_data['automation']['uri'] ?? null;
			
			if(!is_null($automation_uri) && false != ($callback = DAO_Automation::getByUri($automation_uri))) {
				if($callback->extension_id != AutomationTrigger_UiSheetData::ID)
					return;
				
				$automator = DevblocksPlatform::services()->automation();
				
				$callback_inputs = $sheet_data['automation']['inputs'] ?? [];
				
				$callback_init = [
					'inputs' => $callback_inputs,
					'sheet_limit' => $sheet_limit,
					'sheet_page' => $sheet_page,
					'sheet_filter' => $sheet_filter,
				];
				
				$callback_results = $automator->executeScript($callback, $callback_init, $error);
				
				if(false === $callback_results) {
					$sheet_data = [];
					
				} else {
					$callback_return = $callback_results->getKeyPath('__return');
					
					$sheet_data = $callback_return['data'] ?? [];
					$total = $callback_return['total'] ?? count($sheet_data);
					$sheet_paging = $sheets->getPaging(count($sheet_data), $sheet_page, $sheet_limit, $total);
				}
			}
			
		} else if (is_array($sheet_data)) {
			if($sheet_filter) {
				$sheet_data_is_indexed = DevblocksPlatform::arrayIsIndexed($sheet_data);
				
				$sheet_data = array_filter($sheet_data, function($row, $key) use ($sheet_filter, $sheet_data_is_indexed) {
					$text = implode(' ', $row);
					
					if(!$sheet_data_is_indexed)
						$text = $key . ' ' . $text;
					
					return (bool) stristr($text, $sheet_filter);
				}, ARRAY_FILTER_USE_BOTH);
			}
			
			$total = count($sheet_data);
			$sheet_offset = $sheet_page * $sheet_limit;
			
			if($sheet_limit)
				$sheet_data = array_slice($sheet_data, $sheet_offset, $sheet_limit, true);
			
			$sheet_paging = $sheets->getPaging(count($sheet_data), $sheet_page, $sheet_limit, $total);
			
			// If the values are empty, synthesize a key
			foreach($sheet_data as $k => $v) {
				if(is_array($v)) {
					if(empty($v)) {
						$sheet_data[$k] = ['key' => $k];
					}
					
					$sheet_data[$k]['__index'] = $k;
				}
			}
		}
		
		if(!is_array($sheet_data))
			$sheet_data = [];
		
		$tpl->assign('var', $this->_key);
		$tpl->assign('default', $default);
		$tpl->assign('label', $label);
		
		$sheets->addType('date', $sheets->types()->date(false));
		$sheets->addType('link', $sheets->types()->link(false));
		$sheets->addType('selection', $sheets->types()->selection());
		$sheets->addType('slider', $sheets->types()->slider(false));
		$sheets->addType('text', $sheets->types()->text(false));
		$sheets->addType('time_elapsed', $sheets->types()->timeElapsed(false));
		$sheets->setDefaultType('text');
		
		$tpl->assign('session', $session);
		
		$layout = $sheets->getLayout($sheet_schema);
		$tpl->assign('layout', $layout);
		
		$rows = $sheets->getRows($sheet_schema, $sheet_data);
		$tpl->assign('rows', $rows);
		
		$columns = $sheets->getColumns($sheet_schema);
		$tpl->assign('columns', $columns);
		
		$tpl->assign('filter', $sheet_filter);
		
		if($layout['paging'] ?? false)
			$tpl->assign('paging', $sheet_paging);
	}
	
	function render(Model_AutomationContinuation $continuation) {
		$tpl = DevblocksPlatform::services()->template();
		
		$prompt_key = rtrim('sheet/' . $this->_key, '/');
		
		$form = $continuation->state_data['dict']['__return']['form']['elements'] ?? [];
		
		if(!array_key_exists($prompt_key, $form))
			return;
		
		@$prompt = $form[$prompt_key];
		
		if(is_null($prompt))
			return;
		
		$tpl->assign('continuation_token', $continuation->token);
		
		$this->_render($prompt_key, $continuation);
		
		$tpl->display('devblocks:cerberusweb.core::automations/triggers/interaction.website/await/sheet.tpl');
	}
	
	private function _promptAction_refresh(string $prompt_key, Model_AutomationContinuation $continuation) {
		$tpl = DevblocksPlatform::services()->template();
		
		@$page = DevblocksPlatform::importGPC($_POST['page']);
		@$filter = DevblocksPlatform::importGPC($_POST['filter']);
		
		$is_dirty = false;
		
		$prompt =& $continuation->state_data['dict']['__return']['form']['elements'][$prompt_key];
		
		if(is_null($prompt))
			return;
		
		$layout_style = $prompt['schema']['layout']['style'] ?? 'table';
		
		if(!is_null($page) && is_numeric($page)) {
			$prompt['page'] = intval($page);
			$is_dirty = true;
		}
		
		if(!is_null($filter) && is_string($filter)) {
			$prompt['filter'] = $filter;
			$is_dirty = true;
		}
		
		$this->_render($prompt_key, $continuation);
		
		$tpl->assign('layout_style', $layout_style);
		
		$tpl->assign('sheet_selection_key', uniqid('selection_'));
		
		$tpl->display('devblocks:cerberusweb.core::automations/triggers/interaction.website/await/sheet/render.tpl');
		
		if($is_dirty) {
			DAO_AutomationContinuation::update($continuation->token, [
				DAO_AutomationContinuation::STATE_DATA => json_encode($continuation->state_data)
			]);
		}
	}
}