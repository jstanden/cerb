<?php 
class Controller_Default extends DevblocksControllerExtension {
	const ID = 'core.controller.page';
	
	private function _getAllowedPages() {
		$active_worker = CerberusApplication::getActiveWorker();
		$page_manifests = DevblocksPlatform::getExtensions('cerberusweb.page', false, false);

		// Check worker level ACL (if set by manifest)
		foreach($page_manifests as $idx => $page_manifest) {
			// If ACL policy defined
			if(isset($page_manifest->params['acl'])) {
				if($active_worker && !$active_worker->hasPriv($page_manifest->params['acl'])) {
					unset($page_manifests[$idx]);
				}
			}
		}
		
		return $page_manifests;
	}
	
	public function handleRequest(DevblocksHttpRequest $request) {
		$path = $request->path;

		$controller_uri = array_shift($path);

		$page = null;
		
		if(null != ($page_manifest = CerberusApplication::getPageManifestByUri($controller_uri)))
			$page = $page_manifest->createInstance(); /* @var $page CerberusPageExtension */
		
		if(!$page) {
			switch($controller_uri) {
				case 'apple-touch-icon-precomposed.png':
				case 'apple-touch-icon.png':
					$bytes = file_get_contents(APP_PATH . '/apple-touch-icon.png');
					
					DevblocksPlatform::services()->http()
						->setHeader('Cache-Control', 'max-age=86400')
						->setHeader('Content-Length', strlen($bytes))
						->setHeader('Content-Type', 'image/png')
						->setHeader('Expires', gmdate('D, d M Y H:i:s',time()+86400) . ' GMT')
						->setHeader('Pragma', 'cache')
					;
			
					DevblocksPlatform::dieWithHttpErrorHtml($bytes, 200);
					
				case 'favicon.ico':
					$bytes = file_get_contents(APP_PATH . '/favicon.ico');
					
					DevblocksPlatform::services()->http()
						->setHeader('Cache-Control', 'max-age=86400')
						->setHeader('Content-Length', strlen($bytes))
						->setHeader('Content-Type', 'image/x-icon')
						->setHeader('Expires', gmdate('D, d M Y H:i:s',time()+86400) . ' GMT')
						->setHeader('Pragma', 'cache')
					;
					
					DevblocksPlatform::dieWithHttpErrorHtml($bytes, 200);
					
				case 'portal':
					DevblocksPlatform::dieWithHttpError(null, 404);
				
				case 'robots.txt':
					$bytes = file_get_contents(APP_PATH . '/robots.txt');
					
					DevblocksPlatform::services()->http()
						->setHeader('Cache-Control', 'max-age=86400')
						->setHeader('Content-Length', strlen($bytes))
						->setHeader('Content-Type', 'text/plain')
						->setHeader('Expires', gmdate('D, d M Y H:i:s',time()+86400) . ' GMT')
						->setHeader('Pragma', 'cache')
					;
					
					DevblocksPlatform::dieWithHttpErrorHtml($bytes, 200);
					
				case 'branding':
					$this->_handleBrandingRequest(array_shift($path));
					exit;

				default:
					return true;
			}
		}
		
		$action = array_shift($path);
		
		if(!is_null($action)) {
			if($page->isVisible()) {
				$page->invoke($action);
			} elseif($request->is_ajax) {
				// If we're unauthenticated and requesting an action, return an error code for the browser
				DevblocksPlatform::dieWithHttpError(null, 401);
			}
		}
	}
	
