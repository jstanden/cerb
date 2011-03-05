<?php
class ExamplePageMenuItem_SetupPluginsExample extends Extension_PageMenuItem {
	const POINT = 'examplepagemenuitem.setup.plugins.example_item';
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('extension', $this);
		$tpl->display('devblocks:example.page.menu.item::menu_item.tpl');
	}
};
