<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2011, WebGroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Cerberus Public License.
| The latest version of this license can be found here:
| http://www.cerberusweb.com/license.php
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerberusweb.com	  http://www.webgroupmedia.com/
***********************************************************************/
/*
 * IMPORTANT LICENSING NOTE from your friends on the Cerberus Helpdesk Team
 * 
 * Sure, it would be so easy to just cheat and edit this file to use the 
 * software without paying for it.  But we trust you anyway.  In fact, we're 
 * writing this software for you! 
 * 
 * Quality software backed by a dedicated team takes money to develop.  We 
 * don't want to be out of the office bagging groceries when you call up 
 * needing a helping hand.  We'd rather spend our free time coding your 
 * feature requests than mowing the neighbors' lawns for rent money. 
 * 
 * We've never believed in hiding our source code out of paranoia over not 
 * getting paid.  We want you to have the full source code and be able to 
 * make the tweaks your organization requires to get more done -- despite 
 * having less of everything than you might need (time, people, money, 
 * energy).  We shouldn't be your bottleneck.
 * 
 * We've been building our expertise with this project since January 2002.  We 
 * promise spending a couple bucks [Euro, Yuan, Rupees, Galactic Credits] to 
 * let us take over your shared e-mail headache is a worthwhile investment.  
 * It will give you a sense of control over your inbox that you probably 
 * haven't had since spammers found you in a game of 'E-mail Battleship'. 
 * Miss. Miss. You sunk my inbox!
 * 
 * A legitimate license entitles you to support from the developers,  
 * and the warm fuzzy feeling of feeding a couple of obsessed developers 
 * who want to help you get more done.
 *
 * - Jeff Standen, Darren Sugita, Dan Hildebrandt, Scott Luther,
 * 		and Jerry Kanoholani. 
 *	 WEBGROUP MEDIA LLC. - Developers of Cerberus Helpdesk
 */

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

	    @$action = DevblocksPlatform::strAlphaNumDash(array_shift($path)) . 'Action';

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
		// [JAS]: Ajax? // [TODO] Explore outputting whitespace here for Safari
//	    if(empty($path))
//			return;

		$tpl = DevblocksPlatform::getTemplateService();
		$session = DevblocksPlatform::getSessionService();
		$settings = DevblocksPlatform::getPluginSettingsService();
		$translate = DevblocksPlatform::getTranslationService();
	    $active_worker = CerberusApplication::getActiveWorker();
		
		$visit = $session->getVisit();
		$page_manifests = $this->_getAllowedPages();

		$controller = array_shift($path);

		// Default page [TODO] This is supposed to come from framework.config.php
		if(empty($controller)) 
			$controller = 'tickets';

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
			
			$unread_notifications = DAO_Notification::getUnreadCountByWorker($active_worker->id);
			$tpl->assign('active_worker_notify_count', $unread_notifications);
			
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
		
		$tpl->display('devblocks:cerberusweb.core::border.tpl');
		
//		$cache = DevblocksPlatform::getCacheService();
//		$cache->printStatistics();
	}
};

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
            
            $escapedSubject = htmlspecialchars($event[SearchFields_Notification::MESSAGE],null,LANG_CHARSET_CODE);
            $eDesc = $eItem->addChild('description', '');

            if(isset($event[SearchFields_Notification::URL])) {
//	            $link = $event[SearchFields_Notification::URL];
	            $link = $url->write('c=preferences&a=redirectRead&id='.$event[SearchFields_Notification::ID], true);
	            $eLink = $eItem->addChild('link', $link);
	            
            } else {
	            $link = $url->write('c=activity&tab=events', true);
	            $eLink = $eItem->addChild('link', $link);
            	
            }
            	
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
            
            $link = $url->write('c=display&id='.$ticket[SearchFields_Ticket::TICKET_MASK], true);
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

            $link = $url->write('c=tasks&a=display&id='.$task[SearchFields_Task::ID], true);
            $eLink = $eItem->addChild('link', $link);
            	
            $eDate = $eItem->addChild('pubDate', gmdate('D, d M Y H:i:s T', $created));
            
            $eGuid = $eItem->addChild('guid', md5($escapedSubject . $link . $created));
            $eGuid->addAttribute('isPermaLink', "false");
        }

        return $xml->asXML();
	}
};
