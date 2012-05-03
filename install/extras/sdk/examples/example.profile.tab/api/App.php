<?php
if (class_exists('Extension_ContextProfileTab')):
class ExContextProfileTab extends Extension_ContextProfileTab {
	function showTab($context, $context_id) {
		$tpl = DevblocksPlatform::getTemplateService();
		
		$tpl->assign('context', $context);
		$tpl->assign('context_id', $context_id);
		
		$tpl->display('devblocks:example.profile.tab::tab.tpl');
	}
}
endif;