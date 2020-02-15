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

class PageSection_ProfilesWorker extends Extension_PageSection {
	function render() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		$request = DevblocksPlatform::getHttpRequest();
		$stack = $request->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // worker
		@$context_id = array_shift($stack);
		
		switch($context_id) {
			case 'me':
				$context_id = $active_worker->id;
				break;
				
			default:
				@$context_id = intval($context_id);
				break;
		}
		
		$context = CerberusContexts::CONTEXT_WORKER;
		
		Page_Profiles::renderProfile($context, $context_id, $stack);
	}
	
	function savePeekJsonAction() {
		@$id = DevblocksPlatform::importGPC($_POST['id'],'integer');
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string');
		@$delete = DevblocksPlatform::importGPC($_POST['do_delete'],'integer',0);

		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(403);
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			if(!$active_worker || !$active_worker->is_superuser)
				throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.admin'));
	
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
				@$email_ids = DevblocksPlatform::importGPC($_POST['email_ids'],'array:int', []);
				@$dob = DevblocksPlatform::importGPC($_POST['dob'],'string', '');
				@$location = DevblocksPlatform::importGPC($_POST['location'],'string', '');
				@$mobile = DevblocksPlatform::importGPC($_POST['mobile'],'string', '');
				@$phone = DevblocksPlatform::importGPC($_POST['phone'],'string', '');
				@$gender = DevblocksPlatform::importGPC($_POST['gender'],'string', '');
				@$at_mention_name = DevblocksPlatform::strToPermalink(DevblocksPlatform::importGPC($_POST['at_mention_name'],'string'));
				@$language = DevblocksPlatform::importGPC($_POST['lang_code'],'string');
				@$timezone = DevblocksPlatform::importGPC($_POST['timezone'],'string');
				@$time_format = DevblocksPlatform::importGPC($_POST['time_format'],'string');
				@$calendar_id = DevblocksPlatform::importGPC($_POST['calendar_id'],'string');
				@$is_superuser = DevblocksPlatform::importGPC($_POST['is_superuser'],'bit', 0);
				@$disabled = DevblocksPlatform::importGPC($_POST['is_disabled'],'bit',0);
				@$is_password_disabled = DevblocksPlatform::importGPC($_POST['is_password_disabled'],'bit',0);
				@$is_mfa_required = DevblocksPlatform::importGPC($_POST['is_mfa_required'],'bit',0);
				@$group_memberships = DevblocksPlatform::importGPC($_POST['group_memberships'],'array');
				
				$existing_worker = DAO_Worker::get($id);
				$error = null;
				
				// ============================================
				// Defaults
				
				if(empty($first_name))
					$first_name = "Anonymous";
				
				if(!in_array($gender, array('M','F','')))
					$gender = '';
				
				$dob_ts = null;
				
				if(!empty($dob) && false == ($dob_ts = strtotime($dob . ' 00:00 GMT')))
					$dob_ts = null;
				
				$is_superuser = ($active_worker->is_superuser && $is_superuser) ? 1 : 0;
				
				// ============================================
				// Validation
				
				if(empty($id)) {
					$fields = [
						DAO_Worker::AT_MENTION_NAME => $at_mention_name,
						DAO_Worker::DOB => (null == $dob_ts) ? null : gmdate('Y-m-d', $dob_ts),
						DAO_Worker::EMAIL_ID => $email_id,
						DAO_Worker::FIRST_NAME => trim($first_name),
						DAO_Worker::GENDER => $gender,
						DAO_Worker::IS_DISABLED => $disabled,
						DAO_Worker::IS_MFA_REQUIRED => $is_mfa_required,
						DAO_Worker::IS_PASSWORD_DISABLED => $is_password_disabled,
						DAO_Worker::IS_SUPERUSER => $is_superuser,
						DAO_Worker::LANGUAGE => $language,
						DAO_Worker::LAST_NAME => trim($last_name),
						DAO_Worker::LOCATION => $location,
						DAO_Worker::MOBILE => $mobile,
						DAO_Worker::PHONE => $phone,
						DAO_Worker::TIME_FORMAT => $time_format,
						DAO_Worker::TIMEZONE => $timezone,
						DAO_Worker::TITLE => $title,
					];
					
					// Update alternate email addresses
					$fields[DAO_Worker::_EMAIL_IDS] = array_unique(array_merge($email_ids, [$email_id]));
					
					if(!DAO_Worker::validate($fields, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_Worker::onBeforeUpdateByActor($active_worker, $fields, null, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(false == ($id = DAO_Worker::create($fields)))
						return false;
					
					DAO_Worker::onUpdateByActor($active_worker, $fields, $id);
					
					// View marquee
					if(!empty($id) && !empty($view_id)) {
						C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_WORKER, $id);
					}
					
				} else {
					// Update
					$fields = [
						DAO_Worker::AT_MENTION_NAME => $at_mention_name,
						DAO_Worker::DOB => (null == $dob_ts) ? null : gmdate('Y-m-d', $dob_ts),
						DAO_Worker::EMAIL_ID => $email_id,
						DAO_Worker::FIRST_NAME => $first_name,
						DAO_Worker::GENDER => $gender,
						DAO_Worker::IS_DISABLED => $disabled,
						DAO_Worker::IS_MFA_REQUIRED => $is_mfa_required,
						DAO_Worker::IS_PASSWORD_DISABLED => $is_password_disabled,
						DAO_Worker::IS_SUPERUSER => $is_superuser,
						DAO_Worker::LANGUAGE => $language,
						DAO_Worker::LAST_NAME => $last_name,
						DAO_Worker::LOCATION => $location,
						DAO_Worker::MOBILE => $mobile,
						DAO_Worker::PHONE => $phone,
						DAO_Worker::TIME_FORMAT => $time_format,
						DAO_Worker::TIMEZONE => $timezone,
						DAO_Worker::TITLE => $title,
					];
					
					// Update alternate email addresses
					$fields[DAO_Worker::_EMAIL_IDS] = array_unique(array_merge($email_ids, [$email_id]));
					
					if(!DAO_Worker::validate($fields, $error, $id))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_Worker::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					// Update worker
					DAO_Worker::update($id, $fields);
					DAO_Worker::onUpdateByActor($active_worker, $fields, $id);
				}
				
				// Create a new calendar for this worker?
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
				
				if($existing_worker && $existing_worker->calendar_id != $calendar_id)
					DAO_Worker::update($id, [DAO_Worker::CALENDAR_ID => $calendar_id]);
				
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
					if(false == ($updated_worker = DAO_Worker::get($id)))
						throw new Exception_DevblocksAjaxValidationError("Failed to create the worker record.");
					
					// Passwords
					if($is_password_disabled) {
						DAO_Worker::setAuth($id, null);
					}
					
					// Are we sending an invite?
					if(
						// Are they a new record?
						!$existing_worker 
						// Or are we re-enabling passwords on an existing worker?
						|| ($existing_worker && !$is_password_disabled && $existing_worker->is_password_disabled && !DAO_Worker::hasAuth($updated_worker->id))
					) {
						$url = DevblocksPlatform::services()->url();
						
						$invite_code = CerberusApplication::generatePassword(64);
						
						$fields = [
							DAO_ConfirmationCode::CONFIRMATION_CODE => $invite_code,
							DAO_ConfirmationCode::CREATED => time(),
							DAO_ConfirmationCode::NAMESPACE_KEY => 'login.invite',
							DAO_ConfirmationCode::META_JSON => json_encode([
								'worker_id' => $updated_worker->id,
							]),
						];
						DAO_ConfirmationCode::create($fields);
						
						$labels = $values = $worker_labels = $worker_values = [];
						CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, $updated_worker, $worker_labels, $worker_values, '', true, true);
						CerberusContexts::merge('worker_', null, $worker_labels, $worker_values, $labels, $values);
						
						$values['url'] = $url->write('c=login&a=invite', true) . '/' . $invite_code;
						
						CerberusApplication::sendEmailTemplate($updated_worker->getEmailString(), 'worker_invite', $values);
					}
					
					// Custom field saves
					@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', []);
					if(!DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_WORKER, $updated_worker->id, $field_ids, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					// Aliases
					DAO_ContextAlias::set(CerberusContexts::CONTEXT_WORKER, $updated_worker->id, DevblocksPlatform::parseCrlfString(sprintf("%s%s", $first_name, $last_name ? (' '.$last_name) : '') . "\n" . $aliases));
					
					// Avatar image
					@$avatar_image = DevblocksPlatform::importGPC($_POST['avatar_image'], 'string', '');
					DAO_ContextAvatar::upsertWithImage(CerberusContexts::CONTEXT_WORKER, $updated_worker->id, $avatar_image);
					
					// Flush caches
					DAO_WorkerRole::clearWorkerCache($updated_worker->id);
					
					// Index immediately
					$search = Extension_DevblocksSearchSchema::get(Search_Worker::ID);
					$search->indexIds([$updated_worker->id]);
				}
			}
			
			$label = $updated_worker->getName();
			
			echo json_encode([
				'status' => true,
				'id' => $id,
				'label' => $label,
				'view_id' => $view_id,
			]);
			return;
			
		} catch (Exception_DevblocksAjaxValidationError $e) {
			echo json_encode([
				'status' => false,
				'error' => $e->getMessage(),
				'field' => $e->getFieldName(),
			]);
			return;
			
		} catch (Exception $e) {
			echo json_encode([
				'status' => false,
				'error' => 'An error occurred.',
			]);
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
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('view_id', $view_id);

		if(!empty($id_csv)) {
			$ids = DevblocksPlatform::parseCsvString($id_csv);
			$tpl->assign('ids', implode(',', $ids));
		}
		
		// Custom Fields
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_WORKER, false);
		$tpl->assign('custom_fields', $custom_fields);
		
		// Languages
		$translate = DevblocksPlatform::getTranslationService();
		$locales = $translate->getLocaleStrings();
		$tpl->assign('languages', $locales);
		
		// Timezones
		$date = DevblocksPlatform::services()->date();
		$tpl->assign('timezones', $date->getTimezones());
		
		// Broadcast
		
		if(false == ($context_ext = Extension_DevblocksContext::get(CerberusContexts::CONTEXT_WORKER)))
			return [];
		
		/* @var $context_ext IDevblocksContextBroadcast */
			
		// Recipient fields
		$recipient_fields = $context_ext->broadcastRecipientFieldsGet();
		$tpl->assign('broadcast_recipient_fields', $recipient_fields);
		
		// Placeholders
		$token_values = $context_ext->broadcastPlaceholdersGet();
		@$token_labels = $token_values['_labels'] ?: [];
		
		$placeholders = Extension_DevblocksContext::getPlaceholderTree($token_labels);
		$tpl->assign('placeholders', $placeholders);
		
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		$buckets = DAO_Bucket::getAll();
		$tpl->assign('buckets', $buckets);
		
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
		@$is_password_disabled = trim(DevblocksPlatform::importGPC($_POST['is_password_disabled'],'string',''));
		@$is_mfa_required = trim(DevblocksPlatform::importGPC($_POST['is_mfa_required'],'string',''));
		@$title = trim(DevblocksPlatform::importGPC($_POST['title'],'string',''));
		@$location = trim(DevblocksPlatform::importGPC($_POST['location'],'string',''));
		@$gender = trim(DevblocksPlatform::importGPC($_POST['gender'],'string',''));
		@$language = trim(DevblocksPlatform::importGPC($_POST['language'],'string',''));
		@$timezone = trim(DevblocksPlatform::importGPC($_POST['timezone'],'string',''));

		$do = array();
		
		// Do: Disabled
		if(0 != strlen($is_disabled))
			$do['is_disabled'] = $is_disabled;
		
		// Do: Password Disabled
		if(0 != strlen($is_password_disabled))
			$do['is_password_disabled'] = $is_password_disabled;
		
		// Do: MFA Required
		if(0 != strlen($is_mfa_required))
			$do['is_mfa_required'] = $is_mfa_required;
			
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
			@$broadcast_bucket_id = DevblocksPlatform::importGPC($_REQUEST['broadcast_bucket_id'],'integer',0);
			@$broadcast_to = DevblocksPlatform::importGPC($_REQUEST['broadcast_to'],'array',[]);
			@$broadcast_subject = DevblocksPlatform::importGPC($_REQUEST['broadcast_subject'],'string',null);
			@$broadcast_message = DevblocksPlatform::importGPC($_REQUEST['broadcast_message'],'string',null);
			@$broadcast_format = DevblocksPlatform::importGPC($_REQUEST['broadcast_format'],'string',null);
			@$broadcast_html_template_id = DevblocksPlatform::importGPC($_REQUEST['broadcast_html_template_id'],'integer',0);
			@$broadcast_is_queued = DevblocksPlatform::importGPC($_REQUEST['broadcast_is_queued'],'integer',0);
			@$broadcast_status_id = DevblocksPlatform::importGPC($_REQUEST['broadcast_status_id'],'integer',0);
			@$broadcast_file_ids = DevblocksPlatform::sanitizeArray(DevblocksPlatform::importGPC($_REQUEST['broadcast_file_ids'],'array',array()), 'integer', array('nonzero','unique'));
			
			if(0 != strlen($do_broadcast) && !empty($broadcast_subject) && !empty($broadcast_message)) {
				$do['broadcast'] = [
					'to' => $broadcast_to,
					'subject' => $broadcast_subject,
					'message' => $broadcast_message,
					'format' => $broadcast_format,
					'html_template_id' => $broadcast_html_template_id,
					'is_queued' => $broadcast_is_queued,
					'status_id' => $broadcast_status_id,
					'group_id' => $broadcast_group_id,
					'bucket_id' => $broadcast_bucket_id,
					'worker_id' => $active_worker->id,
					'file_ids' => $broadcast_file_ids,
				];
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
			$view->addParams([
				new DevblocksSearchCriteria(SearchFields_Worker::ID, 'in', $ids)
			], true);
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