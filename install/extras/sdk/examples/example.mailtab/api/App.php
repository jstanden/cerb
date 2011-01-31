<?php
if (class_exists('Extension_PreferenceTab')):
class ExMailTab extends Extension_MailTab {
	function showTab() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->display('devblocks:example.mailtab::index.tpl');		
	}
}
endif;