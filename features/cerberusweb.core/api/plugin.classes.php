<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2013, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerberusweb.com/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerberusweb.com	  http://www.webgroupmedia.com/
***********************************************************************/

class ChPageController extends DevblocksControllerExtension {
	const ID = 'core.controller.page';
	
	// [TODO] We probably need a CerberusApplication scope for getting content that has ACL applied
	private function _getAllowedPages() {
		$active_worker = CerberusApplication::getActiveWorker();
		$page_manifests = DevblocksPlatform::getExtensions('cerberusweb.page', false);

		// [TODO] This may cause problems on other pages where an active worker isn't required
		// Check RSS/etc (was bugged on login)
		
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
		$controller = array_shift($path);

		$page = null;
		if(null != ($page_manifest = CerberusApplication::getPageManifestByUri($controller))) {
			$page = $page_manifest->createInstance(); /* @var $page CerberusPageExtension */
		}

		if(empty($page)) {
			switch($controller) {
				case "portal":
					header("Status: 404");
					die(); // 404
					break;
					
				default:
					return; // default page
					break;
			}
		}

		@$action = DevblocksPlatform::strAlphaNum(array_shift($path), '\_') . 'Action';

		switch($action) {
			case NULL:
				// [TODO] Index/page render
				break;
				
			default:
				// Default action, call arg as a method suffixed with Action
				
				if($page->isVisible()) {
					if(method_exists($page,$action)) {
						call_user_func(array($page, $action)); // [TODO] Pass HttpRequest as arg?
					}
				} else {
					// if Ajax [TODO] percolate isAjax from platform to handleRequest
					// die("Access denied.  Session expired?");
				}

				break;
		}
	}
	
