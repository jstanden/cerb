<?php
class PortalPage_Dashboard extends Extension_PortalPage {
	const ID = 'cerb.portal.page.dashboard';
	
	// [TODO] Cache?
	function getDashboardMeta(array $page_meta) {
		$zones = $page_meta['layout'] ?? [];
		$widgets = [];
		
		foreach($zones as $zone_key => $zone_widgets) {
			foreach($zone_widgets as $widget_key => $widget_meta) {
				if(array_key_exists('disabled', $widget_meta) && $widget_meta['disabled']) {
					unset($zones[$zone_key][$widget_key]);
					continue;
				}
				
				list($widget_type, $widget_name) = explode('/', $widget_key);
				
				$zones[$zone_key][$widget_key]['key'] = $widget_name;
				$zones[$zone_key][$widget_key]['type'] = $widget_type;
				
				$width = intval($zones[$zone_key][$widget_key]['width'] ?? null);
				
				if(!in_array($width, [25,33,50,75,100]))
					$width = 100;
				
				$zones[$zone_key][$widget_key]['width'] = $width;
				
				// Index widgets zone locations by name
				$widgets[$widget_name] = sprintf('%s:%s:%s', 'layout', $zone_key, $widget_name);
			}
			
			$zones[$zone_key] = array_combine(
				array_column($zones[$zone_key], 'key'),
				$zones[$zone_key]
			);
		}
		
		$page_meta['layout'] = $zones;
		$page_meta['widgets'] = $widgets;
		
		return $page_meta;
	}
	
	function render(array $page_meta, array $route_meta, Model_CommunityTool $portal) {
		$renderer = new Extension_PortalPageRenderer(function() use ($page_meta, $portal) {
			$tpl = DevblocksPlatform::services()->template();
			
			$dashboard_meta = $this->getDashboardMeta($page_meta);
			
			$zones = $dashboard_meta['layout'] ?? [];
			
			foreach($zones as $zone => $widgets) {
				foreach($widgets as $widget_key => $widget_meta) {
					if(null == ($widget = Extension_PortalWidget::getByType($widget_meta['type'] ?? null)))
						break;
					
					$zones[$zone][$widget_key]['_init'] = $widget->init($widget_meta, $page_meta);
				}
			}
			
			$tpl->assign('page', $page_meta);
			$tpl->assign('layout', array_keys($zones));
			$tpl->assign('zones', $zones);
			
			// Template
			
			$tpl->display('devblocks:cerberusweb.core::portals/builder/pages/dashboard.tpl');
		});
		
		$layout = $route_meta['layout'] ?? null;
		
		if('bare' == $layout) {
			parent::renderBareLayout($page_meta, $portal, $renderer);
		} else {
			parent::renderDefaultLayout($page_meta, $portal, $renderer);
		}
	}
}