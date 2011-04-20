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
class ChCoreTour extends DevblocksHttpResponseListenerExtension {
	/*
		return array(
        'tourHeaderMyTasks' => new DevblocksTourCallout('tourHeaderMyTasks','','My Tasks','Here you can quickly jump to a summary of your current tasks.'),
        'tourHeaderTeamLoads' => new DevblocksTourCallout('tourHeaderTeamLoads','','My Team Loads','Here you can quickly display the workload of any of your teams.  You can display a team\'s dashboard by clicking them.'),
        'tourHeaderGetTickets' => new DevblocksTourCallout('tourHeaderGetTickets','','Get Tickets',"The 'Get Tickets' link will assign you available tickets from your desired teams."),
        'tourHeaderQuickLookup' => new DevblocksTourCallout('tourHeaderQuickLookup','','Quick Lookup','Here you can quickly search for tickets from anywhere in the helpdesk.  This is generally most useful when someone calls up and you need to promptly locate their ticket.'),
        
        'tourDashboardViews' => new DevblocksTourCallout('tourDashboardViews','','Ticket Lists','This is where your customized lists of tickets are displayed.'),
        'tourDisplayConversation' => new DevblocksTourCallout('tourDisplayConversation','','Conversation','This is where all e-mail replies will be displayed for this ticket.  Your responses will be sent to all requesters.'),
        'btnReplyFirst' => new DevblocksTourCallout('btnReplyFirst','','Replying',"Clicking the Reply button while displaying a ticket will allow you to write a response, as you would in any e-mail client, without leaving the ticket's page. This allows you to reference the ticket's information and history as you write."),
        'tourDisplayPaging' => new DevblocksTourCallout('tourDisplayPaging','','Paging',"If you clicked on a ticket from a list, the detailed ticket page will show your progress from that list in the top right. You can also use the keyboard shortcuts to advance through the list with the bracket keys: ' [ ' and ' ] '."),
        'displayTabs' => new DevblocksTourCallout('displayTabs','','Pluggable Tabs',"With Cerberus Helpdesk's pluggable architecture, new capabilities can be added to your ticket management. For example, you could display all the CRM opportunities or billing invoices associated with the ticket's requesters."),
        'tourConfigMaintPurge' => new DevblocksTourCallout('tourConfigMaintPurge','','Purge Deleted','Here you may purge any deleted tickets from the database.'),
        'tourDashboardSearchCriteria' => new DevblocksTourCallout('tourDashboardSearchCriteria','','Search Criteria','Here you can change the criteria of the current search.'),
        'tourConfigMenu' => new DevblocksTourCallout('tourConfigMenu','','Menu','This is where you may choose to configure various components of the helpdesk.'),
        'tourConfigMailRouting' => new DevblocksTourCallout('tourConfigMailRouting','','Mail Routing','This is where you instruct the helpdesk how to deliver new messages.'),
        //'' => new DevblocksTourCallout('','',''),
		);
	*/
	function run(DevblocksHttpResponse $response, Smarty $tpl) {
		$path = $response->path;

		switch(array_shift($path)) {
			case 'welcome':
				$tour = array(
	                'title' => 'Welcome!',
	                'body' => "This assistant will help you become familiar with the helpdesk by following along and providing information about the current page.  You may follow the 'Points of Interest' links highlighted below to read tips about nearby functionality.",
	                'callouts' => array(
							new DevblocksTourCallout(
								'body > ul.navmenu',
								'Navigation Menu',
								'This global navigation menu divides the application into major sections.',
								'topLeft',
								'bottomLeft',
								10,
								20
							),
							new DevblocksTourCallout(
								'body > table:first td:nth(1) a',
								'Quick Links',
								'Hovering over your name provides a menu with useful shortcuts. Clicking on it takes you to your profile.',
								'topRight',
								'bottomLeft',
								0,
								0
							),
							new DevblocksTourCallout(
								'body fieldset:nth(1)',
								'Social',
								'Practice makes perfect.',
								'bottomLeft',
								'topLeft',
								20,
								0
							),
						),
					);
				break;

			case "display":
				$tour = array(
	                'title' => 'Display Ticket',
	                'body' => "This screen displays the currently selected ticket.  Here you can modify the ticket or send a new reply to all requesters.<br><br>Clicking the Requester History tab will show all the past and present tickets from the ticket's requesters. This is an easy way to find and merge duplicate tickets from the same requester, or from several requesters from the same organization.<br><br>Often, a ticket may require action from several workers before it's complete. You can create tasks for each worker to track the progress of these actions. In Cerberus Helpdesk, workers don't \"own\" tickets. Each ticket has a \"next worker\" who is responsible for moving the ticket forward.<br>",
	                'callouts' => array(
						//$callouts['tourDisplayConversation'],
						//$callouts['btnReplyFirst'],
						//$callouts['tourDisplayPaging'],
						//$callouts['displayTabs'],
					)
				);
				break;

			case "preferences":
				$tour = array(
             	   'title' => 'Preferences',
            	    'body' => 'This screen allows you to change the personal preferences on your helpdesk account.',
				);
				break;

			case "groups":
				$tour = array(
             	   'title' => 'My Groups',
              	  'body' => 'This screen allows you to administer and configure groups for which you are a manager.  This includes members, buckets, mail routing rules, and other group-specific preferences.',
				);
				break;

			case "config":
				switch(array_shift($path)) {
					default:
					case NULL:
					case "general":
						$tour = array(
	                        'title' => 'Setup',
    	                    'body' => 'This is where you configure the application.',
						);
						break;

					case "workflow":
						$tour = array(
	                        'title' => 'Team Configuration',
    	                    'body' => "Here you may create new helpdesk workers and organize them into teams.  Common teams often include departments (such as: Support, Sales, Development, Marketing, Billing, etc.) or various projects that warrant their own workloads.",
						);
						break;

					case "fnr":
						$tour = array(
	                        'title' => 'Fetch & Retrieve',
	                        'body' => "The Fetch & Retrieve config allows you to define a wide variety of sources for pulling support data from (wikis, blogs, kbs, faqs, etc).  Any source that returns RSS-style XML results to a search can be used.",
						);
						break;

					case "mail":
						$tour = array(
	                        'title' => 'Mail Configuration',
	                        'body' => "This section controls the heart of your helpdesk: e-mail.  Here you may define the routing rules that determine what to do with new messages.  This is also where you set your preferences for sending mail out of the helpdesk.  To configure the POP3 downloader, click 'helpdesk config'->'scheduler'->'POP3 Mail Checker'",
	                        'callouts' => array(
								//$callouts['tourConfigMailRouting']
							)
						);
						break;

					case "maintenance":
						$tour = array(
	                        'title' => 'Maintenance',
	                        'body' => 'This section is dedicated to ensuring your helpdesk continues to operate lightly and quickly.',
	                        'callouts' => array(
								//$callouts['tourConfigMaintPurge'],
							)
						);
						break;

					case "plugins":
						$tour = array(
	                        'title' => 'Features & Plugins',
	                        'body' => "This is where you can extend Cerb5 by installing new functionality through plugins.",
	                        'callouts' => array(
							)
						);
						break;
					case "jobs":
						$tour = array(
	                        'title' => 'Scheduler',
	                        'body' => "The scheduler is where you can set up tasks that will periodically run behind-the-scenes.",
	                        'callouts' => array(
							)
						);
						break;
				}
				break;

			case NULL:
			case "tickets":
				switch(array_shift($path)) {
					default:
					case NULL:
					case 'overview':
						$tour = array(
	                        'title' => 'Mail Overview',
	                        'body' => "The Mail tab provides the ability to compose outgoing email as well as view lists of tickets, either here in the general overview, in specific search result lists, or in your personalized ticket lists in 'my workspaces'.",
	                        'callouts' => array(
								new DevblocksTourCallout(
									'#mailTabs',
									'Tabs',
									'You can switch between several perspectives from the tabs.',
									'bottomLeft',
									'topLeft',
									10,
									10
								),
								new DevblocksTourCallout(
									'#viewmail_workflow_sidebar',
									'Subtotals',
									'You can display subtotals for any worklist on a wide variety of properties, including your own custom fields.',
									'bottomLeft',
									'topLeft',
									25,
									0
								),
								new DevblocksTourCallout(
									'#tourHeaderQuickLookup',
									'Quick Search',
									"You can use this search box to quickly find particular conversations.",
									'topRight',
									'bottomLeft',
									10,
									0
								),
								new DevblocksTourCallout(
									'#viewmail_workflow TABLE.worklistBody TH:first',
									'Watchers',
									"Click the green plus button next to any object to add yourself as a watcher.  You will receive a notification any time there is activity.",
									'bottomLeft',
									'topMiddle',
									0,
									5
								),
								new DevblocksTourCallout(
									'#viewmail_workflow TABLE.worklist:first',
									'Peek',
									"You can preview the content of any mail conversation in the worklist by hovering over the row and clicking the peek button that pops up to the right of the subject. Peek is especially helpful when confirming tickets are spam if they have an ambiguous subject. This saves you a lot of time that would otherwise be wasted clicking into each ticket and losing your place in the list.",
									'bottomLeft',
									'topMiddle',
									0,
									20
								),
								new DevblocksTourCallout(
									'#mail_workflow_actions',
									'List Actions',
									'Each list of tickets provides a toolbar of possible actions. Actions may be applied to specific tickets or to the entire list. Bulk Update allows you to apply several actions at once to any tickets in a list that match your criteria.',
									'topLeft',
									'topLeft',
									10,
									15
								),
							),					
						);
						break;
						
					case 'lists':
						$tour = array(
	                        'title' => 'My Workspaces',
	                        'body' => 'Here is where you set up personalized lists of tickets.  Any Overview or Search results list can be copied here by clicking the "copy" link in the list title bar.',
	                        'callouts' => array(
								//$callouts['tourDashboardViews'],
							)
						);
						break;
						
					case 'search':
						$tour = array(
	                        'title' => 'Searching Tickets',
	                        'body' => '',
	                        'callouts' => array(
								//$callouts['tourDashboardSearchCriteria']
							)
						);
						break;

					case 'compose':
						$tour = array(
	                        'title' => 'Compose Mail',
    	                    'body' => '',
						);
						break;
						
					case 'create':
						$tour = array(
	                        'title' => 'Log Ticket',
    	                    'body' => '',
						);
						break;
				}
				break;
				
			case 'contacts':
				switch(array_shift($path)) {
					default:
					case NULL:
					case 'orgs':
						$tour = array(
	                        'title' => 'Organizations',
	                        'body' => '',
	                        'callouts' => array(
							)
						);
						break;
						
					case 'addresses':
						$tour = array(
	                        'title' => 'Addresses',
	                        'body' => '',
	                        'callouts' => array(
							)
						);
						break;
						
					case 'import':
						$tour = array(
	                        'title' => 'Importing Orgs and Addresses',
	                        'body' => 'Use this screen to import Organizational and Address info.  The import allows comparison checking to do incremental imports and not duplicate data.',
	                        'callouts' => array(
							)
						);
						break;
				}
				break;
				
			case 'kb':
				$tour = array(
	                'title' => 'Knowledgebase',
	                'body' => "",
	                'callouts' => array(
					)
				);
				break;
				
			case 'tasks':
				$tour = array(
	                'title' => 'Tasks',
	                'body' => "Often, a ticket may require action from several workers before it's complete. You can create tasks for each worker to track the progress of these actions. In Cerberus Helpdesk, workers don't \"own\" tickets. Each ticket has a \"next worker\" who is responsible for moving the ticket forward.",
	                'callouts' => array(
					)
				);
				break;
				
			case 'community':
				$tour = array(
	                'title' => 'Communities',
	                'body' => 'Here you can create Public Community interfaces to Cerberus, including Knowledgebases, Contact Forms, and Support Centers.',
	                'callouts' => array(
					)
				);
				break;
				
		}

		if(!empty($tour))
		$tpl->assign('tour', $tour);
	}
};