	public function writeResponse(DevblocksHttpResponse $response) {
		$path = $response->path;

		$tpl = DevblocksPlatform::services()->template();
		$session = DevblocksPlatform::services()->session();
		$settings = DevblocksPlatform::services()->pluginSettings();
		$translate = DevblocksPlatform::getTranslationService();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$visit = $session->getVisit();
		$page_manifests = $this->_getAllowedPages();

		$controller = array_shift($path);
		
		// Default page
		if(empty($controller)) {
			if(is_a($active_worker, 'Model_Worker')) {
				$controller = 'pages';
				$path = ['pages'];
				
				// Find the worker's first page
				
				if(null != ($menu_json = DAO_WorkerPref::get($active_worker->id, 'menu_json', '[]'))) {
					@$menu = json_decode($menu_json);

					if(is_array($menu) && !empty($menu)) {
						$page_id = current($menu);
						$path[] = $page_id;
					}
				}

				$response = new DevblocksHttpResponse($path);
				
				DevblocksPlatform::setHttpResponse($response);
			}
		}
		
		// [JAS]: Require us to always be logged in for Cerberus pages
		if(empty($visit) && 0 != strcasecmp(strval($controller),'login')) {
			$query = [];
			// Must be a valid page controller
			if(!empty($response->path)) {
				if(is_array($response->path) && !empty($response->path) && CerberusApplication::getPageManifestByUri(current($response->path))) {
					$url = implode('/', $response->path);
					
					if(array_key_exists('q', $_GET))
						$url .= '?' . http_build_query($_GET);
					
					$query = ['url' => $url];
				}
			}
			DevblocksPlatform::redirect(new DevblocksHttpRequest(array('login'),$query));
		}
		
		$page = null;
		if(null != ($page_manifest = CerberusApplication::getPageManifestByUri($controller))) {
			@$page = $page_manifest->createInstance(); /* @var $page CerberusPageExtension */
		}
		
		if(empty($page)) {
			CerberusApplication::respondWithErrorReason(CerbErrorReason::NotFound);
		}
		
		// [JAS]: Listeners (Step-by-step guided tour, etc.)
		$listenerManifests = DevblocksPlatform::getExtensions('devblocks.listener.http');
		foreach($listenerManifests as $listenerManifest) { /* @var $listenerManifest DevblocksExtensionManifest */
			if(null == ($inst = $listenerManifest->createInstance())) /* @var $inst DevblocksHttpResponseListenerExtension */
				continue;
			$inst->run($response, $tpl);
		}

		$tpl->assign('active_worker', $active_worker);
		
		if(!empty($visit) && !is_null($active_worker)) {
			$keyboard_shortcuts = intval(DAO_WorkerPref::get($active_worker->id,'keyboard_shortcuts',1));
			$tpl->assign('pref_keyboard_shortcuts', $keyboard_shortcuts);
			
			$dark_mode = intval(DAO_WorkerPref::get($active_worker->id,'dark_mode',0));
			$tpl->assign('pref_dark_mode', $dark_mode);
			
			$active_worker_memberships = $active_worker->getMemberships();
			$tpl->assign('active_worker_memberships', $active_worker_memberships);
		}
		
		// [JAS]: Variables provided to all page templates
		$tpl->assign('settings', $settings);
		$tpl->assign('session', $_SESSION);
		$tpl->assign('translate', $translate);
		$tpl->assign('visit', $visit);
		
		$tpl->assign('page_manifests',$page_manifests);
		$tpl->assign('page',$page);

		$tpl->assign('response_path', $response->path);
		$tpl->assign('response_uri', implode('/', $response->path));
		
		// Prebody Renderers
		$preBodyRenderers = DevblocksPlatform::getExtensions('cerberusweb.renderer.prebody', true);
		if(!empty($preBodyRenderers))
			$tpl->assign('prebody_renderers', $preBodyRenderers);

		// Postbody Renderers
		$postBodyRenderers = DevblocksPlatform::getExtensions('cerberusweb.renderer.postbody', true);
		if(!empty($postBodyRenderers))
			$tpl->assign('postbody_renderers', $postBodyRenderers);
		
		// Timings
		$tpl->assign('render_time', (microtime(true) - DevblocksPlatform::getStartTime()));
		if(function_exists('memory_get_usage') && function_exists('memory_get_peak_usage')) {
			$tpl->assign('render_memory', memory_get_usage() - DevblocksPlatform::getStartMemory());
			$tpl->assign('render_peak_memory', memory_get_peak_usage() - DevblocksPlatform::getStartPeakMemory());
		}

		// Conversational interactions
		$global_menu = Toolbar_GlobalMenu::getInteractionsMenu();
		$tpl->assign('global_interactions_show', null != $global_menu);
		
		if(!empty($active_worker)) {
			// Proactive interactions
			$proactive_interactions_count = DAO_BotInteractionProactive::getCountByWorker($active_worker->id);
			$tpl->assign('proactive_interactions_count', $proactive_interactions_count);
			
			// Pages menu
			$pages_menu = $active_worker->getPagesMenu();
			$tpl->assign('pages_menu', $pages_menu);
		}
		
		// Template
		$tpl->display('devblocks:cerberusweb.core::border.tpl');
		
		if(!empty($active_worker)) {
			$unread_notifications = DAO_Notification::getUnreadCountByWorker($active_worker->id);
			$tpl->assign('active_worker_notify_count', $unread_notifications);
			$tpl->display('devblocks:cerberusweb.core::badge_notifications_script.tpl');
		}
	}

