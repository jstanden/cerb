<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2016, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerb.io/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://cerb.io	    http://webgroup.media
***********************************************************************/

class PageSection_ProfilesWorker extends Extension_PageSection {
	function render() {
		$request = DevblocksPlatform::getHttpRequest();
		$stack = $request->path;
		
		if(!isset($stack[2]))
			return;

		$tpl = DevblocksPlatform::getTemplateService();
		$request = DevblocksPlatform::getHttpRequest();
		
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
			
		$tpl->assign('worker', $worker);
		
		// Properties
		
		$translate = DevblocksPlatform::getTranslationService();
		
		$properties = array();
		
		$properties['email'] = array(
			'label' => mb_ucfirst($translate->_('common.email')),
			'type' => Model_CustomField::TYPE_LINK,
			'params' => array('context' => CerberusContexts::CONTEXT_ADDRESS),
			'value' => $worker->email_id,
		);
		
		if(!empty($worker->location)) {
			$properties['location'] = array(
				'label' => mb_ucfirst($translate->_('common.location')),
				'type' => Model_CustomField::TYPE_SINGLE_LINE,
				'value' => $worker->location,
			);
		}
		
		$properties['is_superuser'] = array(
			'label' => mb_ucfirst($translate->_('worker.is_superuser')),
			'type' => Model_CustomField::TYPE_CHECKBOX,
			'value' => $worker->is_superuser,
		);
		
		if(!empty($worker->mobile)) {
			$properties['mobile'] = array(
				'label' => mb_ucfirst($translate->_('common.mobile')),
				'type' => Model_CustomField::TYPE_SINGLE_LINE,
				'value' => $worker->mobile,
			);
		}
		
		if(!empty($worker->phone)) {
			$properties['phone'] = array(
				'label' => mb_ucfirst($translate->_('common.phone')),
				'type' => Model_CustomField::TYPE_SINGLE_LINE,
				'value' => $worker->phone,
			);
		}
		
		$properties['language'] = array(
			'label' => mb_ucfirst($translate->_('common.language')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $worker->language,
		);
		
		$properties['timezone'] = array(
			'label' => mb_ucfirst($translate->_('common.timezone')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $worker->timezone,
		);
		
		if(!empty($worker->calendar_id)) {
			$properties['calendar_id'] = array(
				'label' => mb_ucfirst($translate->_('common.calendar')),
				'type' => Model_CustomField::TYPE_LINK,
				'params' => array('context' => CerberusContexts::CONTEXT_CALENDAR),
				'value' => $worker->calendar_id,
			);
		}
		
		// Custom Fields

		@$values = array_shift(DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_WORKER, $worker->id)) or array();
		$tpl->assign('custom_field_values', $values);
		
		$properties_cfields = Page_Profiles::getProfilePropertiesCustomFields(CerberusContexts::CONTEXT_WORKER, $values);
		
		if(!empty($properties_cfields))
			$properties = array_merge($properties, $properties_cfields);

		// Custom Fieldsets

		$properties_custom_fieldsets = Page_Profiles::getProfilePropertiesCustomFieldsets(CerberusContexts::CONTEXT_WORKER, $worker->id, $values);
		$tpl->assign('properties_custom_fieldsets', $properties_custom_fieldsets);
		
		// Link counts
		
		$properties_links = array(
			CerberusContexts::CONTEXT_WORKER => array(
				$worker->id => 
					DAO_ContextLink::getContextLinkCounts(
						CerberusContexts::CONTEXT_WORKER,
						$worker->id,
						array(CerberusContexts::CONTEXT_WORKER, CerberusContexts::CONTEXT_CUSTOM_FIELDSET)
					),
			),
		);
		
		$tpl->assign('properties_links', $properties_links);
		
		// Properties
		
		$tpl->assign('properties', $properties);
		
		// Macros
		
		$macros = DAO_TriggerEvent::getReadableByActor(
			$active_worker,
			'event.macro.worker'
		);
		$tpl->assign('macros', $macros);

		// Tabs
		$tab_manifests = Extension_ContextProfileTab::getExtensions(false, CerberusContexts::CONTEXT_WORKER);
		$tpl->assign('tab_manifests', $tab_manifests);
		
		// Prefs
		$profile_worker_prefs = DAO_WorkerPref::getByWorker($worker->id);
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
		
		header('Content-Type: application/json; charset=' . LANG_CHARSET_CODE);
		
		try {
			if(!$active_worker || !$active_worker->is_superuser)
				throw new Exception_DevblocksAjaxValidationError("You don't have permission to edit this record.");
	
			if(empty($first_name)) $first_name = "Anonymous";
			
			if(!empty($id) && !empty($delete)) {
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
				@$group_ids = DevblocksPlatform::importGPC($_POST['group_ids'],'array');
				@$group_roles = DevblocksPlatform::importGPC($_POST['group_roles'],'array');
				
				// ============================================
				// Defaults

				if(!in_array($gender, array('M','F','')))
					$gender = '';
				
				$dob_ts = null;
				
				$is_superuser = ($active_worker->is_superuser && $is_superuser) ? 1 : 0;
				
				// ============================================
				// Validation
				
				if(empty($first_name))
					throw new Exception_DevblocksAjaxValidationError("The 'First Name' field is required.", 'first_name');
				
				if(empty($email_id))
					throw new Exception_DevblocksAjaxValidationError("The 'Email' field is required.", 'email_id');
				
				if(!empty($dob) && false == ($dob_ts = strtotime($dob . ' 00:00 GMT')))
					throw new Exception_DevblocksAjaxValidationError("The specified date of birth is invalid.", 'dob');
				
				// Verify that the given email address exists
				if(false == ($worker_address = DAO_Address::get($email_id)))
					throw new Exception_DevblocksAjaxValidationError("The given email address is invalid.", 'email_id');
				
				// Check if the email address is used by another worker
				if(false != ($worker_check = DAO_Worker::getByEmail($worker_address->email)) && (empty($id) || $worker_check->id != $id))
					throw new Exception_DevblocksAjaxValidationError("The given email address is already associated with another worker.", 'email_id');
				
				if(DAO_AddressOutgoing::getByEmail($worker_address->email, false))
					throw new Exception_DevblocksAjaxValidationError("You can not assign an email address to a worker that is already assigned to a group/bucket.", 'email_id');
				
				// Verify passwords if not blank
				if($password_new && ($password_new != $password_verify))
						throw new Exception_DevblocksAjaxValidationError("The given passwords do not match.", 'password_new');
				
				// Verify auth extension
				if(false == ($auth_extension = Extension_LoginAuthenticator::get($auth_extension_id)))
						throw new Exception_DevblocksAjaxValidationError("The login method is invalid.", 'auth_extension_id');
				
				// Verify @mention name
				if(!empty($at_mention_name)) {
					$at_mentions = DAO_Worker::getByAtMentions(array($at_mention_name));
					
					// Remove the current worker id
					unset($at_mentions[$id]);
					
					if(!empty($at_mentions))
						throw new Exception_DevblocksAjaxValidationError(sprintf("The @mention name (%s) is already in use by another worker.", $at_mention_name), 'at_mention_name');
				}
				
				// Verify timezone is legit
				$date = DevblocksPlatform::getDateService();
				$timezones = $date->getTimezones();
				if(false === array_search($timezone, $timezones))
						throw new Exception_DevblocksAjaxValidationError("The given timezone is invalid.", 'timezone');
				
				// Verify language
				$languages = DAO_Translation::getDefinedLangCodes();
				if($language && !isset($languages[$language]))
						throw new Exception_DevblocksAjaxValidationError("The given language is invalid.", 'language');
				
				if(empty($id)) {
					if(empty($password_new)) {
						// Creating new worker.  If password is empty, email it to them
						$replyto_default = DAO_AddressOutgoing::getDefault();
						$replyto_personal = $replyto_default->getReplyPersonal();
						$url = DevblocksPlatform::getUrlService();
						$password = CerberusApplication::generatePassword(8);
						
						try {
							$mail_service = DevblocksPlatform::getMailService();
							$mail = $mail_service->createMessage();
							
							$mail->setTo(array($worker_address->email => $first_name . ' ' . $last_name));
							
							if(!empty($replyto_personal)) {
								$mail->setFrom($replyto_default->email, $replyto_personal);
							} else {
								$mail->setFrom($replyto_default->email);
							}
							
							$mail->setSubject('Your new Cerb login information!');
							
							$headers = $mail->getHeaders();
							
							$headers->addTextHeader('X-Mailer','Cerb ' . APP_VERSION . ' (Build '.APP_BUILD.')');
							
							$body = sprintf("Your new Cerb login information is below:\r\n".
								"\r\n".
								"URL: %s\r\n".
								"Login: %s\r\n".
								"\r\n",
									$url->write('',true),
									$worker_address->email
							);
							
							$mail->setBody($body);
		
							if(!$mail_service->send($mail)) {
								throw new Exception('Password notification email failed to send.');
							}
							
						} catch (Exception $e) {
							// [TODO] need to report to the admin when the password email doesn't send.  The try->catch
							// will keep it from killing php, but the password will be empty and the user will never get an email.
						}
					}
					
					// [TODO] Fix the redundancy here between create/update (unset common $fields)
					$fields = array(
						DAO_Worker::FIRST_NAME => $first_name,
						DAO_Worker::LAST_NAME => $last_name,
						DAO_Worker::TITLE => $title,
						DAO_Worker::IS_SUPERUSER => $is_superuser,
						DAO_Worker::IS_DISABLED => $disabled,
						DAO_Worker::EMAIL_ID => $worker_address->id,
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
					
					if(false == ($id = DAO_Worker::create($fields)))
						return false;
					
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
					DAO_Worker::EMAIL_ID => $worker_address->id,
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
				
				// Update worker
				DAO_Worker::update($id, $fields);
				
				// Auth
				if(!empty($password_new) && $password_new == $password_verify) {
					DAO_Worker::setAuth($id, $password_new);
				}
				
				// Update group memberships
				if(is_array($group_ids) && is_array($group_roles))
				foreach($group_ids as $idx => $group_id) {
					if(empty($group_roles[$idx])) {
						DAO_Group::unsetGroupMember($group_id, $id);
					} else {
						DAO_Group::setGroupMember($group_id, $id, (2==$group_roles[$idx]));
					}
				}
	
				if($id) {
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
	
	function showBulkPopupAction() {
		@$id_csv = DevblocksPlatform::importGPC($_REQUEST['ids']);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id']);

		$active_worker = CerberusApplication::getActiveWorker();
		
		// Permissions
		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError("You don't have permission to edit this record.", 403);
		
		$tpl = DevblocksPlatform::getTemplateService();
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
		$date = DevblocksPlatform::getDateService();
		$tpl->assign('timezones', $date->getTimezones());
		
		// Broadcast
		
		CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, null, $token_labels, $token_values);
		
		$placeholders = Extension_DevblocksContext::getPlaceholderTree($token_labels);
		$tpl->assign('placeholders', $placeholders);
		
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		$html_templates = DAO_MailHtmlTemplate::getAll();
		$tpl->assign('html_templates', $html_templates);
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/workers/bulk.tpl');
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
		if($active_worker->hasPriv('context.worker.worklist.broadcast')) {
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
			
			if(!CerberusContexts::isWriteableByActor($calendar->owner_context, $calendar->owner_context_id, $active_worker))
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
		$url_writer = DevblocksPlatform::getUrlService();
		
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