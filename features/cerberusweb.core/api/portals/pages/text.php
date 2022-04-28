<?php
class PortalPage_Text extends Extension_PortalPage {
	const ID = 'cerb.portal.page.text';
	
//	function invoke(array $page_meta, Model_CommunityTool $portal) {
//		return false;
//	}
	
	function render(array $page_meta, array $route_meta, Model_CommunityTool $portal) {
		$renderer = new Extension_PortalPageRenderer(function() use ($page_meta, $portal) {
			$tpl = DevblocksPlatform::services()->template();
			
			$content_html = Portal_Builder::parseMarkdown($page_meta['content'] ?? '');
			$content_params = $widget_meta['content_params'] ?? [];
			
			// [TODO] Placeholders
			// [TODO] content_params
			
			$tpl->assign('content_html', $content_html);
			$tpl->display('devblocks:cerberusweb.core::portals/builder/pages/text.tpl');
		});
		
		$layout = $route_meta['layout'] ?? null;
		
		if('bare' == $layout) {
			parent::renderBareLayout($page_meta, $portal, $renderer);
		} else {
			parent::renderDefaultLayout($page_meta, $portal, $renderer);
		}
	}
}