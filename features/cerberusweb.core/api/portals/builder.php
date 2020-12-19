<?php
// [TODO]
class CerbMarkdown extends Parsedown {
	protected $safeLinksWhitelist = [
		'data:image/gif;base64,',
		'data:image/jpeg;base64,',
		'data:image/png;base64,',
		'ftp://',
		'ftps://',
		'git:',
		'http://',
		'https://',
		'irc:',
		'ircs:',
		'mailto:',
		'news:',
		'ssh:',
		'steam:',
		'tel:',
	];
	
	protected function inlineImage($Excerpt) {
		$image = parent::inlineImage($Excerpt);
		
		$alt = $image['element']['attributes']['alt'] ?? null;
		
		$matches = [];
		
		if($alt && preg_match('#(.*?)\s*(\d*)x(\d*)#', $alt, $matches)) {
			$width = $matches[2];
			$height = $matches[3];
			
			if($width || $height) {
				$image['element']['attributes']['alt'] = $matches[1];
				
				if($width)
					$image['element']['attributes']['width'] = $width;
				
				if($height)
					$image['element']['attributes']['height'] = $height;
			}
		}
		
		return $image;
	}
	
	protected function inlineLink($Excerpt) {
		$url_writer = DevblocksPlatform::services()->url();
		
		$link = parent::inlineLink($Excerpt);
		
		$href = $link['element']['attributes']['href'] ?? null;
		
		if(DevblocksPlatform::strStartsWith($href, '/')) {
			$link['element']['attributes']['href'] = $url_writer->write('') . ltrim($href, '/');
			
		} else if(DevblocksPlatform::strStartsWith($href, 'cerb:resource:')) {
			$uri_parts = DevblocksPlatform::services()->ui()->parseURI($href);
			$link['element']['attributes']['href'] = $url_writer->write('') . '_resource/' . ltrim($uri_parts['context_id'], '/');
				
		} else if (DevblocksPlatform::strStartsWith($href, ['http:','https:'])) {
			$link['element']['attributes']['target'] = '_blank';
			$link['element']['attributes']['rel'] = 'nofollow noopener';
		}
		
		return $link;
	}
}

class Portal_Builder extends Extension_CommunityPortal {
	const ID = 'cerb.portal.builder';
	
	const CONFIG_PORTAL_KATA = 'portal_kata';
	
	// [TODO] Markdown service?
	// [TODO] Handle cache invalidation
	static function parseMarkdown($text) : ?string {
		if(!is_string($text))
			return null;
		
		$parser = new CerbMarkdown();
		$parser->setBreaksEnabled(true);
		$parser->setMarkupEscaped(true);
		$parser->setSafeMode(true);
		
		// Is it a single line with no linefeeds?
		//if(false === strpos($text, "\n")) {
		//	return $parser->line($text);
		//} else {
			return $parser->parse($text);
		//}
	}
	
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

	public function saveConfiguration(Model_CommunityTool $instance) {
		$portal_id = DevblocksPlatform::importGPC($_POST['portal_id'] ?? null, 'integer', 0);
		$params = DevblocksPlatform::importGPC($_POST['params'] ?? null, 'array', []);
		
		if(false == ($active_worker = CerberusApplication::getActiveWorker()))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		header('Content-Type: application/json; charset=utf-8');
		
		// Only valid param keys
		$params = array_intersect_key(
			$params,
			array_fill_keys([
				'portal_kata',
			], true));
		
		try {
			if(!$portal_id || false == ($portal = DAO_CommunityTool::get($portal_id)))
				throw new Exception_DevblocksAjaxValidationError('Invalid portal ID.');
			
			if(!(Context_CommunityTool::isWriteableByActor($portal, $active_worker)))
				DevblocksPlatform::dieWithHttpError(null, 403);
			
			@$portal_kata = $params['portal_kata'] ?: '';
			
			// Portal KATA
			
			DAO_CommunityToolProperty::set($portal->code, self::CONFIG_PORTAL_KATA, $portal_kata);
			
			return true;
			
		} catch (Exception_DevblocksAjaxValidationError $e) {
			//echo json_encode(['status' => false, 'error' => $e->getMessage()]);
			return false;
		}
	}
	
	// [TODO] Make this reusable
	// [TODO] From manifests
	private function _getWidgetExtensionByType($widget_type) : ?Extension_PortalWidget {
		return Extension_PortalWidget::getByType($widget_type);
	}
	
	private function _getConfiguration($section=null) {
		$portal = ChPortalHelper::getPortal();
		
		$params = DAO_CommunityToolProperty::getAllByTool($portal->code);
		
		$config_kata = $params[self::CONFIG_PORTAL_KATA] ?? '';
		
		// [TODO] Validate KATA schema
		
		$config = DevblocksPlatform::services()->kata()->parse($config_kata);
		
		if($section)
			return $config[$section] ?? [];
		
		return $config;
	}
	