	public function writeResponse(DevblocksHttpResponse $response) {
		$path = $response->path;

		$tpl = DevblocksPlatform::getTemplateService();
		$session = DevblocksPlatform::getSessionService();
		$settings = DevblocksPlatform::getPluginSettingsService();
		$translate = DevblocksPlatform::getTranslationService();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$visit = $session->getVisit();
		$page_manifests = $this->_getAllowedPages();

		$controller = array_shift($path);

		// Default page
		if(empty($controller)) {
			if(is_a($active_worker, 'Model_Worker')) {
				$controller = 'pages';
				$path = array('pages');
				
				// Find the worker's first page
				
				if(null != ($menu_json = DAO_WorkerPref::get($active_worker->id, 'menu_json', null))) {
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
		if(empty($visit) && 0 != strcasecmp($controller,'login')) {
			$query = array();
			// Must be a valid page controller
			if(!empty($response->path)) {
				if(is_array($response->path) && !empty($response->path) && CerberusApplication::getPageManifestByUri(current($response->path)))
					$query = array('url'=> urlencode(implode('/',$response->path)));
			}
			DevblocksPlatform::redirect(new DevblocksHttpRequest(array('login'),$query));
		}
		
		$page = null;
		if(null != ($page_manifest = CerberusApplication::getPageManifestByUri($controller))) {
			@$page = $page_manifest->createInstance(); /* @var $page CerberusPageExtension */
		}
		
		if(empty($page)) {
			//header("HTTP/1.1 404 Not Found");
			//header("Status: 404 Not Found");
			//DevblocksPlatform::redirect(new DevblocksHttpResponse(''));
			$tpl->assign('settings', $settings);
			$tpl->assign('session', $_SESSION);
			$tpl->assign('translate', $translate);
			$tpl->assign('visit', $visit);
			$tpl->display('devblocks:cerberusweb.core::404.tpl');
			return;
		}
		
		// [JAS]: Listeners (Step-by-step guided tour, etc.)
		$listenerManifests = DevblocksPlatform::getExtensions('devblocks.listener.http');
		foreach($listenerManifests as $listenerManifest) { /* @var $listenerManifest DevblocksExtensionManifest */
			 $inst = $listenerManifest->createInstance(); /* @var $inst DevblocksHttpRequestListenerExtension */
			 $inst->run($response, $tpl);
		}

		$tpl->assign('active_worker', $active_worker);
		$tour_enabled = false;
		
		if(!empty($visit) && !is_null($active_worker)) {
			$tour_enabled = intval(DAO_WorkerPref::get($active_worker->id, 'assist_mode', 1));

			$keyboard_shortcuts = intval(DAO_WorkerPref::get($active_worker->id,'keyboard_shortcuts',1));
			$tpl->assign('pref_keyboard_shortcuts', $keyboard_shortcuts);
			
			$active_worker_memberships = $active_worker->getMemberships();
			$tpl->assign('active_worker_memberships', $active_worker_memberships);
			
			DAO_Worker::logActivity($page->getActivity());
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

		// Contexts
		$contexts = Extension_DevblocksContext::getAll(false);
		$tpl->assign('contexts', $contexts);

		$tpl->display('devblocks:cerberusweb.core::border.tpl');
		
		if(!empty($active_worker)) {
			$unread_notifications = DAO_Notification::getUnreadCountByWorker($active_worker->id);
			$tpl->assign('active_worker_notify_count', $unread_notifications);
			$tpl->display('devblocks:cerberusweb.core::badge_notifications_script.tpl');
		}
	}
};

if(class_exists('Extension_DevblocksEventAction')):
class VaAction_HttpRequest extends Extension_DevblocksEventAction {
	function render(Extension_DevblocksEvent $event, Model_TriggerEvent $trigger, $params=array(), $seq=null) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('params', $params);
		
		if(!is_null($seq))
			$tpl->assign('namePrefix', 'action'.$seq);
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_action_http_request.tpl');
	}
	
	function simulate($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();

		$out = null;
		
		@$http_verb = $params['http_verb'];
		@$http_url = $tpl_builder->build($params['http_url'], $dict);
		@$http_body = $tpl_builder->build($params['http_body'], $dict);
		@$run_in_simulator = $params['run_in_simulator'];
		@$response_placeholder = $params['response_placeholder'];
		
		if(empty($http_verb))
			return "[ERROR] HTTP verb is required.";
		
		if(empty($http_url))
			return "[ERROR] HTTP URL is required.";
		
		if(empty($response_placeholder))
			return "[ERROR] No result placeholder given.";
		
		// Output
		$out = sprintf(">>> Sending HTTP request:\n%s %s\n%s\n",
			mb_convert_case($http_verb, MB_CASE_UPPER),
			$http_url,
			(in_array($http_verb, array('post','put')) ? ("\n" . $http_body. "\n") : "")
		);
		
		$out .= sprintf(">>> Saving response to {{%s}}\n",
			$response_placeholder
		);

		// If set to run in simulator as well
		if($run_in_simulator) {
			$response = $this->_execute($http_verb, $http_url, array(), $http_body);
			$dict->$response_placeholder = $response;
		}
		
		return $out;
	}
	
	function run($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();

		@$http_verb = $params['http_verb'];
		@$http_url = $tpl_builder->build($params['http_url'], $dict);
		@$http_body = $tpl_builder->build($params['http_body'], $dict);
		@$response_placeholder = $params['response_placeholder'];
		
		if(empty($http_verb) || empty($http_url))
			return false;
		
		if(empty($response_placeholder))
			return false;
		
		$response = $this->_execute($http_verb, $http_url, array(), $http_body);
		$dict->$response_placeholder = $response;
	}
	
	private function _execute($verb, $url, $params=array(), $body=null) {
		switch($verb) {
			case 'get':
				return $this->_get($url, $params);
				break;
				
			case 'post':
			case 'put':
				return $this->_post($url, $params, $body, $verb);
				break;
				
			case 'delete':
				// [TODO]
				break;
		}
		
	}
	
	private function _post($url, $params=array(), $body=null, $verb='post') {
		if(!empty($params) && is_array($params))
			$url .= '?' . http_build_query($params);
		
		$ch = curl_init($url);
		
		$headers = array();
		
		//$headers[] = 'Content-Type: application/json';
		
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		
		switch($verb) {
			case 'post':
				curl_setopt($ch, CURLOPT_POST, 1);
				break;
				
			case 'put':
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
				curl_setopt($ch, CURLOPT_POST, 1);
				break;
		}
		
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(json_decode($body, true)));
		
		if(!empty($headers))
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		$out = curl_exec($ch);
		
		$info = curl_getinfo($ch);

		// [TODO] This can fail without HTTPS
		
		if(curl_errno($ch)) {
			
		} else {
			switch(@$info['content_type']) {
				case 'application/json':
					@$out = json_decode($out, true);
					break;
			}
		}
		
		curl_close($ch);
		return array(
			'content_type' => $info['content_type'],
			'body' => $out,
		);
	}
	
	private function _get($url, $params=array()) {
		if(!empty($params) && is_array($params))
			$url .= '?' . http_build_query($params);
		
		$ch = curl_init($url);
		
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$out = curl_exec($ch);

		$info = curl_getinfo($ch);
		
		if(curl_errno($ch)) {
			
		} else {
			switch(@$info['content_type']) {
				case 'application/json':
					@$out = json_decode($out, true);
					break;
					
				case 'image/gif':
				case 'image/jpeg':
				case 'image/jpg':
				case 'image/png':
					@$out = base64_encode($out);
					break;
			}
		}
		
		curl_close($ch);
		return array(
			'content_type' => $info['content_type'],
			'body' => $out,
		);
	}
};
endif;

// RSS Sources

class ChRssSource_Notification extends Extension_RssSource {
	function getSourceName() {
		return "Notifications";
	}
	
	function getFeedAsRss($feed) {
		$xmlstr = <<<XML
		<rss version='2.0' xmlns:atom='http://www.w3.org/2005/Atom'>
		</rss>
XML;

		$xml = new SimpleXMLElement($xmlstr);
		$translate = DevblocksPlatform::getTranslationService();
		$url = DevblocksPlatform::getUrlService();

		// Channel
		$channel = $xml->addChild('channel');
		$channel->addChild('title', $feed->title);
		$channel->addChild('link', $url->write('',true));
		$channel->addChild('description', '');
		
		// View
		$view = new View_Notification();
		$view->name = $feed->title;
		$view->addParams($feed->params['params'], true);
		$view->renderLimit = 100;
		$view->renderSortBy = $feed->params['sort_by'];
		$view->renderSortAsc = $feed->params['sort_asc'];

		// Results
		list($results, $count) = $view->getData();

		foreach($results as $event) {
			$created = intval($event[SearchFields_Notification::CREATED_DATE]);
			if(empty($created)) $created = time();

			$eItem = $channel->addChild('item');
			

			if(isset($event[SearchFields_Notification::URL])) {
				$link = $url->write('c=preferences&a=redirectRead&id='.$event[SearchFields_Notification::ID], true);
			} else {
				$link = $url->write('c=profiles&type=worker&who=me', true);
			}
			
			$escapedSubject = htmlspecialchars($event[SearchFields_Notification::MESSAGE],null,LANG_CHARSET_CODE);
			$eTitle = $eItem->addChild('title', $escapedSubject);
			$eDesc = $eItem->addChild('description', '');
			$eLink = $eItem->addChild('link', $link);

			$eDate = $eItem->addChild('pubDate', gmdate('D, d M Y H:i:s T', $created));
			$eGuid = $eItem->addChild('guid', md5($escapedSubject . $link . $created));
			$eGuid->addAttribute('isPermaLink', "false");
		}

		return $xml->asXML();
	}
};

class ChRssSource_Ticket extends Extension_RssSource {
	function getSourceName() {
		return "Tickets";
	}
	
	function getFeedAsRss($feed) {
		$xmlstr = <<<XML
		<rss version='2.0' xmlns:atom='http://www.w3.org/2005/Atom'>
		</rss>
XML;

		$xml = new SimpleXMLElement($xmlstr);
		$translate = DevblocksPlatform::getTranslationService();
		$url = DevblocksPlatform::getUrlService();

		// Channel
		$channel = $xml->addChild('channel');
		$channel->addChild('title', $feed->title);
		$channel->addChild('link', $url->write('',true));
		$channel->addChild('description', '');
		
		// View
		$view = new View_Ticket();
		$view->name = $feed->title;
		$view->addParams($feed->params['params'], true);
		$view->renderLimit = 100;
		$view->renderSortBy = $feed->params['sort_by'];
		$view->renderSortAsc = $feed->params['sort_asc'];

		// Results
		list($tickets, $count) = $view->getData();
		
		foreach($tickets as $ticket) {
			$created = intval($ticket[SearchFields_Ticket::TICKET_UPDATED_DATE]);
			if(empty($created)) $created = time();

			$eItem = $channel->addChild('item');
			
			$escapedSubject = htmlspecialchars($ticket[SearchFields_Ticket::TICKET_SUBJECT],null,LANG_CHARSET_CODE);
			$eTitle = $eItem->addChild('title', $escapedSubject);

			$eDesc = $eItem->addChild('description', $this->_getTicketLastAction($ticket));
			
			$link = $url->write('c=profiles&type=ticket&id='.$ticket[SearchFields_Ticket::TICKET_MASK], true);
			$eLink = $eItem->addChild('link', $link);
				
			$eDate = $eItem->addChild('pubDate', gmdate('D, d M Y H:i:s T', $created));
			
			$eGuid = $eItem->addChild('guid', md5($escapedSubject . $link . $created));
			$eGuid->addAttribute('isPermaLink', "false");
		}

		return $xml->asXML();
	}
	
	private function _getTicketLastAction($ticket) {
		$action_code = $ticket[SearchFields_Ticket::TICKET_LAST_ACTION_CODE];
		$output = '';
		
		// [TODO] Translate
		switch($action_code) {
			case CerberusTicketActionCode::TICKET_OPENED:
				$output = sprintf("New from %s",
					$ticket[SearchFields_Ticket::TICKET_LAST_WROTE]
				);
				break;
			case CerberusTicketActionCode::TICKET_CUSTOMER_REPLY:
				$output = sprintf("Incoming from %s",
					$ticket[SearchFields_Ticket::TICKET_LAST_WROTE]
				);
				break;
			case CerberusTicketActionCode::TICKET_WORKER_REPLY:
				$output = sprintf("Outgoing from %s",
					$ticket[SearchFields_Ticket::TICKET_LAST_WROTE]
				);
				break;
		}
		
		return $output;
	}
	
};

class ChRssSource_Task extends Extension_RssSource {
	function getSourceName() {
		return "Tasks";
	}
	
	function getFeedAsRss($feed) {
		$xmlstr = <<<XML
		<rss version='2.0' xmlns:atom='http://www.w3.org/2005/Atom'>
		</rss>
XML;

		$xml = new SimpleXMLElement($xmlstr);
		$translate = DevblocksPlatform::getTranslationService();
		$url = DevblocksPlatform::getUrlService();

		// Channel
		$channel = $xml->addChild('channel');
		$channel->addChild('title', $feed->title);
		$channel->addChild('link', $url->write('',true));
		$channel->addChild('description', '');
		
		// View
		$view = new View_Task();
		$view->name = $feed->title;
		$view->addParams($feed->params['params'], true);
		$view->renderLimit = 100;
		$view->renderSortBy = $feed->params['sort_by'];
		$view->renderSortAsc = $feed->params['sort_asc'];

		// Results
		list($results, $count) = $view->getData();

		$task_sources = DevblocksPlatform::getExtensions('cerberusweb.task.source',true);
		
		foreach($results as $task) {
			$created = intval($task[SearchFields_Task::UPDATED_DATE]);
			if(empty($created)) $created = time();

			$eItem = $channel->addChild('item');
			
			$escapedSubject = htmlspecialchars($task[SearchFields_Task::TITLE],null,LANG_CHARSET_CODE);
			$escapedSubject = mb_convert_encoding($escapedSubject, 'utf-8', LANG_CHARSET_CODE);
			$eTitle = $eItem->addChild('title', $escapedSubject);

			$eDesc = $eItem->addChild('description', '');

			$link = $url->write('c=profiles&type=task&id='.$task[SearchFields_Task::ID], true);
			$eLink = $eItem->addChild('link', $link);
				
			$eDate = $eItem->addChild('pubDate', gmdate('D, d M Y H:i:s T', $created));
			
			$eGuid = $eItem->addChild('guid', md5($escapedSubject . $link . $created));
			$eGuid->addAttribute('isPermaLink', "false");
		}

		return $xml->asXML();
	}
};
