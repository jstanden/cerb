<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2017, Webgroup Media LLC
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

class PageSection_ProfilesWorker extends Extension_PageSection {
	function render() {
		$request = DevblocksPlatform::getHttpRequest();
		$stack = $request->path;
		
		if(!isset($stack[2]))
			return;

		$tpl = DevblocksPlatform::services()->template();
		$request = DevblocksPlatform::getHttpRequest();
		
		$context = CerberusContexts::CONTEXT_WORKER;
		$active_worker = CerberusApplication::getActiveWorker();

		$stack = $request->path;
		
		@array_shift($stack); // profiles
		@array_shift($stack); // worker
		
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		$workers = DAO_Worker::getAllActive();
		$tpl->assign('workers', $workers);
		
		@$id = array_shift($stack);

		switch($id) {
			case 'me':
				$worker_id = $active_worker->id;
				break;
				
			default:
				@$worker_id = intval($id);
				break;
		}

		if(false != (@$tab = array_shift($stack)))
			$tpl->assign('tab', $tab);
		
		$point = 'cerberusweb.profiles.worker.' . $worker_id;

		if(empty($worker_id) || null == ($worker = DAO_Worker::get($worker_id)))
			return;
			
		// Dictionary
		$labels = array();
		$values = array();
		CerberusContexts::getContext($context, $worker, $labels, $values, '', true, false);
		$dict = DevblocksDictionaryDelegate::instance($values);
		$tpl->assign('dict', $dict);
		
		// Properties
		
		$translate = DevblocksPlatform::getTranslationService();
		
		$properties = array();
		
		$properties['email'] = array(
			'label' => mb_ucfirst($translate->_('common.email')),
			'type' => Model_CustomField::TYPE_LINK,
			'params' => array('context' => CerberusContexts::CONTEXT_ADDRESS),
			'value' => $dict->address_id,
		);
		
		if(!empty($dict->location)) {
			$properties['location'] = array(
				'label' => mb_ucfirst($translate->_('common.location')),
				'type' => Model_CustomField::TYPE_SINGLE_LINE,
				'value' => $dict->location,
			);
		}
		
		$properties['is_superuser'] = array(
			'label' => mb_ucfirst($translate->_('worker.is_superuser')),
			'type' => Model_CustomField::TYPE_CHECKBOX,
			'value' => $dict->is_superuser,
		);
		
		if(!empty($dict->mobile)) {
			$properties['mobile'] = array(
				'label' => mb_ucfirst($translate->_('common.mobile')),
				'type' => Model_CustomField::TYPE_SINGLE_LINE,
				'value' => $dict->mobile,
			);
		}
		
		if(!empty($dict->phone)) {
			$properties['phone'] = array(
				'label' => mb_ucfirst($translate->_('common.phone')),
				'type' => Model_CustomField::TYPE_SINGLE_LINE,
				'value' => $dict->phone,
			);
		}
		
		$properties['language'] = array(
			'label' => mb_ucfirst($translate->_('common.language')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $dict->language,
		);
		
		$properties['timezone'] = array(
			'label' => mb_ucfirst($translate->_('common.timezone')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $dict->timezone,
		);
		
		if(!empty($dict->calendar_id)) {
			$properties['calendar_id'] = array(
				'label' => mb_ucfirst($translate->_('common.calendar')),
				'type' => Model_CustomField::TYPE_LINK,
				'params' => array('context' => CerberusContexts::CONTEXT_CALENDAR),
				'value' => $dict->calendar_id,
			);
		}
		
		// Custom Fields

		@$values = array_shift(DAO_CustomFieldValue::getValuesByContextIds($context, $dict->id)) or array();
		$tpl->assign('custom_field_values', $values);
		
		$properties_cfields = Page_Profiles::getProfilePropertiesCustomFields($context, $values);
		
		if(!empty($properties_cfields))
			$properties = array_merge($properties, $properties_cfields);

		// Custom Fieldsets

		$properties_custom_fieldsets = Page_Profiles::getProfilePropertiesCustomFieldsets($context, $dict->id, $values);
		$tpl->assign('properties_custom_fieldsets', $properties_custom_fieldsets);
		
		// Profile counts
		$profile_counts = array(
			'bots' => DAO_Bot::count($context, $dict->id),
			'groups' => DAO_Group::countByMemberId($dict->id),
		);
		$tpl->assign('profile_counts', $profile_counts);
		
		// Link counts
		
		$properties_links = array(
			$context => array(
				$dict->id => 
					DAO_ContextLink::getContextLinkCounts(
						$context,
						$dict->id,
						array(CerberusContexts::CONTEXT_CUSTOM_FIELDSET)
					),
			),
		);
		
		$tpl->assign('properties_links', $properties_links);
		
		// Properties
		
		$tpl->assign('properties', $properties);
		
		// Interactions
		$interactions = Event_GetInteractionsForWorker::getInteractionsByPointAndWorker('record:' . $context, $dict, $active_worker);
		$interactions_menu = Event_GetInteractionsForWorker::getInteractionMenu($interactions);
		$tpl->assign('interactions_menu', $interactions_menu);
		
		// Tabs
		$tab_manifests = Extension_ContextProfileTab::getExtensions(false, $context);
		$tpl->assign('tab_manifests', $tab_manifests);
		
		// Prefs
		$profile_worker_prefs = DAO_WorkerPref::getByWorker($dict->id);
		$tpl->assign('profile_worker_prefs', $profile_worker_prefs);
		
		// Template
		$tpl->display('devblocks:cerberusweb.core::profiles/worker.tpl');
	}
	
	function savePeekJsonAction() {
		@$id = DevblocksPlatform::importGPC($_POST['id'],'integer');
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string');
		@$delete = DevblocksPlatform::importGPC($_POST['do_delete'],'integer',0);

		$translate = DevblocksPlatform::getTranslationService();
		$active_worker = CerberusApplication::getActiveWorker();
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			if(!$active_worker || !$active_worker->is_superuser)
				throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.admin'));
	
			if(empty($first_name)) $first_name = "Anonymous";
			
			if(!empty($id) && !empty($delete)) {
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", CerberusContexts::CONTEXT_WORKER)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				// Can't delete or disable self
				if($active_worker->id == $id)
					throw new Exception_DevblocksAjaxValidationError("You can't delete yourself.");
					
				DAO_Worker::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
				
			} else {
				@$first_name = DevblocksPlatform::importGPC($_POST['first_name'],'string');
				@$last_name = DevblocksPlatform::importGPC($_POST['last_name'],'string');
				@$aliases = DevblocksPlatform::importGPC($_POST['aliases'],'string','');
				@$title = DevblocksPlatform::importGPC($_POST['title'],'string');
				@$email_id = DevblocksPlatform::importGPC($_POST['email_id'],'integer', 0);
				@$dob = DevblocksPlatform::importGPC($_POST['dob'],'string', '');
				@$location = DevblocksPlatform::importGPC($_POST['location'],'string', '');
				@$mobile = DevblocksPlatform::importGPC($_POST['mobile'],'string', '');
				@$phone = DevblocksPlatform::importGPC($_POST['phone'],'string', '');
				@$gender = DevblocksPlatform::importGPC($_POST['gender'],'string', '');
				@$auth_extension_id = DevblocksPlatform::importGPC($_POST['auth_extension_id'],'string');
				@$at_mention_name = DevblocksPlatform::strToPermalink(DevblocksPlatform::importGPC($_POST['at_mention_name'],'string'));
				@$language = DevblocksPlatform::importGPC($_POST['lang_code'],'string');
				@$timezone = DevblocksPlatform::importGPC($_POST['timezone'],'string');
				@$time_format = DevblocksPlatform::importGPC($_POST['time_format'],'string');
				@$calendar_id = DevblocksPlatform::importGPC($_POST['calendar_id'],'string');
				@$password_new = DevblocksPlatform::importGPC($_POST['password_new'],'string');
				@$password_verify = DevblocksPlatform::importGPC($_POST['password_verify'],'string');
				@$is_superuser = DevblocksPlatform::importGPC($_POST['is_superuser'],'bit', 0);
				@$disabled = DevblocksPlatform::importGPC($_POST['is_disabled'],'bit',0);
				@$group_memberships = DevblocksPlatform::importGPC($_POST['group_memberships'],'array');
				
				// ============================================
				// Defaults

				if(!in_array($gender, array('M','F','')))
					$gender = '';
				
				$dob_ts = null;
				
				$is_superuser = ($active_worker->is_superuser && $is_superuser) ? 1 : 0;
				
				// ============================================
				// Validation
				
				// Verify passwords if not blank
				if($password_new && ($password_new != $password_verify))
					throw new Exception_DevblocksAjaxValidationError("The given passwords do not match.", 'password_new');
				
				if(empty($id)) {
					$fields = array(
						DAO_Worker::FIRST_NAME => $first_name,
						DAO_Worker::LAST_NAME => $last_name,
						DAO_Worker::TITLE => $title,
						DAO_Worker::IS_SUPERUSER => $is_superuser,
						DAO_Worker::IS_DISABLED => $disabled,
						DAO_Worker::EMAIL_ID => $email_id,
						DAO_Worker::AUTH_EXTENSION_ID => $auth_extension_id,
						DAO_Worker::AT_MENTION_NAME => $at_mention_name,
						DAO_Worker::LANGUAGE => $language,
						DAO_Worker::TIMEZONE => $timezone,
						DAO_Worker::TIME_FORMAT => $time_format,
						DAO_Worker::GENDER => $gender,
						DAO_Worker::LOCATION => $location,
						DAO_Worker::DOB => (null == $dob_ts) ? null : gmdate('Y-m-d', $dob_ts),
						DAO_Worker::MOBILE => $mobile,
						DAO_Worker::PHONE => $phone,
					);
					
					if(!DAO_Worker::validate($fields, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_Worker::onBeforeUpdateByActor($active_worker, $fields, null, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(false == ($id = DAO_Worker::create($fields)))
						return false;
					
					DAO_Worker::onUpdateByActor($active_worker, $fields, $id);
					
					// Creating new worker.  If no password, email them an invite
					if(empty($password_new)) {
						$url = DevblocksPlatform::services()->url();
						$worker = DAO_Worker::get($id);
						
						$labels = $values = [];
						CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, $worker, $worker_labels, $worker_values, '', true, true);
						CerberusContexts::merge('worker_', null, $worker_labels, $worker_values, $labels, $values);
						
						$values['url'] = $url->write('c=login', true) . '?email=' . rawurlencode($worker->getEmailString());
						
						CerberusApplication::sendEmailTemplate($worker->getEmailString(), 'worker_invite', $values);
					}
					
					// View marquee
					if(!empty($id) && !empty($view_id)) {
						C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_WORKER, $id);
					}
					
				} // end create worker
				
				// Calendar
				
				// Create a calendar for this worker
				if('new' == $calendar_id) {
					$fields = array(
						DAO_Calendar::NAME => sprintf("%s%s's Calendar", $first_name, $last_name ? (' ' . $last_name) : ''),
						DAO_Calendar::OWNER_CONTEXT => CerberusContexts::CONTEXT_WORKER,
						DAO_Calendar::OWNER_CONTEXT_ID => $id,
						DAO_Calendar::PARAMS_JSON => json_encode(array(
							"manual_disabled" => "0",
							"sync_enabled" => "0",
							"start_on_mon" => "1",
							"hide_start_time" => "0",
							"color_available" => "#A0D95B",
							"color_busy" => "#C8C8C8",
							"series" => array(
								array("datasource"=>""),
								array("datasource"=>""),
								array("datasource"=>""),
							)
						)),
						DAO_Calendar::UPDATED_AT => time(),
					);
					$calendar_id = DAO_Calendar::create($fields);
					
				} else {
					if(false != ($calendar = DAO_Calendar::get($calendar_id))) {
						$calendar_id = intval($calendar->id);
					} else {
						$calendar_id = 0;
					}
				}
				
				// Update
				$fields = array(
					DAO_Worker::FIRST_NAME => $first_name,
					DAO_Worker::LAST_NAME => $last_name,
					DAO_Worker::TITLE => $title,
					DAO_Worker::EMAIL_ID => $email_id,
					DAO_Worker::IS_SUPERUSER => $is_superuser,
					DAO_Worker::IS_DISABLED => $disabled,
					DAO_Worker::AUTH_EXTENSION_ID => $auth_extension_id,
					DAO_Worker::AT_MENTION_NAME => $at_mention_name,
					DAO_Worker::LANGUAGE => $language,
					DAO_Worker::TIMEZONE => $timezone,
					DAO_Worker::TIME_FORMAT => $time_format,
					DAO_Worker::GENDER => $gender,
					DAO_Worker::LOCATION => $location,
					DAO_Worker::DOB => (null == $dob_ts) ? null : gmdate('Y-m-d', $dob_ts),
					DAO_Worker::MOBILE => $mobile,
					DAO_Worker::PHONE => $phone,
					DAO_Worker::CALENDAR_ID => $calendar_id,
				);
				
				if(!DAO_Worker::validate($fields, $error, $id))
					throw new Exception_DevblocksAjaxValidationError($error);
				
				if(!DAO_Worker::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
					throw new Exception_DevblocksAjaxValidationError($error);
				
				// Update worker
				DAO_Worker::update($id, $fields);
				DAO_Worker::onUpdateByActor($active_worker, $fields, $id);
				
				// Auth
				if(!empty($password_new) && $password_new == $password_verify) {
					DAO_Worker::setAuth($id, $password_new);
				}
				
				// Update group memberships
				if(is_array($group_memberships))
				foreach($group_memberships as $group_id => $membership) {
					switch($membership) {
						case 0:
							DAO_Group::unsetGroupMember($group_id, $id);
							break;
						case 1:
						case 2:
							DAO_Group::setGroupMember($group_id, $id, (2==$membership));
							break;
					}
				}
				
				if($id) {
					// Aliases
					DAO_ContextAlias::set(CerberusContexts::CONTEXT_WORKER, $id, DevblocksPlatform::parseCrlfString(sprintf("%s%s", $first_name, $last_name ? (' '.$last_name) : '') . "\n" . $aliases));
					
					// Custom field saves
					@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', array());
					DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_WORKER, $id, $field_ids);
					
					// Avatar image
					@$avatar_image = DevblocksPlatform::importGPC($_REQUEST['avatar_image'], 'string', '');
					DAO_ContextAvatar::upsertWithImage(CerberusContexts::CONTEXT_WORKER, $id, $avatar_image);
					
					// Flush caches
					DAO_WorkerRole::clearWorkerCache($id);
					
					// Index immediately
					$search = Extension_DevblocksSearchSchema::get(Search_Worker::ID);
					$search->indexIds(array($id));
				}
			}
			
			echo json_encode(array(
				'status' => true,
				'id' => $id,
				'label' => $first_name . ($first_name && $last_name ? ' ' : '') . $last_name,
				'view_id' => $view_id,
			));
			return;
			
		} catch (Exception_DevblocksAjaxValidationError $e) {
			echo json_encode(array(
				'status' => false,
				'error' => $e->getMessage(),
				'field' => $e->getFieldName(),
			));
			return;
			
		} catch (Exception $e) {
			echo json_encode(array(
				'status' => false,
				'error' => 'An error occurred.',
			));
			return;
			
		}
	}
	
	function showSettingsTabAction() {
		@$worker_id = DevblocksPlatform::importGPC($_REQUEST['worker_id'], 'integer', 0);
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		// ACL
		if(!($active_worker->is_superuser || $active_worker->id == $worker_id))
			return;
		
		if(false == ($worker = DAO_Worker::get($worker_id)))
			return;
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('worker', $worker);
		
		$tpl->display('devblocks:cerberusweb.core::internal/workers/profile/tab_settings.tpl');
	}
	
	function showSettingsSectionTabAction() {
		@$worker_id = DevblocksPlatform::importGPC($_REQUEST['worker_id'], 'integer', 0);
		@$tab = DevblocksPlatform::importGPC($_REQUEST['tab'], 'string', null);
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		// ACL
		if(!($active_worker->is_superuser || $active_worker->id == $worker_id))
			return;
		
		if(false == ($worker = DAO_Worker::get($worker_id)))
			return;
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('worker', $worker);
		
		switch($tab) {
			case 'profile':
				$prefs = [];
				$prefs['assist_mode'] = intval(DAO_WorkerPref::get($worker->id, 'assist_mode', 1));
				$prefs['keyboard_shortcuts'] = intval(DAO_WorkerPref::get($worker->id, 'keyboard_shortcuts', 1));
				$tpl->assign('prefs', $prefs);
				
				$tpl->display('devblocks:cerberusweb.core::internal/workers/profile/tabs/profile.tpl');
				break;
				
			case 'pages':
				$page_ids = DAO_WorkerPref::getAsJson($worker->id, 'menu_json', '[]');
				
				if($page_ids) {
					$pages = DAO_WorkspacePage::getIds($page_ids);
					$tpl->assign('pages', $pages);
				}
				
				$tpl->display('devblocks:cerberusweb.core::internal/workers/profile/tabs/pages.tpl');
				break;
				
			case 'availability':
				$prefs = [];
				$prefs['availability_calendar_id'] = intval($worker->calendar_id);
				$prefs['time_format'] = $worker->time_format ?: DevblocksPlatform::getDateTimeFormat();
				$tpl->assign('prefs', $prefs);
				
				// Availability
				$calendars = DAO_Calendar::getAll();
				$tpl->assign('calendars', $calendars);
				
				$tpl->display('devblocks:cerberusweb.core::internal/workers/profile/tabs/availability.tpl');
				break;
				
			case 'localization':
				$date_service = DevblocksPlatform::services()->date();
				
				$prefs = [];
				$prefs['time_format'] = $worker->time_format ?: DevblocksPlatform::getDateTimeFormat();
				$tpl->assign('prefs', $prefs);
				
				// Timezones
				$tpl->assign('timezones', $date_service->getTimezones());
				@$server_timezone = DevblocksPlatform::getTimezone();
				$tpl->assign('server_timezone', $server_timezone);
				
				// Languages
				$langs = DAO_Translation::getDefinedLangCodes();
				$tpl->assign('langs', $langs);
				$tpl->assign('selected_language', $worker->language ?: 'en_US');
				
				$tpl->display('devblocks:cerberusweb.core::internal/workers/profile/tabs/localization.tpl');
				break;
				
			case 'mail':
				$prefs = [];
				$prefs['mail_disable_html_display'] = DAO_WorkerPref::get($worker->id,'mail_disable_html_display',0);
				$prefs['mail_always_show_all'] = DAO_WorkerPref::get($worker->id,'mail_always_show_all',0);
				$prefs['mail_display_inline_log'] = DAO_WorkerPref::get($worker->id,'mail_display_inline_log',0);
				$prefs['mail_reply_html'] = DAO_WorkerPref::get($worker->id,'mail_reply_html',0);
				$prefs['mail_reply_textbox_size_auto'] = DAO_WorkerPref::get($worker->id,'mail_reply_textbox_size_auto',0);
				$prefs['mail_reply_textbox_size_px'] = DAO_WorkerPref::get($worker->id,'mail_reply_textbox_size_px',300);
				$prefs['mail_reply_button'] = DAO_WorkerPref::get($worker->id,'mail_reply_button',0);
				$prefs['mail_status_compose'] = DAO_WorkerPref::get($worker->id,'compose.status','waiting');
				$prefs['mail_status_reply'] = DAO_WorkerPref::get($worker->id,'mail_status_reply','waiting');
				$prefs['mail_signature_pos'] = DAO_WorkerPref::get($worker->id,'mail_signature_pos',2);
				$tpl->assign('prefs', $prefs);
				
				// Alternate addresses
				$addresses = DAO_AddressToWorker::getByWorker($worker->id);
				$tpl->assign('addresses', $addresses);
				
				$tpl->display('devblocks:cerberusweb.core::internal/workers/profile/tabs/mail.tpl');
				break;
				
			case 'search':
				// Search
				$search_contexts = Extension_DevblocksContext::getAll(false, ['search']);
				$tpl->assign('search_contexts', $search_contexts);
				
				$search_favorites = DAO_WorkerPref::getAsJson($worker->id, 'search_favorites_json', '[]');
				$search_favorites = array_flip($search_favorites);
				$tpl->assign('search_favorites', $search_favorites);
				
				$tpl->display('devblocks:cerberusweb.core::internal/workers/profile/tabs/search.tpl');
				break;
				
			case 'security':
				// Secret questions
				$secret_questions_json = DAO_WorkerPref::get($worker->id, 'login.recover.secret_questions', null);
				if(false !== ($secret_questions = json_decode($secret_questions_json, true)) && is_array($secret_questions)) {
					$tpl->assign('secret_questions', $secret_questions);
				}
				
				// Load the worker's auth extension
				if(null != ($ext = Extension_LoginAuthenticator::get($worker->auth_extension_id, true))) {
					/* @var $ext Extension_LoginAuthenticator */
					$tpl->assign('auth_extension', $ext);
				}
				
				$tpl->display('devblocks:cerberusweb.core::internal/workers/profile/tabs/security.tpl');
				break;
				
			case 'sessions':
				// View
				$defaults = C4_AbstractViewModel::loadFromClass('View_DevblocksSession');
				$defaults->id = 'workerprefs_sessions';
				
				$view = C4_AbstractViewLoader::getView($defaults->id, $defaults);
				
				$view->is_ephemeral = true;
				
				$view->addParamsRequired(array(
					SearchFields_DevblocksSession::USER_ID => new DevblocksSearchCriteria(SearchFields_DevblocksSession::USER_ID, '=', $worker->id),
				));
				
				$view->addParamsHidden(array(SearchFields_DevblocksSession::USER_ID));
				
				$tpl->assign('view', $view);
				$tpl->display('devblocks:cerberusweb.core::internal/views/search_and_view.tpl');
				break;
				
			case 'watchers':
				// Activities
				$activities = DevblocksPlatform::getActivityPointRegistry();
				$tpl->assign('activities', $activities);
				
				$dont_notify_on_activities = WorkerPrefs::getDontNotifyOnActivities($worker->id);
				$tpl->assign('dont_notify_on_activities', $dont_notify_on_activities);
				
				$tpl->display('devblocks:cerberusweb.core::internal/workers/profile/tabs/watchers.tpl');
				break;
		}
	}
	
	function saveSettingsSectionTabJsonAction() {
		@$worker_id = DevblocksPlatform::importGPC($_REQUEST['worker_id'], 'integer', 0);
		@$tab = DevblocksPlatform::importGPC($_REQUEST['tab'], 'string', null);
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			// ACL
			if(!($active_worker->is_superuser || $active_worker->id == $worker_id))
				throw new Exception_DevblocksAjaxValidationError("You do not have permission to modify this worker.");
			
			if(false == ($worker = DAO_Worker::get($worker_id)))
				throw new Exception_DevblocksAjaxValidationError("This worker record does not exist.");
			
			switch($tab) {
				case 'profile':
					@$gender = DevblocksPlatform::importGPC($_REQUEST['gender'],'string');
					@$location = DevblocksPlatform::importGPC($_REQUEST['location'],'string');
					@$phone = DevblocksPlatform::importGPC($_REQUEST['phone'],'string');
					@$mobile = DevblocksPlatform::importGPC($_REQUEST['mobile'],'string');
					@$dob = DevblocksPlatform::importGPC($_REQUEST['dob'],'string');
					@$avatar_image = DevblocksPlatform::importGPC($_REQUEST['avatar_image'],'string');
					
					$worker_fields = [];
					
					$dob_ts = null;
					
					if(!empty($dob) && false == ($dob_ts = strtotime($dob . ' 00:00 GMT')))
						$dob_ts = null;
					
					// Account info
					
					$worker_fields[DAO_Worker::LOCATION] = $location;
					$worker_fields[DAO_Worker::PHONE] = $phone;
					$worker_fields[DAO_Worker::MOBILE] = $mobile;
					$worker_fields[DAO_Worker::DOB] = (null == $dob_ts) ? null : gmdate('Y-m-d', $dob_ts);
					
					if(in_array($gender, array('M','F','')))
						$worker_fields[DAO_Worker::GENDER] = $gender;
					
					// Validate
					if(!DAO_Worker::validate($worker_fields, $error, $worker->id))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					// Update
					if(!empty($worker_fields))
						DAO_Worker::update($worker->id, $worker_fields);
					
					@$assist_mode = DevblocksPlatform::importGPC($_REQUEST['assist_mode'],'integer',0);
					DAO_WorkerPref::set($worker->id, 'assist_mode', $assist_mode);
					
					@$keyboard_shortcuts = DevblocksPlatform::importGPC($_REQUEST['keyboard_shortcuts'],'integer',0);
					DAO_WorkerPref::set($worker->id, 'keyboard_shortcuts', $keyboard_shortcuts);
					
					DAO_ContextAvatar::upsertWithImage(CerberusContexts::CONTEXT_WORKER, $worker->id, $avatar_image);
					
					echo json_encode([
						'status' => true,
					]);
					return;
					break;
					
				case 'pages':
					@$page_ids = DevblocksPlatform::importGPC($_REQUEST['pages'],'array:integer',[]);
					
					$pages = DAO_WorkspacePage::getIds($page_ids);
					
					if(!Context_WorkspacePage::isReadableByActor($pages, $worker))
						throw new Exception_DevblocksAjaxValidationError(
							sprintf("%s can't view a selected workspace page.",
								$worker->getName()
							)
						);
					
					DAO_WorkerPref::setAsJson($worker->id, 'menu_json', $page_ids);
					
					echo json_encode([
						'status' => true,
					]);
					return;
					break;
					
				case 'availability':
					@$availability_calendar_id = DevblocksPlatform::importGPC($_REQUEST['availability_calendar_id'],'integer',0);
					
					$worker_fields = [];
					$worker_fields[DAO_Worker::CALENDAR_ID] = $availability_calendar_id;
					
					// Validate
					if(!DAO_Worker::validate($worker_fields, $error, $worker->id))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					// Update
					if(!empty($worker_fields))
						DAO_Worker::update($worker->id, $worker_fields);
					
					echo json_encode([
						'status' => true,
					]);
					return;
					break;
					
				case 'localization':
					@$lang_code = DevblocksPlatform::importGPC($_REQUEST['lang_code'],'string','en_US');
					@$time_format = DevblocksPlatform::importGPC($_REQUEST['time_format'],'string',null);
					@$timezone = DevblocksPlatform::importGPC($_REQUEST['timezone'],'string');
					
					$worker_fields = [];
					
					$worker_fields[DAO_Worker::LANGUAGE] = $lang_code;
					$worker_fields[DAO_Worker::TIME_FORMAT] = $time_format;
					$worker_fields[DAO_Worker::TIMEZONE] = $timezone;
					
					// Validate
					if(!DAO_Worker::validate($worker_fields, $error, $worker->id))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					// Update
					if(!empty($worker_fields))
						DAO_Worker::update($worker->id, $worker_fields);
					
					// Update this session?
					if($worker->id == $active_worker->id) {
						$_SESSION['locale'] = $lang_code;
						$_SESSION['timezone'] = $timezone;
					
						DevblocksPlatform::setLocale($lang_code);
						DevblocksPlatform::setTimezone($timezone);
					}
					
					echo json_encode([
						'status' => true,
					]);
					return;
					break;
					
				case 'mail':
					@$mail_disable_html_display = DevblocksPlatform::importGPC($_REQUEST['mail_disable_html_display'],'integer',0);
					DAO_WorkerPref::set($worker->id, 'mail_disable_html_display', $mail_disable_html_display);
					
					@$mail_always_show_all = DevblocksPlatform::importGPC($_REQUEST['mail_always_show_all'],'integer',0);
					DAO_WorkerPref::set($worker->id, 'mail_always_show_all', $mail_always_show_all);
					
					@$mail_display_inline_log = DevblocksPlatform::importGPC($_REQUEST['mail_display_inline_log'],'integer',0);
					DAO_WorkerPref::set($worker->id, 'mail_display_inline_log', $mail_display_inline_log);
					
					@$mail_reply_html = DevblocksPlatform::importGPC($_REQUEST['mail_reply_html'],'integer',0);
					DAO_WorkerPref::set($worker->id, 'mail_reply_html', $mail_reply_html);
					
					@$mail_reply_textbox_size_px = DevblocksPlatform::importGPC($_REQUEST['mail_reply_textbox_size_px'],'integer',0);
					DAO_WorkerPref::set($worker->id, 'mail_reply_textbox_size_px', max(100, min(2000, $mail_reply_textbox_size_px)));
					
					@$mail_reply_textbox_size_auto = DevblocksPlatform::importGPC($_REQUEST['mail_reply_textbox_size_auto'],'integer',0);
					DAO_WorkerPref::set($worker->id, 'mail_reply_textbox_size_auto', $mail_reply_textbox_size_auto);
					
					@$mail_reply_button = DevblocksPlatform::importGPC($_REQUEST['mail_reply_button'],'integer',0);
					DAO_WorkerPref::set($worker->id, 'mail_reply_button', $mail_reply_button);
					
					@$mail_signature_pos = DevblocksPlatform::importGPC($_REQUEST['mail_signature_pos'],'integer',0);
					DAO_WorkerPref::set($worker->id, 'mail_signature_pos', $mail_signature_pos);
					
					@$mail_status_compose = DevblocksPlatform::importGPC($_REQUEST['mail_status_compose'],'string','waiting');
					DAO_WorkerPref::set($worker->id, 'compose.status', $mail_status_compose);
					
					@$mail_status_reply = DevblocksPlatform::importGPC($_REQUEST['mail_status_reply'],'string','waiting');
					DAO_WorkerPref::set($worker->id, 'mail_status_reply', $mail_status_reply);
					
					echo json_encode([
						'status' => true,
					]);
					return;
					break;
					
				case 'search':
					@$search_favorites = DevblocksPlatform::importGPC($_REQUEST['search_favorites'],'array',[]);
					DAO_WorkerPref::setAsJson($worker->id, 'search_favorites_json', $search_favorites);
					
					echo json_encode([
						'status' => true,
					]);
					return;
					break;
					
				case 'security':
					// Secret questions
					@$q = DevblocksPlatform::importGPC($_REQUEST['sq_q'], 'array', array('','',''));
					@$h = DevblocksPlatform::importGPC($_REQUEST['sq_h'], 'array', array('','',''));
					@$a = DevblocksPlatform::importGPC($_REQUEST['sq_a'], 'array', array('','',''));
					
					$secret_questions = array(
						array('q'=>$q[0], 'h'=>$h[0], 'a'=>$a[0]),
						array('q'=>$q[1], 'h'=>$h[1], 'a'=>$a[1]),
						array('q'=>$q[2], 'h'=>$h[2], 'a'=>$a[2]),
					);
					
					DAO_WorkerPref::set($worker->id, 'login.recover.secret_questions', json_encode($secret_questions));
					
					if(null != ($ext = Extension_LoginAuthenticator::get($worker->auth_extension_id, true))) {
						/* @var $ext Extension_LoginAuthenticator */
						$ext->saveWorkerPrefs($worker);
					}
					
					echo json_encode([
						'status' => true,
					]);
					return;
					break;
					
				case 'watchers':
					@$activity_points = DevblocksPlatform::importGPC($_REQUEST['activity_point'],'array',array());
					@$activity_points_enabled = DevblocksPlatform::importGPC($_REQUEST['activity_enable'],'array',array());
					
					$dont_notify_on_activities = array_diff($activity_points, $activity_points_enabled);
					WorkerPrefs::setDontNotifyOnActivities($worker->id, $dont_notify_on_activities);
					
					echo json_encode([
						'status' => true,
					]);
					return;
					break;
			}
			
		} catch (Exception_DevblocksAjaxValidationError $e) {
			echo json_encode([
				'status' => false,
				'error' => $e->getMessage(),
			]);
			
		} catch (Exception $e) {
			echo json_encode([
				'status' => false,
				'error' => 'An unexpected error occurred.',
			]);
		}
	}
	
	function showBulkPopupAction() {
		@$id_csv = DevblocksPlatform::importGPC($_REQUEST['ids']);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id']);

		$active_worker = CerberusApplication::getActiveWorker();
		
		// Permissions
		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError("You don't have permission to edit this record.", 403);
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('view_id', $view_id);

		if(!empty($id_csv)) {
			$ids = DevblocksPlatform::parseCsvString($id_csv);
			$tpl->assign('ids', implode(',', $ids));
		}
		
		// Custom Fields
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_WORKER, false);
		$tpl->assign('custom_fields', $custom_fields);
		
		// Auth extensions
		$auth_extensions = Extension_LoginAuthenticator::getAll(false);
		$tpl->assign('auth_extensions', $auth_extensions);
		
		// Languages
		$translate = DevblocksPlatform::getTranslationService();
		$locales = $translate->getLocaleStrings();
		$tpl->assign('languages', $locales);
		
		// Timezones
		$date = DevblocksPlatform::services()->date();
		$tpl->assign('timezones', $date->getTimezones());
		
		// Broadcast
		
		CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, null, $token_labels, $token_values);
		
