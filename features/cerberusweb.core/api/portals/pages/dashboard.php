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
	
	function render(Model_PortalPage $page, Model_CommunityTool $portal, DevblocksHttpResponse $response) {
		$identity = ChPortalHelper::getIdentity();
		
		$path = $response->path;
		
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
		
		if('POST' == DevblocksPlatform::getHttpMethod()) {
			$action = array_shift($path);
			
			if($action) {
				switch($action) {
					case 'updateWidget':
						header('Content-Type: text/html; charset=utf-8');
						
						@$widget_id = DevblocksPlatform::importGPC($_REQUEST['widget'], 'integer', 0);
						
						if(false == ($widget = DAO_PortalWidget::get($widget_id)))
							exit;
						
						if($widget->portal_page_id != $page->id)
							exit;
						
						$widget->render($dict);
						break;
				}
				
				exit;
			}
		}
		
		$renderer = new Extension_PortalPageRenderer(function() use ($page, $portal, $response, $dict) {
			$tpl = DevblocksPlatform::services()->template();
			
			$tpl->assign('dict', $dict);
			
			// Widgets
			
			$widgets = DAO_PortalWidget::getByPortalPageId($page->id);
			
			// Layouts
			
			@$layout = $page->params['layout'] ?: '';
			
			$zones = [
				'content' => [],
			];
			
			switch($layout) {
				case 'sidebar_left':
					$zones = [
						'sidebar' => [],
						'content' => [],
					];
					break;
					
				case 'sidebar_right':
					$zones = [
						'content' => [],
						'sidebar' => [],
					];
					break;
					
				case 'thirds':
					$zones = [
						'left' => [],
						'center' => [],
						'right' => [],
					];
					break;
			}
	
			// Sanitize zones
			foreach($widgets as $widget_id => $widget) {
				if(array_key_exists($widget->zone, $zones)) {
					$zones[$widget->zone][$widget_id] = $widget;
					continue;
				}
				
				// If the zone doesn't exist, drop the widget into the first zone
				$zones[key($zones)][$widget_id] = $widget;
			}
			
			$tpl->assign('layout', $layout);
			$tpl->assign('zones', $zones);
			$tpl->assign('model', $page);
			
			$tpl->assign('page_query', $page->getUrlQuery());
			
			// Template
			
			$tpl->display('devblocks:cerberusweb.core::portals/builder/pages/dashboard.tpl');
		});
		
		parent::renderDefaultLayout($page, $portal, $renderer);
	}
}