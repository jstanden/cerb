<?php
class PortalLayoutWidget_Menu extends Extension_PortalLayoutWidget {
	const ID = 'cerb.portal.layout.widget.menu';
	
	public function init(array $widget_meta, array $layout_meta) {
		return $this->fetch($widget_meta, $layout_meta);
	}

	public function fetch(array $widget_meta, array $layout_meta) {
		$tpl = DevblocksPlatform::services()->template();
		
		$menu = $widget_meta['items'] ?? [];
		
		$func_build_menu = function(array &$menu) use (&$func_build_menu) {
			foreach($menu as $k => $v) {
				if(!is_array($v))
					continue;
				
				list($type, $key) = array_pad(explode('/', $k), 2, null);
				
				$menu[$k]['type'] = $type;
				$menu[$k]['key'] = $key;
				
				if('menu' == $type && array_key_exists('items', $v) && is_array($v['items'])) {
					$func_build_menu($menu[$k]['items']);
				}
			}
			
			$menu = array_combine(
				array_column($menu, 'key'),
				$menu
			);
		};
		
		$func_build_menu($menu);
		
		$tpl->assign('menu', $menu);
		
		return $tpl->fetch('devblocks:cerberusweb.core::portals/builder/layout/widgets/menu.tpl');
	}
	
	// [TODO] Used?
	public function render(array $widget_meta, array $layout_meta) {
		echo $this->fetch($widget_meta, $layout_meta);
	}
}