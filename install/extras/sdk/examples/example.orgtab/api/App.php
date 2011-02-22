<?php
if (class_exists('Extension_OrgTab')):
class ExOrgTab extends Extension_OrgTab {
	function showTab() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->display('devblocks:example.orgtab::index.tpl');		
	}
}
endif;