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

class ChPreferencesPage extends CerberusPageExtension {
	function isVisible() {
		// The current session must be a logged-in worker to use this page.
		if(null == ($worker = CerberusApplication::getActiveWorker()))
			return false;
		return true;
	}

	function render() {
		$translate = DevblocksPlatform::getTranslationService();
		$tpl = DevblocksPlatform::services()->template();
		$response = DevblocksPlatform::getHttpResponse();
		$visit = CerberusApplication::getVisit();

		$path = $response->path;

		array_shift($path); // preferences

		$tab_manifests = DevblocksPlatform::getExtensions(Extension_PreferenceTab::POINT, false);
		$tpl->assign('tab_manifests', $tab_manifests);

		@$section = array_shift($path); // section
		switch($section) {
			case 'confirm_email':
				@$code = array_shift($path);
				$active_worker = CerberusApplication::getActiveWorker();

				$worker_addresses = DAO_AddressToWorker::getWhere(sprintf("%s = '%s' AND %s = %d",
					DAO_AddressToWorker::CODE,
					addslashes(str_replace(' ','',$code)),
					DAO_AddressToWorker::WORKER_ID,
					$active_worker->id
				));
				
				@$worker_address = array_shift($worker_addresses); /* @var $worker_address Model_AddressToWorker */
				
				if(!empty($code)
					&& $worker_address instanceof Model_AddressToWorker
					&& $worker_address->code == $code
					&& $worker_address->code_expire > time()) {

						DAO_AddressToWorker::update($worker_address->address_id, array(
							DAO_AddressToWorker::CODE => '',
							DAO_AddressToWorker::IS_CONFIRMED => 1,
							DAO_AddressToWorker::CODE_EXPIRE => 0
						));
						
						$output = array(sprintf($translate->_('prefs.address.confirm.tip'), $worker_address->getEmailAsString()));
						$tpl->assign('pref_success', $output);

				} else {
					$errors = array($translate->_('prefs.address.confirm.invalid_code'));
					$tpl->assign('pref_errors', $errors);
				}

				$tpl->display('devblocks:cerberusweb.core::preferences/index.tpl');
				break;

			default:
				$tpl->assign('tab', $section);
				$tpl->display('devblocks:cerberusweb.core::preferences/index.tpl');
				break;
		}
	}

	// Ajax
	function showTabAction() {
		@$ext_id = DevblocksPlatform::importGPC($_REQUEST['ext_id'],'string','');

		if(null != ($tab_mft = DevblocksPlatform::getExtension($ext_id))
			&& null != ($inst = $tab_mft->createInstance())
			&& $inst instanceof Extension_PreferenceTab) {
				$inst->showTab();
		}
	}

	// Post
	function saveTabAction() {
		@$ext_id = DevblocksPlatform::importGPC($_REQUEST['ext_id'],'string','');

		if(null != ($inst = DevblocksPlatform::getExtension($ext_id, true))
			&& $inst instanceof Extension_PreferenceTab) {
				$inst->saveTab();
		}
	}

	/*
	 * Proxy any func requests to be handled by the tab directly,
	 * instead of forcing tabs to implement controllers.  This should check
	 * for the *Action() functions just as a handleRequest would
	 */
	function handleTabActionAction() {
		@$tab = DevblocksPlatform::importGPC($_REQUEST['tab'],'string','');
		@$action = DevblocksPlatform::importGPC($_REQUEST['action'],'string','');

		if(null != ($tab_mft = DevblocksPlatform::getExtension($tab))
			&& null != ($inst = $tab_mft->createInstance())
			&& $inst instanceof Extension_PreferenceTab) {
				if(method_exists($inst,$action.'Action')) {
					call_user_func(array(&$inst, $action.'Action'));
				}
		}
	}
	
	function showWatcherTabAction() {
		$active_worker = CerberusApplication::getActiveWorker();
		$tpl = DevblocksPlatform::services()->template();
		
		// Activities
		$activities = DevblocksPlatform::getActivityPointRegistry();
		$tpl->assign('activities', $activities);

		$dont_notify_on_activities = WorkerPrefs::getDontNotifyOnActivities($active_worker->id);
		$tpl->assign('dont_notify_on_activities', $dont_notify_on_activities);
		
		$tpl->display('devblocks:cerberusweb.core::preferences/tabs/watcher/index.tpl');
	}
	
