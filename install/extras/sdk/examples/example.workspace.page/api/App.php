<?php
if(class_exists('Extension_WorkspacePage')):
class ExWorkspacePage extends Extension_WorkspacePage {
	function renderPage(Model_WorkspacePage $page) {
		$tpl = DevblocksPlatform::services()->template();
		
		$tpl->assign('page', $page);
		
		$tpl->display('devblocks:example.workspace.page::page.tpl');
	}
};
endif;