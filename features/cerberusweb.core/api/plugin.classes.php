<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2011, WebGroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
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

class Event_NotificationReceivedByWorker extends Extension_DevblocksEvent {
	const ID = 'event.notification.received.owner';
	
	static function trigger($notification_id, $worker_id) {
		$events = DevblocksPlatform::getEventService();
		$events->trigger(
	        new Model_DevblocksEvent(
	            self::ID,
                array(
                    'notification_id' => $notification_id,
                	'_whisper' => array(
                		CerberusContexts::CONTEXT_WORKER => array($worker_id),
                	),
                )
            )
		);
	} 
	
	function setEvent(Model_DevblocksEvent $event_model=null) {
		@$notification_id = $event_model->params['notification_id']; 
		
		$labels = array();
		$values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_NOTIFICATION, $notification_id, $labels, $values, null, true);
		
		$this->setLabels($labels);
		$this->setValues($values);
	}
	
	function getConditionExtensions() {
		$labels = $this->getLabels();
		
		// [TODO] Move this into snippets somehow
		$types = array(
			'created|date' => Model_CustomField::TYPE_DATE,
			'message' => Model_CustomField::TYPE_SINGLE_LINE,
			'is_read' => Model_CustomField::TYPE_CHECKBOX,
			'url' => Model_CustomField::TYPE_SINGLE_LINE,
			'assignee_full_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'assignee_first_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'assignee_last_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'assignee_title' => Model_CustomField::TYPE_SINGLE_LINE,
			'assignee_address_address' => Model_CustomField::TYPE_SINGLE_LINE,
			'assignee_address_num_nonspam' => Model_CustomField::TYPE_NUMBER,
			'assignee_address_num_spam' => Model_CustomField::TYPE_NUMBER,
			'assignee_address_is_banned' => Model_CustomField::TYPE_CHECKBOX,
			'assignee_address_org_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'assignee_address_org_created' => Model_CustomField::TYPE_DATE,
			'assignee_address_org_city' => Model_CustomField::TYPE_SINGLE_LINE,
			'assignee_address_org_country' => Model_CustomField::TYPE_SINGLE_LINE,
			'assignee_address_org_province' => Model_CustomField::TYPE_SINGLE_LINE,
			'assignee_address_org_postal' => Model_CustomField::TYPE_SINGLE_LINE,
			'assignee_address_org_phone' => Model_CustomField::TYPE_SINGLE_LINE,
			'assignee_address_org_street' => Model_CustomField::TYPE_SINGLE_LINE,
			'assignee_address_org_website' => Model_CustomField::TYPE_SINGLE_LINE,
		);

		$conditions = $this->_importLabelsTypesAsConditions($labels, $types);
		
		return $conditions;		
	}
	
	function renderConditionExtension($token, $params=array(), $seq=null) {
		$conditions = $this->getConditions();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('params', $params);

		if(!is_null($seq))
			$tpl->assign('namePrefix','condition'.$seq);
		
		//$tpl->display('devblocks:cerberusweb.core::internal/decisions/conditions/_bool.tpl');
	}
	
	function runConditionExtension($token, $params, $values) {
		$pass = true;
		
		switch($token) {
			default:
				$pass = false;
				//var_dump('unimplemented');
				break;
		}
		
		return $pass;
	}
	
	function getActionExtensions() { // $id
		$actions = array(
			'send_email_owner' => array('label' => 'Send email to me'),
			'create_task' => array('label' =>'Create a task'),
		);
		return $actions;
	}
	
	function renderActionExtension($token, $trigger_id=null, $params=array(), $seq=null) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('params', $params);

		if(!is_null($seq))
			$tpl->assign('namePrefix','action'.$seq);

		$labels = $this->getLabels();
		$tpl->assign('token_labels', $labels);
			
		switch($token) {
			case 'send_email_owner':
				$tpl->display('devblocks:cerberusweb.core::events/notification_received_by_owner/action_send_email_owner.tpl');
				break;
				
			case 'create_task':
				DevblocksEventHelper::renderActionCreateTask();
				break;
		}
			
		//$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_set_string.tpl');
	}
	
	function runActionExtension($token, $trigger_id, $params, &$values) {
		@$notification_id = $values['id'];

		if(empty($notification_id))
			return;
		
		switch($token) {
			case 'send_email_owner':
				@$to = $values['assignee_address_address'];
				
				if(
					empty($to)
					|| !isset($params['subject'])
					|| !isset($params['content'])
				)
					break;
					
				// Translate message tokens
				$tpl_builder = DevblocksPlatform::getTemplateBuilder();
				$subject = strtr($tpl_builder->build($params['subject'], $values), "\r\n", ' '); // no CRLF
				$content = $tpl_builder->build($params['content'], $values);

				CerberusMail::quickSend(
					$to,
					$subject,
					$content
				);
				break;
				
			case 'create_task':
				DevblocksEventHelper::runActionCreateTask($params, $values); //, CerberusContexts::CONTEXT_NOTIFICATION, $values['id']
				break;				
		}
	}
	
};

class Event_MailReceivedByWatcher extends Extension_DevblocksEvent {
	const ID = 'event.mail.received.owner';
	
	static function trigger($message_id, $worker_id) {
		$events = DevblocksPlatform::getEventService();
		$events->trigger(
	        new Model_DevblocksEvent(
	            self::ID,
                array(
                	'message_id' => $message_id,
                    'worker_id' => $worker_id,
                	'_whisper' => array(
                		CerberusContexts::CONTEXT_WORKER => array($worker_id),
                	),
                )
            )
		);
	} 

	/**
	 * 
	 * @param integer $message_id
	 * @param integer $worker_id
	 * @return Model_DevblocksEvent
	 */
	function generateSampleEventModel($message_id=null, $worker_id=null) {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(empty($message_id)) {
			// Pull the latest ticket
			list($results) = DAO_Ticket::search(
				array(),
				array(
					new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CLOSED,'=',0),
				),
				10,
				0,
				SearchFields_Ticket::TICKET_ID,
				false,
				false
			);
			
			shuffle($results);
			
			$result = array_shift($results);
			
			$message_id = $result[SearchFields_Ticket::TICKET_LAST_MESSAGE_ID];
			$worker_id = $active_worker->id;
		}
		