	function saveWatcherTabAction() {
		@$activity_points = DevblocksPlatform::importGPC($_REQUEST['activity_point'],'array',array());
		@$activity_points_enabled = DevblocksPlatform::importGPC($_REQUEST['activity_enable'],'array',array());
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		$dont_notify_on_activities = array_diff($activity_points, $activity_points_enabled);
		WorkerPrefs::setDontNotifyOnActivities($active_worker->id, $dont_notify_on_activities);
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('preferences','watcher')));
	}

	function showNotificationsBulkPanelAction() {
		@$ids = DevblocksPlatform::importGPC($_REQUEST['ids']);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id']);

		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('view_id', $view_id);

		if(!empty($ids)) {
			$id_list = DevblocksPlatform::parseCsvString($ids);
			$tpl->assign('ids', implode(',', $id_list));
		}

		// Custom Fields
		//$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_NOTIFICATION, false);
		//$tpl->assign('custom_fields', $custom_fields);

		$tpl->display('devblocks:cerberusweb.core::preferences/tabs/notifications/bulk.tpl');
	}

	function startNotificationsBulkUpdateJsonAction() {
		// Filter: whole list or check
		@$filter = DevblocksPlatform::importGPC($_REQUEST['filter'],'string','');
		$ids = array();

		// View
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		$view = C4_AbstractViewLoader::getView($view_id);
		$view->setAutoPersist(false);

		// Task fields
		$is_read = trim(DevblocksPlatform::importGPC($_POST['is_read'],'string',''));

		$do = array();

		// Do: Mark Read
		if(0 != strlen($is_read))
			$do['is_read'] = $is_read;

		switch($filter) {
			// Checked rows
			case 'checks':
				@$ids_str = DevblocksPlatform::importGPC($_REQUEST['ids'],'string');
				$ids = DevblocksPlatform::parseCsvString($ids_str);
				break;
				
			case 'sample':
				@$sample_size = min(DevblocksPlatform::importGPC($_REQUEST['filter_sample_size'],'integer',0),9999);
				$filter = 'checks';
				$ids = $view->getDataSample($sample_size);
				break;
			default:
				break;
		}
		
		// If we have specific IDs, add a filter for those too
		if(!empty($ids)) {
			$view->addParam(new DevblocksSearchCriteria(SearchFields_Notification::ID, 'in', $ids));
		}
		
		// Create batches
		$batch_key = DAO_ContextBulkUpdate::createFromView($view, $do);
		
		header('Content-Type: application/json; charset=utf-8');
		
		echo json_encode(array(
			'cursor' => $batch_key,
		));
		
		return;
	}

	function viewNotificationsMarkReadAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		@$row_ids = DevblocksPlatform::importGPC($_REQUEST['row_id'],'array',array());

		$active_worker = CerberusApplication::getActiveWorker();
		

		try {
			if(is_array($row_ids))
			foreach($row_ids as $row_id) {
				$row_id = intval($row_id);
				
				// Only close notifications if the current worker owns them
				if(null != ($notification = DAO_Notification::get($row_id))) {
					if($notification->worker_id == $active_worker->id) {
						
						DAO_Notification::update($notification->id, array(
							DAO_Notification::IS_READ => 1,
						));
					}
				}
				
			}
			
			DAO_Notification::clearCountCache($active_worker->id);
			
		} catch (Exception $e) {
			//
		}
		
		$view = C4_AbstractViewLoader::getView($view_id);
		$view->render();
		
		exit;
	}
	
	function viewNotificationsExploreAction() {
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
		$keys = array();
		$contexts = array();
		
		$view->renderTotal = false;
		
		do {
			$models = array();
			list($results, $total) = $view->getData();

			if(is_array($results))
			foreach($results as $event_id => $row) {
				if($event_id==$explore_from)
					$orig_pos = $pos;

				$entry = json_decode($row[SearchFields_Notification::ENTRY_JSON], true);
				
				$content = CerberusContexts::formatActivityLogEntry($entry, 'text');
				$context = $row[SearchFields_Notification::CONTEXT];
				$context_id = $row[SearchFields_Notification::CONTEXT_ID];
				
				// Composite key
				$key = $row[SearchFields_Notification::WORKER_ID]
					. '_' . $context
					. '_' . $context_id
					;
					
				$url = $url_writer->write(sprintf("c=preferences&a=redirectRead&id=%d", $row[SearchFields_Notification::ID]));
				
				if(empty($url))
					continue;
				
				if(!empty($context) && !empty($context_id)) {
					// Is this a dupe?
					if(isset($keys[$key])) {
						continue;
					} else {
						$keys[$key] = ++$pos;
					}
				} else {
					++$pos;
				}
				
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos;
				$model->params = array(
					'id' => $row[SearchFields_Notification::ID],
					'content' => $content,
					'url' => $url,
				);
				$models[] = $model;
			}
			
			if(!empty($models))
				DAO_ExplorerSet::createFromModels($models);

			$view->renderPage++;

		} while(!empty($results));

		// Add the manifest row
		
		DAO_ExplorerSet::set(
			$hash,
			array(
				'title' => $view->name,
				'created' => time(),
				'worker_id' => $active_worker->id,
				'total' => $pos,
				'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=profiles&k=worker&id=me&tab=notifications', true),
				//'toolbar_extension_id' => '',
			),
			0
		);
		
		// Clamp the starting position based on dupe key folding
		$orig_pos = DevblocksPlatform::intClamp($orig_pos, 1, count($keys));
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('explore',$hash,$orig_pos)));
	}

	/**
	 * Open an event, mark it read, and redirect to its URL.
	 * Used by Home->Notifications view.
	 *
	 */
	function redirectReadAction() {
		$worker = CerberusApplication::getActiveWorker();

		$request = DevblocksPlatform::getHttpRequest();
		$stack = $request->path;

		array_shift($stack); // preferences
		array_shift($stack); // redirectRead
		@$id = array_shift($stack); // id

		if(null != ($notification = DAO_Notification::get($id))) {
			switch($notification->context) {
				case '':
				case CerberusContexts::CONTEXT_APPLICATION:
				case CerberusContexts::CONTEXT_CUSTOM_FIELD:
				case CerberusContexts::CONTEXT_CUSTOM_FIELDSET:
				case CerberusContexts::CONTEXT_MESSAGE:
				case CerberusContexts::CONTEXT_WORKSPACE_PAGE:
				case CerberusContexts::CONTEXT_WORKSPACE_TAB:
				case CerberusContexts::CONTEXT_WORKSPACE_WIDGET:
				case CerberusContexts::CONTEXT_WORKSPACE_WORKLIST:
					// Mark as read before we redirect
					if(empty($notification->is_read)) {
						DAO_Notification::update($id, array(
							DAO_Notification::IS_READ => 1
						));
					
						DAO_Notification::clearCountCache($worker->id);
					}
					break;
			}

			session_write_close();
			header("Location: " . $notification->getURL());
		}
		exit;
	}

	function showGeneralTabAction() {
		$date_service = DevblocksPlatform::services()->date();
		$tpl = DevblocksPlatform::services()->template();

		$worker = CerberusApplication::getActiveWorker();
		$tpl->assign('worker', $worker);

		$prefs = array();
		$prefs['assist_mode'] = intval(DAO_WorkerPref::get($worker->id, 'assist_mode', 1));
		$prefs['keyboard_shortcuts'] = intval(DAO_WorkerPref::get($worker->id, 'keyboard_shortcuts', 1));
		$prefs['availability_calendar_id'] = intval($worker->calendar_id);
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
		$prefs['time_format'] = $worker->time_format ?: DevblocksPlatform::getDateTimeFormat();
		$tpl->assign('prefs', $prefs);
		
		// Alternate addresses
		$addresses = DAO_AddressToWorker::getByWorker($worker->id);
		$tpl->assign('addresses', $addresses);
		
		// Timezones
		$tpl->assign('timezones', $date_service->getTimezones());
		@$server_timezone = DevblocksPlatform::getTimezone();
		$tpl->assign('server_timezone', $server_timezone);

		// Languages
		$langs = DAO_Translation::getDefinedLangCodes();
		$tpl->assign('langs', $langs);
		$tpl->assign('selected_language', $worker->language ?: 'en_US');

		// Availability
		$calendars = DAO_Calendar::getAll();
		$tpl->assign('calendars', $calendars);
		
		// Template
		$tpl->display('devblocks:cerberusweb.core::preferences/modules/general.tpl');
	}

	function showSecurityTabAction() {
		$tpl = DevblocksPlatform::services()->template();

		$worker = CerberusApplication::getActiveWorker();
		$tpl->assign('worker', $worker);
		
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
		
		$tpl->display('devblocks:cerberusweb.core::preferences/modules/security.tpl');
	}
	
	function saveSecurityTabAction() {
		$worker = CerberusApplication::getActiveWorker();
		
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
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('preferences','security')));
	}
	
	function showSessionsTabAction() {
		$tpl = DevblocksPlatform::services()->template();

		$worker = CerberusApplication::getActiveWorker();
		$tpl->assign('worker', $worker);
		
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
	}
	
	function saveDefaultsAction() {
		@$first_name = DevblocksPlatform::importGPC($_REQUEST['first_name'],'string');
		@$last_name = DevblocksPlatform::importGPC($_REQUEST['last_name'],'string');
		@$title = DevblocksPlatform::importGPC($_REQUEST['title'],'string');
		@$gender = DevblocksPlatform::importGPC($_REQUEST['gender'],'string');
		@$location = DevblocksPlatform::importGPC($_REQUEST['location'],'string');
		@$phone = DevblocksPlatform::importGPC($_REQUEST['phone'],'string');
		@$mobile = DevblocksPlatform::importGPC($_REQUEST['mobile'],'string');
		@$dob = DevblocksPlatform::importGPC($_REQUEST['dob'],'string');
		@$at_mention_name = DevblocksPlatform::importGPC($_REQUEST['at_mention_name'],'string');
		@$avatar_image = DevblocksPlatform::importGPC($_REQUEST['avatar_image'],'string');
		
		@$timezone = DevblocksPlatform::importGPC($_REQUEST['timezone'],'string');
		@$lang_code = DevblocksPlatform::importGPC($_REQUEST['lang_code'],'string','en_US');
		@$mail_signature_pos = DevblocksPlatform::importGPC($_REQUEST['mail_signature_pos'],'integer',0);

		$worker = CerberusApplication::getActiveWorker();
		$translate = DevblocksPlatform::getTranslationService();
		
		$tpl = DevblocksPlatform::services()->template();
		$pref_errors = array();

		$worker_fields = array();
		
		$dob_ts = null;
		
		if(!empty($dob) && false == ($dob_ts = strtotime($dob . ' 00:00 GMT')))
			$dob_ts = null;
			
		// Account info
		
		if(!empty($first_name))
			$worker_fields[DAO_Worker::FIRST_NAME] = $first_name;
		
		$worker_fields[DAO_Worker::LAST_NAME] = $last_name;
		$worker_fields[DAO_Worker::TITLE] = $title;
		$worker_fields[DAO_Worker::LOCATION] = $location;
		$worker_fields[DAO_Worker::PHONE] = $phone;
		$worker_fields[DAO_Worker::MOBILE] = $mobile;
		$worker_fields[DAO_Worker::AT_MENTION_NAME] = $at_mention_name;
		$worker_fields[DAO_Worker::DOB] = (null == $dob_ts) ? null : gmdate('Y-m-d', $dob_ts);
		
		if(in_array($gender, array('M','F','')))
			$worker_fields[DAO_Worker::GENDER] = $gender;
		
		DAO_ContextAvatar::upsertWithImage(CerberusContexts::CONTEXT_WORKER, $worker->id, $avatar_image);
		
		// Time
		
		$_SESSION['timezone'] = $timezone;
		DevblocksPlatform::setTimezone($timezone);
		$worker_fields[DAO_Worker::TIMEZONE] = $timezone;
		
		@$time_format = DevblocksPlatform::importGPC($_REQUEST['time_format'],'string',null);
		$worker_fields[DAO_Worker::TIME_FORMAT] = $time_format;

		// Language
		
		$_SESSION['locale'] = $lang_code;
		DevblocksPlatform::setLocale($lang_code);
		$worker_fields[DAO_Worker::LANGUAGE] = $lang_code;

		// Availability calendar
		
		@$availability_calendar_id = DevblocksPlatform::importGPC($_REQUEST['availability_calendar_id'],'integer',0);
		$worker_fields[DAO_Worker::CALENDAR_ID] = $availability_calendar_id;
		
		if(!empty($worker_fields))
			DAO_Worker::update($worker->id, $worker_fields);
		
		// Prefs
		
		@$assist_mode = DevblocksPlatform::importGPC($_REQUEST['assist_mode'],'integer',0);
		DAO_WorkerPref::set($worker->id, 'assist_mode', $assist_mode);

		@$keyboard_shortcuts = DevblocksPlatform::importGPC($_REQUEST['keyboard_shortcuts'],'integer',0);
		DAO_WorkerPref::set($worker->id, 'keyboard_shortcuts', $keyboard_shortcuts);

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
		
		// Alternate Email Addresses
		@$new_email = DevblocksPlatform::importGPC($_REQUEST['new_email'],'string','');
		@$worker_email_ids = DevblocksPlatform::importGPC($_REQUEST['worker_email_ids'],'array',array());

		$current_addresses = DAO_AddressToWorker::getByWorker($worker->id);
		$removed_addresses = array_diff(array_keys($current_addresses), $worker_email_ids);
		
		// Confirm deletions are assigned to the current worker
		if(is_array($removed_addresses))
		foreach($removed_addresses as $removed_address) {
			// Don't remove the primary
			if($removed_address == $worker->email_id)
				continue;

			DAO_AddressToWorker::unassign($removed_address);
		}

		// Assign a new e-mail address if it's legitimate
		if(!empty($new_email)) {
			if(null != ($addy = DAO_Address::lookupAddress($new_email, true))) {
				if(null == ($assigned = DAO_AddressToWorker::getByEmail($new_email))) {
					$this->_sendConfirmationEmail($new_email, $worker);
				} else {
					$pref_errors[] = vsprintf($translate->_('prefs.address.exists'), $new_email);
				}
			} else {
				$pref_errors[] = vsprintf($translate->_('prefs.address.invalid'), $new_email);
			}
		}

		$tpl->assign('pref_errors', $pref_errors);

		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('preferences')));
	}

	function resendConfirmationAction() {
		@$email = DevblocksPlatform::importGPC($_REQUEST['email'],'string','');

		$worker = CerberusApplication::getActiveWorker();
		$this->_sendConfirmationEmail($email, $worker);

		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('preferences')));
	}

	private function _sendConfirmationEmail($to, $worker) {
		$translate = DevblocksPlatform::getTranslationService();
		$settings = DevblocksPlatform::services()->pluginSettings();
		$url_writer = DevblocksPlatform::services()->url();
		$tpl = DevblocksPlatform::services()->template();

		if(false == ($addy = DAO_Address::lookupAddress($to, true)))
			return false;
		
		// Tentatively assign the e-mail address to this worker
		DAO_AddressToWorker::assign($addy->id, $worker->id);

		// Create a confirmation code and save it
		$code = CerberusApplication::generatePassword(20);
		DAO_AddressToWorker::update($addy->id, array(
			DAO_AddressToWorker::CODE => $code,
			DAO_AddressToWorker::CODE_EXPIRE => (time() + 24*60*60)
		));

		// Email the confirmation code to the address
		// [TODO] This function can return false, and we need to do something different if it does.
		CerberusMail::quickSend(
			$addy->email,
			vsprintf($translate->_('prefs.address.confirm.mail.subject'),
				$settings->get('cerberusweb.core',CerberusSettings::HELPDESK_TITLE,CerberusSettingsDefaults::HELPDESK_TITLE)
			),
			vsprintf($translate->_('prefs.address.confirm.mail.body'),
				array(
					$worker->getName(),
					$url_writer->writeNoProxy('c=preferences&a=confirm_email&code='.$code,true)
				)
			)
		);

		$output = array(vsprintf($translate->_('prefs.address.confirm.mail.subject'), $addy->email));
		$tpl->assign('pref_success', $output);
	}

};