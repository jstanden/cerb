<?php
class ExamplePageMenu_SetupCustomMenu extends Extension_PageMenu {
	const POINT = 'examplepagemenu.setup.menu.custom';
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('extension', $this);
		$tpl->display('devblocks:example.page.menu::menu.tpl');
	}
};

class ExamplePageMenu_SetupCustomItem extends Extension_PageMenuItem {
	const POINT = 'examplepagemenu.setup.menu.custom.item';
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('extension', $this);
		$tpl->display('devblocks:example.page.menu::menu_item.tpl');
	}
};