<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2014, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerberusweb.com/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerbweb.com	    http://www.webgroupmedia.com/
***********************************************************************/

class PageSection_ProfilesContact extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();
		$translate = DevblocksPlatform::getTranslationService();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // contact
		$id = array_shift($stack); // 123

		@$id = intval($id);
		
		if(null == ($contact = DAO_Contact::get($id))) {
			return;
		}
		$tpl->assign('contact', $contact);
	
		// Tab persistence
		
		$point = 'profiles.contact.tab';
		$tpl->assign('point', $point);
		
		if(null == (@$tab_selected = $stack[0])) {
			$tab_selected = $visit->get($point, '');
		}
		$tpl->assign('tab_selected', $tab_selected);
	
		// Properties
			
		$properties = array();
			
		$properties['email'] = array(
			'label' => ucfirst($translate->_('common.email')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $contact->primary_email_id,
			'params' => array('context' => CerberusContexts::CONTEXT_ADDRESS),
		);
		
		$properties['org'] = array(
			'label' => ucfirst($translate->_('common.organization')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $contact->org_id,
			'params' => array('context' => CerberusContexts::CONTEXT_ORG),
		);
		
		$properties['title'] = array(
			'label' => ucfirst($translate->_('common.title')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $contact->title,
		);
		
		$properties['created'] = array(
			'label' => ucfirst($translate->_('common.created')),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $contact->created_at,
		);
		
		$properties['updated'] = array(
			'label' => ucfirst($translate->_('common.updated')),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $contact->updated_at,
		);
		
		$properties['last_login'] = array(
			'label' => ucfirst($translate->_('common.last_login')),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $contact->last_login_at,
		);
	
		// Custom Fields

		@$values = array_shift(DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_CONTACT, $contact->id)) or array();
		$tpl->assign('custom_field_values', $values);
		
		$properties_cfields = Page_Profiles::getProfilePropertiesCustomFields(CerberusContexts::CONTEXT_CONTACT, $values);
		
		if(!empty($properties_cfields))
			$properties = array_merge($properties, $properties_cfields);
		
		// Custom Fieldsets

		$properties_custom_fieldsets = Page_Profiles::getProfilePropertiesCustomFieldsets(CerberusContexts::CONTEXT_CONTACT, $contact->id, $values);
		$tpl->assign('properties_custom_fieldsets', $properties_custom_fieldsets);
		
		// Link counts
		
		$properties_links = array(
			CerberusContexts::CONTEXT_CONTACT => array(
				$contact->id => 
					DAO_ContextLink::getContextLinkCounts(
						CerberusContexts::CONTEXT_CONTACT,
						$contact->id,
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
			'event.macro.contact'
		);
		$tpl->assign('macros', $macros);

		// Tabs
		$tab_manifests = Extension_ContextProfileTab::getExtensions(false, CerberusContexts::CONTEXT_CONTACT);
		$tpl->assign('tab_manifests', $tab_manifests);
		
		// Template
		$tpl->display('devblocks:cerberusweb.core::profiles/contact.tpl');
	}
	
	function savePeekJsonAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'], 'string', '');
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'], 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		header('Content-Type: application/json; charset=' . LANG_CHARSET_CODE);
		
		try {
		
			if(!empty($id) && !empty($do_delete)) { // Delete
				// [TODO] [ACL] Check delete permission
				
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
				@$title = DevblocksPlatform::importGPC($_REQUEST['title'], 'string', '');
				@$org_id = DevblocksPlatform::importGPC($_REQUEST['org_id'], 'integer', 0);
				@$primary_email_id = DevblocksPlatform::importGPC($_REQUEST['primary_email_id'], 'integer', 0);
				@$username = DevblocksPlatform::importGPC($_REQUEST['username'], 'string', '');
				@$gender = DevblocksPlatform::importGPC($_REQUEST['gender'], 'string', '');
				@$dob = DevblocksPlatform::importGPC($_REQUEST['dob'], 'string', '');
				@$location = DevblocksPlatform::importGPC($_REQUEST['location'], 'string', '');
				@$phone = DevblocksPlatform::importGPC($_REQUEST['phone'], 'string', '');
				@$mobile = DevblocksPlatform::importGPC($_REQUEST['mobile'], 'string', '');
				@$password = DevblocksPlatform::importGPC($_REQUEST['password'], 'string', '');
				
				// Defaults
				
				if(!in_array($gender, array('M','F','')))
					$gender = '';
				
				$dob_ts = null;
				
				// Validation
				
				if(empty($first_name))
					throw new Exception_DevblocksAjaxValidationError("The 'First Name' field is required.", 'first_name');
				
				if(!empty($primary_email_id) && false == (DAO_Address::get($primary_email_id)))
					throw new Exception_DevblocksAjaxValidationError("The specified email address is invalid.", 'primary_email_id');

				if(!empty($dob) && false == ($dob_ts = strtotime($dob . ' 00:00 GMT')))
					throw new Exception_DevblocksAjaxValidationError("The specified date of birth is invalid.", 'dob');
				
				// Insert/Update
				
				if(empty($id)) { // New
					$fields = array(
						DAO_Contact::FIRST_NAME => $first_name,
						DAO_Contact::LAST_NAME => $last_name,
						DAO_Contact::TITLE => $title,
						DAO_Contact::ORG_ID => $org_id,
						DAO_Contact::PRIMARY_EMAIL_ID => $primary_email_id,
						DAO_Contact::USERNAME => $username,
						DAO_Contact::GENDER => $gender,
						DAO_Contact::DOB => (null == $dob_ts) ? null : gmdate('Y-m-d', $dob_ts),
						DAO_Contact::LOCATION => $location,
						DAO_Contact::PHONE => $phone,
						DAO_Contact::MOBILE => $mobile,
						DAO_Contact::CREATED_AT => time(),
						DAO_Contact::UPDATED_AT => time(),
					);

					if(!empty($password)) {
						$salt = CerberusApplication::generatePassword(8);
						$fields[DAO_Contact::AUTH_SALT] = $salt;
						$fields[DAO_Contact::AUTH_PASSWORD] = md5($salt.md5($password));
					}
					
					$id = DAO_Contact::create($fields);
					
					// Watchers
					@$add_watcher_ids = DevblocksPlatform::sanitizeArray(DevblocksPlatform::importGPC($_REQUEST['add_watcher_ids'],'array',array()),'integer',array('unique','nonzero'));
					if(!empty($add_watcher_ids))
						CerberusContexts::addWatchers(CerberusContexts::CONTEXT_CONTACT, $id, $add_watcher_ids);
					
					// Context Link (if given)
					@$link_context = DevblocksPlatform::importGPC($_REQUEST['link_context'],'string','');
					@$link_context_id = DevblocksPlatform::importGPC($_REQUEST['link_context_id'],'integer','');
					if(!empty($id) && !empty($link_context) && !empty($link_context_id)) {
						DAO_ContextLink::setLink(CerberusContexts::CONTEXT_CONTACT, $id, $link_context, $link_context_id);
					}
					
					if(!empty($view_id) && !empty($id))
						C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_CONTACT, $id);
					
				} else { // Edit
					$fields = array(
						DAO_Contact::FIRST_NAME => $first_name,
						DAO_Contact::LAST_NAME => $last_name,
						DAO_Contact::TITLE => $title,
						DAO_Contact::ORG_ID => $org_id,
						DAO_Contact::PRIMARY_EMAIL_ID => $primary_email_id,
						DAO_Contact::USERNAME => $username,
						DAO_Contact::GENDER => $gender,
						DAO_Contact::DOB => (null == $dob_ts) ? null : gmdate('Y-m-d', $dob_ts),
						DAO_Contact::LOCATION => $location,
						DAO_Contact::PHONE => $phone,
						DAO_Contact::MOBILE => $mobile,
						DAO_Contact::UPDATED_AT => time(),
					);
					
					if(!empty($password)) {
						$salt = CerberusApplication::generatePassword(8);
						$fields[DAO_Contact::AUTH_SALT] = $salt;
						$fields[DAO_Contact::AUTH_PASSWORD] = md5($salt.md5($password));
					}
					
					DAO_Contact::update($id, $fields);
				}
				
				if($id) {
					// Custom fields
					@$field_ids = DevblocksPlatform::importGPC($_REQUEST['field_ids'], 'array', array());
					DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_CONTACT, $id, $field_ids);
					
					// Avatar image
					@$avatar_image = DevblocksPlatform::importGPC($_REQUEST['avatar_image'], 'string', '');
					DAO_ContextAvatar::upsertWithImage(CerberusContexts::CONTEXT_CONTACT, $id, $avatar_image);
				}
			}
			
			echo json_encode(array(
				'status' => true,
				'id' => $id,
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
//					'worker_id' => $active_worker->id,
					'total' => $total,
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=search&type=contact', true),
					'toolbar_extension_id' => 'cerberusweb.contexts.contact.explore.toolbar',
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

		$active_worker = CerberusApplication::getActiveWorker();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('view_id', $view_id);

		if(!empty($ids)) {
			$id_list = DevblocksPlatform::parseCsvString($ids);
			$tpl->assign('ids', implode(',', $id_list));
		}
		
		// Custom Fields
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_CONTACT, false);
		$tpl->assign('custom_fields', $custom_fields);
		
		// Macros
		
		$macros = DAO_TriggerEvent::getReadableByActor(
			$active_worker,
			'event.macro.contact'
		);
		$tpl->assign('macros', $macros);
		
		$tpl->display('devblocks:cerberusweb.core::internal/contact/bulk.tpl');
	}
	
	function saveBulkPanelAction() {
		// Filter: whole list or check
		@$filter = DevblocksPlatform::importGPC($_REQUEST['filter'],'string','');
		$ids = array();
		
		// View
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		$view = C4_AbstractViewLoader::getView($view_id);
		$view->setAutoPersist(false);
		
		// Contact fields
		@$title = trim(DevblocksPlatform::importGPC($_POST['title'],'string',''));
		@$org_id = DevblocksPlatform::importGPC($_POST['org_id'],'integer',0);
		@$location = trim(DevblocksPlatform::importGPC($_POST['location'],'string',''));
		@$gender = DevblocksPlatform::importGPC($_POST['gender'],'string','');

		// Scheduled behavior
		@$behavior_id = DevblocksPlatform::importGPC($_POST['behavior_id'],'string','');
		@$behavior_when = DevblocksPlatform::importGPC($_POST['behavior_when'],'string','');
		@$behavior_params = DevblocksPlatform::importGPC($_POST['behavior_params'],'array',array());
		
		$do = array();
		$active_worker = CerberusApplication::getActiveWorker();
		
		// Do: Title
		if(0 != strlen($title))
			$do['title'] = $title;
		
		// Do: Location
		if(0 != strlen($location))
			$do['location'] = $location;
		
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
		
		// Do: Custom fields
		$do = DAO_CustomFieldValue::handleBulkPost($do);

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
		
		$view->doBulkUpdate($filter, $do, $ids);
		$view->render();
		return;
	}
};