class EventListener_Triggers extends DevblocksEventListenerExtension {
	static $_traversal_log = array();
	static $_trigger_log = array();
	static $_trigger_stack = array();
	static $_depth = 0;
	
	static function increaseDepth($trigger_id) {
		++self::$_depth;
		
		self::$_trigger_log[] = $trigger_id;
		self::$_trigger_stack[] = $trigger_id;
	}
	
	static function decreaseDepth() {
		--self::$_depth;
		array_pop(self::$_trigger_stack);
	}
	
	/**
	 * Are we currently nested inside this trigger at any depth?
	 * @param integer $trigger_id
	 */
	static function inception($trigger_id) {
		return in_array($trigger_id, self::$_trigger_stack);
	}
	
	static function triggerHasSprung($trigger_id) {
		return in_array($trigger_id, self::$_trigger_log);
	}
	
	static function logNode($node_id) {
		self::$_traversal_log[] = $node_id;
	}

	static function getDepth() {
		return self::$_depth;
	}
	
	static function getTriggerLog() {
		return self::$_trigger_log;
	}
	
	static function getNodeLog() {
		return self::$_traversal_log;
	}
	
	static function clear() {
		self::$_traversal_log = array();
		self::$_trigger_log = array();
		self::$_trigger_stack = array();
		self::$_depth = 0;
	}
	