		return new Model_DevblocksEvent(
			self::ID,
			array(
				'message_id' => $message_id,
				'worker_id' => $worker_id,
			)
		);
	}	
	
	function setEvent(Model_DevblocksEvent $event_model=null) {
		@$message_id = $event_model->params['message_id']; 
		@$worker_id = $event_model->params['worker_id'];
		 
		$labels = array();
		$values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_MESSAGE, $message_id, $labels, $values, null, true);

		$values['sender_is_worker'] = (!empty($values['worker_id'])) ? 1 : 0;
		$values['sender_is_me'] = (!empty($worker_id) && isset($values['worker_id']) && $worker_id==$values['worker_id']) ? 1 : 0;
		
		/**
		 * Ticket
		 */
		@$ticket_id = $values['ticket_id']; 
		$ticket_labels = array();
		$ticket_values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_TICKET, $ticket_id, $ticket_labels, $ticket_values, null, true);

			// Fill some custom values
			if(!is_null($event_model)) {
				$values['is_first'] = ($values['id'] == $ticket_values['initial_message_id']) ? 1 : 0;
			}
		
			// Clear dupe content
			CerberusContexts::scrubTokensWithRegexp(
				$ticket_labels,
				$ticket_values,
				array(
					"#^initial_message_#",
					"#^latest_message_#",
					"#^id$#",
				)
			);
			
			// Merge
			CerberusContexts::merge(
				'ticket_',
				'',
				$ticket_labels,
				$ticket_values,
				$labels,
				$values
			);
				
			
		/**
		 * Sender Worker
		 */
		@$worker_id = $values['worker_id'];
		$worker_labels = array();
		$worker_values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, $worker_id, $worker_labels, $worker_values, null, true);
				
			// Clear dupe content
			CerberusContexts::scrubTokensWithRegexp(
				$worker_labels,
				$worker_values,
				array(
					"#^address_org_#",
				)
			);
		
			// Merge
			CerberusContexts::merge(
				'sender_worker_',
				'Message sender ',
				$worker_labels,
				$worker_values,
				$labels,
				$values
			);
						
		$this->setLabels($labels);
		$this->setValues($values);
	}
	
	function getConditionExtensions() {
		$labels = $this->getLabels();
		
		$labels['is_first'] = 'Message is first in conversation';
		$labels['sender_is_worker'] = 'Message sender is a worker';
		$labels['sender_is_me'] = 'Message sender is me';
		
		$types = array(
			'content' => Model_CustomField::TYPE_MULTI_LINE,
			'created|date' => Model_CustomField::TYPE_DATE,
			'is_first' => Model_CustomField::TYPE_CHECKBOX,
			'is_outgoing' => Model_CustomField::TYPE_CHECKBOX,
			'sender_address' => Model_CustomField::TYPE_SINGLE_LINE,
			'sender_first_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'sender_full_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'sender_is_banned' => Model_CustomField::TYPE_CHECKBOX,
			'sender_is_worker' => Model_CustomField::TYPE_CHECKBOX,
			'sender_is_me' => Model_CustomField::TYPE_CHECKBOX,
			'sender_last_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'sender_num_nonspam' => Model_CustomField::TYPE_NUMBER,
			'sender_num_spam' => Model_CustomField::TYPE_NUMBER,
			'sender_org_city' => Model_CustomField::TYPE_SINGLE_LINE,
			'sender_org_country' => Model_CustomField::TYPE_SINGLE_LINE,
			'sender_org_created' => Model_CustomField::TYPE_DATE,
			'sender_org_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'sender_org_phone' => Model_CustomField::TYPE_SINGLE_LINE,
			'sender_org_postal' => Model_CustomField::TYPE_SINGLE_LINE,
			'sender_org_province' => Model_CustomField::TYPE_SINGLE_LINE,
			'sender_org_street' => Model_CustomField::TYPE_SINGLE_LINE,
			'sender_org_website' => Model_CustomField::TYPE_SINGLE_LINE,
			'sender_worker_address_address' => Model_CustomField::TYPE_SINGLE_LINE,
			'sender_worker_full_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'storage_size' => Model_CustomField::TYPE_NUMBER,
		
			"ticket_bucket_name|default('Inbox')" => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_created|date' => Model_CustomField::TYPE_DATE,
			'ticket_group_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_mask' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_subject' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_updated|date' => Model_CustomField::TYPE_DATE,
			'ticket_url' => Model_CustomField::TYPE_URL,
		);

		$conditions = $this->_importLabelsTypesAsConditions($labels, $types);
		
		return $conditions;
	}

	function renderConditionExtension($token, $params=array(), $seq=null) {
		return;
		
//		$conditions = $this->getConditions();
		
//		$tpl = DevblocksPlatform::getTemplateService();
//		$tpl->assign('params', $params);

//		if(!is_null($seq))
//			$tpl->assign('namePrefix','condition'.$seq);
		
//		$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_set_string.tpl');
	}

	function runConditionExtension($token, $params, $values) {
		$pass = true;
		
		switch($token) {
			default:
				$pass = false;
				break;
		}
		
		return $pass;
	}	
	
	function getActionExtensions() { // $id
		$actions = array(
			//'add_owners' => array('label' => 'Add owners'),
			//'set_spam_training' => array('label' => 'Set spam training'),
			//'set_status' => array('label' => 'Set status'),
			'set_subject' => array('label' => 'Set subject'),
			'send_email' => array('label' => 'Send email'),
			'send_email_recipients' => array('label' => 'Send email to recipients'),
			'create_comment' => array('label' =>'Create a comment'),
			'create_notification' => array('label' =>'Create a notification'),
			'create_task' => array('label' =>'Create a task'),
			'create_ticket' => array('label' =>'Create a ticket'),
		);
		
		// [TODO] Add set custom fields
		
		return $actions;
	}
	
	function renderActionExtension($token, $trigger_id=null, $params=array(), $seq=null) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('params', $params);

		if(!is_null($seq))
			$tpl->assign('namePrefix','action'.$seq);

		$labels = $this->getLabels();
		$tpl->assign('token_labels', $labels);
			
		switch($token) {
			case 'set_subject':
				$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_set_string.tpl');
				break;
				
			case 'send_email':
				DevblocksEventHelper::renderActionSendEmail();
				break;
				
			case 'send_email_recipients':
				$tpl->assign('workers', DAO_Worker::getAll());
				$tpl->display('devblocks:cerberusweb.core::events/mail_received_by_owner/action_send_email_recipients.tpl');
				break;
				
			case 'create_comment':
				DevblocksEventHelper::renderActionCreateComment();
				break;
				
			case 'create_notification':
				DevblocksEventHelper::renderActionCreateNotification();
				break;
				
			case 'create_task':
				DevblocksEventHelper::renderActionCreateTask();
				break;
				
			case 'create_ticket':
				DevblocksEventHelper::renderActionCreateTicket();
				break;
		}
		
		$tpl->clearAssign('params');
		$tpl->clearAssign('namePrefix');
		$tpl->clearAssign('token_labels');
	}
	
	function runActionExtension($token, $trigger_id, $params, &$values) {
		@$ticket_id = $values['ticket_id'];
		@$message_id = $values['id'];

		if(empty($ticket_id) || empty($message_id))
			return;
		
		switch($token) {
			case 'set_subject':
				DAO_Ticket::update($ticket_id,array(
					DAO_Ticket::SUBJECT => $params['value'],
				));
				break;
			
			case 'send_email':
				DevblocksEventHelper::runActionSendEmail($params, $values);
				break;
				
			case 'send_email_recipients':
				// Translate message tokens
				$tpl_builder = DevblocksPlatform::getTemplateBuilder();
				$content = $tpl_builder->build($params['content'], $values);
				
				$properties = array(
					'ticket_id' => $ticket_id,
					'message_id' => $message_id,
					'content' => $content,
					'agent_id' => 0, //$worker_id,
				);
				CerberusMail::sendTicketMessage($properties);
				break;
				
			case 'create_comment':
				DevblocksEventHelper::runActionCreateComment($params, $values, CerberusContexts::CONTEXT_TICKET, $ticket_id);
				break;
				
			case 'create_notification':
				$url_writer = DevblocksPlatform::getUrlService();
				$url = $url_writer->write('c=display&id='.$values['ticket_mask'], true);
				
				DevblocksEventHelper::runActionCreateNotification($params, $values, $url);
				break;
				
			case 'create_task':
				DevblocksEventHelper::runActionCreateTask($params, $values, CerberusContexts::CONTEXT_TICKET, $ticket_id);
				break;

			case 'create_ticket':
				DevblocksEventHelper::runActionCreateTicket($params, $values, CerberusContexts::CONTEXT_TICKET, $ticket_id);
				break;
		}
	}
};