	// [TODO] Cache
	// [TODO] Disabled filter
	private function _getPages() {
		$portal = ChPortalHelper::getPortal();
		$identity = ChPortalHelper::getIdentity();
		
		$pages = $this->_getConfiguration('pages');
		
		$request_headers = DevblocksPlatform::getHttpHeaders() ?: [];
		unset($request_headers['cookie']);
		
		$request = DevblocksPlatform::readRequest();
		$request_path = $request->path;
		array_shift($request_path); // portal
		array_shift($request_path); // uri
		
		$pages_dict = DevblocksDictionaryDelegate::instance([
			'identity__context' => CerberusContexts::CONTEXT_IDENTITY,
			'identity_id' => $identity->id ?? null,
			
			'portal__context' => CerberusContexts::CONTEXT_PORTAL,
			'portal_id' => $portal->id ?? null,
			
			'request_method' => DevblocksPlatform::strUpper($_SERVER['REQUEST_METHOD']),
			'request_body' => DevblocksPlatform::getHttpBody(),
			'request_client_ip' => DevblocksPlatform::getClientIp(),
			'request_headers' => $request_headers,
			'request_params' => DevblocksPlatform::getHttpParams(),
			'request_path' => implode('/', $request_path),
		]);
		
		$pages = DevblocksPlatform::services()->kata()->formatTree($pages, $pages_dict);
		
		$func_build_pages = function(array &$pages) use (&$func_build_pages) {
			foreach($pages as $k => $v) {
				if(!is_array($v))
					continue;
				
				list($type, $key) = array_pad(explode('/', $k), 2, null);
				
				$pages[$k]['type'] = $type;
				$pages[$k]['key'] = $key ?: $type;
				
				if('interaction' == $type) {
					if(!array_key_exists('name', $pages[$k]))
						$pages[$k]['name'] = $pages[$k]['key'];
				}
			}
			
			$pages = array_combine(
				array_column($pages, 'key'),
				$pages
			);
		};
		
		$func_build_pages($pages);
		
		return $pages;
	}
	
	// [TODO] Cache
	// [TODO] Disabled filter
	private function _getInteractions(DevblocksDictionaryDelegate $interactions_dict) : array {
		$interactions = $this->_getConfiguration('interactions');
		
		$interactions = DevblocksPlatform::services()->kata()->formatTree($interactions, $interactions_dict);
		
		$func_build_interactions = function(array &$interactions) use (&$func_build_interactions) {
			foreach($interactions as $k => $v) {
				if(!is_array($v))
					continue;
				
				list($type, $key) = array_pad(explode('/', $k), 2, null);
				
				$interactions[$k]['type'] = $type;
				$interactions[$k]['key'] = $key ?: $type;
				
				if(!array_key_exists('name', $interactions[$k]))
					$interactions[$k]['name'] = $interactions[$k]['key'];
			}
			
			$interactions = array_combine(
				array_column($interactions, 'key'),
				$interactions
			);
		};
		
		$func_build_interactions($interactions);
		
		return $interactions;
	}
	
	// [TODO] Cache this?
	// [TODO] Disabled filter
	private function _getRoutes() {
		$portal = ChPortalHelper::getPortal();
		$identity = ChPortalHelper::getIdentity();
		
		$routing = $this->_getConfiguration('routes');
		
		$request_headers = DevblocksPlatform::getHttpHeaders() ?: [];
		unset($request_headers['cookie']);
		
		// [TODO] Verify this approach
		$request = DevblocksPlatform::readRequest();
		$request_path = $request->path;
		array_shift($request_path); // portal
		array_shift($request_path); // uri
		
		$routing_dict = DevblocksDictionaryDelegate::instance([
			'identity__context' => CerberusContexts::CONTEXT_IDENTITY,
			'identity_id' => $identity->id ?? null,
			
			'portal__context' => CerberusContexts::CONTEXT_PORTAL,
			'portal_id' => $portal->id ?? null,
			
			'request_method' => DevblocksPlatform::strUpper($_SERVER['REQUEST_METHOD']),
			'request_body' => DevblocksPlatform::getHttpBody(),
			'request_client_ip' => DevblocksPlatform::getClientIp(),
			'request_headers' => $request_headers,
			'request_params' => DevblocksPlatform::getHttpParams(),
			'request_path' => implode('/', $request_path),
		]);
		
		$routing = DevblocksPlatform::services()->kata()->formatTree($routing, $routing_dict);
		
		$func_build_routing = function(array &$routing) use (&$func_build_routing) {
			foreach($routing as $k => $v) {
				if(!is_array($v))
					continue;
				
				list($type, $key) = array_pad(explode('/', $k), 2, null);
				
				$routing[$k]['type'] = $type;
				$routing[$k]['key'] = $key ?: $type;
				
				if(!array_key_exists('name', $routing[$k]))
					$routing[$k]['name'] = $routing[$k]['key'];
			}
			
			$routing = array_combine(
				array_column($routing, 'key'),
				$routing
			);
		};
		
		$func_build_routing($routing);
		
		return $routing;
	}
	
	// [TODO] Get just one, without parsing the formatting
	private function _getLayouts() {
		$portal = ChPortalHelper::getPortal();
		$identity = ChPortalHelper::getIdentity();
		
		$layouts = $this->_getConfiguration('layouts');
		
		$request_headers = DevblocksPlatform::getHttpHeaders() ?: [];
		unset($request_headers['cookie']);
		
		// [TODO] Verify this approach
		$request = DevblocksPlatform::readRequest();
		$request_path = $request->path;
		array_shift($request_path); // portal
		array_shift($request_path); // uri
		
		$layouts_dict = DevblocksDictionaryDelegate::instance([
			'identity__context' => CerberusContexts::CONTEXT_IDENTITY,
			'identity_id' => $identity->id ?? null,
			
			'portal__context' => CerberusContexts::CONTEXT_PORTAL,
			'portal_id' => $portal->id ?? null,
			
			'request_method' => DevblocksPlatform::strUpper($_SERVER['REQUEST_METHOD']),
			'request_body' => DevblocksPlatform::getHttpBody(),
			'request_client_ip' => DevblocksPlatform::getClientIp(),
			'request_headers' => $request_headers,
			'request_params' => DevblocksPlatform::getHttpParams(),
			'request_path' => implode('/', $request_path),
		]);
		
		$layouts = DevblocksPlatform::services()->kata()->formatTree($layouts, $layouts_dict);
		
		$func_build_layouts = function(array &$layouts) use (&$func_build_layouts) {
			foreach($layouts as $k => $v) {
				if(!is_array($v))
					continue;
				
				list($type, $key) = array_pad(explode('/', $k), 2, null);
				
				$layouts[$k]['type'] = $type;
				$layouts[$k]['key'] = $key ?: $type;
				
				if(!array_key_exists('name', $layouts[$k]))
					$layouts[$k]['name'] = $layouts[$k]['key'];
			}
			
			$layouts = array_combine(
				array_column($layouts, 'key'),
				$layouts
			);
		};
		
		$func_build_layouts($layouts);
		
		return $layouts;
	}
	
