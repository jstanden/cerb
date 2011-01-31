<?php
if (class_exists('Extension_ActivityTab')):
class ExActivityTab extends Extension_ActivityTab {
	function showTab() {
		$translate = DevblocksPlatform::getTranslationService();
		$tpl = DevblocksPlatform::getTemplateService();
		
		$tpl->display('devblocks:example.activitytab::index.tpl');		
	}
}
endif;