<?php
abstract class Extension_DevblocksEventAction extends DevblocksExtension {
	const POINT = 'devblocks.event.action';
	
	/**
	 * @internal
	 */
	public static function getAll($as_instances=false, $for_event=null) {
		$extensions = DevblocksPlatform::getExtensions('devblocks.event.action', false);
		$results = [];
		
		foreach($extensions as $ext_id => $ext) {
			// If the action doesn't specify event filters, add to everything
			if(!isset($ext->params['events'][0])) {
				$results[$ext_id] = $as_instances ? $ext->createInstance() : $ext;
				
			} else {
				// Loop through the patterns
				foreach(array_keys($ext->params['events'][0]) as $evt_pattern) {
					$evt_pattern = DevblocksPlatform::strToRegExp($evt_pattern);
					
					if(preg_match($evt_pattern, $for_event))
						$results[$ext_id] = $as_instances ? $ext->createInstance() : $ext;
				}
			}
		}
		
		if($as_instances)
			DevblocksPlatform::sortObjects($results, 'manifest->params->[label]');
		else
			DevblocksPlatform::sortObjects($results, 'params->[label]');
		
		return $results;
	}
	
	/**
	 * Return information about the action (params, notes, etc)
	 */
	static function getMeta() { return []; }
	
	/**
	 * Render the behavior action's configuration template.
	 */
	abstract function render(Extension_DevblocksEvent $event, Model_TriggerEvent $trigger, $params=[], $seq=null);
	
	/**
	 * Simulate the behavior action.
	 */
	function simulate($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {}
	
	/**
	 * Run the behavior action.
	 */
	abstract function run($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict);
}