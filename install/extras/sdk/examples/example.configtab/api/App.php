<?php
class ExConfigTab extends Extension_ConfigTab {
	function showTab() {
		$tpl = DevblocksPlatform::getTemplateService();
		
		$params = $this->getParams();
		$tpl->assign('params', $params);
		
		$tpl->display('devblocks:example.configtab::index.tpl');		
	}
	
	function saveTab() {
		@$example_setting = DevblocksPlatform::importGPC($_POST['example_setting'],'string','');
		
		//$this->setParam('example_setting', $example_setting);
	}
}