<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2019, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerb.ai/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://cerb.ai	    http://webgroup.media
 ***********************************************************************/

class ChCoreTour extends DevblocksHttpResponseListenerExtension {
	function run(DevblocksHttpResponse $response, Smarty $tpl) {
		$path = $response->path;

		switch(array_shift($path)) {
			case 'welcome':
				$tour = array(
					'title' => 'Welcome!',
					'body' => "This assistant will help you become familiar with the application by following along and providing information about the current page.  You may follow the 'Points of Interest' links highlighted below to read tips about nearby functionality.",
					'callouts' => array(
							new DevblocksTourCallout(
								'#tourHeaderMenu',
								'Navigation Bar',
								'The navigation bar is located at the top of your browser window. It displays a list of shortcuts to pages. You can add and remove shortcuts here, and drag them to rearrange their order.',
								'middle bottom',
								'middle top'
							),
							new DevblocksTourCallout(
								'#cerb-logo',
								'Logo',
								'Click the logo as a shortcut to your default page.',
								'middle top',
								'middle bottom'
							),
							new DevblocksTourCallout(
								'#lnkSignedIn',
								'Worker Menu',
								'Clicking your name provides a menu with useful shortcuts.',
								'right bottom',
								'middle top-10'
							),
							new DevblocksTourCallout(
								'UL.navmenu:first LI.tour-navmenu-search',
								'Search page',
								'Use this page to search for any kind of record from anywhere.',
								'right bottom',
								'middle-10 top-5'
								),
							new DevblocksTourCallout(
								'body fieldset:nth(1)',
								'Social',
								'These resources will help you get the most out of Cerb.',
								'left bottom',
								'left+20 top'
							),
						),
					);
				break;

			case 'pages':
				switch(array_shift($path)) {
					case null:
						$tour = array(
							'title' => 'Workspace Pages',
							'body' =>"Pages give you the freedom to build a completely personalized interface based on how you use the software. You can add as many new pages as you want, and your favorites can be added to the navigation menu for quick access.",
							'callouts' => array(
								new DevblocksTourCallout(
									'#tourHeaderMenu',
									'Navigation Bar',
									'The navigation bar is located at the top of your browser window. It displays a list of shortcuts to pages.',
									'middle bottom',
									'middle top'
									),
								new DevblocksTourCallout(
									'#viewpages TABLE.worklist TD.title-toolbar SPAN.glyphicons-circle-plus',
									'Add Pages',
									'You can add a page by clicking on the (+) icon in the pages worklist.',
									'right bottom',
									'middle top'
									),
								new DevblocksTourCallout(
									'#viewpages TABLE.worklistBody > THEAD TH:nth(0) A',
									'Add to Menu',
									'You can add or remove a page from your navigation bar by clicking the icon in this column.',
									'left bottom',
									'middle top'
									),
								new DevblocksTourCallout(
									'#viewpages TABLE.worklistBody > THEAD TH:nth(1) A',
									'View Page',
									'You can view a page by clicking the link in this column.',
									'left bottom',
									'middle top'
									),
							)
						);
						break;
						
					default:
						$tour = array(
							'title' => 'Workspace Page',
							'body' =>"Pages give you the freedom to build a completely personalized interface based on how you use the software. You can add as many new pages as you want, and your favorites can be added to the navigation menu for quick access.",
							'callouts' => array(
								new DevblocksTourCallout(
									'DIV[id^=pageTabs]:first LI[role=tab]:last',
									'Add a workspace tab',
									'Click this tab to add new tabs to this workspace.',
									'left bottom',
									'middle top+5'
									),
								new DevblocksTourCallout(
									'FORM BUTTON.add:first',
									'Add a page shortcut',
									'Click this button to add or remove the page from your shortcuts.',
									'right bottom',
									'middle top'
									),
								new DevblocksTourCallout(
									'FORM BUTTON.config-page:first',
									'Edit pages and tabs',
									'Click this button to edit pages and tabs.',
									'right bottom',
									'middle top+5'
									),
								new DevblocksTourCallout(
									'FORM BUTTON.config-page:first',
									'Export pages and tabs',
									'Click this button to export pages and tabs so you can import them elsewhere.',
									'right bottom',
									'middle top+5'
									),
							)
						);
						break;
						
				}
				break;
			
				case 'search':
					$tour = array(
						'title' => 'Search',
						'body' => 'The search menu provides quick access to a worklist of any kind of object from anywhere in the application. You can find the search menu at the top of the screen in the far right side of the navigation menu.',
						'callouts' => array(
							new DevblocksTourCallout(
								'UL.navmenu:first LI.tour-navmenu-search',
								'Search page',
								'Use this page to search for any kind of record from anywhere.',
								'right bottom',
								'middle top'
								),
							new DevblocksTourCallout(
								'FORM.quick-search:first',
								'Quick search',
								'Use this widget to quickly add search filters to the worklist.',
								'right bottom',
								'middle top'
								),
							new DevblocksTourCallout(
								'TABLE.worklist:first',
								'Worklist',
								'This worklist displays your current search results.',
								'left bottom',
								'left+5 top+5'
								),
					)
					);
					break;
				
			case 'config':
				switch(array_shift($path)) {
					default:
						$tour = array(
							'title' => 'Setup',
							'body' => 'This page is where you configure and customize Cerb.',
							'callouts' => array(
								new DevblocksTourCallout(
									'DIV.cerb-menu',
									'Menu',
									'Use the menu to access different configuration sections.',
									'middle bottom',
									'middle top'
									),
								new DevblocksTourCallout(
									'DIV.cerb-menu > UL > LI:nth(8)',
									'Plugins',
									'Use this menu to install and configure optional plugins that enhance Cerb functionality. You can also download third-party plugins from the community.',
									'left bottom',
									'left+20 top+5'
									),
							),
						);
						break;
						
					case 'branding':
						$tour = array(
							'title' => 'Branding',
							'body' => 'This setup page provides options for personalizing your copy of Cerb with your own logo and browser title.',
						);
						break;
						
					case 'localization':
						$tour = array(
							'title' => 'Localization',
							'body' => 'This setup page provides options for localizing your copy of Cerb.',
						);
						break;

					case 'security':
						$tour = array(
							'title' => 'Security',
							'body' => 'Security is IP-based.  On this page you should enter the IPs that are allowed to access the /cron and /update URLs.',
						);
						break;
						
					case 'fields':
						$tour = array(
							'title' => 'Custom Fields',
							'body' => 'Custom fields allow you to track any kind of information that is important to your team for each record type.',
						);
						break;
						
					case 'license':
						$tour = array(
							'title' => 'License',
							'body' => "This setup page manages your Cerb license.  If you don't have a license, one can be <a href='https://cerb.ai/pricing/' target='_blank' rel='noopener'>purchased from the project website</a>.",
						);
						break;
						
					case 'scheduler':
						$tour = array(
							'title' => 'Scheduler',
							'body' => 'The scheduler is where you can set up tasks that will periodically run behind-the-scenes.',
						);
						break;

					case 'snippets':
						$tour = array(
							'title' => 'Snippets',
							'body' => 'This setup page provides a place to globally manage snippets.',
						);
						break;
						
					case 'sessions':
						$tour = array(
							'title' => 'Sessions',
							'body' => 'This setup page provides a place to globally manage worker sessions.',
						);
						break;

					case 'groups':
						$tour = array(
							'title' => 'Groups',
							'body' => "Here you may organize workers into groups.  Common groups often include departments (such as: Support, Sales, Development, Marketing, Billing, etc.) or various projects that warrant their own workloads.",
						);
						break;
						
					case 'acl':
						$tour = array(
							'title' => 'Worker Roles',
							'body' => "This setup page provides a way to restrict the access rights of workers by role.",
						);
						break;
						
					case 'workers':
						$tour = array(
							'title' => 'Worker',
							'body' => "Here you may create, manage, or remove worker accounts.",
						);
						break;
						
					case 'mail_incoming':
						$tour = array(
							'title' => 'Incoming Mail',
							'body' => "This page configures incoming mail preferences.",
						);
						break;
						
					case 'mail_outgoing':
						$tour = array(
							'title' => 'Outgoing Mail',
							'body' => "This page configures outgoing mail preferences.",
						);
						break;
						
					case 'storage_content':
						$tour = array(
							'title' => 'Storage Content',
							'body' => "This page provides a summary of the content stored by the system.  You can also configure when and where each kind of content is archived for long-term storage.",
						);
						break;

					case 'storage_profiles':
						$tour = array(
							'title' => 'Storage Profiles',
							'body' => "Storage profiles allow you to create new archival locations for storing content; for example, in Amazon's durable S3 storage service.",
						);
						break;

					case 'storage_attachments':
						$tour = array(
							'title' => 'Attachments',
							'body' => "This page displays email attachments as a subset of stored content.  This can be used to purge inactive or unusually large content.",
						);
						break;
						
					case 'portals':
						$tour = array(
							'title' => 'Community Portals',
							'body' => "Here you can create public, community-facing interfaces -- knowledgebases, contact forms, and Support Centers.",
						);
						break;
						
					case 'plugins':
						$tour = array(
							'title' => 'Plugins',
							'body' => "This is where you can add new functionality to Cerb through plugins.",
							'callouts' => array(
								new DevblocksTourCallout(
									'#viewplugins_installed',
									'Installed plugins',
									'This worklist displays the plugins that have been installed.',
									'left bottom',
									'left+10 top+5'
									),
							)
						);
						break;
						
				}
				break;

			case 'profiles':
				switch(array_shift($path)) {
					default:
						$tour = array(
							'title' => 'Profiles',
							'body' => "A profile displays all the information related to a particular record.",
							'callouts' => array(
							),
						);
						break;
					
					case 'group':
						$tour = array(
							'title' => 'Group Profile',
							'body' => 'This page enables you to configure groups for which you are a manager.  This includes members, buckets, mail routing rules, and other group-specific preferences.',
						);
						break;
					
					case 'kb':
						$tour = array(
							'title' => 'Knowledgebase Article',
							'body' => "The knowledgebase is a collection of informative articles organized into categories.  Categories can be based on anything: product lines, languages, etc.",
							'callouts' => array(
							),
						);
						break;
						
					case 'ticket':
						$tour = array(
							'title' => 'Ticket Profile',
							'body' => "This is a detailed profile page for an email conversation.",
							'callouts' => array(
								new DevblocksTourCallout(
									'#btnProfileCard',
									'Peek',
									'Click this button to open the ticket card.',
									'left bottom',
									'middle top'
									),
								new DevblocksTourCallout(
									'#btnProfileCardEdit',
									'Edit',
									'Click this button to edit the ticket properties.',
									'left bottom',
									'middle top'
									),
								new DevblocksTourCallout(
									'#spanProfileWatchers',
									'Watchers',
									'A watcher will automatically receive notifications about new activity on this record.  Click this button to add or remove yourself as a watcher.',
									'left bottom',
									'middle top'
									),
								new DevblocksTourCallout(
									'#profileToolbar',
									'Mask',
									"Each conversation is identified by a mask that may be used as a reference number in future conversations, or over the phone.",
									'left bottom',
									'left top'
									),
								new DevblocksTourCallout(
									'#profile-tabs-cerberusweb_contexts_ticket',
									'Conversation Timeline',
									'This is where all email replies will be displayed for this ticket. Your responses will be sent to all participants.',
									'left bottom',
									'left top'
									),
								new DevblocksTourCallout(
									'#btnProfileComment',
									'Comments',
									'Comments are a private way to leave messages for other workers regarding this conversation.  They are not visible to participants.',
									'left top',
									'middle bottom'
									),
								new DevblocksTourCallout(
									'#profile-tabs-cerberusweb_contexts_ticket',
									'Activity Log',
									'The Log tab displays everything that has happened to this conversation: worker replies, customer replies, status changes, merges, and more.',
									'left bottom',
									'left top'
									),
							)
						);
						break;
						
					case 'worker':
						$tour = array(
							'title' => 'Worker Profiles',
							'body' => "You can think of your profile as your homepage within Cerb.  It provides quick access to your notifications, activity history, calendar, bot, and watchlist.",
							'callouts' => array(
							),
						);
						break;
				}
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
	
	static function getTriggerStack() {
		return self::$_trigger_stack;
	}
	
	static function getTriggerLog() {
		return self::$_trigger_log;
	}
	
	static function getNodeLog() {
		return self::$_traversal_log;
	}
	
	static function setNodeLog(array $log) {
		self::$_traversal_log = $log;
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
		$logger = DevblocksPlatform::services()->log('Bot');
		
		$logger->info(sprintf("EVENT: %s",
			$event->id
		));

		// Keep track of the runners to return at the end
		
		$runners = [];
		$triggers = [];
		
		// Load all VAs
		
		$trigger_vas = DAO_Bot::getAll();
		
		// Are we limited to only one trigger on this event, or all of them?
		
		if(isset($event->params['_whisper']['_trigger_id'][0])) {
			if(null != ($trigger = DAO_TriggerEvent::get($event->params['_whisper']['_trigger_id'][0]))) {
				$triggers[$trigger->id] = $trigger;
			}
			unset($event->params['_whisper']['_trigger_id']);
			
		} else {
			$triggers = DAO_TriggerEvent::getByEvent($event->id, false);
		}
		
		// Filter by matching event params on triggers
		if(isset($event->params['_whisper']['event_params']) && isset($event->params['_whisper']['event_params'])) {
			foreach($triggers as $trigger_id => $trigger) {
				$pass = true;
				
				foreach($event->params['_whisper']['event_params'] as $k => $v) {
					if(!$pass)
						break;
					
					@$ref_v = $trigger->event_params[$k];
					
					if(is_array($ref_v)) {
						if(!in_array($v, $ref_v)) {
							$pass = false;
						}
					
					} else {
						if($ref_v != $v) {
							$pass = false;
						}
					}
				}
				
				if(!$pass)
					unset($triggers[$trigger_id]);
			}
			
			unset($event->params['_whisper']['event_params']);
		}
		
		// We're restricting the scope of the event
		if(isset($event->params['_whisper']) && is_array($event->params['_whisper']) && !empty($event->params['_whisper'])) {
			foreach($triggers as $trigger_id => $trigger) { /* @var $trigger Model_TriggerEvent */
				if(false == (@$trigger_va = $trigger_vas[$trigger->bot_id]))
					continue;

				if($trigger_va->is_disabled)
					continue;
				
				if (
					null != ($allowed_ids = @$event->params['_whisper'][$trigger_va->owner_context])
					&& in_array($trigger_va->owner_context_id, !is_array($allowed_ids) ? array($allowed_ids) : $allowed_ids)
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
		
		if(empty($triggers))
			return;
		
		if(null == ($mft = Extension_DevblocksEvent::get($event->id, false)))
			return;
		
		if(null == ($event_ext = $mft->createInstance())
			|| !$event_ext instanceof Extension_DevblocksEvent)  /* @var $event_ext Extension_DevblocksEvent */
				return;
		
		// Load only if needed
		$dict = null;
		
		foreach($triggers as $trigger) { /* @var $trigger Model_TriggerEvent */
			if(false == (@$trigger_va = $trigger_vas[$trigger->bot_id]))
				continue;
			
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
			
			$logger->info(sprintf("Running behavior %s (#%d) for %s (#%d)",
				$trigger->title,
				$trigger->id,
				$trigger_va->name,
				$trigger->bot_id
			));
			
			// Load the intermediate data ONCE! (if at least one VA is responding)
			if(is_null($dict)) {
				$event_ext->setEvent($event, $trigger);
				$values = $event_ext->getValues();
				
				// Lazy-loader dictionary
				$dict = new DevblocksDictionaryDelegate($values);
				
				// We're preloading some variable values
				if(isset($event->params['_variables']) && is_array($event->params['_variables'])) {
					foreach($event->params['_variables'] as $var_key => $var_val) {
						if(!array_key_exists($var_key, $trigger->variables))
							continue;
						
						switch($trigger->variables[$var_key]['type']) {
							case Model_CustomField::TYPE_LINK:
								$link_context = $trigger->variables[$var_key]['params']['context'] ?? null;
								if($link_context && DevblocksPlatform::strEndsWith($var_key, '_id')) {
									$ctx_key = mb_substr($var_key, 0, -3) . '__context';
									$dict->set($ctx_key, $link_context);
								}
								$dict->$var_key = $var_val;
								break;
								
							default:
								$dict->$var_key = $var_val;
								break;
						}
					}
				}
				
				unset($values);
			}
			
			$trigger->runDecisionTree($dict, false, $event_ext);
			
			// Snapshot the dictionary of the behavior at conclusion
			$runners[$trigger->id] = $dict;
			
			self::decreaseDepth();
		}

		/*
		 * Clear our event chain when we finish all triggers and we're
		 * no longer nested.
		 */
		if(0 == self::getDepth()) {
			self::clear();
		}
		
		return $runners;
	}
};

class ChCoreEventListener extends DevblocksEventListenerExtension {
	/**
	 * @param Model_DevblocksEvent $event
	 */
	function handleEvent(Model_DevblocksEvent $event) {
		// Cerb Workflow
		switch($event->id) {
			case 'comment.create':
				$this->_handleCommentCreate($event);
				break;
				
			case 'context.update':
				$this->_handleContextUpdate($event);
				break;
				
			case 'context.delete':
				$this->_handleContextDelete($event);
				break;
				
			case 'context_link.set':
				$this->_handleContextLinkSet($event);
				break;
				
			case 'cron.heartbeat':
				$this->_handleCronHeartbeat($event);
				break;
				
			case 'cron.maint':
				$this->_handleCronMaint($event);
				break;
		}
	}
	
	private function _handleContextLinkSet($event) {
		$from_context = $event->params['from_context'] ?? null;
		$from_context_id = $event->params['from_context_id'] ?? null;
		$to_context = $event->params['to_context'] ?? null;
		$to_context_id = $event->params['to_context_id'] ?? null;
		
		if($to_context == Context_ProjectBoardColumn::ID) {
			if(false == ($to_column = DAO_ProjectBoardColumn::get($to_context_id)))
				return;
			
			$to_column->runDropActionsForCard($from_context, $from_context_id);
		}
	}
	
	private function _handleContextUpdate($event) {
		$context = $event->params['context'] ?? null;
		$context_ids = $event->params['context_ids'] ?? null;
		
		DAO_ContextScheduledBehavior::updateRelativeSchedules($context, $context_ids);
	}
	
	private function _handleContextDelete($event) {
		$context = $event->params['context'] ?? null;
		$context_ids = $event->params['context_ids'] ?? null;
		
		if(empty($context))
			return;
		
		if(!is_array($context_ids) || empty($context_ids))
			return;
		
		// Core
		DAO_Attachment::deleteLinks($context, $context_ids);
		DAO_Calendar::deleteByContext($context, $context_ids);
		DAO_Comment::deleteByContext($context, $context_ids);
		DAO_ContextActivityLog::deleteByContext($context, $context_ids);
		DAO_ContextAlias::delete($context, $context_ids);
		DAO_ContextAvatar::deleteByContext($context, $context_ids);
		DAO_ContextLink::delete($context, $context_ids);
		DAO_ContextMergeHistory::deleteByContextIds($context, $context_ids);
		DAO_CustomFieldset::deleteByOwner($context, $context_ids);
		DAO_CustomFieldset::removeByContextIds($context, $context_ids, false);
		DAO_CustomFieldValue::deleteByContextIds($context, $context_ids);
		DAO_Notification::deleteByContext($context, $context_ids);
		DAO_ContextScheduledBehavior::deleteByContext($context, $context_ids);
		DAO_Snippet::deleteByOwner($context, $context_ids);
		DAO_Bot::deleteByOwner($context, $context_ids);
		DAO_WorkspacePage::deleteByOwner($context, $context_ids);
	}
	
	private function _handleCronMaint($event) {
		DevblocksPlatform::services()->queue()->maint();
		
		DAO_AutomationLog::maint();
		DAO_BotSession::maint();
		DAO_ConfirmationCode::maint();
		DAO_ExplorerSet::maint();
		DAO_OAuthToken::maint();
		DAO_Ticket::maint();
		DAO_Worker::maint();
		DAO_Notification::maint();
		DAO_Attachment::maint();
		DAO_WorkerViewModel::flush();
		DAO_ContextBulkUpdate::maint();
		
		DevblocksPlatform::services()->metrics()->maint();
	}
	
	private function _handleCronHeartbeat($event) {
		$this->_handleCronHeartbeatMetrics();
		$this->_handleCronHeartbeatReopenTickets();
		$this->_handleCronHeartbeatReopenTasks();
		DAO_AutomationContinuation::maint();
		DAO_AutomationDatastore::maint();
		DAO_AutomationResource::maint();
		DAO_BotDatastore::maint();
		DAO_BotInteractionProactive::maint();
		DAO_DevblocksRegistry::maint();
		DAO_MessageHtmlCache::maint();
		Cerb_DevblocksSessionHandler::gc(0); // Purge inactive sessions
	}
	
	private function _handleCronHeartbeatMetrics() {
		$metrics = DevblocksPlatform::services()->metrics();
		
		// Active workers
		
		$results = Cerb_DevblocksSessionHandler::getLoggedInSeats();
		
		if (is_array($results)) {
			foreach ($results as $row) {
				$metrics->increment('cerb.workers.active', 1, ['worker_id' => $row['user_id']]);
			}
		}

		$this->_handleCronHeartbeatMetricsTicket();
	}
	
	private function _handleCronHeartbeatMetricsTicket() {
		$metrics = DevblocksPlatform::services()->metrics();
		$registry = DevblocksPlatform::services()->registry();
		$db = DevblocksPlatform::services()->database();
		
		// ============================
		// Open tickets by group/bucket
		
		$registry_key = 'metrics.cerb.tickets.open.last';
		
		$last_ts = $registry->get($registry_key, DevblocksRegistryEntry::TYPE_NUMBER, 0);
		
		// If we last persisted this within 15 mins (but non-zero), abort
		if ($last_ts && (time() - $last_ts) < 900)
			return;
		
		$results = $db->GetArrayReader("SELECT COUNT(id) AS hits, group_id, bucket_id FROM ticket WHERE status_id = 0 GROUP BY group_id, bucket_id");
		
		if (is_array($results)) {
			foreach ($results as $row) {
				$metrics->increment('cerb.tickets.open', $row['hits'], ['group_id' => $row['group_id'], 'bucket_id' => $row['bucket_id']]);
			}
		}
		
		$registry->set($registry_key, time(), DevblocksRegistryEntry::TYPE_NUMBER);
	}
	
	private function _handleCronHeartbeatReopenTickets() {
		// Re-open any conversations past their reopen date
		list($results,) = DAO_Ticket::search(
			array(),
			array(
				SearchFields_Ticket::TICKET_STATUS_ID => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_STATUS_ID,'in',array(Model_Ticket::STATUS_WAITING, Model_Ticket::STATUS_CLOSED)),
				array(
					DevblocksSearchCriteria::GROUP_AND,
					new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_REOPEN_AT,DevblocksSearchCriteria::OPER_GT,0),
					new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_REOPEN_AT,DevblocksSearchCriteria::OPER_LT,time()),
				),
			),
			200,
			0,
			DAO_Ticket::ID,
			true,
			false
		);
		
		$fields = array(
			DAO_Ticket::STATUS_ID => Model_Ticket::STATUS_OPEN,
			DAO_Ticket::REOPEN_AT => 0
		);
		
		// Only update records with fields that changed
		
		if(!is_array($results))
			return;
		
		$models = DAO_Ticket::getIds(array_keys($results));
		
		foreach($models as $model_id => $model) {
			$update_fields = Cerb_ORMHelper::uniqueFields($fields, $model);
			
			if(!empty($update_fields))
				DAO_Ticket::update($model_id, $update_fields);
		}
	}
	
	private function _handleCronHeartbeatReopenTasks() {
		// Re-open any conversations past their reopen date
		list($results,) = DAO_Task::search(
			array(),
			array(
				SearchFields_Task::STATUS_ID => new DevblocksSearchCriteria(SearchFields_Task::STATUS_ID,'in',array(1,2)),
				array(
					DevblocksSearchCriteria::GROUP_AND,
					new DevblocksSearchCriteria(SearchFields_Task::REOPEN_AT,DevblocksSearchCriteria::OPER_GT,0),
					new DevblocksSearchCriteria(SearchFields_Task::REOPEN_AT,DevblocksSearchCriteria::OPER_LT,time()),
				),
			),
			200,
			0,
			DAO_Task::ID,
			true,
			false
		);
		
		$fields = array(
			DAO_Task::STATUS_ID => 0,
			DAO_Task::REOPEN_AT => 0
		);
		
		// Only update records with fields that changed
		$models = DAO_Task::getIds(array_keys($results));
		
		foreach($models as $model_id => $model) {
			$update_fields = Cerb_ORMHelper::uniqueFields($fields, $model);
			
			if(!empty($update_fields))
				DAO_Task::update($model_id, $update_fields);
		}
	}
	
	private function _handleCommentCreate($event) { /* @var $event Model_DevblocksEvent */
		$fields = $event->params['fields'] ?? null;
		
		if(!isset($fields[DAO_Comment::CONTEXT]) || !isset($fields[DAO_Comment::CONTEXT_ID]))
			return;
			
		// Context-specific behavior for comments
		switch($fields[DAO_Comment::CONTEXT]) {
			case CerberusContexts::CONTEXT_TASK:
				$update_fields = array(
					DAO_Task::UPDATED_DATE => time(),
				);
				DAO_Task::update($fields[DAO_Comment::CONTEXT_ID], $update_fields);
				break;
				
			case CerberusContexts::CONTEXT_TICKET:
				$update_fields = array(
					DAO_Ticket::UPDATED_DATE => time(),
				);
				DAO_Ticket::update($fields[DAO_Comment::CONTEXT_ID], $update_fields, false);
				break;
		}
	}
	
};
