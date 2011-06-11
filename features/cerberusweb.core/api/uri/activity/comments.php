<?php
if (class_exists('Extension_ActivityTab')):
class ActivityTab_Comments extends Extension_ActivityTab {
	function showTab() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->display('devblocks:cerberusweb.core::activity/comments/index.tpl');		
	}
}
endif;