	private function _getLayoutPart(string $part, array $layout_meta) {
		$sections = $layout_meta[$part] ?? [];
		
		if(!$sections || !is_array($sections))
			return [];
		
		// Re-key sections
		$sections = array_combine(
			array_map(fn($k) => DevblocksPlatform::services()->string()->strAfter($k, '/') ?? $k, array_keys($sections)),
			$sections
		);
		
		// Blocks
		foreach(array_keys($sections) as $section_key) {
			foreach(array_keys($sections[$section_key]) as $block_key) {
				if(array_key_exists('hidden', $sections[$section_key][$block_key]) && $sections[$section_key][$block_key]['hidden']) {
					unset($sections[$section_key][$block_key]);
					continue;
				}
				
				// [TODO] Sanitize (percent, pixels, etc)
				if(!array_key_exists('width', $sections[$section_key][$block_key]))
					$sections[$section_key][$block_key]['width'] = (100/count($sections[$section_key])) . '%';
				
				// [TODO] Sanitize int
				if(!array_key_exists('scale', $sections[$section_key][$block_key]))
					$sections[$section_key][$block_key]['scale'] = 1;
				
				// [TODO] Sanitize left/center/right
				if(!array_key_exists('align', $sections[$section_key][$block_key]))
					$sections[$section_key][$block_key]['align'] = 'left';
				
				// Widgets
				if(!array_key_exists('widgets', $sections[$section_key][$block_key]))
					$sections[$section_key][$block_key]['widgets'] = [];
				
				// Widgets
				foreach($sections[$section_key][$block_key]['widgets'] as $widget_name => $widget) {
					if(array_key_exists('hidden', $widget) && $widget['hidden']) {
						unset($sections[$section_key][$block_key]['widgets'][$widget_name]);
						continue;
					}
					
					list($widget_type, $widget_key) = array_pad(explode('/', $widget_name), 2, null);
					
					// Run the widget
					if(is_array($widget) && false != ($layout_widget = Extension_PortalLayoutWidget::getByType($widget_type))) {
						$sections[$section_key][$block_key]['widgets'][$widget_name]['_html'] = $layout_widget->init($widget, $layout_meta);
					}
					
					$sections[$section_key][$block_key]['widgets'][$widget_name]['_type'] = $widget_type;
					$sections[$section_key][$block_key]['widgets'][$widget_name]['_key'] = $widget_key ?: $widget_type;
				}
				
				// Re-key widgets
				$sections[$section_key][$block_key]['widgets'] = array_combine(
					array_map(fn($k) => DevblocksPlatform::services()->string()->strAfter($k, '/') ?? $k, array_keys($sections[$section_key][$block_key]['widgets'])),
					$sections[$section_key][$block_key]['widgets']
				);
			}
			
			// Re-key blocks
			$sections[$section_key] = array_combine(
				array_map(fn($k) => DevblocksPlatform::services()->string()->strAfter($k, '/') ?? $k, array_keys($sections[$section_key])),
				$sections[$section_key]
			);
		}
		
		return $sections;
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
		if(false == ChPortalHelper::getPortal())
			return;
		
		// [TODO] move all of these into a `_` path?
		
		switch(current($response->path)) {
			case '_interaction':
				$this->_responseInteraction($response);
				break;
			
			case '_resource':
				$this->_responseResource($response);
				break;

			case '_widget':
				$this->_responseWidget($response);
				break;
				
			default:
				$this->_responseDefault();
				break;
		}
	}
	