class Event_MailClosedInGroup extends Extension_DevblocksEvent {
	const ID = 'event.mail.closed.group';
	
	static function trigger($ticket_id, $group_id) {
		$events = DevblocksPlatform::getEventService();
		$events->trigger(
	        new Model_DevblocksEvent(
	            self::ID,
                array(
                    'ticket_id' => $ticket_id,
                    'group_id' => $group_id,
                	'_whisper' => array(
                		CerberusContexts::CONTEXT_GROUP => array($group_id),
                	),
                )
            )
		);
	}
	
	/**
	 * 
	 * @param integer $ticket_id
	 * @param integer $group_id
	 * @return Model_DevblocksEvent
	 */
	function generateSampleEventModel($ticket_id=null, $group_id=null) {
		if(empty($ticket_id)) {
			// Pull the latest ticket
			list($results) = DAO_Ticket::search(
				array(),
				array(
					new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CLOSED,'=',0),
				),
				10,
				0,
				SearchFields_Ticket::TICKET_ID,
				false,
				false
			);
			
			shuffle($results);
			
			$result = array_shift($results);
			
			$ticket_id = $result[SearchFields_Ticket::TICKET_ID];
			$group_id = $result[SearchFields_Ticket::TICKET_TEAM_ID];
		}
		
		return new Model_DevblocksEvent(
			self::ID,
			array(
				'ticket_id' => $ticket_id,
				'group_id' => $group_id,
			)
		);
	}	
	
	function setEvent(Model_DevblocksEvent $event_model=null) {
		$labels = array();
		$values = array();

		/**
		 * Ticket
		 */
		
		@$ticket_id = $event_model->params['ticket_id']; 
		$ticket_labels = array();
		$ticket_values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_TICKET, $ticket_id, $ticket_labels, $ticket_values, null, true);

			// Merge
			CerberusContexts::merge(
				'ticket_',
				'',
				$ticket_labels,
				$ticket_values,
				$labels,
				$values
			);

		/**
		 * Group
		 */
		@$group_id = $values['group_id'];
		$group_labels = array();
		$group_values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_GROUP, $group_id, $group_labels, $group_values, null, true);
				
			// Merge
			CerberusContexts::merge(
				'group_',
				'',
				$group_labels,
				$group_values,
				$labels,
				$values
			);
		
		/**
		 * Return
		 */

		$this->setLabels($labels);
		$this->setValues($values);		
	}
	
	function getConditionExtensions() {
		$labels = $this->getLabels();
		
		$types = array(
			'ticket_initial_message_content' => Model_CustomField::TYPE_MULTI_LINE,
			'ticket_initial_message_created|date' => Model_CustomField::TYPE_DATE,
			'ticket_initial_message_is_outgoing' => Model_CustomField::TYPE_CHECKBOX,
			'ticket_initial_message_sender_address' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_initial_message_sender_first_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_initial_message_sender_full_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_initial_message_sender_is_banned' => Model_CustomField::TYPE_CHECKBOX,
			'ticket_initial_message_sender_last_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_initial_message_sender_num_nonspam' => Model_CustomField::TYPE_NUMBER,
			'ticket_initial_message_sender_num_spam' => Model_CustomField::TYPE_NUMBER,
			'ticket_initial_message_sender_org_city' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_initial_message_sender_org_country' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_initial_message_sender_org_created' => Model_CustomField::TYPE_DATE,
			'ticket_initial_message_sender_org_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_initial_message_sender_org_phone' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_initial_message_sender_org_postal' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_initial_message_sender_org_province' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_initial_message_sender_org_street' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_initial_message_sender_org_website' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_initial_message_storage_size' => Model_CustomField::TYPE_NUMBER,
		
			'ticket_latest_message_content' => Model_CustomField::TYPE_MULTI_LINE,
			'ticket_latest_message_created|date' => Model_CustomField::TYPE_DATE,
			'ticket_latest_message_is_outgoing' => Model_CustomField::TYPE_CHECKBOX,
			'ticket_latest_message_sender_address' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_latest_message_sender_first_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_latest_message_sender_full_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_latest_message_sender_is_banned' => Model_CustomField::TYPE_CHECKBOX,
			'ticket_latest_message_sender_last_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_latest_message_sender_num_nonspam' => Model_CustomField::TYPE_NUMBER,
			'ticket_latest_message_sender_num_spam' => Model_CustomField::TYPE_NUMBER,
			'ticket_latest_message_sender_org_city' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_latest_message_sender_org_country' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_latest_message_sender_org_created' => Model_CustomField::TYPE_DATE,
			'ticket_latest_message_sender_org_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_latest_message_sender_org_phone' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_latest_message_sender_org_postal' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_latest_message_sender_org_province' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_latest_message_sender_org_street' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_latest_message_sender_org_website' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_latest_message_storage_size' => Model_CustomField::TYPE_NUMBER,
		
			"group_name" => Model_CustomField::TYPE_SINGLE_LINE,
		
			"ticket_bucket_name|default('Inbox')" => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_created|date' => Model_CustomField::TYPE_DATE,
			'ticket_group_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_mask' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_spam_score' => null,
			'ticket_spam_training' => null,
			'ticket_status' => null,
			'ticket_subject' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_updated|date' => Model_CustomField::TYPE_DATE,
			'ticket_url' => Model_CustomField::TYPE_URL,
		);

		$conditions = $this->_importLabelsTypesAsConditions($labels, $types);
		
		return $conditions;		
	}
	
	function renderConditionExtension($token, $params=array(), $seq=null) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('params', $params);

		if(!is_null($seq))
			$tpl->assign('namePrefix','condition'.$seq);
		
		switch($token) {
			case 'ticket_spam_score':
				$tpl->display('devblocks:cerberusweb.core::events/mail_received_by_group/condition_spam_score.tpl');
				break;
			case 'ticket_spam_training':
				$tpl->display('devblocks:cerberusweb.core::events/mail_received_by_group/condition_spam_training.tpl');
				break;
			case 'ticket_status':
				$tpl->display('devblocks:cerberusweb.core::events/mail_received_by_group/condition_status.tpl');
				break;
		}

		$tpl->clearAssign('namePrefix');
		$tpl->clearAssign('params');
	}
	
	function runConditionExtension($token, $params, $values) {
		$pass = true;
		
		switch($token) {
			case 'ticket_spam_score':
				$not = (substr($params['oper'],0,1) == '!');
				$oper = ltrim($params['oper'],'!');
				@$value = intval($values[$token] * 100);

				switch($oper) {
					case 'is':
						$pass = intval($value)==intval($params['value']);
						break;
					case 'gt':
						$pass = intval($value) > intval($params['value']);
						break;
					case 'lt':
						$pass = intval($value) < intval($params['value']);
						break;
				}
				
				$pass = ($not) ? !$pass : $pass;
				break;
				
			case 'ticket_spam_training':
			case 'ticket_status':
				$not = (substr($params['oper'],0,1) == '!');
				$oper = ltrim($params['oper'],'!');
				@$value = $values[$token];
				
				if(!isset($params['values']) || !is_array($params['values'])) {
					$pass = false;
					break;
				}
				
				switch($oper) {
					case 'in':
						$pass = false;
						foreach($params['values'] as $v) {
							if($v == $value) {
								$pass = true;
								break;
							}
						}
						break;
				}
				$pass = ($not) ? !$pass : $pass;
				break;
				
			default:
				$pass = false;
				break;
		}
		
		return $pass;
	}
	
	function getActionExtensions() {
		$actions = array(
			'create_comment' => array('label' =>'Create a comment'),
			'create_notification' => array('label' =>'Create a notification'),
			'create_task' => array('label' =>'Create a task'),
			'create_ticket' => array('label' =>'Create a ticket'),
			'move_to_bucket' => array('label' => 'Move to bucket'),
			//'move_to_group' => array('label' => 'Move to group'),
			'send_email' => array('label' => 'Send email'),
			'send_email_recipients' => array('label' => 'Send email to recipients'),
			'set_spam_training' => array('label' => 'Set spam training'),
			'set_status' => array('label' => 'Set status'),
		);
		return $actions;
	}
	
	function renderActionExtension($token, $trigger_id=null, $params=array(), $seq=null) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('params', $params);

		if(!is_null($seq))
			$tpl->assign('namePrefix','action'.$seq);

		$labels = $this->getLabels();
		$tpl->assign('token_labels', $labels);
			
		switch($token) {
			case 'send_email':
				DevblocksEventHelper::renderActionSendEmail();
				break;
				
			case 'send_email_recipients':
				// [TODO] Share
				$tpl->assign('workers', DAO_Worker::getAll());
				$tpl->display('devblocks:cerberusweb.core::events/mail_received_by_owner/action_send_email_recipients.tpl');
				break;
				
			case 'create_comment':
				DevblocksEventHelper::renderActionCreateComment();
				break;
				
			case 'create_notification':
				DevblocksEventHelper::renderActionCreateNotification();
				break;
				
			case 'create_task':
				DevblocksEventHelper::renderActionCreateTask();
				break;
				
			case 'create_ticket':
				DevblocksEventHelper::renderActionCreateTicket();
				break;
				
			case 'set_spam_training':
				$tpl->display('devblocks:cerberusweb.core::events/mail_received_by_group/action_set_spam_training.tpl');
				break;
				
			case 'set_status':
				$tpl->display('devblocks:cerberusweb.core::events/mail_received_by_group/action_set_status.tpl');
				break;
				
			case 'move_to_bucket':
				// [TODO] Use trigger cache
				if(null != ($trigger = DAO_TriggerEvent::get($trigger_id))) {
					$buckets = DAO_Bucket::getByTeam($trigger->owner_context_id);
					$tpl->assign('buckets', $buckets);
				}
				$tpl->display('devblocks:cerberusweb.core::events/mail_received_by_group/action_move_to_bucket.tpl');
				break;
				
		}
		
		$tpl->clearAssign('params');
		$tpl->clearAssign('namePrefix');
		$tpl->clearAssign('token_labels');		
	}
	
	function runActionExtension($token, $trigger_id, $params, &$values) {
		@$ticket_id = $values['ticket_id'];
		@$message_id = $values['ticket_latest_message_id'];

		if(empty($message_id) || empty($ticket_id))
			return;
		
		switch($token) {
			case 'send_email':
				DevblocksEventHelper::runActionSendEmail($params, $values);
				break;
				
			case 'send_email_recipients':
				// Translate message tokens
				$tpl_builder = DevblocksPlatform::getTemplateBuilder();
				$content = $tpl_builder->build($params['content'], $values);
				
				$properties = array(
					'ticket_id' => $ticket_id,
					'message_id' => $message_id,
					'content' => $content,
					'agent_id' => 0, //$worker_id,
				);
				
				if(isset($params['is_autoreply']) && !empty($params['is_autoreply']))
					$properties['is_autoreply'] = true;
				
				CerberusMail::sendTicketMessage($properties);
				break;
				
			case 'create_comment':
				DevblocksEventHelper::runActionCreateComment($params, $values, CerberusContexts::CONTEXT_TICKET, $ticket_id);
				break;
				
			case 'create_notification':
				$url_writer = DevblocksPlatform::getUrlService();
				$url = $url_writer->write('c=display&id='.$values['ticket_mask'], true);
				
				DevblocksEventHelper::runActionCreateNotification($params, $values, $url);
				break;
				
			case 'create_task':
				DevblocksEventHelper::runActionCreateTask($params, $values, CerberusContexts::CONTEXT_TICKET, $ticket_id);
				break;

			case 'create_ticket':
				DevblocksEventHelper::runActionCreateTicket($params, $values, CerberusContexts::CONTEXT_TICKET, $ticket_id);
				break;

			case 'set_spam_training':
				@$to_training = $params['value'];
				@$current_training = $values['ticket_spam_training'];

				if($to_training == $current_training)
					break;
					
				switch($to_training) {
					case 'S':
						CerberusBayes::markTicketAsSpam($ticket_id);
						$values['ticket_spam_training'] = $to_training;
						break;
					case 'N':
						CerberusBayes::markTicketAsNotSpam($ticket_id);
						$values['ticket_spam_training'] = $to_training;
						break;
				}
				break;
				
			case 'set_status':
				@$to_status = $params['status'];
				@$current_status = $values['ticket_status'];
				
				if($to_status == $current_status)
					break;
					
				// Status
				switch($to_status) {
					case 'open':
						$fields = array(
							DAO_Ticket::IS_WAITING => 0,
							DAO_Ticket::IS_CLOSED => 0,
							DAO_Ticket::IS_DELETED => 0,
						);
						break;
					case 'waiting':
						$fields = array(
							DAO_Ticket::IS_WAITING => 1,
							DAO_Ticket::IS_CLOSED => 0,
							DAO_Ticket::IS_DELETED => 0,
						);
						break;
					case 'closed':
						$fields = array(
							DAO_Ticket::IS_WAITING => 0,
							DAO_Ticket::IS_CLOSED => 1,
							DAO_Ticket::IS_DELETED => 0,
						);
						break;
					case 'deleted':
						$fields = array(
							DAO_Ticket::IS_WAITING => 0,
							DAO_Ticket::IS_CLOSED => 1,
							DAO_Ticket::IS_DELETED => 1,
						);
						break;
					default:
						$fields = array();
						break;
				}
				if(!empty($fields)) {
					DAO_Ticket::update($ticket_id, $fields);
					$values['ticket_status'] = $to_status;
				}
				break;
				
			case 'move_to_bucket':
				@$to_bucket_id = intval($params['bucket_id']);
				@$current_bucket_id = intval($values['ticket_bucket_id']);
				$buckets = DAO_Bucket::getAll();
				
				// Don't trigger a move event into the same bucket.
				if($to_bucket_id == $current_bucket_id)
					break;
				
				if(!empty($to_bucket_id) && !isset($buckets[$to_bucket_id]))
					break;
					
				// Move
				DAO_Ticket::update($ticket_id, array(
					DAO_Ticket::CATEGORY_ID => $to_bucket_id, 
				));
				$values['ticket_bucket_id'] = $to_bucket_id;
				break;
		}
	}	
};