		$placeholders = Extension_DevblocksContext::getPlaceholderTree($token_labels);
		$tpl->assign('placeholders', $placeholders);
		
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		$html_templates = DAO_MailHtmlTemplate::getAll();
		$tpl->assign('html_templates', $html_templates);
		
		$tpl->display('devblocks:cerberusweb.core::workers/bulk.tpl');
	}
	
	function startBulkUpdateJsonAction() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		// Permissions
		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError("You don't have permission to edit this record.", 403);
		
		// Filter: whole list or check
		@$filter = DevblocksPlatform::importGPC($_REQUEST['filter'],'string','');
		$ids = array();
		
		// View
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		$view = C4_AbstractViewLoader::getView($view_id);
		$view->setAutoPersist(false);
		
		// Worker fields
		@$is_disabled = trim(DevblocksPlatform::importGPC($_POST['is_disabled'],'string',''));
		@$auth_extension_id = trim(DevblocksPlatform::importGPC($_POST['auth_extension_id'],'string',''));
		@$title = trim(DevblocksPlatform::importGPC($_POST['title'],'string',''));
		@$location = trim(DevblocksPlatform::importGPC($_POST['location'],'string',''));
		@$gender = trim(DevblocksPlatform::importGPC($_POST['gender'],'string',''));
		@$language = trim(DevblocksPlatform::importGPC($_POST['language'],'string',''));
		@$timezone = trim(DevblocksPlatform::importGPC($_POST['timezone'],'string',''));

		$do = array();
		
		// Do: Disabled
		if(0 != strlen($is_disabled))
			$do['is_disabled'] = $is_disabled;
		
		// Do: Authentication Extension
		if(0 != strlen($auth_extension_id))
			$do['auth_extension_id'] = $auth_extension_id;
			
		if(0 != strlen($title))
			$do['title'] = $title;
		
		if(0 != strlen($location))
			$do['location'] = $location;
		
		if(0 != strlen($gender))
			$do['gender'] = $gender;
		
		if(0 != strlen($language))
			$do['language'] = $language;
			
		if(0 != strlen($timezone))
			$do['timezone'] = $timezone;
			
		// Do: Custom fields
		$do = DAO_CustomFieldValue::handleBulkPost($do);
		
		// Broadcast: Compose
		if($active_worker->hasPriv('contexts.cerberusweb.contexts.worker.broadcast')) {
			@$do_broadcast = DevblocksPlatform::importGPC($_REQUEST['do_broadcast'],'string',null);
			@$broadcast_group_id = DevblocksPlatform::importGPC($_REQUEST['broadcast_group_id'],'integer',0);
			@$broadcast_subject = DevblocksPlatform::importGPC($_REQUEST['broadcast_subject'],'string',null);
			@$broadcast_message = DevblocksPlatform::importGPC($_REQUEST['broadcast_message'],'string',null);
			@$broadcast_format = DevblocksPlatform::importGPC($_REQUEST['broadcast_format'],'string',null);
			@$broadcast_html_template_id = DevblocksPlatform::importGPC($_REQUEST['broadcast_html_template_id'],'integer',0);
			@$broadcast_is_queued = DevblocksPlatform::importGPC($_REQUEST['broadcast_is_queued'],'integer',0);
			@$broadcast_status_id = DevblocksPlatform::importGPC($_REQUEST['broadcast_status_id'],'integer',0);
			@$broadcast_file_ids = DevblocksPlatform::sanitizeArray(DevblocksPlatform::importGPC($_REQUEST['broadcast_file_ids'],'array',array()), 'integer', array('nonzero','unique'));
			
			if(0 != strlen($do_broadcast) && !empty($broadcast_subject) && !empty($broadcast_message)) {
				$do['broadcast'] = array(
					'subject' => $broadcast_subject,
					'message' => $broadcast_message,
					'format' => $broadcast_format,
					'html_template_id' => $broadcast_html_template_id,
					'is_queued' => $broadcast_is_queued,
					'status_id' => $broadcast_status_id,
					'group_id' => $broadcast_group_id,
					'worker_id' => $active_worker->id,
					'file_ids' => $broadcast_file_ids,
				);
			}
		}
		
		switch($filter) {
			// Checked rows
			case 'checks':
				@$ids_str = DevblocksPlatform::importGPC($_REQUEST['ids'],'string');
				$ids = DevblocksPlatform::parseCsvString($ids_str);
				break;
				
			default:
				break;
		}
		
		// If we have specific IDs, add a filter for those too
		if(!empty($ids)) {
			$view->addParam(new DevblocksSearchCriteria(SearchFields_Worker::ID, 'in', $ids));
		}
		
		// Create batches
		$batch_key = DAO_ContextBulkUpdate::createFromView($view, $do);
		
		header('Content-Type: application/json; charset=utf-8');
		
		echo json_encode(array(
			'cursor' => $batch_key,
		));
		
		return;
	}
	
	function setAvailabilityCalendarAction() {
		@$availability_calendar_id = DevblocksPlatform::importGPC($_REQUEST['availability_calendar_id'],'integer', 0);
		
		$active_worker = CerberusApplication::getActiveWorker();

		if(!empty($availability_calendar_id)) {
			if(false == ($calendar = DAO_Calendar::get($availability_calendar_id)))
				$availability_calendar_id = 0;
			
			if(!Context_Calendar::isWriteableByActor($calendar, $active_worker))
				$availability_calendar_id = 0;
		}
		
		if(empty($availability_calendar_id)) {
			$fields = array(
				DAO_Calendar::NAME => $active_worker->getName() .  "'s Schedule",
				DAO_Calendar::OWNER_CONTEXT => CerberusContexts::CONTEXT_WORKER,
				DAO_Calendar::OWNER_CONTEXT_ID => $active_worker->id,
				DAO_Calendar::PARAMS_JSON => '{"manual_disabled":"0","sync_enabled":"0","color_available":"#A0D95B","color_busy":"#C8C8C8"}',
				DAO_Calendar::UPDATED_AT => time(),
			);
			$availability_calendar_id = DAO_Calendar::create($fields);
		}
		
		if(!empty($availability_calendar_id)) {
			$fields = array(
				DAO_Worker::CALENDAR_ID => $availability_calendar_id,
			);
			DAO_Worker::update($active_worker->id, $fields);
		}
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('profiles','worker','me','availability')));
	}
	
	function viewExploreAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		
		$active_worker = CerberusApplication::getActiveWorker();
		$url_writer = DevblocksPlatform::services()->url();
		
		// Generate hash
		$hash = md5($view_id.$active_worker->id.time());
		
		// Loop through view and get IDs
		$view = C4_AbstractViewLoader::getView($view_id);
		$view->setAutoPersist(false);

		// Page start
		@$explore_from = DevblocksPlatform::importGPC($_REQUEST['explore_from'],'integer',0);
		if(empty($explore_from)) {
			$orig_pos = 1+($view->renderPage * $view->renderLimit);
		} else {
			$orig_pos = 1;
		}
		
		$view->renderPage = 0;
		$view->renderLimit = 250;
		$pos = 0;
		
		do {
			$models = array();
			list($results, $total) = $view->getData();

			// Summary row
			if(0==$view->renderPage) {
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'title' => $view->name,
					'created' => time(),
					//'worker_id' => $active_worker->id,
					'total' => $total,
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=search&type=worker', true),
//					'toolbar_extension_id' => 'cerberusweb.explorer.toolbar.',
				);
				$models[] = $model;
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
			foreach($results as $worker_id => $row) {
				if($worker_id==$explore_from)
					$orig_pos = $pos;
				
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'id' => $row[SearchFields_Worker::ID],
					'url' => $url_writer->writeNoProxy(sprintf("c=profiles&type=worker&id=%d", $row[SearchFields_Worker::ID]), true),
				);
				$models[] = $model;
			}
			
			DAO_ExplorerSet::createFromModels($models);
			
			$view->renderPage++;
			
		} while(!empty($results));
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('explore',$hash,$orig_pos)));
	}
};