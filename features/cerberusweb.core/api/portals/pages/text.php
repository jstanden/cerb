<?php
class PortalPage_Text extends Extension_PortalPage {
	const ID = 'cerb.portal.page.text';
	
	public function renderConfig(Model_PortalPage $model) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('model', $model);
		$tpl->display('devblocks:cerberusweb.core::portals/builder/pages/text/config.tpl');
	}
	
	public function saveConfig(array $fields, $id, &$error=null) {
		if(!array_key_exists(DAO_PortalPage::PARAMS_JSON, $fields)) {
			$error = 'Portal page parameters are required.';
			return false;
		}
		
		if(false === (json_decode($fields[DAO_PortalPage::PARAMS_JSON], true))) {
			$error = 'Unable to read portal parameters.';
			return false;
		}
		
		return true;
	}
	
	function render(Model_PortalPage $page, Model_CommunityTool $portal, DevblocksHttpResponse $response) {
		$renderer = new Extension_PortalPageRenderer(function() use ($page, $portal, $response) {
			$tpl = DevblocksPlatform::services()->template();
			$content_html = DevblocksPlatform::parseMarkdown($page->params['content']);
			$tpl->assign('content_html', $content_html);
			$tpl->display('devblocks:cerberusweb.core::portals/builder/pages/text.tpl');
		});
		
		parent::renderDefaultLayout($page, $portal, $renderer);
	}
}