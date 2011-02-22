<?php
if (class_exists('Extension_DatacenterTab')):
class ExDatacenterTab extends Extension_DatacenterTab {
	function showTab() {
		$tpl = DevblocksPlatform::getTemplateService();		
		$tpl->display('devblocks:example.datacenter.tab::index.tpl');		
	}
}
endif;