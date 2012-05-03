<?php
if (class_exists('Extension_ContextProfileScript')):
class ExContextProfileScript extends Extension_ContextProfileScript {
	function renderScript($context, $context_id) {
		$tpl = DevblocksPlatform::getTemplateService();
		
		$tpl->assign('context', $context);
		$tpl->assign('context_id', $context_id);
		
		$tpl->display('devblocks:example.profile.script::script.js.tpl');
	}
}
endif;