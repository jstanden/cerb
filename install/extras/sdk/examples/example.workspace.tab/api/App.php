<?php
if(class_exists('Extension_WorkspaceTab')):
class ExWorkspaceTab extends Extension_WorkspaceTab {
	public function renderTab(Model_WorkspacePage $page, Model_WorkspaceTab $tab) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->display('devblocks:example.workspace.tab::tab.tpl');
	}

	function exportTabConfigJson(Model_WorkspacePage $page, Model_WorkspaceTab $tab) {
		$json = array(
			'tab' => array(
				'name' => $tab->name,
				'extension_id' => $tab->extension_id,
				'params' => $tab->params,
			),
		);
		
		return json_encode($json);
	}
	
	function importTabConfigJson($json, Model_WorkspaceTab $tab) {
		if(empty($tab) || empty($tab->id) || !is_array($json) || !isset($json['tab']))
			return false;
		
		return true;
	}
}
endif;