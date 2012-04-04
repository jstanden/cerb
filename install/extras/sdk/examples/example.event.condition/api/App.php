<?php
class ExampleEventCondition_ExampleCondition extends Extension_DevblocksEventCondition {
	const ID = 'exampleeventcondition.condition';
	
	function render(Extension_DevblocksEvent $event, Model_TriggerEvent $trigger, $params=array(), $seq=null) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('params', $params);
		
		if(!is_null($seq))
			$tpl->assign('namePrefix','condition'.$seq);
			
		$tpl->display('devblocks:example.event.condition::config.tpl');
	}
	
	function run($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		//var_dump($params['oper']);
		//var_dump($params['value']);
		// [TODO] Run a condition and return a boolean
		return true;
	}
};