	/**
	 * @param Model_DevblocksEvent $event
	 */
	function handleEvent(Model_DevblocksEvent $event) {
		$logger = DevblocksPlatform::getConsoleLog("Assistant");
		
		$logger->info(sprintf("EVENT: %s",
			$event->id
		));
		
		// [TODO] Check if any triggers are watching this event
		// [TODO] From cache!!! ::getAll()  ::getByEvent()
		$where = sprintf("%s = 0 AND %s = %s",
			DAO_TriggerEvent::IS_DISABLED,
			DAO_TriggerEvent::EVENT_POINT,
			C4_ORMHelper::qstr($event->id)
		);
		$triggers = DAO_TriggerEvent::getWhere($where);

		if(empty($triggers))
			return;

		// We're restricting the scope of the event
		if(isset($event->params['_whisper']) && is_array($event->params['_whisper'])) {
			foreach($triggers as $trigger_id => $trigger) { /* @var $trigger Model_TriggerEvent */
				if(
					null != ($allowed_ids = @$event->params['_whisper'][$trigger->owner_context])
					&& in_array($trigger->owner_context_id, !is_array($allowed_ids) ? array($allowed_ids) : $allowed_ids)
					) {
					// We're allowed to see this event
				} else {
					// We're not allowed to see this event
					//$logger->info(sprintf("Removing trigger %d (%s) since it is not in this whisper",
					//	$trigger_id,
					//	$trigger->title
					//));
					unset($triggers[$trigger_id]);
				}
			}
		}
			
		// [TODO] This could be cached in a runtime registry too
		if(null == ($mft = DevblocksPlatform::getExtension($event->id, false))) 
			return;
		
		if(null == ($event_ext = $mft->createInstance()) 
			|| !$event_ext instanceof Extension_DevblocksEvent)  /* @var $event_ext Extension_DevblocksEvent */
				return;
			
		// Load the intermediate data ONCE!
		$event_ext->setEvent($event);
		$values = $event_ext->getValues();
		foreach($triggers as $trigger) { /* @var $trigger Model_TriggerEvent */
			if(self::inception($trigger->id)) {
				$logger->info(sprintf("Skipping trigger %d (%s) because we're currently inside of it.",
					$trigger->id,
					$trigger->title
				));
				continue;
			}			
			
			/*
			 * If a top level trigger already ran as a consequence of the 
			 * event chain, don't run it again. 
			 */
			if(self::getDepth() == 0 && self::triggerHasSprung($trigger->id)) {
				$logger->info(sprintf("Skipping trigger %d (%s) because it has already run this event chain.",
					$trigger->id,
					$trigger->title
				));
				continue;
			}			
			
			self::increaseDepth($trigger->id);
			
			$logger->info(sprintf("Running decision tree on trigger %d (%s) for %s=%d",
				$trigger->id,
				$trigger->title,
				$trigger->owner_context,
				$trigger->owner_context_id
			));
			
			$trigger->runDecisionTree($values);
			
			self::decreaseDepth();
		}

		/*
		 * Clear our event chain when we finish all triggers and we're 
		 * no longer nested.
		 */
		if(0 == self::getDepth()) {
			//var_dump(self::getTriggerLog());
			//var_dump(self::getNodeLog());
			self::clear();
		}
		
		return;
		
		// [TODO] ACTION: HTTP POST
		/*
		if(extension_loaded('curl')) {
			$postfields = array(
				'json' => json_encode($values)
			);
			
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, "http://localhost/website/webhooks/notify.php");
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			$response = curl_exec($ch);
			curl_close($ch);
			echo($response);
		}
		*/	
	}
};

