<?php
class PortalWidget_Text extends Extension_PortalWidget {
	const ID = 'cerb.portal.widget.text';
	
	public function renderConfig(Model_PortalWidget $model) {
		$tpl = DevblocksPlatform::services()->template();
		
		$tpl->assign('model', $model);
		$tpl->display('devblocks:cerberusweb.core::portals/builder/widgets/text/config.tpl');
	}
	
	public function saveConfig(array $fields, $id, &$error=null) { 
		return true;
	}

	public function render(Model_PortalWidget $widget, DevblocksDictionaryDelegate $dict) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();

		$template = $widget->params['content'];
		
		if(false != ($markdown = $tpl_builder->build($template, $dict))) {
			if(false != ($content = DevblocksPlatform::parseMarkdown($markdown))) {
				$tpl->assign('content_html', $content);
			}
		}
		
		$tpl->assign('widget', $widget);
		$tpl->display('devblocks:cerberusweb.core::portals/builder/widgets/text.tpl');
	}
}