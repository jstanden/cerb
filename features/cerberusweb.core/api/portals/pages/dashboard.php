<?php
class PortalPage_Dashboard extends Extension_PortalPage {
	const ID = 'cerb.portal.page.dashboard';
	
	public function renderConfig(Model_PortalPage $model) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('model', $model);
		$tpl->display('devblocks:cerberusweb.core::portals/builder/pages/dashboard/config.tpl');
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
	
	function invoke(Model_PortalPage $page, Model_CommunityTool $portal, DevblocksHttpResponse $response) {
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		@$invoke = DevblocksPlatform::importGPC($_POST['invoke'], 'string', null);
		
		switch($invoke) {
			case 'widgetRefresh':
				return $this->_portalAction_widgetRefresh($page, $portal);
		}
		
		return false;
	}
	
	private function _portalAction_widgetRefresh(Model_PortalPage $page, Model_CommunityTool $portal) {
		$identity = ChPortalHelper::getIdentity();

		@$widget_uri = DevblocksPlatform::importGPC($_POST['widget'], 'string', null);
		
		// Dictionary
		
		$values = [
			'identity__context' => CerberusContexts::CONTEXT_IDENTITY,
			'identity_id' => $identity ? $identity->id : 0,
			
			'portal__context' => CerberusContexts::CONTEXT_PORTAL,
			'portal_id' => intval($portal->id),
			
			'page__context' => CerberusContexts::CONTEXT_PORTAL_PAGE,
			'page_id' => intval($page->id),
		];
		
		$dict = DevblocksDictionaryDelegate::instance($values);
		
		header('Content-Type: text/html; charset=utf-8');
		
		if(false == ($widget = DAO_PortalWidget::getByPortalAndUri($portal->id, $widget_uri)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		$widget->render($dict);
	}
	
	function render(Model_PortalPage $page, Model_CommunityTool $portal, DevblocksHttpResponse $response) {
		$identity = ChPortalHelper::getIdentity();
		
		// Dictionary
		
		$values = [
			'identity__context' => CerberusContexts::CONTEXT_IDENTITY,
			'identity_id' => $identity ? $identity->id : 0,
			
			'portal__context' => CerberusContexts::CONTEXT_PORTAL,
			'portal_id' => intval($portal->id),
			
			'page__context' => CerberusContexts::CONTEXT_PORTAL_PAGE,
			'page_id' => intval($page->id),
		];
		
		$dict = DevblocksDictionaryDelegate::instance($values);
		
		$renderer = new Extension_PortalPageRenderer(function() use ($page, $portal, $response, $dict) {
			$tpl = DevblocksPlatform::services()->template();
			$kata = DevblocksPlatform::services()->kata();
			
			$tpl->assign('dict', $dict);
			$error = null;
			
			// Widgets
			
			$layout_kata = $page->params['layout_kata'];
			
			if(false == ($layout = $kata->parse($layout_kata, $error)))
				return;
			
			$layout = $kata->formatTree($layout, $dict);
			
			if(!array_key_exists('zones', $layout))
				return;
			
			$zones = $layout['zones'];
			
			if(!is_array($zones))
				$zones = [];
			
			foreach($zones as $zone_key => $zone_widgets) {
				foreach($zone_widgets as $widget_key => $widget_meta) {
					list($widget_uri,) = explode('/', $widget_key);
					
					@$width = intval($zones[$zone_key]['widgets'][$widget_key]['width']);
					
					if(!in_array($width, [25,50,75,100]))
						$width = 100;
					
					$zones[$zone_key][$widget_key]['width'] = $width;
					$zones[$zone_key][$widget_key]['uri'] = $widget_uri;
				}
			}
			
			$tpl->assign('layout', array_keys($zones));
			$tpl->assign('zones', $zones);
			$tpl->assign('model', $page);
			
			$tpl->assign('page_query', $page->getUrlQuery());
			
			// Template
			
			$tpl->display('devblocks:cerberusweb.core::portals/builder/pages/dashboard.tpl');
		});
		
		parent::renderDefaultLayout($page, $portal, $renderer);
	}
}