class Event_MailMovedToGroup extends Extension_DevblocksEvent {
	const ID = 'event.mail.moved.group';
	
	static function trigger($ticket_id, $group_id) {
		$events = DevblocksPlatform::getEventService();
		$events->trigger(
	        new Model_DevblocksEvent(
	            self::ID,
                array(
                    'ticket_id' => $ticket_id,
                    'group_id' => $group_id,
                	'_whisper' => array(
                		CerberusContexts::CONTEXT_GROUP => array($group_id),
                	),
                )
            )
		);
	}
	
	/**
	 * 
	 * @param integer $ticket_id
	 * @param integer $group_id
	 * @return Model_DevblocksEvent
	 */
	function generateSampleEventModel($ticket_id=null, $group_id=null) {
		if(empty($ticket_id)) {
			// Pull the latest ticket
			list($results) = DAO_Ticket::search(
				array(),
				array(
					new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CLOSED,'=',0),
				),
				10,
				0,
				SearchFields_Ticket::TICKET_ID,
				false,
				false
			);
			
			shuffle($results);
			
			$result = array_shift($results);
			
			$ticket_id = $result[SearchFields_Ticket::TICKET_ID];
			$group_id = $result[SearchFields_Ticket::TICKET_TEAM_ID];
		}
		
		return new Model_DevblocksEvent(
			self::ID,
			array(
				'ticket_id' => $ticket_id,
				'group_id' => $group_id,
			)
		);
	}	
	
	function setEvent(Model_DevblocksEvent $event_model=null) {
		$labels = array();
		$values = array();

		/**
		 * Ticket
		 */
		
		@$ticket_id = $event_model->params['ticket_id']; 
		$ticket_labels = array();
		$ticket_values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_TICKET, $ticket_id, $ticket_labels, $ticket_values, null, true);

			// Merge
			CerberusContexts::merge(
				'ticket_',
				'',
				$ticket_labels,
				$ticket_values,
				$labels,
				$values
			);

		/**
		 * Group
		 */
		@$group_id = $values['group_id'];
		$group_labels = array();
		$group_values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_GROUP, $group_id, $group_labels, $group_values, null, true);
				
			// Merge
			CerberusContexts::merge(
				'group_',
				'',
				$group_labels,
				$group_values,
				$labels,
				$values
			);
		
		/**
		 * Return
		 */

		$this->setLabels($labels);
		$this->setValues($values);		
	}
	
	function getConditionExtensions() {
		$labels = $this->getLabels();
		
		$types = array(
			'ticket_initial_message_content' => Model_CustomField::TYPE_MULTI_LINE,
			'ticket_initial_message_created|date' => Model_CustomField::TYPE_DATE,
			'ticket_initial_message_is_outgoing' => Model_CustomField::TYPE_CHECKBOX,
			'ticket_initial_message_sender_address' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_initial_message_sender_first_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_initial_message_sender_full_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_initial_message_sender_is_banned' => Model_CustomField::TYPE_CHECKBOX,
			'ticket_initial_message_sender_last_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_initial_message_sender_num_nonspam' => Model_CustomField::TYPE_NUMBER,
			'ticket_initial_message_sender_num_spam' => Model_CustomField::TYPE_NUMBER,
			'ticket_initial_message_sender_org_city' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_initial_message_sender_org_country' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_initial_message_sender_org_created' => Model_CustomField::TYPE_DATE,
			'ticket_initial_message_sender_org_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_initial_message_sender_org_phone' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_initial_message_sender_org_postal' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_initial_message_sender_org_province' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_initial_message_sender_org_street' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_initial_message_sender_org_website' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_initial_message_storage_size' => Model_CustomField::TYPE_NUMBER,
		
			'ticket_latest_message_content' => Model_CustomField::TYPE_MULTI_LINE,
			'ticket_latest_message_created|date' => Model_CustomField::TYPE_DATE,
			'ticket_latest_message_is_outgoing' => Model_CustomField::TYPE_CHECKBOX,
			'ticket_latest_message_sender_address' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_latest_message_sender_first_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_latest_message_sender_full_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_latest_message_sender_is_banned' => Model_CustomField::TYPE_CHECKBOX,
			'ticket_latest_message_sender_last_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_latest_message_sender_num_nonspam' => Model_CustomField::TYPE_NUMBER,
			'ticket_latest_message_sender_num_spam' => Model_CustomField::TYPE_NUMBER,
			'ticket_latest_message_sender_org_city' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_latest_message_sender_org_country' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_latest_message_sender_org_created' => Model_CustomField::TYPE_DATE,
			'ticket_latest_message_sender_org_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_latest_message_sender_org_phone' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_latest_message_sender_org_postal' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_latest_message_sender_org_province' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_latest_message_sender_org_street' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_latest_message_sender_org_website' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_latest_message_storage_size' => Model_CustomField::TYPE_NUMBER,
		
			"group_name" => Model_CustomField::TYPE_SINGLE_LINE,
		
			"ticket_bucket_name|default('Inbox')" => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_created|date' => Model_CustomField::TYPE_DATE,
			'ticket_group_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_mask' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_spam_score' => null,
			'ticket_spam_training' => null,
			'ticket_status' => null,
			'ticket_subject' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_updated|date' => Model_CustomField::TYPE_DATE,
			'ticket_url' => Model_CustomField::TYPE_URL,
		);

		$conditions = $this->_importLabelsTypesAsConditions($labels, $types);
		
		return $conditions;		
	}
	
	function renderConditionExtension($token, $params=array(), $seq=null) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('params', $params);

		if(!is_null($seq))
			$tpl->assign('namePrefix','condition'.$seq);
		
		switch($token) {
			case 'ticket_spam_score':
				$tpl->display('devblocks:cerberusweb.core::events/mail_received_by_group/condition_spam_score.tpl');
				break;
			case 'ticket_spam_training':
				$tpl->display('devblocks:cerberusweb.core::events/mail_received_by_group/condition_spam_training.tpl');
				break;
			case 'ticket_status':
				$tpl->display('devblocks:cerberusweb.core::events/mail_received_by_group/condition_status.tpl');
				break;
		}

		$tpl->clearAssign('namePrefix');
		$tpl->clearAssign('params');
	}
	
	function runConditionExtension($token, $params, $values) {
		$pass = true;
		
		switch($token) {
			case 'ticket_spam_score':
				$not = (substr($params['oper'],0,1) == '!');
				$oper = ltrim($params['oper'],'!');
				@$value = intval($values[$token] * 100);

				switch($oper) {
					case 'is':
						$pass = intval($value)==intval($params['value']);
						break;
					case 'gt':
						$pass = intval($value) > intval($params['value']);
						break;
					case 'lt':
						$pass = intval($value) < intval($params['value']);
						break;
				}
				
				$pass = ($not) ? !$pass : $pass;
				break;
				
			case 'ticket_spam_training':
			case 'ticket_status':
				$not = (substr($params['oper'],0,1) == '!');
				$oper = ltrim($params['oper'],'!');
				@$value = $values[$token];
				
				if(!isset($params['values']) || !is_array($params['values'])) {
					$pass = false;
					break;
				}
				
				switch($oper) {
					case 'in':
						$pass = false;
						foreach($params['values'] as $v) {
							if($v == $value) {
								$pass = true;
								break;
							}
						}
						break;
				}
				$pass = ($not) ? !$pass : $pass;
				break;
				
			default:
				$pass = false;
				break;
		}
		
		return $pass;
	}
	
	function getActionExtensions() {
		$actions = array(
			'create_comment' => array('label' =>'Create a comment'),
			'create_notification' => array('label' =>'Create a notification'),
			'create_task' => array('label' =>'Create a task'),
			'create_ticket' => array('label' =>'Create a ticket'),
			'move_to_bucket' => array('label' => 'Move to bucket'),
			//'move_to_group' => array('label' => 'Move to group'),
			'send_email' => array('label' => 'Send email'),
			'send_email_recipients' => array('label' => 'Send email to recipients'),
			'set_spam_training' => array('label' => 'Set spam training'),
			'set_status' => array('label' => 'Set status'),
		);
		return $actions;
	}
	
	function renderActionExtension($token, $trigger_id=null, $params=array(), $seq=null) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('params', $params);

		if(!is_null($seq))
			$tpl->assign('namePrefix','action'.$seq);

		$labels = $this->getLabels();
		$tpl->assign('token_labels', $labels);
			
		switch($token) {
			case 'send_email':
				DevblocksEventHelper::renderActionSendEmail();
				break;
				
			case 'send_email_recipients':
				// [TODO] Share
				$tpl->assign('workers', DAO_Worker::getAll());
				$tpl->display('devblocks:cerberusweb.core::events/mail_received_by_owner/action_send_email_recipients.tpl');
				break;
				
			case 'create_comment':
				DevblocksEventHelper::renderActionCreateComment();
				break;
				
			case 'create_notification':
				DevblocksEventHelper::renderActionCreateNotification();
				break;
				
			case 'create_task':
				DevblocksEventHelper::renderActionCreateTask();
				break;
				
			case 'create_ticket':
				DevblocksEventHelper::renderActionCreateTicket();
				break;
				
			case 'set_spam_training':
				$tpl->display('devblocks:cerberusweb.core::events/mail_received_by_group/action_set_spam_training.tpl');
				break;
				
			case 'set_status':
				$tpl->display('devblocks:cerberusweb.core::events/mail_received_by_group/action_set_status.tpl');
				break;
				
			case 'move_to_bucket':
				// [TODO] Use trigger cache
				if(null != ($trigger = DAO_TriggerEvent::get($trigger_id))) {
					$buckets = DAO_Bucket::getByTeam($trigger->owner_context_id);
					$tpl->assign('buckets', $buckets);
				}
				$tpl->display('devblocks:cerberusweb.core::events/mail_received_by_group/action_move_to_bucket.tpl');
				break;
				
		}
		
		$tpl->clearAssign('params');
		$tpl->clearAssign('namePrefix');
		$tpl->clearAssign('token_labels');		
	}
	
	function runActionExtension($token, $trigger_id, $params, &$values) {
		@$ticket_id = $values['ticket_id'];
		@$message_id = $values['ticket_latest_message_id'];

		if(empty($message_id) || empty($ticket_id))
			return;
		
		switch($token) {
			case 'send_email':
				DevblocksEventHelper::runActionSendEmail($params, $values);
				break;
				
			case 'send_email_recipients':
				// Translate message tokens
				$tpl_builder = DevblocksPlatform::getTemplateBuilder();
				$content = $tpl_builder->build($params['content'], $values);
				
				$properties = array(
					'ticket_id' => $ticket_id,
					'message_id' => $message_id,
					'content' => $content,
					'agent_id' => 0, //$worker_id,
				);
				
				if(isset($params['is_autoreply']) && !empty($params['is_autoreply']))
					$properties['is_autoreply'] = true;
				
				CerberusMail::sendTicketMessage($properties);
				break;
				
			case 'create_comment':
				DevblocksEventHelper::runActionCreateComment($params, $values, CerberusContexts::CONTEXT_TICKET, $ticket_id);
				break;
				
			case 'create_notification':
				$url_writer = DevblocksPlatform::getUrlService();
				$url = $url_writer->write('c=display&id='.$values['ticket_mask'], true);
				
				DevblocksEventHelper::runActionCreateNotification($params, $values, $url);
				break;
				
			case 'create_task':
				DevblocksEventHelper::runActionCreateTask($params, $values, CerberusContexts::CONTEXT_TICKET, $ticket_id);
				break;

			case 'create_ticket':
				DevblocksEventHelper::runActionCreateTicket($params, $values, CerberusContexts::CONTEXT_TICKET, $ticket_id);
				break;

			case 'set_spam_training':
				@$to_training = $params['value'];
				@$current_training = $values['ticket_spam_training'];

				if($to_training == $current_training)
					break;
					
				switch($to_training) {
					case 'S':
						CerberusBayes::markTicketAsSpam($ticket_id);
						$values['ticket_spam_training'] = $to_training;
						break;
					case 'N':
						CerberusBayes::markTicketAsNotSpam($ticket_id);
						$values['ticket_spam_training'] = $to_training;
						break;
				}
				break;
				
			case 'set_status':
				@$to_status = $params['status'];
				@$current_status = $values['ticket_status'];
				
				if($to_status == $current_status)
					break;
					
				// Status
				switch($to_status) {
					case 'open':
						$fields = array(
							DAO_Ticket::IS_WAITING => 0,
							DAO_Ticket::IS_CLOSED => 0,
							DAO_Ticket::IS_DELETED => 0,
						);
						break;
					case 'waiting':
						$fields = array(
							DAO_Ticket::IS_WAITING => 1,
							DAO_Ticket::IS_CLOSED => 0,
							DAO_Ticket::IS_DELETED => 0,
						);
						break;
					case 'closed':
						$fields = array(
							DAO_Ticket::IS_WAITING => 0,
							DAO_Ticket::IS_CLOSED => 1,
							DAO_Ticket::IS_DELETED => 0,
						);
						break;
					case 'deleted':
						$fields = array(
							DAO_Ticket::IS_WAITING => 0,
							DAO_Ticket::IS_CLOSED => 1,
							DAO_Ticket::IS_DELETED => 1,
						);
						break;
					default:
						$fields = array();
						break;
				}
				if(!empty($fields)) {
					DAO_Ticket::update($ticket_id, $fields);
					$values['ticket_status'] = $to_status;
				}
				break;
				
			case 'move_to_bucket':
				@$to_bucket_id = intval($params['bucket_id']);
				@$current_bucket_id = intval($values['ticket_bucket_id']);
				$buckets = DAO_Bucket::getAll();
				
				// Don't trigger a move event into the same bucket.
				if($to_bucket_id == $current_bucket_id)
					break;
				
				if(!empty($to_bucket_id) && !isset($buckets[$to_bucket_id]))
					break;
					
				// Move
				DAO_Ticket::update($ticket_id, array(
					DAO_Ticket::CATEGORY_ID => $to_bucket_id, 
				));
				$values['ticket_bucket_id'] = $to_bucket_id;
				break;
		}
	}	
};