	private function _handleBrandingRequest($resource) {
		switch($resource) {
			case 'logo':
			case 'logo-dark':
				$logo = null;
				$resource_content = null;

				// Load the dark logo if requested
				if($resource == 'logo-dark')
					$logo = DAO_Resource::getByName('ui.logo.dark');

				// Otherwise, load the light logo
				if(!$logo)
					$logo = DAO_Resource::getByName('ui.logo');

				// If we have a logo resource
				if($logo && $logo->extension_id == ResourceType_Image::ID)
					$resource_content = $logo->getExtension()->getContentData($logo);

				// If we don't have a logo resource, use the Cerb logo defaults
				if(!($resource_content instanceof Model_Resource_ContentData)) {
					$resource_content = new Model_Resource_ContentData();

					$resource_content->headers = [
						'Content-Type: image/svg+xml',
					];

					$plugin = DevblocksPlatform::getPlugin('cerberusweb.core');
					$dir = $plugin->getStoragePath() . DIRECTORY_SEPARATOR . 'resources';

					if($resource == 'logo-dark') {
						$logo_path = $dir . DIRECTORY_SEPARATOR . 'images/wgm/cerb_logo_dark.svg';
					} else {
						$logo_path = $dir . DIRECTORY_SEPARATOR . 'images/wgm/cerb_logo.svg';
					}

					$resource_content->data = fopen($logo_path, 'rb');
				}

				// If no expiration, synthesize 1d
				if(!$resource_content->expires_at)
					$resource_content->expires_at = time() + 86400; // 1 day

				$resource_content->headers = array_merge($resource_content->headers, [
					'Pragma: cache',
					sprintf('Cache-control: max-age=%d', $resource_content->expires_at - time()),
					'Expires: ' . gmdate('D, d M Y H:i:s', $resource_content->expires_at) . ' GMT',
					'Accept-Ranges: bytes',
				]);

				// Pass through
				if($resource_content instanceof Model_Resource_ContentData) {
					$resource_content->writeHeaders();
					$resource_content->writeBody();
				}
				break;

			case 'stylesheet':
				DevblocksPlatform::services()->http()
					->setHeader('Cache-Control', ' max-age=86400') // 1 day // , must-revalidate
					->setHeader('Content-Type', 'text/css')
					->setHeader('Expires', gmdate('D, d M Y H:i:s',time()+86400) . ' GMT') // 1 day
				;

				echo DevblocksPlatform::getPluginSetting('cerberusweb.core', CerberusSettings::UI_USER_STYLESHEET, '');
				break;

			default:
				DevblocksPlatform::dieWithHttpError(null, 404);
		}
	}
};