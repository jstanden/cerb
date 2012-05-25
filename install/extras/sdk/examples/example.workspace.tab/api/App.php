<?php
if(class_exists('Extension_WorkspaceTab')):
class ExWorkspaceTab extends Extension_WorkspaceTab {
	public function renderTab(Model_WorkspacePage $page, Model_WorkspaceTab $tab) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->display('devblocks:example.workspace.tab::tab.tpl');
	}
}
endif;