class Event_MailReceivedByGroup extends Extension_DevblocksEvent {
	const ID = 'event.mail.received.group';
	
	static function trigger($message_id, $group_id) {
		$events = DevblocksPlatform::getEventService();
		$events->trigger(
	        new Model_DevblocksEvent(
	            self::ID,
                array(
                    'message_id' => $message_id,
                    'group_id' => $group_id,
                	'_whisper' => array(
                		CerberusContexts::CONTEXT_GROUP => array($group_id),
                	),
                )
            )
		);
	} 
	
	/**
	 * 
	 * @param integer $message_id
	 * @param integer $group_id
	 * @return Model_DevblocksEvent
	 */
	function generateSampleEventModel($message_id=null, $group_id=null) {
		if(empty($message_id)) {
			// Pull the latest ticket
			list($results) = DAO_Ticket::search(
				array(),
				array(
					new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CLOSED,'=',0),
				),
				10,
				0,
				SearchFields_Ticket::TICKET_ID,
				false,
				false
			);
			
			shuffle($results);
			
			$result = array_shift($results);
			
			$message_id = $result[SearchFields_Ticket::TICKET_LAST_MESSAGE_ID];
			$group_id = $result[SearchFields_Ticket::TICKET_TEAM_ID];
		}
		
		return new Model_DevblocksEvent(
			self::ID,
			array(
				'message_id' => $message_id,
				'group_id' => $group_id,
			)
		);
	}
	
	function setEvent(Model_DevblocksEvent $event_model=null) {
		/**
		 * Message
		 */
		
		@$message_id = $event_model->params['message_id'];
		$labels = array();
		$values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_MESSAGE, $message_id, $labels, $values, null, true);

		// Fill in some custom values
		$values['sender_is_worker'] = (!empty($values['worker_id'])) ? 1 : 0;
		
		/**
		 * Ticket
		 */
		
		@$ticket_id = $values['ticket_id']; 
		$ticket_labels = array();
		$ticket_values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_TICKET, $ticket_id, $ticket_labels, $ticket_values, null, true);

			// Fill some custom values
			if(!is_null($event_model)) {
				$values['is_first'] = ($values['id'] == $ticket_values['initial_message_id']) ? 1 : 0;
			}
		
			// Clear dupe content
			CerberusContexts::scrubTokensWithRegexp(
				$ticket_labels,
				$ticket_values,
				array(
					"#^initial_message_#",
					"#^latest_message_#",
					"#^group_#",
					"#^id$#",
				)
			);
			
			// Merge
			CerberusContexts::merge(
				'ticket_',
				'',
				$ticket_labels,
				$ticket_values,
				$labels,
				$values
			);

		/**
		 * Group
		 */
		@$group_id = $event_model->params['group_id'];
		$group_labels = array();
		$group_values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_GROUP, $group_id, $group_labels, $group_values, null, true);
				
			// Merge
			CerberusContexts::merge(
				'group_',
				'',
				$group_labels,
				$group_values,
				$labels,
				$values
			);
		
		/**
		 * Sender Worker
		 */
		@$worker_id = $values['worker_id'];
		$worker_labels = array();
		$worker_values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, $worker_id, $worker_labels, $worker_values, null, true);
				
			// Clear dupe content
			CerberusContexts::scrubTokensWithRegexp(
				$worker_labels,
				$worker_values,
				array(
					"#^address_org_#",
				)
			);
		
			// Merge
			CerberusContexts::merge(
				'sender_worker_',
				'Message sender ',
				$worker_labels,
				$worker_values,
				$labels,
				$values
			);
			
		/**
		 * Return
		 */
		
		$this->setLabels($labels);
		$this->setValues($values);		
	}
	
	function getConditionExtensions() {
		$labels = $this->getLabels();
		
		$labels['is_first'] = 'Message is first in conversation';
		$labels['sender_is_worker'] = 'Message sender is a worker';
		
		$types = array(
			'content' => Model_CustomField::TYPE_MULTI_LINE,
			'created|date' => Model_CustomField::TYPE_DATE,
			'is_first' => Model_CustomField::TYPE_CHECKBOX,
			'is_outgoing' => Model_CustomField::TYPE_CHECKBOX,
			'sender_address' => Model_CustomField::TYPE_SINGLE_LINE,
			'sender_first_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'sender_full_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'sender_is_banned' => Model_CustomField::TYPE_CHECKBOX,
			'sender_is_worker' => Model_CustomField::TYPE_CHECKBOX,
			'sender_last_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'sender_num_nonspam' => Model_CustomField::TYPE_NUMBER,
			'sender_num_spam' => Model_CustomField::TYPE_NUMBER,
			'sender_org_city' => Model_CustomField::TYPE_SINGLE_LINE,
			'sender_org_country' => Model_CustomField::TYPE_SINGLE_LINE,
			'sender_org_created' => Model_CustomField::TYPE_DATE,
			'sender_org_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'sender_org_phone' => Model_CustomField::TYPE_SINGLE_LINE,
			'sender_org_postal' => Model_CustomField::TYPE_SINGLE_LINE,
			'sender_org_province' => Model_CustomField::TYPE_SINGLE_LINE,
			'sender_org_street' => Model_CustomField::TYPE_SINGLE_LINE,
			'sender_org_website' => Model_CustomField::TYPE_SINGLE_LINE,
			'sender_worker_address_address' => Model_CustomField::TYPE_SINGLE_LINE,
			'sender_worker_full_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'storage_size' => Model_CustomField::TYPE_NUMBER,
		
			"group_name" => Model_CustomField::TYPE_SINGLE_LINE,
		
			"ticket_bucket_name|default('Inbox')" => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_created|date' => Model_CustomField::TYPE_DATE,
			'ticket_group_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_mask' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_spam_score' => null,
			'ticket_spam_training' => null,
			'ticket_status' => null,
			'ticket_subject' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_updated|date' => Model_CustomField::TYPE_DATE,
			'ticket_url' => Model_CustomField::TYPE_URL,
		);

		$conditions = $this->_importLabelsTypesAsConditions($labels, $types);
		
		return $conditions;		
	}
	
	function renderConditionExtension($token, $params=array(), $seq=null) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('params', $params);

		if(!is_null($seq))
			$tpl->assign('namePrefix','condition'.$seq);
		
		switch($token) {
			case 'ticket_spam_score':
				$tpl->display('devblocks:cerberusweb.core::events/mail_received_by_group/condition_spam_score.tpl');
				break;
			case 'ticket_spam_training':
				$tpl->display('devblocks:cerberusweb.core::events/mail_received_by_group/condition_spam_training.tpl');
				break;
			case 'ticket_status':
				$tpl->display('devblocks:cerberusweb.core::events/mail_received_by_group/condition_status.tpl');
				break;
		}

		$tpl->clearAssign('namePrefix');
		$tpl->clearAssign('params');
	}
	
	function runConditionExtension($token, $params, $values) {
		$pass = true;
		
		switch($token) {
			case 'ticket_spam_score':
				$not = (substr($params['oper'],0,1) == '!');
				$oper = ltrim($params['oper'],'!');
				@$value = intval($values[$token] * 100);

				switch($oper) {
					case 'is':
						$pass = intval($value)==intval($params['value']);
						break;
					case 'gt':
						$pass = intval($value) > intval($params['value']);
						break;
					case 'lt':
						$pass = intval($value) < intval($params['value']);
						break;
				}
				
				$pass = ($not) ? !$pass : $pass;
				break;
				
			case 'ticket_spam_training':
			case 'ticket_status':
				$not = (substr($params['oper'],0,1) == '!');
				$oper = ltrim($params['oper'],'!');
				@$value = $values[$token];
				
				if(!isset($params['values']) || !is_array($params['values'])) {
					$pass = false;
					break;
				}
				
				switch($oper) {
					case 'in':
						$pass = false;
						foreach($params['values'] as $v) {
							if($v == $value) {
								$pass = true;
								break;
							}
						}
						break;
				}
				$pass = ($not) ? !$pass : $pass;
				break;
				
			default:
				$pass = false;
				break;
		}
		
		return $pass;
	}
	
	function getActionExtensions() {
		$actions = array(
			'create_comment' => array('label' =>'Create a comment'),
			'create_notification' => array('label' =>'Create a notification'),
			'create_task' => array('label' =>'Create a task'),
			'create_ticket' => array('label' =>'Create a ticket'),
			'move_to_bucket' => array('label' => 'Move to bucket'),
			//'move_to_group' => array('label' => 'Move to group'),
			'send_email' => array('label' => 'Send email'),
			'send_email_recipients' => array('label' => 'Send email to recipients'),
			'set_spam_training' => array('label' => 'Set spam training'),
			'set_status' => array('label' => 'Set status'),
		);
		return $actions;
	}
	
	function renderActionExtension($token, $trigger_id=null, $params=array(), $seq=null) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('params', $params);

		if(!is_null($seq))
			$tpl->assign('namePrefix','action'.$seq);

		$labels = $this->getLabels();
		$tpl->assign('token_labels', $labels);
			
		switch($token) {
			case 'send_email':
				DevblocksEventHelper::renderActionSendEmail();
				break;
				
			case 'send_email_recipients':
				// [TODO] Share
				$tpl->assign('workers', DAO_Worker::getAll());
				$tpl->display('devblocks:cerberusweb.core::events/mail_received_by_owner/action_send_email_recipients.tpl');
				break;
				
			case 'create_comment':
				DevblocksEventHelper::renderActionCreateComment();
				break;
				
			case 'create_notification':
				DevblocksEventHelper::renderActionCreateNotification();
				break;
				
			case 'create_task':
				DevblocksEventHelper::renderActionCreateTask();
				break;
				
			case 'create_ticket':
				DevblocksEventHelper::renderActionCreateTicket();
				break;
				
			case 'set_spam_training':
				$tpl->display('devblocks:cerberusweb.core::events/mail_received_by_group/action_set_spam_training.tpl');
				break;
				
			case 'set_status':
				$tpl->display('devblocks:cerberusweb.core::events/mail_received_by_group/action_set_status.tpl');
				break;
				
			case 'move_to_bucket':
				// [TODO] Use trigger cache
				if(null != ($trigger = DAO_TriggerEvent::get($trigger_id))) {
					$buckets = DAO_Bucket::getByTeam($trigger->owner_context_id);
					$tpl->assign('buckets', $buckets);
				}
				$tpl->display('devblocks:cerberusweb.core::events/mail_received_by_group/action_move_to_bucket.tpl');
				break;
				
		}
		
		$tpl->clearAssign('params');
		$tpl->clearAssign('namePrefix');
		$tpl->clearAssign('token_labels');		
	}
	
	function runActionExtension($token, $trigger_id, $params, &$values) {
		@$message_id = $values['id'];
		@$ticket_id = $values['ticket_id'];

		if(empty($message_id) || empty($ticket_id))
			return;
		
		switch($token) {
			case 'send_email':
				DevblocksEventHelper::runActionSendEmail($params, $values);
				break;
				
			case 'send_email_recipients':
				// Translate message tokens
				$tpl_builder = DevblocksPlatform::getTemplateBuilder();
				$content = $tpl_builder->build($params['content'], $values);
				
				$properties = array(
					'ticket_id' => $ticket_id,
					'message_id' => $message_id,
					'content' => $content,
					'agent_id' => 0, //$worker_id,
				);
				
				if(isset($params['is_autoreply']) && !empty($params['is_autoreply']))
					$properties['is_autoreply'] = true;
				
				CerberusMail::sendTicketMessage($properties);
				break;
				
			case 'create_comment':
				DevblocksEventHelper::runActionCreateComment($params, $values, CerberusContexts::CONTEXT_TICKET, $ticket_id);
				break;
				
			case 'create_notification':
				$url_writer = DevblocksPlatform::getUrlService();
				$url = $url_writer->write('c=display&id='.$values['ticket_mask'], true);
				
				DevblocksEventHelper::runActionCreateNotification($params, $values, $url);
				break;
				
			case 'create_task':
				DevblocksEventHelper::runActionCreateTask($params, $values, CerberusContexts::CONTEXT_TICKET, $ticket_id);
				break;

			case 'create_ticket':
				DevblocksEventHelper::runActionCreateTicket($params, $values, CerberusContexts::CONTEXT_TICKET, $ticket_id);
				break;

			case 'set_spam_training':
				@$to_training = $params['value'];
				@$current_training = $values['ticket_spam_training'];

				if($to_training == $current_training)
					break;
					
				switch($to_training) {
					case 'S':
						CerberusBayes::markTicketAsSpam($ticket_id);
						$values['ticket_spam_training'] = $to_training;
						break;
					case 'N':
						CerberusBayes::markTicketAsNotSpam($ticket_id);
						$values['ticket_spam_training'] = $to_training;
						break;
				}
				break;
				
			case 'set_status':
				@$to_status = $params['status'];
				@$current_status = $values['ticket_status'];
				
				if($to_status == $current_status)
					break;
					
				// Status
				switch($to_status) {
					case 'open':
						$fields = array(
							DAO_Ticket::IS_WAITING => 0,
							DAO_Ticket::IS_CLOSED => 0,
							DAO_Ticket::IS_DELETED => 0,
						);
						break;
					case 'waiting':
						$fields = array(
							DAO_Ticket::IS_WAITING => 1,
							DAO_Ticket::IS_CLOSED => 0,
							DAO_Ticket::IS_DELETED => 0,
						);
						break;
					case 'closed':
						$fields = array(
							DAO_Ticket::IS_WAITING => 0,
							DAO_Ticket::IS_CLOSED => 1,
							DAO_Ticket::IS_DELETED => 0,
						);
						break;
					case 'deleted':
						$fields = array(
							DAO_Ticket::IS_WAITING => 0,
							DAO_Ticket::IS_CLOSED => 1,
							DAO_Ticket::IS_DELETED => 1,
						);
						break;
					default:
						$fields = array();
						break;
				}
				if(!empty($fields)) {
					DAO_Ticket::update($ticket_id, $fields);
					$values['ticket_status'] = $to_status;
				}
				break;
				
			case 'move_to_bucket':
				@$to_bucket_id = intval($params['bucket_id']);
				@$current_bucket_id = intval($values['ticket_bucket_id']);
				$buckets = DAO_Bucket::getAll();
				
				// Don't trigger a move event into the same bucket.
				if($to_bucket_id == $current_bucket_id)
					break;
				
				if(!empty($to_bucket_id) && !isset($buckets[$to_bucket_id]))
					break;
					
				// Move
				DAO_Ticket::update($ticket_id, array(
					DAO_Ticket::CATEGORY_ID => $to_bucket_id, 
				));
				$values['ticket_bucket_id'] = $to_bucket_id;
				break;
		}
	}
};

