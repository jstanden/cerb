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

class PageSection_ProfilesAddress extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$translate = DevblocksPlatform::getTranslationService();
		$response = DevblocksPlatform::getHttpResponse();
		$active_worker = CerberusApplication::getActiveWorker();

		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // address
		@$id = intval(array_shift($stack));
		
		$address = DAO_Address::get($id);
		$tpl->assign('address', $address);
		
		$point = 'cerberusweb.profiles.address';
		$tpl->assign('point', $point);
		
		// Properties
		
		$properties = array();
		
		if(!empty($address->contact_id)) {
			if(null != ($contact = $address->getContact())) {
				$properties['contact'] = array(
					'label' => mb_ucfirst($translate->_('common.contact')),
					'type' => Model_CustomField::TYPE_LINK,
					'value' => $address->contact_id,
					'params' => array(
						'context' => CerberusContexts::CONTEXT_CONTACT,
					),
				);
			}
		}
		
		if(!empty($address->contact_org_id)) {
			if(null != ($org = $address->getOrg())) {
				$properties['org'] = array(
					'label' => mb_ucfirst($translate->_('common.organization')),
					'type' => Model_CustomField::TYPE_LINK,
					'value' => $address->contact_org_id,
					'params' => array(
						'context' => CerberusContexts::CONTEXT_ORG,
					),
				);
			}
		}
		
		$properties['num_spam'] = array(
			'label' => mb_ucfirst($translate->_('address.num_spam')),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $address->num_spam,
		);
		
		$properties['num_nonspam'] = array(
			'label' => mb_ucfirst($translate->_('address.num_nonspam')),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $address->num_nonspam,
		);
		
		$properties['is_banned'] = array(
			'label' => mb_ucfirst($translate->_('address.is_banned')),
			'type' => Model_CustomField::TYPE_CHECKBOX,
			'value' => $address->is_banned,
		);
		
		$properties['is_defunct'] = array(
			'label' => mb_ucfirst($translate->_('address.is_defunct')),
			'type' => Model_CustomField::TYPE_CHECKBOX,
			'value' => $address->is_defunct,
		);
		
		
		// Custom Fields

		@$values = array_shift(DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_ADDRESS, $address->id)) or array();
		$tpl->assign('custom_field_values', $values);
		
		$properties_cfields = Page_Profiles::getProfilePropertiesCustomFields(CerberusContexts::CONTEXT_ADDRESS, $values);
		
		if(!empty($properties_cfields))
			$properties = array_merge($properties, $properties_cfields);
		
		// Custom Fieldsets

		$properties_custom_fieldsets = Page_Profiles::getProfilePropertiesCustomFieldsets(CerberusContexts::CONTEXT_ADDRESS, $address->id, $values);
		$tpl->assign('properties_custom_fieldsets', $properties_custom_fieldsets);
		
		// Link counts
		
		$properties_links = array(
			CerberusContexts::CONTEXT_ADDRESS => array(
				$address->id => 
					DAO_ContextLink::getContextLinkCounts(
						CerberusContexts::CONTEXT_ADDRESS,
						$address->id,
						array(CerberusContexts::CONTEXT_WORKER, CerberusContexts::CONTEXT_CUSTOM_FIELDSET)
					),
			),
		);
		
		if(isset($address->contact_org_id)) {
			$properties_links[CerberusContexts::CONTEXT_ORG] = array(
				$address->contact_org_id => 
					DAO_ContextLink::getContextLinkCounts(
						CerberusContexts::CONTEXT_ORG,
						$address->contact_org_id,
						array(CerberusContexts::CONTEXT_WORKER, CerberusContexts::CONTEXT_CUSTOM_FIELDSET)
					),
			);
		}
		
		$tpl->assign('properties_links', $properties_links);
		
		// Properties
		
		$tpl->assign('properties', $properties);
		
		// Macros
		
		$macros = DAO_TriggerEvent::getReadableByActor(
			$active_worker,
			'event.macro.address'
		);
		$tpl->assign('macros', $macros);
		
		// Tabs
		$tab_manifests = Extension_ContextProfileTab::getExtensions(false, CerberusContexts::CONTEXT_ADDRESS);
		$tpl->assign('tab_manifests', $tab_manifests);

		// Template
		$tpl->display('devblocks:cerberusweb.core::profiles/address.tpl');
	}
	
	function savePeekJsonAction() {
		$active_worker = CerberusApplication::getActiveWorker();
		$db = DevblocksPlatform::getDatabaseService();
		
		header('Content-Type: application/json; charset=' . LANG_CHARSET_CODE);
		
		try {
			@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer', 0);
			@$email = mb_convert_case(trim(DevblocksPlatform::importGPC($_REQUEST['email'],'string','')), MB_CASE_LOWER);
			@$contact_id = DevblocksPlatform::importGPC($_REQUEST['contact_id'],'integer',0);
			@$org_id = DevblocksPlatform::importGPC($_REQUEST['org_id'],'integer',0);
			@$is_banned = DevblocksPlatform::importGPC($_REQUEST['is_banned'],'bit',0);
			@$is_defunct = DevblocksPlatform::importGPC($_REQUEST['is_defunct'],'bit',0);
			@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string', '');
			
			if(!$active_worker->hasPriv('core.addybook.addy.actions.update'))
				throw new Exception_DevblocksAjaxValidationError("You don't have permission to modify this record.");
				
			// Common fields
			$fields = array(
				DAO_Address::CONTACT_ORG_ID => $org_id,
				DAO_Address::CONTACT_ID => $contact_id,
				DAO_Address::IS_BANNED => $is_banned,
				DAO_Address::IS_DEFUNCT => $is_defunct,
			);
			
			if(empty($id)) {
				if(empty($email))
					throw new Exception_DevblocksAjaxValidationError("The 'Email' field is required.", 'email');
				
				$validated_emails = CerberusUtils::parseRfcAddressList($email);
				
				if(empty($validated_emails) || !is_array($validated_emails))
					throw new Exception_DevblocksAjaxValidationError("The given email address is invalid.", 'email');
				
				$email = $validated_emails[0]->mailbox . '@' . $validated_emails[0]->host;
				
				if(false != DAO_Address::getByEmail($email))
					throw new Exception_DevblocksAjaxValidationError('A record already exists for the given email address.', 'email');
				
				if($contact_id && false == DAO_Contact::get($contact_id))
					throw new Exception_DevblocksAjaxValidationError('The given contact record is invalid.', 'contact_id');
				
				if($org_id && false == DAO_Contact::get($org_id))
					throw new Exception_DevblocksAjaxValidationError('The given organization record is invalid.', 'org_id');
				
				$fields[DAO_Address::EMAIL] = $email;

				if(false == ($id = DAO_Address::create($fields)))
					throw new Exception_DevblocksAjaxValidationError('An unexpected error occurred while trying to save the record.');
				
				// Watchers
				
				@$add_watcher_ids = DevblocksPlatform::sanitizeArray(DevblocksPlatform::importGPC($_REQUEST['add_watcher_ids'],'array',array()),'integer',array('unique','nonzero'));
				if(!empty($add_watcher_ids))
					CerberusContexts::addWatchers(CerberusContexts::CONTEXT_ADDRESS, $id, $add_watcher_ids);
				
				// Context Link (if given)
				@$link_context = DevblocksPlatform::importGPC($_REQUEST['link_context'],'string','');
				@$link_context_id = DevblocksPlatform::importGPC($_REQUEST['link_context_id'],'integer','');
				if(!empty($id) && !empty($link_context) && !empty($link_context_id)) {
					DAO_ContextLink::setLink(CerberusContexts::CONTEXT_ADDRESS, $id, $link_context, $link_context_id);
				}
				
				// View marquee
				if(!empty($id) && !empty($view_id)) {
					C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_ADDRESS, $id);
				}
				
			} else {
				if(false != ($address = DAO_Address::get($id))) {
					$email = $address->email;
					DAO_Address::update($id, $fields);
				}
			}
	
			if($id) {
				// Custom field saves
				@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', array());
				DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_ADDRESS, $id, $field_ids);

				// Index immediately
				$search = Extension_DevblocksSearchSchema::get(Search_Address::ID);
				$search->indexIds(array($id));
			}
			
			/*
			 * Notify anything that wants to know when Address Peek saves.
			 */
			$eventMgr = DevblocksPlatform::getEventService();
			$eventMgr->trigger(
				new Model_DevblocksEvent(
					'address.peek.saved',
					array(
						'address_id' => $id,
						'changed_fields' => $fields,
					)
				)
			);
			
			echo json_encode(array(
				'status' => true,
				'id' => $id,
				'label' => $email,
				'view_id' => $view_id,
			));
			
		} catch (Exception_DevblocksAjaxValidationError $e) {
				echo json_encode(array(
					'status' => false,
					'id' => $id,
					'error' => $e->getMessage(),
					'field' => $e->getFieldName(),
				));
			
		} catch (Exception $e) {
				echo json_encode(array(
					'status' => false,
					'id' => $id,
					'error' => 'An unexpected error occurred.',
				));
			
		}
	}
};