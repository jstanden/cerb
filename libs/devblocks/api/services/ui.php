<?php
class _DevblocksUiManager {
	private static $instance = null;
		
		private function __construct() {}
	
	/**
	 * @return _DevblocksUiManager
	 */
	public static function getInstance() {
		if(null == self::$instance) {
			self::$instance = new _DevblocksUiManager();
		}
		return self::$instance;
	}
	
	/**
	 * @return DevblocksUiEventHandler
	 */
	public function eventHandler() {
		return new DevblocksUiEventHandler();
	}
	
	/**
	 * @return DevblocksUiToolbar
	 */
	public function toolbar() {
		return new DevblocksUiToolbar();
	}
}

class DevblocksUiEventHandler {
	function parse(?string $handlers_kata, DevblocksDictionaryDelegate $dict, &$error=null) {
		if(is_null($handlers_kata))
			return [];
		
		$kata = DevblocksPlatform::services()->kata();
		
		$results = [];
		
		if(false == ($handlers = $kata->parse($handlers_kata, $error)))
			return [];
		
		$handlers = $kata->formatTree($handlers, $dict);
		
		foreach($handlers as $handler_key => $handler_data) {
			if(array_key_exists('disabled', $handler_data) && $handler_data['disabled'])
				continue;
			
			list($handler_type, $handler_name) = explode('/', $handler_key, 2);
			
			$result = [
				'type' => $handler_type,
				'key' => $handler_name,
				'data' => $handler_data,
			];
			
			$results[$handler_name] = $result;
		}
		
		return $results;
	}
	
	function handleOnce(string $trigger, array $handlers, array $initial_state, &$error=null, callable $behavior_callback=null) {
		$automator = DevblocksPlatform::services()->automation();
		
		foreach($handlers as $handler) {
			if('automation' == @$handler['type']) {
				if(false == ($automation = DAO_Automation::getByNameAndTrigger(@$handler['data']['name'], $trigger)))
					continue;
				
				if(array_key_exists('inputs', @$handler['data']))
					$initial_state['inputs'] = $handler['data']['inputs'];
				
				if(false == ($automation_results = $automator->executeScript($automation, $initial_state, $error)))
					return null;
				
				return $automation_results;
				
			// @deprecated
			} elseif('behavior' == @$handler['type']) {
				if(is_callable($behavior_callback)) {
					if(null != ($behavior = DAO_TriggerEvent::get($handler['data']['id'])))
						return $behavior_callback($behavior, $handler);
					
					return false;
				}
			}
		}
		
		return null;
	}
	
	function handleEach(string $trigger, array $handlers, $initial_state, &$error=null, callable $behavior_callback=null) {
		$results = [];
		
		// [TODO] Preload automations?
		// [TODO] Can these forward propagate to communicate with each other? (e.g. mail rules)
		
		foreach($handlers as $handler_key => $handler) {
			$result = $this->handleOnce($trigger, [$handler], $initial_state, $error, $behavior_callback);
			
			if(is_null($result))
				continue;
			
			$results[$handler_key] = $result;
		}
		
		return $results;
	}
}

class DevblocksUiToolbar {
	function parse($kata, DevblocksDictionaryDelegate $dict) {
		$error = null;
		$kata_tree = null;
		
		if(is_array($kata)) {
			$kata_tree = $kata;
			unset($kata);
			
		} elseif (is_string($kata)) {
			if(false == ($kata_tree = DevblocksPlatform::services()->kata()->parse($kata, $error))) {
				return false;
			}
		}
		
		if(!is_array($kata_tree))
			return [];
		
		$kata_tree = DevblocksPlatform::services()->kata()->formatTree($kata_tree, $dict);
		
		if(!is_array($kata_tree))
			return [];
		
		$results = [];
		
		foreach($kata_tree as $toolbar_item_key => $toolbar_item) {
			if(!is_array($toolbar_item))
				continue;
			
			@list($type, $key) = explode('/', $toolbar_item_key);
			
			if(!$key)
				continue;
			
			$result = [
				'key' => $key,
				'type' => $type,
				'schema' => $toolbar_item,
			];
			
			$results[$key] = $result;
		}
		
		return $results;
	}
	
	function render(array $toolbar) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('toolbar', $toolbar);
		$tpl->display('devblocks:devblocks.core::ui/toolbar/render.tpl');
	}
}