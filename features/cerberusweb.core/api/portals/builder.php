<?php
class Portal_Builder extends Extension_CommunityPortal {
	const ID = 'cerb.portal.builder';
	
	private $_menu_pages = null;
	
	/**
	 * @param Model_CommunityTool $instance
	 */
	public function configure(Model_CommunityTool $instance) {
		$tpl = DevblocksPlatform::services()->template();
		
		$params = DAO_CommunityToolProperty::getAllByTool($instance->code);
		$tpl->assign('params', $params);
		
		$tpl->assign('model', $instance);
		$tpl->display('devblocks:cerberusweb.core::portals/builder/config.tpl');
	}

	public function saveConfigurationJsonAction() {
		@$portal_id = DevblocksPlatform::importGPC($_POST['portal_id'], 'integer', 0);
		@$params = DevblocksPlatform::importGPC($_POST['params'], 'array', []);
		
		header('Content-Type: application/json; charset=utf-8');
		
		// Only valid param keys
		$params = array_intersect_key(
			$params,
			array_fill_keys([
				'user_stylesheet',
				'footer_html',
			], true));
		
		try {
			if(!$portal_id || false == ($portal = DAO_CommunityTool::get($portal_id)))
				throw new Exception_DevblocksAjaxValidationError('Invalid portal ID.');
			
			@$user_stylesheet = $params['user_stylesheet'] ?: '';
			@$footer_html = $params['footer_html'] ?: '';
			
			// Stylesheet
			
			DAO_CommunityToolProperty::set($portal->code, 'user_stylesheet', $user_stylesheet);
			
			// Footer
			
			DAO_CommunityToolProperty::set($portal->code, 'footer_html', $footer_html);
			
			echo json_encode(['status' => true]);
			
		} catch (Exception_DevblocksAjaxValidationError $e) {
			echo json_encode(['status' => false, 'error' => $e->getMessage()]);
		}
	}
	
	private function _() {
		
	}

	/**
	 * 
	 * {@inheritDoc}
	 * @see Extension_CommunityPortal::handleRequest()
	 */
	public function handleRequest(DevblocksHttpRequest $request) {
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Extension_CommunityPortal::writeResponse()
	 */
	public function writeResponse(DevblocksHttpResponse $response) {
		$identity = ChPortalHelper::getIdentity();
		
		if(false == ($portal = ChPortalHelper::getPortal()))
			return;
		
		$stack = $response->path;
		
		$page = null;
		
		$pages = DAO_PortalPage::getByPortalId($portal->id);
		
		$uri_mapping = [];
		
		foreach(array_column($pages, 'uri', 'id') as $uri_page_id => $uri_page_path) {
			$parts = explode('/', $uri_page_path);
			$ptr =& $uri_mapping;
			
			$parts_len = count($parts);
			
			foreach($parts as $part_idx => $part) {
				$part_idx++;
				
				if(is_array($ptr)) {
					if(!array_key_exists($part, $ptr)) {
						$ptr[$part] = [];
					}
					
					// Leaf
					if($parts_len == $part_idx) {
						if(!array_key_exists($part, $ptr)) {
							$ptr[$part] = [];
						}
						$ptr[$part][''] = $uri_page_id;
					}
					
					$ptr =& $ptr[$part];
				}
			}
		}
		
		unset($ptr);
		
		$uri_requested = $stack;
		$uri_lookup = $uri_requested;
		$ptr =& $uri_mapping;
		
		while($uri_lookup) {
			$uri_key = array_shift($uri_lookup);
			
			if(array_key_exists($uri_key, $ptr)) {
				$ptr =& $ptr[$uri_key];
				array_shift($stack);
			}
		}
		
		// Recurse back to the parent if we need to
		if(array_key_exists('', $ptr)) {
			$page = @$pages[$ptr['']];
		}
		
		unset($ptr);
		
		// Otherwise default to the first page
		if(!$page) {
			$menu_pages = $this->getMenuPages($portal);
			$page = $pages[key($menu_pages)];
		}
		
		if($page) {
			if(!$page->isVisibleByIdentity($identity)) {
				return;
			}
			
			$delegate_response = $response;
			$delegate_response->path = $stack;
			$page->render($portal, $delegate_response);
		}
	}
	
	function renderDefaultLayout(Model_PortalPage $page, Model_CommunityTool $portal, Extension_PortalPageRenderer $renderer) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		
		$pages = $this->getMenuPages($portal);
		$tpl->assign('pages', $pages);
		
		$tpl->assign('renderer', $renderer);
		
		// Footer
		
		$footer_html_template = $portal->getParam('footer_html');
		$footer_html = null;
		
		if($footer_html_template) {
			$footer_html = $tpl_builder->build($footer_html_template, []);
		}
		
		// Identity
		
		$tpl->assign('identity', ChPortalHelper::getIdentity());
		
		// Template
		
		$tpl->assign('page', $page);
		$tpl->assign('portal', $portal);
		$tpl->assign('footer_html', $footer_html);
		$tpl->display('devblocks:cerberusweb.core::portals/builder/layouts/default.tpl');
	}
	
	function renderBareLayout(Model_PortalPage $page, Model_CommunityTool $portal, Extension_PortalPageRenderer $renderer) {
		$tpl = DevblocksPlatform::services()->template();
		
		$tpl->assign('renderer', $renderer);
		
		// Identity
		
		$tpl->assign('identity', ChPortalHelper::getIdentity());
		
		// Template
		
		$tpl->assign('page', $page);
		$tpl->assign('portal', $portal);
		$tpl->display('devblocks:cerberusweb.core::portals/builder/layouts/bare.tpl');
	}
	
	function getMenuPages(Model_CommunityTool $portal) {
		if(!is_null($this->_menu_pages))
			return $this->_menu_pages;
		
		$identity = ChPortalHelper::getIdentity();
		$pages = DAO_PortalPage::getByPortalId($portal->id);
		
		$pages = array_filter($pages, function($page) use ($identity) {
			// Only pages in the menu
			if(!$page->pos)
				return false;
			
			// Is private and we have no login?
			if($page->is_private && !$identity)
				return false;
			
			return true;
		});
		
		$this->_menu_pages = $pages;
		
		return $this->_menu_pages;
	}
}