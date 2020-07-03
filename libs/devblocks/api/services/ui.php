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
	 * @return DevblocksUiToolbar
	 */
	public function toolbar() {
		return new DevblocksUiToolbar();
	}
}

class DevblocksUiToolbar {
	function parse(?string $kata, DevblocksDictionaryDelegate $dict) {
		$error = null;
		
		if(!is_string($kata))
			return [];
		
		if(false == ($kata_tree = DevblocksPlatform::services()->kata()->parse($kata, $error))) {
			return false;
		}
		
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