	private function _responseResource(DevblocksHttpResponse $response) {
		$path = $response->path;
		array_shift($path); // _resource
		$uri = array_shift($path); // portal.icon.svg
		
		if('GET' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		if('builder.js' == $uri) {
			// Allow caching, but invalidate from the `X-Cerb-Version` header
			header('Content-Type: text/javascript');
			
			$ttl_secs = 86400; // 1 day
			header('Pragma: cache');
			header(sprintf('Cache-control: max-age=%d', $ttl_secs));
			header(sprintf('Expires: %s GMT', gmdate('D, d M Y H:i:s', time() + $ttl_secs)));
			
			$tpl = DevblocksPlatform::services()->templateSandbox();
			$tpl->assign('cerb_build', APP_BUILD);
			$tpl->display('devblocks:cerberusweb.core::portals/builder/includes/builder.js');
			
		} else {
			if(!($resource = DAO_Resource::getByNameAndType($uri, ResourceType_PortalImage::ID)))
				DevblocksPlatform::dieWithHttpError(DevblocksPlatform::translate('files.not_found'), 404);
			
			// [TODO] Merge headers
			
			$resource_content = $resource->getExtension()->getContentData($resource);
			
			/*
			if($resource_content->expires_at) {
				$resource_content->headers =
					array_merge(
						$resource_content->headers,
						[
							'Pragma: cache',
							sprintf('Cache-control: max-age=%d', $resource_content->expires_at - time()),
							'Expires: ' . gmdate('D, d M Y H:i:s', $resource_content->expires_at) . ' GMT',
							'Accept-Ranges: bytes',
						]
					)
				;
			}
			*/
			
			$resource_content->writeHeaders();
			$resource_content->writeBody();
			
			//$ext = DevblocksPlatform::services()->string()->strAfter($resource->name, '.');
			//$mime_type = DevblocksPlatform::services()->file()->getMimeTypebyExt($ext);
			//header('Content-Type: ' . $resource->);
			
//			$ttl_secs = 86400; // 1 day
//			header('Pragma: cache');
//			header(sprintf('Cache-control: max-age=%d', $ttl_secs));
//			header(sprintf('Expires: %s GMT', gmdate('D, d M Y H:i:s', time() + $ttl_secs)));
			
			// [TODO] Sanitize output
			// [TODO] Streams for large objects
			
//			$bytes = Storage_Resource::get($resource);
//			echo $bytes;
		}
	}
	
	private function _responseWidget(DevblocksHttpResponse $response) {
		$path = $response->path;
		array_shift($path);
		
		// Require an HTTP POST
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		$action = implode('/', $path);
		
		switch($action) {
			case 'invoke':
				break;
			
			// [TODO] Init?
			
			case 'refresh':
				$page_name = DevblocksPlatform::importGPC($_POST['page'] ?? null, 'string');
				$widget_name = DevblocksPlatform::importGPC($_POST['widget'] ?? null, 'string');
				
				$pages = $this->_getPages();
				
				$page_meta = $pages[$page_name] ?? [];
				$page_type = $page_meta['type'] ?? null;
				
				// Check page permissions
				if(array_key_exists('disabled', $page_meta) && $page_meta['disabled'])
					break;
			
				if(null == ($page = Extension_PortalPage::getByType($page_type)))
					break;
				
				/* @var PortalPage_Dashboard $page */
				
				if(!($page instanceof PortalPage_Dashboard))
					break;
				
				$dashboard_meta = $page->getDashboardMeta($page_meta);
				
				$dict = DevblocksDictionaryDelegate::instance($dashboard_meta);
				$widget_keypath = $dict->getKeyPath('widgets:'. $widget_name, '', ':');
				$widget_meta = $dict->getKeyPath($widget_keypath, [],':');
			
				if(null == ($widget = $this->_getWidgetExtensionByType($widget_meta['type'] ?? null)))
					break;
				
				$widget->render($widget_meta, $page_meta);
				break;
		}
	}	
	
	private function _responseInteraction(DevblocksHttpResponse $response) {
		$path = $response->path;
		array_shift($path);
		
		// Require an HTTP POST
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		// Add conditional CORS headers
		//$this->_respondWithCORS();
		
		// For cache invalidation
		header(sprintf('X-Cerb-Version: %s',  APP_BUILD));
		
		$action = array_shift($path);
		
		switch($action) {
			case 'start':
				$interaction = DevblocksPlatform::importGPC($_POST['interaction'] ?? null, 'string');
				
				$interaction_params = DevblocksPlatform::strParseQueryString(
					DevblocksPlatform::importGPC($_POST['interaction_params'] ?? null, 'string')
				);
				
				$this->_interactionStart($interaction, $interaction_params);
				break;
			
			case 'continue':
				$continuation_token = DevblocksPlatform::importGPC($_POST['continuation_token'] ?? null, 'string');
				$this->_interactionContinue($continuation_token);
				break;
			
			case 'invoke':
				$continuation_token = DevblocksPlatform::importGPC($_POST['continuation_token'] ?? null, 'string');
				$this->_interactionInvoke($continuation_token);
				break;
		}
	}
	
	private function _responseDefault() {
		$portal = ChPortalHelper::getPortal();
		
		// Store in portal config
		$pages = $this->_getPages();
		$routing = $this->_getRoutes();
		
//		var_dump($pages);
		
		foreach($routing as $route_meta) {
			if(array_key_exists('disabled', $route_meta) && $route_meta['disabled'])
				continue;
			
			// [TODO] Handle the kind of route
			
			$page_meta = $pages[$route_meta['name']] ?? [];
			//$page_meta['route'] = $route;
			$page_type = $page_meta['type'] ?? '';
			
			$page = Extension_PortalPage::getByType($page_type);
			
			if($page instanceof Extension_PortalPage)
				$page->render($page_meta, $route_meta, $portal);
			
			break;
		}
	}
	
	function renderDefaultLayout(array $page_meta, Model_CommunityTool $portal, Extension_PortalPageRenderer $renderer) {
		$tpl = DevblocksPlatform::services()->template();
		
		//var_dump($page_meta);
		
		// [TODO] Get layout name from page/route
		// [TODO] Only get one layout
		$layouts = $this->_getLayouts();
		
		$layout_meta = $layouts['default'];
		
		$header = $this->_getLayoutPart('header', $layout_meta);
		$tpl->assign('header', $header);
		
		$footer = $this->_getLayoutPart('footer', $layout_meta);
		$tpl->assign('footer', $footer);
		
		$tpl->assign('renderer', $renderer);
		
		// Template
		
		$tpl->assign('portal', $portal);
		//$tpl->assign('identity', ChPortalHelper::getIdentity());
		$tpl->display('devblocks:cerberusweb.core::portals/builder/layout/default.tpl');
	}
	
	// [TODO] Phase this out (just a headerless/footerless layout; maybe default)
	function renderBareLayout(array $page_meta, Model_CommunityTool $portal, Extension_PortalPageRenderer $renderer) {
		$tpl = DevblocksPlatform::services()->template();
		
		$tpl->assign('renderer', $renderer);
		
		// Identity
		
		$tpl->assign('identity', ChPortalHelper::getIdentity());
		
		// Template
		
		//$tpl->assign('page_meta', $page_meta);
		$tpl->assign('portal', $portal);
		$tpl->display('devblocks:cerberusweb.core::portals/builder/layout/bare.tpl');
	}
	
	public function interactionCreate(string $interaction_uri, array $interaction_params, array $initial_state=[], array $state_data=[], $continuation_token=null) : array {
		$automator = DevblocksPlatform::services()->automation();
		
		$identity = ChPortalHelper::getIdentity();
		$portal = ChPortalHelper::getPortal();
		$user_agent = DevblocksPlatform::getClientUserAgent();
		
		$error = null;
		
		$initial_state['interaction_params'] = $interaction_params;
		$initial_state['identity__context'] = CerberusContexts::CONTEXT_IDENTITY;
		$initial_state['identity_id'] = $identity->id ?? null;
		$initial_state['portal__context'] = CerberusContexts::CONTEXT_PORTAL;
		$initial_state['portal_id'] = $portal->id ?? null;
		$initial_state['client_ip'] = DevblocksPlatform::getClientIp();
		$initial_state['client_browser_name'] = $user_agent['browser'] ?? null;
		$initial_state['client_browser_platform'] = $user_agent['platform'] ?? null;
		$initial_state['client_browser_version'] = $user_agent['version'] ?? null;
		
		if(false == ($automation = DAO_Automation::getByUri($interaction_uri, AutomationTrigger_InteractionPortal::ID)))
			return [];
		
		$automation_results = $automator->executeScript($automation, $initial_state, $error);
		
		if(!($automation_results instanceof DevblocksDictionaryDelegate)) {
			DevblocksPlatform::dieWithHttpError("null automation results", 404);
		}
		
		$exit_state = $automation_results->getKeyPath('__exit');
		
		if('await' == $exit_state) {
			// [TODO] Identity / portal
			$state_data['trigger'] = AutomationTrigger_InteractionPortal::ID;
			$state_data['dict'] = $automation_results->getDictionary();
			
			// Is the continuation token valid?
			
			$continuation = null;
			
			if($continuation_token && false == ($continuation = DAO_AutomationContinuation::getByToken($continuation_token)))
				$continuation_token = null;
				
			if($continuation_token && (($continuation->state_data['trigger'] ?? null) != AutomationTrigger_InteractionPortal::ID))
				$continuation_token = null;
			
			if($continuation_token) {
				// [TODO] Remove any children of this root/parent token
				//DAO_AutomationContinuation::
				
				DAO_AutomationContinuation::update($continuation_token, [
					DAO_AutomationContinuation::UPDATED_AT => time(),
					DAO_AutomationContinuation::EXPIRES_AT => time() + 1200, // 20 mins
					DAO_AutomationContinuation::STATE => $exit_state,
					DAO_AutomationContinuation::STATE_DATA => json_encode($state_data),
					DAO_AutomationContinuation::URI => $automation->name,
				]);
				
			} else {
				// [TODO] Are we specifically disabling continue?
				
				$continuation_token = DAO_AutomationContinuation::create([
					DAO_AutomationContinuation::UPDATED_AT => time(),
					DAO_AutomationContinuation::EXPIRES_AT => time() + 1200, // 20 mins
					DAO_AutomationContinuation::STATE => $exit_state,
					DAO_AutomationContinuation::STATE_DATA => json_encode($state_data),
					DAO_AutomationContinuation::URI => $automation->name,
				]);
			}
		}
		
		return [
			'automation_results' => $automation_results,
			'continuation_token' => $continuation_token,
		];
	} 
	
	// [TODO] This has to map to interaction URIs
	private function _interactionStart(string $interaction, array $interaction_params=[]) {
		$initial_state = [];
		$state_data = [];
		
		$initial_state['interaction'] = $interaction;
		$initial_state['interaction_params'] = $interaction_params;
		$initial_state['identity__context'] = CerberusContexts::CONTEXT_IDENTITY;
		$initial_state['identity_id'] = $identity->id ?? null;
		$initial_state['portal__context'] = CerberusContexts::CONTEXT_PORTAL;
		$initial_state['portal_id'] = $portal->id ?? null;
		$initial_state['client_ip'] = DevblocksPlatform::getClientIp();
		$initial_state['client_browser_name'] = $user_agent['browser'] ?? null;
		$initial_state['client_browser_platform'] = $user_agent['platform'] ?? null;
		$initial_state['client_browser_version'] = $user_agent['version'] ?? null;
		
		$interactions_dict = DevblocksDictionaryDelegate::instance($initial_state);
		
		$interactions = $this->_getInteractions($interactions_dict);
		
		if(!array_key_exists($interaction, $interactions))
			return [];
		
		if('automation' != $interactions[$interaction]['type'] ?? null)
			return [];
		
		$initial_state = $interactions_dict->getDictionary();
		
		// [TODO]
		return [];
		
		list(, $continuation_token) = $this->interactionCreate($interaction, $interaction_params, $initial_state, $state_data);
		
		// [TODO] If there's no continuation, display immediately
		
		//var_dump($handler);
		//var_dump($state_data);
		
		$tpl = DevblocksPlatform::services()->templateSandbox();
		$tpl->assign('continuation_token', $continuation_token);
		//$tpl->assign('page', $page);
		//$tpl->assign('page_ext', $this);
		//$tpl->assign('portal', $portal);
		$tpl->display('devblocks:cerberusweb.core::portals/builder/pages/interaction/container.tpl');
	}
	
	private function _interactionContinue(string $continuation_token) : void {
		if(false == ($continuation = DAO_AutomationContinuation::getByToken($continuation_token)))
			DevblocksPlatform::dieWithHttpError("null continuation token", 404);
		
		$initial_state = $continuation->state_data['dict'] ?? [];
		
		//var_dump($continuation->state_data);
		
		if(array_key_exists('__return', $initial_state)) {
			if(array_key_exists('interaction', $initial_state['__return'])) {
				$this->_handleAwaitInteraction($continuation);
			} else if(array_key_exists('form', $initial_state['__return'])) {
				$this->_handleAwaitForm($continuation);
			}
		}
	}
	
	private function _interactionInvoke(string $continuation_token) : void {
		if(false == ($continuation = DAO_AutomationContinuation::getByToken($continuation_token)))
			DevblocksPlatform::dieWithHttpError("null continuation token", 404);
		
		// [TODO] Verify this is a portal continuation
		
		$form_components = AutomationTrigger_InteractionPortal::getFormComponentMeta();
		
		$initial_state = $continuation->state_data['dict'] ?? [];
		$last_prompts = ($initial_state['__return']['form']['elements'] ?? null) ?: [];
		
		$prompt_key = rtrim(DevblocksPlatform::importGPC($_POST['prompt_key'] ?? null, 'string'), '/');
		$prompt_action = DevblocksPlatform::importGPC($_POST['prompt_action'] ?? null, 'string');
		$prompt_params = DevblocksPlatform::importGPC($_POST['prompt_params'] ?? [], 'array');
		
		if(!array_key_exists($prompt_key, $last_prompts))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		$last_prompt = $last_prompts[$prompt_key];
		
		list($prompt_type, $prompt_set_key) = array_pad(explode('/', $prompt_key), 2, null);
		
		// [TODO] Interaction params
		
		if(array_key_exists($prompt_type, $form_components)) {
			$component = new $form_components[$prompt_type]($prompt_set_key, null, $last_prompt);
			$component->invoke($prompt_key, $prompt_action, $prompt_params, $continuation);
		}
	}
	
	private function _handleAwaitForm(Model_AutomationContinuation $continuation) {
		$automator = DevblocksPlatform::services()->automation();
		$validation = DevblocksPlatform::services()->validation();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		
		$prompts_without_output = ['say','submit'];
		
		$form_components = AutomationTrigger_InteractionPortal::getFormComponentMeta();
		
		$prompts = DevblocksPlatform::importGPC($_POST['prompts'] ?? null, 'array', []);
		$is_submit = DevblocksPlatform::importGPC($_POST['__submit'] ?? null, 'bool', false);
		
		unset($_POST);
		
		$initial_state = $continuation->state_data['dict'] ?? [];
		
		if(false == ($automation = $continuation->getAutomation()))
			DevblocksPlatform::dieWithHttpError("No automation", 404);
		
		if($automation->extension_id != AutomationTrigger_InteractionPortal::ID)
			DevblocksPlatform::dieWithHttpError("Wrong extension", 403);
		
		// Clear validation errors
		unset($initial_state['__return']['form']['elements']['say/__validation']);
		
		if($is_submit) {
			$last_prompts = ($initial_state['__return']['form']['elements'] ?? null) ?: [];
			$validation_errors = [];
			$validation_values = [];
			
			foreach ($last_prompts as $last_prompt_key => $last_prompt) {
				list($last_prompt_type, $prompt_set_key) = array_pad(explode('/', $last_prompt_key, 2), 2, null);
				
				if (!$prompt_set_key)
					continue;
				
				if (array_key_exists($last_prompt_type, $form_components)) {
					if(in_array($last_prompt_type, $prompts_without_output))
						continue;
					
					$prompt_value = $prompts[$prompt_set_key] ?? null;
					
					$is_required = array_key_exists('required', $last_prompt) && $last_prompt['required'];
					
					$is_set = (is_string($prompt_value) && strlen($prompt_value))
						|| (is_array($prompt_value) && count($prompt_value));
					
					if($is_required || $is_set) {
						$component = new $form_components[$last_prompt_type]($prompt_set_key, $prompt_value, $last_prompt);
						
						$component->validate($validation, $continuation);
						
						$validation_values[$prompt_set_key] = $prompt_value;
						
						// Run custom validation if it exists
						if(array_key_exists('validation', $last_prompt)) {
							$validation_set_key = $prompt_set_key . '__custom';
							$validation_dict = DevblocksDictionaryDelegate::instance($initial_state);
							$validation_dict->set($prompt_set_key, $prompt_value);
							$validation_error = trim($tpl_builder->build($last_prompt['validation'], $validation_dict));
							
							if($validation_error) {
								$validation_values[$validation_set_key] = $prompt_value;
								
								$validation
									->addField($validation_set_key, $last_prompt['label'] ?? $prompt_set_key)
									->error()
									->setError($validation_error)
								;
							}
						}
					}
					
					$initial_state[$prompt_set_key] = $prompt_value;
				}
			}
			
			if ($validation_values) {
				if (false === $validation->validateAll($validation_values, $error))
					$validation_errors[] = $error;
				
				$initial_state = array_merge($initial_state, $validation_values);
			}
			
			if($validation_errors) {
				$initial_state['__return']['form']['elements'] = [
						'say/__validation' => [
							'content' => sprintf("# Correct the following errors to continue:\n%s",
								implode("\n", array_map(function($error) {
									return '* ' . rtrim($error);
								}, $validation_errors))
							),
							'style' => 'error',
						]
					] + $last_prompts;
				
				$automation_results = DevblocksDictionaryDelegate::instance($initial_state);
				
			} else {
				// Format dictionary keys
				foreach($last_prompts as $last_prompt_key => $last_prompt) {
					list($last_prompt_type, $prompt_set_key) = array_pad(explode('/', $last_prompt_key, 2), 2, null);
					$prompt_value = $prompts[$prompt_set_key] ?? null;
					
					if(array_key_exists($last_prompt_type, $form_components)) {
						if(in_array($last_prompt_type, $prompts_without_output))
							continue;
						
						$component = new $form_components[$last_prompt_type]($prompt_set_key, $prompt_value, $last_prompt);
						$initial_state[$prompt_set_key] = $component->formatValue();
					}
				}
				
				if(false === ($automation_results = $automator->executeScript($automation, $initial_state, $error))) {
					$initial_state['__exit'] = 'await';
					$initial_state['__return'] = [
						'form' => [
							'elements' => [
								'say/__validation' => [
									'content' => $error,
									'style' => 'error',
								],
							],
						],
					];
					$automation_results = DevblocksDictionaryDelegate::instance($initial_state);
				}
			}
			
		} else {
			$automation_results = DevblocksDictionaryDelegate::instance($initial_state);
		}
		
		if($automation_results->getKeyPath('__return.interaction')) {
			$this->_respondAwaitInteraction($automation_results, $continuation);
		} else {
			$this->_respondAwaitForm($automation_results, $continuation);
		}
	}
	
	private function _handleAwaitInteraction(Model_AutomationContinuation $continuation) : void {
		$delegate_token = $continuation->state_data['dict']['__return']['interaction']['token'] ?? null;
		
		if(null == $delegate_token)
			DevblocksPlatform::dieWithHttpError("Null delegate", 404);
		
		if(false == ($delegate_continuation = DAO_AutomationContinuation::getByToken($delegate_token)))
			DevblocksPlatform::dieWithHttpError("Null delegate continuation", 404);
		
		// Is the delegate completed?
		
		if('await' != $delegate_continuation->state) {
			$automator = DevblocksPlatform::services()->automation();
			
			if(false == ($automation = $continuation->getAutomation()))
				DevblocksPlatform::dieWithHttpError("Null delegate automation", 404);
			
			$error = null;
			$initial_state = $continuation->state_data['dict'];
			
			if(false === ($automation_results = $automator->executeScript($automation, $initial_state, $error))) {
				$initial_state['__exit'] = 'error';
				$initial_state['__error'] = [
					'message' => 'An unexpected error occurred.'
				];
				$automation_results = DevblocksDictionaryDelegate::instance($initial_state);
			}
			
			if($automation_results->getKeyPath('__return.interaction')) {
				$this->_respondAwaitInteraction($automation_results, $continuation);
			} else {
				$this->_respondAwaitForm($automation_results, $continuation);
			}
			
			return;
		}
		
		$initial_state = $delegate_continuation->state_data['dict'] ?? [];
		
		if(array_key_exists('__return', $initial_state)) {
			if(array_key_exists('interaction', $initial_state['__return'])) {
				$this->_handleAwaitInteraction($delegate_continuation);
			} else if(array_key_exists('form', $initial_state['__return'])) {
				$this->_handleAwaitForm($delegate_continuation);
			}
		}
	}
	
	private function _respondAwaitForm(DevblocksDictionaryDelegate $automation_results, Model_AutomationContinuation $continuation) {
		$form_components = AutomationTrigger_InteractionPortal::getFormComponentMeta();
		
		$exit_code = $automation_results->get('__exit');
		
		$form_title = $automation_results->getKeyPath('__return.form.title');
		
		if($form_title) {
			$tpl = DevblocksPlatform::services()->templateSandbox();
			$tpl->assign('popup_title', $form_title);
			$tpl->display('devblocks:cerberusweb.core::automations/triggers/interaction.portal/await/_set_popup_title.tpl');
		}
		
		$continuation->state_data['dict'] = $automation_results->getDictionary();
		
		$elements = $automation_results->getKeyPath('__return.form.elements', []);
		
		// Synthesize a submit button on await
		if('await' == $exit_code) {
			if(!array_key_exists('submit', $elements)) {
				$submits = array_filter(array_keys($elements), function($element_key) {
					return ($element_key == 'submit' || DevblocksPlatform::strStartsWith($element_key, 'submit/'));
				});
				
				if(!$submits) {
					$elements['submit/' . uniqid()] = [
						'continue' => true,
						'reset' => false,
					];
				}
			}
			
			// Wait up to a day
			$continuation->expires_at = time() + 86400;
			
			// Synthesize an end action on other states
		} else {
			// We just finished a delegate interaction
			if(null != ($parent_continuation = $continuation->getParent())) {
				// Save delegate state
				DAO_AutomationContinuation::update($continuation->token, [
					DAO_AutomationContinuation::STATE => $exit_code,
					DAO_AutomationContinuation::STATE_DATA => json_encode($continuation->state_data),
					DAO_AutomationContinuation::UPDATED_AT => time(),
				]);
				
				if(null != ($output_placeholder = $parent_continuation->state_data['dict']['__return']['interaction']['output'] ?? null)) {
					$parent_continuation->state_data['dict'][$output_placeholder] = $automation_results->get('__return', []);
				}
				
				// Save parent state
				DAO_AutomationContinuation::update($parent_continuation->token, [
					DAO_AutomationContinuation::STATE_DATA => json_encode($parent_continuation->state_data),
					DAO_AutomationContinuation::UPDATED_AT => time(),
				]);
				
				$this->_handleAwaitInteraction($parent_continuation);
				return;
				
			} else { // Not a delegate
				$elements['end/' . uniqid()] = $automation_results->get('__return', []);
			}
		}
		
		foreach($elements as $element_key => $element_data) {
			list($action_key_type, $var) = array_pad(explode('/', $element_key, 2), 2, null);
			
			if(is_array($element_data) && array_key_exists('hidden', $element_data) && $element_data['hidden'])
				continue;
			
			if(array_key_exists($action_key_type, $form_components)) {
				$value = $automation_results->get($var, null);
				
				if(!array_key_exists($action_key_type, $form_components))
					continue;
				
				$component = new $form_components[$action_key_type]($var, $value, $element_data);
				$component->render($continuation);
			}
		}
		
		// Save session scope
		DAO_AutomationContinuation::update($continuation->token, [
			DAO_AutomationContinuation::STATE => $exit_code,
			DAO_AutomationContinuation::STATE_DATA => json_encode($continuation->state_data),
			DAO_AutomationContinuation::EXPIRES_AT => $continuation->expires_at,
			DAO_AutomationContinuation::UPDATED_AT => time(),
		]);
	}
	
	private function _respondAwaitInteraction(DevblocksDictionaryDelegate $automation_results, Model_AutomationContinuation $continuation) {
		$event_handler = DevblocksPlatform::services()->ui()->eventHandler();
		
		// Must have a URI
		if(false == ($interaction_uri = trim($automation_results->getKeyPath('__return.interaction.uri'))))
			DevblocksPlatform::dieWithHttpError("invalid return interaction uri", 404);
		
		$handler_name = uniqid();
		
		$handlers = [
			$handler_name => [
				'id' => 'automation/' . $handler_name,
				'type' => 'automation',
				'key' => $handler_name,
				'data' => [
					'uri' => $interaction_uri,
				]
			]
		];
		
		$initial_state = [
			'inputs' => $automation_results->getKeyPath('__return.interaction.inputs', []),
			'portal__context' => $automation_results->get('portal__context'),
			'portal_id' => $automation_results->get('portal_id'),
			'client_ip' => $automation_results->get('client_ip'),
			'client_browser_name' => $automation_results->get('client_browser_name'),
			'client_browser_platform' => $automation_results->get('client_browser_platform'),
			'client_browser_version' => $automation_results->get('client_browser_version'),
		];
		
		$delegate_results = $event_handler->handleOnce(
			AutomationTrigger_InteractionPortal::ID,
			$handlers,
			$initial_state,
			$error,
			null,
			$handler
		);
		
		if(false == $delegate_results)
			DevblocksPlatform::dieWithHttpError("null delegate results", 404);
		
		$state_data = [
			'trigger' => AutomationTrigger_InteractionPortal::ID,
			'dict' => $delegate_results->getDictionary(),
		];
		
		// Create a new continuation to track the delegate
		
		$delegate_continuation = new Model_AutomationContinuation();
		$delegate_continuation->updated_at = time();
		$delegate_continuation->expires_at = time() + 1200;
		$delegate_continuation->state = $delegate_results->getKeyPath('__exit');
		$delegate_continuation->state_data = $state_data;
		$delegate_continuation->uri = $handler->name;
		
		$delegate_continuation->token = DAO_AutomationContinuation::create([
			DAO_AutomationContinuation::PARENT_TOKEN => $continuation->token,
			DAO_AutomationContinuation::ROOT_TOKEN => $continuation->root_token ?: $continuation->token,
			DAO_AutomationContinuation::UPDATED_AT => $delegate_continuation->updated_at,
			DAO_AutomationContinuation::EXPIRES_AT => $delegate_continuation->expires_at,
			DAO_AutomationContinuation::STATE => $delegate_continuation->state,
			DAO_AutomationContinuation::STATE_DATA => json_encode($delegate_continuation->state_data),
			DAO_AutomationContinuation::URI => $delegate_continuation->uri,
		]);
		
		// Update the parent continuation with the delegate token
		
		$continuation->state_data['dict'] = $automation_results->getDictionary();
		$continuation->state_data['dict']['__return']['interaction']['token'] = $delegate_continuation->token;
		
		DAO_AutomationContinuation::update($continuation->token, [
			DAO_AutomationContinuation::STATE_DATA => json_encode($continuation->state_data),
		]);
		
		if($delegate_results->getKeyPath('__return.interaction')) {
			$this->_respondAwaitInteraction($delegate_results, $delegate_continuation);
		} else {
			$this->_respondAwaitForm($delegate_results, $delegate_continuation);
		}
	}	
}