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
				case 'portal':
					return DevblocksPlatform::dieWithHttpError(null, 404);
				default:
					return true;
			}
		}
		
		$action = array_shift($path);
		
		if(!is_null($action)) {
			if($page->isVisible()) {
				$page->invoke($action);
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
			$tpl->assign('settings', $settings);
			$tpl->assign('session', $_SESSION ?? []);
			$tpl->assign('translate', $translate);
			$tpl->assign('visit', $visit);
			
			if($active_worker) {
				$tpl->assign('pref_dark_mode', DAO_WorkerPref::get($active_worker->id, 'dark_mode', 0));
			}
				
			$message = $tpl->fetch('devblocks:cerberusweb.core::404_page.tpl');
			
			DevblocksPlatform::dieWithHttpErrorHtml($message, 404);
			return;
		}
		
		// [JAS]: Listeners (Step-by-step guided tour, etc.)
		$listenerManifests = DevblocksPlatform::getExtensions('devblocks.listener.http');
		foreach($listenerManifests as $listenerManifest) { /* @var $listenerManifest DevblocksExtensionManifest */
			if(null == ($inst = $listenerManifest->createInstance())) /* @var $inst DevblocksHttpResponseListenerExtension */
				continue;
			$inst->run($response, $tpl);
		}

		$tpl->assign('active_worker', $active_worker);
		$tour_enabled = false;
		
		if(!empty($visit) && !is_null($active_worker)) {
			$tour_enabled = intval(DAO_WorkerPref::get($active_worker->id, 'assist_mode', 1));

			$keyboard_shortcuts = intval(DAO_WorkerPref::get($active_worker->id,'keyboard_shortcuts',1));
			$tpl->assign('pref_keyboard_shortcuts', $keyboard_shortcuts);
			
			$dark_mode = intval(DAO_WorkerPref::get($active_worker->id,'dark_mode',0));
			$tpl->assign('pref_dark_mode', $dark_mode);
			
			$active_worker_memberships = $active_worker->getMemberships();
			$tpl->assign('active_worker_memberships', $active_worker_memberships);
		}
		$tpl->assign('tour_enabled', $tour_enabled);
		
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
};