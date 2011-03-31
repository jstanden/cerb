<?php
class ExampleEventAction_ExampleAction extends Extension_DevblocksEventAction {
	const ID = 'exampleeventaction.action';
	
	function render(Extension_DevblocksEvent $event, $trigger, $params=array(), $seq=null) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('params', $params);

		if(!is_null($seq))
			$tpl->assign('namePrefix','action'.$seq);
			
		$tpl->assign('token_labels', $event->getLabels());
		
		$tpl->display('devblocks:example.event.action::config.tpl');
	}
	
	function run($token, $trigger, $params, &$values) {
		// [TODO] Do something with the $params and $values

		//$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		//$content = $tpl_builder->build($params['value'], $values);
		//var_dump($content);
	}
};