<?php
/***********************************************************************
 | Cerb(tm) developed by WebGroup Media, LLC.
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
								'The navigation bar is located at the top of your browser window. It displays a list of shortcuts to pages.',
								'bottomLeft',
								'topLeft',
								10,
								5
							),
							new DevblocksTourCallout(
								'body > table:first td:nth(1) b',
								'Worker Menu',
								'Clicking your name provides a menu with useful shortcuts.',
								'topRight',
								'bottomLeft',
								0,
								0
							),
							new DevblocksTourCallout(
								'body fieldset:nth(1)',
								'Social',
								'These resources will help you get the most out of Cerb6.',
								'bottomLeft',
								'topLeft',
								20,
								0
							),
						),
					);
				break;

			case 'pages':
				$tour = array(
					'title' => 'Workspace Pages',
					'body' =>
<<< EOF
Pages give you the freedom to build a completely personalized interface based on how you use the software. You can add as many new pages as you want, and your favorites can be added to the navigation menu for quick access.
EOF
					,
					'callouts' => array(
						new DevblocksTourCallout(
							'#tourHeaderMenu',
							'Navigation Bar',
							'The navigation bar is located at the top of your browser window. It displays a list of shortcuts to pages.',
							'bottomLeft',
							'topLeft',
							10,
							5
							),
						new DevblocksTourCallout(
							'UL.navmenu:first LI.add',
							'Pages Menu',
							'You can add pages by clicking on the downward-pointing arrow in the menu',
							'topLeft',
							'bottomLeft',
							10,
							-5
							),
					)
				);
				break;
			
				case 'search':
					$tour = array(
						'title' => 'Search',
						'body' => 'The search menu provides quick access to a worklist of any kind of object from anywhere in the application. You can find the search menu at the top of the screen in the far right side of the navigation menu.',
						'callouts' => array(
							//$callouts['tourDashboardSearchCriteria']
						)
					);
					break;
				
			case 'preferences':
				$tour = array(
			 	   'title' => 'Preferences',
					'body' => 'This screen allows you to change the personal preferences on your helpdesk account.',
				);
				break;

			case 'groups':
				$tour = array(
			 	   'title' => 'Group Setup',
			  	  'body' => 'This screen allows you to administer and configure groups for which you are a manager.  This includes members, buckets, mail routing rules, and other group-specific preferences.',
				);
				break;

			case 'config':
				switch(array_shift($path)) {
					default:
						$tour = array(
							'title' => 'Setup',
							'body' => 'This page is where you configure and customize Cerb6.',
							'callouts' => array(
								new DevblocksTourCallout(
									'DIV.cerb-menu',
									'Menu',
									'Use the menu to access different configuration sections.',
									'bottomLeft',
									'topLeft',
									20,
									10
									),
								new DevblocksTourCallout(
									'DIV.cerb-menu > UL > LI:nth(5)',
									'Plugins',
									'Use this menu to install and configure optional plugins that enhance Cerb6 functionality. You can also download third-party plugins from the community.',
									'bottomLeft',
									'topLeft',
									20,
									10
									),
							),
						);
						break;
						
					case 'branding':
						$tour = array(
							'title' => 'Logo & Title',
							'body' => 'This setup page provides options for personalizing your copy of Cerb6 with your own logo and browser title.',
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
							'body' => "This setup page manages your Cerb6 license.  If you don't have a license, one can be <a href='http://www.cerberusweb.com/buy' target='_blank'>purchased from the project website</a>.",
						);
						break;
						
					case 'scheduler':
						$tour = array(
							'title' => 'Scheduler',
							'body' => 'The scheduler is where you can set up tasks that will periodically run behind-the-scenes.',
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
							'title' => 'Worker Permissions',
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
						
					case 'mail_pop3':
						$tour = array(
							'title' => 'POP3 Accounts',
							'body' => "Here is where you specify the mailboxes that should be checked for new mail to import into Cerb6.",
						);
						break;
						
					case 'mail_routing':
						$tour = array(
							'title' => 'Mail Routing',
							'body' => "Mail routing determines which group should receive a new message.",
						);
						break;

					case 'mail_filtering':
						$tour = array(
							'title' => 'Mail Filtering',
							'body' => "Mail filtering provides a way to remove unwanted mail before it is processed or stored by the system.",
						);
						break;
						
					case 'mail_smtp':
						$tour = array(
							'title' => 'SMTP Server',
							'body' => "This is where you configure your outgoing mail server.",
						);
						break;

					case 'mail_from':
						$tour = array(
							'title' => 'Reply-To Addresses',
							'body' => "Each group or bucket can specify a reply-to address.  This is where you configure all the available reply-to email addresses.  It is <b>very important</b> that these addresses deliver to one of the mailboxes that Cerb6 checks for new mail, otherwise you won't receive correspondence from your audience.",
						);
						break;

					case 'mail_queue':
						$tour = array(
							'title' => 'Mail Queue',
							'body' => "This page displays the mail delivery queue.",
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
							'title' => 'Manage Plugins',
							'body' => "This is where you can extend Cerb6 by installing new functionality through plugins.",
							'callouts' => array(
							)
						);
						break;
						
				}
				break;

			case 'kb':
				$tour = array(
					'title' => 'Knowledgebase',
					'body' =>
<<< EOF
The knowledgebase is a collection of informative articles organized into categories.  Categories can be based on anything: product lines, languages, etc.
EOF
					,
					'callouts' => array(
					),
				);
				break;
				
			case 'profiles':
				switch(array_shift($path)) {
					default:
						$tour = array(
							'title' => 'Profiles',
							'body' =>
<<< EOF
A profile displays all the information related to a particular record.
EOF
							,
							'callouts' => array(
							),
						);
						break;
						
						
					case 'ticket':
						$tour = array(
							'title' => 'Ticket Profile',
							'body' =>
<<< EOF
This is a detailed profile page for an email conversation.
EOF
							,
							'callouts' => array(
								new DevblocksTourCallout(
									'SPAN#spanWatcherToolbar BUTTON:first',
									'Watchers',
									'A watcher will automatically receive notifications about new activity on this record.  Click this button to add or remove yourself as a watcher.',
									'bottomLeft',
									'topRight',
									-10,
									5
									),
								new DevblocksTourCallout(
									'FIELDSET.properties > DIV.property:nth(2)',
									'Mask',
									'Each conversation is identified by a "mask" that may be used as a reference number in future conversations, or over the phone.',
									'bottomRight',
									'topLeft',
									5,
									0
									),
								new DevblocksTourCallout(
									'FIELDSET.properties SPAN#displayTicketRequesterBubbles',
									'Recipients',
									'Your replies to this conversation will automatically be sent to all these recipients.',
									'bottomLeft',
									'topLeft',
									10,
									0
									),
								new DevblocksTourCallout(
									'#displayTabs',
									'Conversation Timeline',
									'This is where all email replies will be displayed for this ticket. Your responses will be sent to all requesters.',
									'bottomLeft',
									'topLeft',
									10,
									10
									),
								new DevblocksTourCallout(
									'#displayTabs DIV#ui-tabs-1 BUTTON#btnComment:first',
									'Comments',
									'Comments are a private way to leave messages for other workers regarding this conversation.  They are not visible to recipients.',
									'topLeft',
									'bottomMiddle',
									0,
									0
									),
								new DevblocksTourCallout(
									'#displayTabs > UL > li:nth(1)',
									'Activity Log',
									'This tab displays everything that has happened to this conversation: worker replies, customer replies, status changes, merges, and more.',
									'topLeft',
									'bottomRight',
									-20,
									-10
									),
								new DevblocksTourCallout(
									'#displayTabs > UL > li:nth(2)',
									'Links',
									'You can connect this conversation to any other record in the system: tasks, organizations, opportunities, time tracking, servers, domains, etc.',
									'bottomLeft',
									'topMiddle',
									0,
									10
									),
							)
						);
						break;
						
					case 'worker':
						$tour = array(
							'title' => 'Worker Profiles',
							'body' =>
<<< EOF
You can think of your profile as your homepage within Cerb6.  It provides quick access to your notifications, activity history, calendar, virtual attendant, and watchlist.
EOF
							,
							'callouts' => array(
							),
						);
						break;
				}
				break;
				
			case 'reports':
				$tour = array(
					'title' => 'Reports',
					'body' =>
<<< EOF
This page helps you to run detailed reports about the metrics collected by Cerb6.
EOF
					,
					'callouts' => array(
					),
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
	
	static function getTriggerStack() {
		return self::$_trigger_stack;
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
		$logger = DevblocksPlatform::getConsoleLog("Attendant");
		
		$logger->info(sprintf("EVENT: %s",
			$event->id
		));

		// Are we limited to only one trigger on this event, or all of them?
		
		if(isset($event->params['_whisper']['_trigger_id'][0])) {
			if(null != ($trigger = DAO_TriggerEvent::get($event->params['_whisper']['_trigger_id'][0]))) {
				$triggers[$trigger->id] = $trigger;
			}
			unset($event->params['_whisper']['_trigger_id']);
		} else {
			$triggers = DAO_TriggerEvent::getByEvent($event->id, false);
		}

		// Allowed
		
		if(empty($triggers))
			return;

		// We're restricting the scope of the event
		if(isset($event->params['_whisper']) && is_array($event->params['_whisper']) && !empty($event->params['_whisper'])) {
			foreach($triggers as $trigger_id => $trigger) { /* @var $trigger Model_TriggerEvent */
				if (
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

		// Lazy-loader dictionary
		$dict = new DevblocksDictionaryDelegate($values);
		
		// We're preloading some variable values
		if(isset($event->params['_variables']) && is_array($event->params['_variables'])) {
			foreach($event->params['_variables'] as $var_key => $var_val) {
				$dict->$var_key = $var_val;
			}
		}
		
		// Registry (trigger variables, etc)
		$registry = DevblocksPlatform::getRegistryService();
		
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
			
			$start_runtime = intval(microtime(true));
			
			$logger->info(sprintf("Running decision tree on trigger %d (%s) for %s=%d",
				$trigger->id,
				$trigger->title,
				$trigger->owner_context,
				$trigger->owner_context_id
			));
			
			$trigger->runDecisionTree($dict);
			
			// Increase the trigger run count
			$registry->increment('trigger.'.$trigger->id.'.counter', 1);
			
			$runtime_ms = intval((microtime(true) - $start_runtime) * 1000);

			$trigger->logUsage($runtime_ms);
			
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
			case 'comment.create':
				$this->_handleCommentCreate($event);
				break;
				
			case 'context.update':
				$this->_handleContextUpdate($event);
				break;
				
			case 'context.delete':
				$this->_handleContextDelete($event);
				break;
				
			case 'context.maint':
				$this->_handleContextMaint($event);
				break;
				
			case 'cron.heartbeat':
				$this->_handleCronHeartbeat($event);
				break;
				
			case 'cron.maint':
				$this->_handleCronMaint($event);
				break;
		}
	}

	private function _handleContextUpdate($event) {
		@$context = $event->params['context'];
		@$context_ids = $event->params['context_ids'];

		DAO_ContextScheduledBehavior::updateRelativeSchedules($context, $context_ids);
	}
	
	private function _handleContextDelete($event) {
		@$context = $event->params['context'];
		@$context_ids = $event->params['context_ids'];
		
		// Core
		DAO_AttachmentLink::removeAllByContext($context, $context_ids);
		DAO_Comment::deleteByContext($context, $context_ids);
		DAO_ContextActivityLog::deleteByContext($context, $context_ids);
		DAO_ContextLink::delete($context, $context_ids);
		DAO_CustomFieldValue::deleteByContextIds($context, $context_ids);
		DAO_Notification::deleteByContext($context, $context_ids);
		DAO_TriggerEvent::deleteByOwner($context, $context_ids);
		DAO_WorkspacePage::deleteByOwner($context, $context_ids);
	}
	
	private function _handleContextMaint($event) {
		$db = DevblocksPlatform::getDatabaseService();
		$logger = DevblocksPlatform::getConsoleLog('Maint');
		
		@$context = $event->params['context'];
		@$context_table = $event->params['context_table'];
		@$context_key = $event->params['context_key'];

		$context_index = $context_table . '.' . $context_key;
		
		$logger->info(sprintf("Running maintenance on context: %s", $context));
		
		// ===========================================================================
		// Comments

		$db->Execute(sprintf("DELETE QUICK ctx ".
			"FROM comment AS ctx ".
			"LEFT JOIN %s ON ctx.context_id=%s ".
			"WHERE ctx.context = %s ".
			"AND %s IS NULL",
			$context_table,
			$context_index,
			$db->qstr($context),
			$context_index
		));
		if(null != ($deletes = $db->Affected_Rows()))
			$logger->info(sprintf("Purged %d %s comments.", $deletes, $context));
		
		// ===========================================================================
		// Context Activity Log

		$db->Execute(sprintf("DELETE QUICK ctx ".
			"FROM context_activity_log AS ctx ".
			"LEFT JOIN %s ON ctx.target_context_id=%s ".
			"WHERE ctx.target_context = %s ".
			"AND %s IS NULL",
			$context_table,
			$context_index,
			$db->qstr($context),
			$context_index
		));
		if(null != ($deletes = $db->Affected_Rows()))
			$logger->info(sprintf("Purged %d %s activity log entries.", $deletes, $context));
		
		// ===========================================================================
		// Context Links
		
		$db->Execute(sprintf("DELETE QUICK ctx ".
			"FROM context_link AS ctx ".
			"LEFT JOIN %s ON ctx.from_context_id=%s ".
			"WHERE ctx.from_context = %s ".
			"AND %s IS NULL",
			$context_table,
			$context_index,
			$db->qstr($context),
			$context_index
		));
		if(null != ($deletes = $db->Affected_Rows()))
			$logger->info(sprintf("Purged %d %s context link sources.", $deletes, $context));
		
		$db->Execute(sprintf("DELETE QUICK ctx ".
			"FROM context_link AS ctx ".
			"LEFT JOIN %s ON ctx.to_context_id=%s ".
			"WHERE ctx.to_context = %s ".
			"AND %s IS NULL",
			$context_table,
			$context_index,
			$db->qstr($context),
			$context_index
		));
		if(null != ($deletes = $db->Affected_Rows()))
			$logger->info(sprintf("Purged %d %s context link targets.", $deletes, $context));
		
		// ===========================================================================
		// Custom fields
		
		$db->Execute(sprintf("DELETE QUICK ctx ".
			"FROM custom_field_stringvalue AS ctx ".
			"LEFT JOIN %s ON (%s=ctx.context_id) ".
			"WHERE ctx.context = %s ".
			"AND %s IS NULL",
			$context_table,
			$context_index,
			$db->qstr($context),
			$context_index
		));
		if(null != ($deletes = $db->Affected_Rows()))
			$logger->info(sprintf("Purged %d %s custom field strings.", $deletes, $context));
		
		$db->Execute(sprintf("DELETE QUICK ctx ".
			"FROM custom_field_numbervalue AS ctx ".
			"LEFT JOIN %s ON (%s=ctx.context_id) ".
			"WHERE ctx.context = %s ".
			"AND %s IS NULL",
			$context_table,
			$context_index,
			$db->qstr($context),
			$context_index
		));
		if(null != ($deletes = $db->Affected_Rows()))
			$logger->info(sprintf("Purged %d %s custom field numbers.", $deletes, $context));
		
		$db->Execute(sprintf("DELETE QUICK ctx ".
			"FROM custom_field_clobvalue AS ctx ".
			"LEFT JOIN %s ON (%s=ctx.context_id) ".
			"WHERE ctx.context = %s ".
			"AND %s IS NULL",
			$context_table,
			$context_index,
			$db->qstr($context),
			$context_index
		));
		if(null != ($deletes = $db->Affected_Rows()))
			$logger->info(sprintf("Purged %d %s custom field clobs.", $deletes, $context));
		
		// ===========================================================================
		// Notifications
		
		$db->Execute(sprintf("DELETE QUICK ctx ".
			"FROM notification AS ctx ".
			"LEFT JOIN %s ON ctx.context_id=%s ".
			"WHERE ctx.context = %s ".
			"AND %s IS NULL",
			$context_table,
			$context_index,
			$db->qstr($context),
			$context_index
		));
		if(null != ($deletes = $db->Affected_Rows()))
			$logger->info(sprintf("Purged %d %s notifications.", $deletes, $context));
		
		// ===========================================================================
		// Virtual Attendant Behavior
		
		$rs = $db->Execute(sprintf("SELECT ctx.id ".
			"FROM trigger_event AS ctx ".
			"LEFT JOIN %s ON ctx.owner_context_id=%s ".
			"WHERE ctx.owner_context = %s ".
			"AND %s IS NULL",
			$context_table,
			$context_index,
			$db->qstr($context),
			$context_index
		));
		
		if(is_resource($rs)) {
			$deletes = 0;
			
			while($row = mysql_fetch_row($rs)) {
				DAO_TriggerEvent::delete($row[0]);
				$deletes++;
			}
			
			if(null != ($deletes = $db->Affected_Rows()))
				$logger->info(sprintf("Purged %d %s virtual attendant behaviors.", $deletes, $context));
		}
	}
	
	private function _handleCronMaint($event) {
		DAO_Address::maint();
		DAO_Bucket::maint();
		DAO_Comment::maint();
		DAO_ConfirmationCode::maint();
		DAO_ExplorerSet::maint();
		DAO_Group::maint();
		DAO_Task::maint();
		DAO_Ticket::maint();
		DAO_Message::maint();
		DAO_Worker::maint();
		DAO_Notification::maint();
		DAO_Snippet::maint();
		DAO_ContactOrg::maint();
		DAO_ContactPerson::maint();
		DAO_OpenIdToContactPerson::maint();
		DAO_Attachment::maint();
		DAO_WorkspacePage::maint();
		DAO_WorkspaceTab::maint();
	}
	
	private function _handleCronHeartbeat($event) {
		// Re-open any conversations past their reopen date
		list($results, $null) = DAO_Ticket::search(
			array(),
			array(
				array(
					DevblocksSearchCriteria::GROUP_OR,
					SearchFields_Ticket::TICKET_CLOSED => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CLOSED,'=',1),
					SearchFields_Ticket::TICKET_WAITING => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_WAITING,'=',1),
				),
				SearchFields_Ticket::TICKET_DELETED => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_DELETED,'=',0),
				array(
					DevblocksSearchCriteria::GROUP_AND,
					new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_REOPEN_AT,DevblocksSearchCriteria::OPER_GT,0),
					new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_REOPEN_AT,DevblocksSearchCriteria::OPER_LT,time()),
				),
			),
			100,
			0,
			DAO_Ticket::ID,
			true,
			false
		);
		
		if(!empty($results)) {
			$fields = array(
				DAO_Ticket::IS_CLOSED => 0,
				DAO_Ticket::IS_WAITING => 0,
				DAO_Ticket::REOPEN_AT => 0
			);
			DAO_Ticket::update(array_keys($results), $fields);
		}
	}
	
	private function _handleCommentCreate($event) { /* @var $event Model_DevblocksEvent */
		@$fields = $event->params['fields'];
		
		if(!isset($fields[DAO_Comment::CONTEXT]) || !isset($fields[DAO_Comment::CONTEXT_ID]))
			return;
			
		// Context-specific behavior for comments
		switch($fields[DAO_Comment::CONTEXT]) {
			case CerberusContexts::CONTEXT_TASK:
				DAO_Task::update($fields[DAO_Comment::CONTEXT_ID], array(
					DAO_Task::UPDATED_DATE => time(),
				));
				break;
			case CerberusContexts::CONTEXT_TICKET:
				DAO_Ticket::update($fields[DAO_Comment::CONTEXT_ID], array(
					DAO_Ticket::UPDATED_DATE => time(),
				));
				break;
		}
	}
	
};