class ChCoreEventListener extends DevblocksEventListenerExtension {
	/**
	 * @param Model_DevblocksEvent $event
	 */
	function handleEvent(Model_DevblocksEvent $event) {
		// Cerberus Helpdesk Workflow
		switch($event->id) {
			case 'cron.heartbeat':
				$this->_handleCronHeartbeat($event);
				break;
				
			case 'cron.maint':
				$this->_handleCronMaint($event);
				break;
		}
	}

	private function _handleCronMaint($event) {
		DAO_Address::maint();
		DAO_Comment::maint();
		DAO_ConfirmationCode::maint();
		DAO_ExplorerSet::maint();
		DAO_Group::maint();
		DAO_Ticket::maint();
		DAO_Message::maint();
		DAO_Worker::maint();
		DAO_Notification::maint();
		DAO_Snippet::maint();
		DAO_ContactPerson::maint();
		DAO_OpenIdToContactPerson::maint();
		DAO_Attachment::maint();
	}
	
	private function _handleCronHeartbeat($event) {
		// Re-open any conversations past their reopen date
		$fields = array(
			DAO_Ticket::IS_CLOSED => 0,
			DAO_Ticket::IS_WAITING => 0,
			DAO_Ticket::DUE_DATE => 0
		);
		$where = sprintf("(%s = %d OR %s = %d) AND %s = %d AND %s > 0 AND %s < %d",
			DAO_Ticket::IS_WAITING,
			1,
			DAO_Ticket::IS_CLOSED,
			1,
			DAO_Ticket::IS_DELETED,
			0,
			DAO_Ticket::DUE_DATE,
			DAO_Ticket::DUE_DATE,
			time()
		);
		DAO_Ticket::updateWhere($fields, $where);
	}
};
