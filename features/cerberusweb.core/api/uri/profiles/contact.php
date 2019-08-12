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

class PageSection_ProfilesContact extends Extension_PageSection {
	function render() {
		$context = CerberusContexts::CONTEXT_CONTACT;
		
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // contact
		@$context_id = intval(array_shift($stack)); // 123
		
		Page_Profiles::renderProfile($context, $context_id, $stack);
	}
	
	function savePeekJsonAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'], 'string', '');
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'], 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
		
			if(!empty($id) && !empty($do_delete)) { // Delete
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", CerberusContexts::CONTEXT_CONTACT)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				DAO_Contact::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
				
			} else {
				@$first_name = DevblocksPlatform::importGPC($_REQUEST['first_name'], 'string', '');
				@$last_name = DevblocksPlatform::importGPC($_REQUEST['last_name'], 'string', '');
				@$aliases = DevblocksPlatform::importGPC($_REQUEST['aliases'],'string','');
				@$title = DevblocksPlatform::importGPC($_REQUEST['title'], 'string', '');
				@$org_id = DevblocksPlatform::importGPC($_REQUEST['org_id'], 'integer', 0);
				@$primary_email_id = DevblocksPlatform::importGPC($_REQUEST['primary_email_id'], 'integer', 0);
				@$username = DevblocksPlatform::importGPC($_REQUEST['username'], 'string', '');
				@$gender = DevblocksPlatform::importGPC($_REQUEST['gender'], 'string', '');
				@$dob = DevblocksPlatform::importGPC($_REQUEST['dob'], 'string', '');
				@$location = DevblocksPlatform::importGPC($_REQUEST['location'], 'string', '');
				@$language = DevblocksPlatform::importGPC($_REQUEST['language'], 'string', '');
				@$timezone = DevblocksPlatform::importGPC($_REQUEST['timezone'], 'string', '');
				@$phone = DevblocksPlatform::importGPC($_REQUEST['phone'], 'string', '');
				@$mobile = DevblocksPlatform::importGPC($_REQUEST['mobile'], 'string', '');
				@$password = DevblocksPlatform::importGPC($_REQUEST['password'], 'string', '');
				
				$error = null;
				
				// Defaults
				
				$dob_ts = null;
				
				if(!empty($dob) && false == ($dob_ts = strtotime($dob . ' 00:00 GMT')))
					$dob_ts = null;
				
				// Insert/Update
				
				if(empty($id)) { // New
					$fields = [
						DAO_Contact::FIRST_NAME => $first_name,
						DAO_Contact::LAST_NAME => $last_name,
						DAO_Contact::TITLE => $title,
						DAO_Contact::ORG_ID => $org_id,
						DAO_Contact::PRIMARY_EMAIL_ID => $primary_email_id,
						DAO_Contact::USERNAME => $username,
						DAO_Contact::GENDER => $gender,
						DAO_Contact::DOB => (null == $dob_ts) ? null : gmdate('Y-m-d', $dob_ts),
						DAO_Contact::LOCATION => $location,
						DAO_Contact::LANGUAGE => $language,
						DAO_Contact::TIMEZONE => $timezone,
						DAO_Contact::PHONE => $phone,
						DAO_Contact::MOBILE => $mobile,
						DAO_Contact::CREATED_AT => time(),
						DAO_Contact::UPDATED_AT => time(),
					];
					
					if(!empty($password)) {
						$salt = CerberusApplication::generatePassword(8);
						$fields[DAO_Contact::AUTH_SALT] = $salt;
						$fields[DAO_Contact::AUTH_PASSWORD] = md5($salt.md5($password));
					}
					
					if(!DAO_Contact::validate($fields, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_Contact::onBeforeUpdateByActor($active_worker, $fields, null, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					$id = DAO_Contact::create($fields);
					DAO_Contact::onUpdateByActor($active_worker, $fields, $id);
					
					if(!empty($view_id) && !empty($id))
						C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_CONTACT, $id);
					
				} else { // Edit
					$fields = [
						DAO_Contact::FIRST_NAME => $first_name,
						DAO_Contact::LAST_NAME => $last_name,
						DAO_Contact::TITLE => $title,
						DAO_Contact::ORG_ID => $org_id,
						DAO_Contact::PRIMARY_EMAIL_ID => $primary_email_id,
						DAO_Contact::USERNAME => $username,
						DAO_Contact::GENDER => $gender,
						DAO_Contact::DOB => (null == $dob_ts) ? null : gmdate('Y-m-d', $dob_ts),
						DAO_Contact::LOCATION => $location,
						DAO_Contact::LANGUAGE => $language,
						DAO_Contact::TIMEZONE => $timezone,
						DAO_Contact::PHONE => $phone,
						DAO_Contact::MOBILE => $mobile,
						DAO_Contact::UPDATED_AT => time(),
					];
					
					if(!DAO_Contact::validate($fields, $error, $id))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_Contact::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!empty($password)) {
						$salt = CerberusApplication::generatePassword(8);
						$fields[DAO_Contact::AUTH_SALT] = $salt;
						$fields[DAO_Contact::AUTH_PASSWORD] = md5($salt.md5($password));
					}
					
					DAO_Contact::update($id, $fields);
					DAO_Contact::onUpdateByActor($active_worker, $fields, $id);
				}
				
				if($id) {
					// Aliases
					DAO_ContextAlias::set(CerberusContexts::CONTEXT_CONTACT, $id, DevblocksPlatform::parseCrlfString(sprintf("%s%s", $first_name, $last_name ? (' '.$last_name) : '') . "\n" . $aliases));
					
					// Custom field saves
					@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', []);
					if(!DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_CONTACT, $id, $field_ids, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					// Avatar image
					@$avatar_image = DevblocksPlatform::importGPC($_REQUEST['avatar_image'], 'string', '');
					DAO_ContextAvatar::upsertWithImage(CerberusContexts::CONTEXT_CONTACT, $id, $avatar_image);
					
					// Index immediately
					$search = Extension_DevblocksSearchSchema::get(Search_Contact::ID);
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
//					'worker_id' => $active_worker->id,
					'total' => $total,
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=search&type=contact', true),
				);
				$models[] = $model;
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
			foreach($results as $opp_id => $row) {
				if($opp_id==$explore_from)
					$orig_pos = $pos;
				
				$name = $row[SearchFields_Contact::FIRST_NAME];

				if(!empty($row[SearchFields_Contact::LAST_NAME]))
					$name .= (!empty($name) ? ' ' : '') . $row[SearchFields_Contact::LAST_NAME];
				
				$url = $url_writer->writeNoProxy(sprintf("c=profiles&type=contact&id=%d-%s", $row[SearchFields_Contact::ID], DevblocksPlatform::strToPermalink($name)), true);
				
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'id' => $row[SearchFields_Contact::ID],
					'url' => $url,
				);
				$models[] = $model;
			}
			
			DAO_ExplorerSet::createFromModels($models);
			
			$view->renderPage++;
			
		} while(!empty($results));
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('explore',$hash,$orig_pos)));
	}
	
	function showBulkPanelAction() {
		@$ids = DevblocksPlatform::importGPC($_REQUEST['ids']);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id']);

		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('view_id', $view_id);

		if(!empty($ids)) {
			$id_list = DevblocksPlatform::parseCsvString($ids);
			$tpl->assign('ids', implode(',', $id_list));
		}
		
		// Custom Fields
		
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_CONTACT, false);
		$tpl->assign('custom_fields', $custom_fields);
		
		// Languages
		$translate = DevblocksPlatform::getTranslationService();
		$locales = $translate->getLocaleStrings();
		$tpl->assign('languages', $locales);
		
		// Timezones
		$date = DevblocksPlatform::services()->date();
		$tpl->assign('timezones', $date->getTimezones());
		
		// Broadcast
		if(false == ($context_ext = Extension_DevblocksContext::get(CerberusContexts::CONTEXT_CONTACT)))
			return [];
		
		/* @var $context_ext IDevblocksContextBroadcast */
			
		// Recipient fields
		$recipient_fields = $context_ext->broadcastRecipientFieldsGet();
		$tpl->assign('broadcast_recipient_fields', $recipient_fields);
		
		// Placeholders
		$token_values = $context_ext->broadcastPlaceholdersGet();
		$token_labels = $token_values['_labels'];
		
		$placeholders = Extension_DevblocksContext::getPlaceholderTree($token_labels);
		$tpl->assign('placeholders', $placeholders);
		
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		$html_templates = DAO_MailHtmlTemplate::getAll();
		$tpl->assign('html_templates', $html_templates);
		
		$tpl->display('devblocks:cerberusweb.core::internal/contact/bulk.tpl');
	}
	
	function startBulkUpdateJsonAction() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		// Filter: whole list or check
		@$filter = DevblocksPlatform::importGPC($_REQUEST['filter'],'string','');
		$ids = array();
		
		// View
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		$view = C4_AbstractViewLoader::getView($view_id);
		$view->setAutoPersist(false);
		
		// Contact fields
		@$status = DevblocksPlatform::importGPC($_POST['status'],'string','');
		@$title = trim(DevblocksPlatform::importGPC($_POST['title'],'string',''));
		@$org_id = DevblocksPlatform::importGPC($_POST['org_id'],'integer',0);
		@$location = trim(DevblocksPlatform::importGPC($_POST['location'],'string',''));
		@$language = trim(DevblocksPlatform::importGPC($_POST['language'],'string',''));
		@$timezone = trim(DevblocksPlatform::importGPC($_POST['timezone'],'string',''));
		@$gender = DevblocksPlatform::importGPC($_POST['gender'],'string','');

		// Scheduled behavior
		@$behavior_id = DevblocksPlatform::importGPC($_POST['behavior_id'],'string','');
		@$behavior_when = DevblocksPlatform::importGPC($_POST['behavior_when'],'string','');
		@$behavior_params = DevblocksPlatform::importGPC($_POST['behavior_params'],'array',array());
		
		$do = array();
		
			// Delete
		if(strlen($status) > 0) {
			switch($status) {
				case 'deleted':
					if($active_worker->is_superuser) {
						$do['delete'] = true;
					}
					break;
			}
		}
		
		// Do: Title
		if(0 != strlen($title))
			$do['title'] = $title;
		
		// Do: Location
		if(0 != strlen($location))
			$do['location'] = $location;
		
		if(0 != strlen($language))
			$do['language'] = $language;
		
		if(0 != strlen($timezone))
			$do['timezone'] = $timezone;
		
		// Do: Gender
		if(0 != strlen($gender) && in_array($gender, array('M','F')))
			$do['gender'] = $gender;
			
		// Do: Org ID
		if(0 != $org_id)
			$do['org_id'] = $org_id;
		
		// Do: Scheduled Behavior
		if(0 != strlen($behavior_id)) {
			$do['behavior'] = array(
				'id' => $behavior_id,
				'when' => $behavior_when,
				'params' => $behavior_params,
			);
		}
		
		// Watchers
		$watcher_params = array();
		
		@$watcher_add_ids = DevblocksPlatform::importGPC($_REQUEST['do_watcher_add_ids'],'array',array());
		if(!empty($watcher_add_ids))
			$watcher_params['add'] = $watcher_add_ids;
			
		@$watcher_remove_ids = DevblocksPlatform::importGPC($_REQUEST['do_watcher_remove_ids'],'array',array());
		if(!empty($watcher_remove_ids))
			$watcher_params['remove'] = $watcher_remove_ids;
		
		if(!empty($watcher_params))
			$do['watchers'] = $watcher_params;
		
		// Do: Custom fields
		$do = DAO_CustomFieldValue::handleBulkPost($do);
		
		// Broadcast: Compose
		if($active_worker->hasPriv('contexts.cerberusweb.contexts.contact.broadcast')) {
			@$do_broadcast = DevblocksPlatform::importGPC($_REQUEST['do_broadcast'],'string',null);
			@$broadcast_group_id = DevblocksPlatform::importGPC($_REQUEST['broadcast_group_id'],'integer',0);
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
			$view->addParams([
				new DevblocksSearchCriteria(SearchFields_Contact::ID, 'in', $ids)
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
};
