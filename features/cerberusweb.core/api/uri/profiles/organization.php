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

class PageSection_ProfilesOrganization extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$translate = DevblocksPlatform::getTranslationService();
		$response = DevblocksPlatform::getHttpResponse();
		$active_worker = CerberusApplication::getActiveWorker();

		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // org
		@$id = intval(array_shift($stack));
		
		// Remember the last tab/URL
		$point = 'cerberusweb.profiles.org';
		$tpl->assign('point', $point);
		
		if(false == ($org = DAO_ContactOrg::get($id)))
			return;
		
		$tpl->assign('contact', $org);
		
		// Properties
		
		$properties = array();
		
		if(!empty($org->street))
			$properties['street'] = array(
				'label' => mb_ucfirst($translate->_('contact_org.street')),
				'type' => Model_CustomField::TYPE_SINGLE_LINE,
				'value' => $org->street,
			);
		
		if(!empty($org->city))
			$properties['city'] = array(
				'label' => mb_ucfirst($translate->_('contact_org.city')),
				'type' => Model_CustomField::TYPE_SINGLE_LINE,
				'value' => $org->city,
			);
		
		if(!empty($org->province))
			$properties['province'] = array(
				'label' => mb_ucfirst($translate->_('contact_org.province')),
				'type' => Model_CustomField::TYPE_SINGLE_LINE,
				'value' => $org->province,
			);
		
		if(!empty($org->postal))
			$properties['postal'] = array(
				'label' => mb_ucfirst($translate->_('contact_org.postal')),
				'type' => Model_CustomField::TYPE_SINGLE_LINE,
				'value' => $org->postal,
			);
		
		if(!empty($org->country))
			$properties['country'] = array(
				'label' => mb_ucfirst($translate->_('contact_org.country')),
				'type' => Model_CustomField::TYPE_SINGLE_LINE,
				'value' => $org->country,
			);
		
		if(!empty($org->phone))
			$properties['phone'] = array(
				'label' => mb_ucfirst($translate->_('common.phone')),
				'type' => Model_CustomField::TYPE_SINGLE_LINE,
				'value' => $org->phone,
			);
		
		if(!empty($org->website))
			$properties['website'] = array(
				'label' => mb_ucfirst($translate->_('common.website')),
				'type' => Model_CustomField::TYPE_URL,
				'value' => $org->website,
			);
		
		$properties['created'] = array(
			'label' => mb_ucfirst($translate->_('common.created')),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $org->created,
		);
		
		// Custom Fields

		@$values = array_shift(DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_ORG, $org->id)) or array();
		$tpl->assign('custom_field_values', $values);
		
		$properties_cfields = Page_Profiles::getProfilePropertiesCustomFields(CerberusContexts::CONTEXT_ORG, $values);
		
		if(!empty($properties_cfields))
			$properties = array_merge($properties, $properties_cfields);
		
		// Custom Fieldsets

		$properties_custom_fieldsets = Page_Profiles::getProfilePropertiesCustomFieldsets(CerberusContexts::CONTEXT_ORG, $org->id, $values);
		$tpl->assign('properties_custom_fieldsets', $properties_custom_fieldsets);
		
		// Link counts
		
		if(isset($org->id)) {
			$properties_links[CerberusContexts::CONTEXT_ORG] = array(
				$org->id => 
					DAO_ContextLink::getContextLinkCounts(
						CerberusContexts::CONTEXT_ORG,
						$org->id,
						array(CerberusContexts::CONTEXT_WORKER, CerberusContexts::CONTEXT_CUSTOM_FIELDSET)
					),
			);
		}
		
		$tpl->assign('properties_links', $properties_links);
		
		// Properties
		
		$tpl->assign('properties', $properties);
		
		// Counts
		$activity_counts = array(
			//'comments' => DAO_Comment::count(CerberusContexts::CONTEXT_ORG, $org->id),
			'contacts' => DAO_Contact::countByOrgId($org->id),
			'emails' => DAO_Address::countByOrgId($org->id),
			//'tickets' => DAO_Ticket::countsByOrgId($org->id),
		);
		$tpl->assign('activity_counts', $activity_counts);

		// Tabs
		
		$tab_manifests = Extension_ContextProfileTab::getExtensions(false, CerberusContexts::CONTEXT_ORG);
		$tpl->assign('tab_manifests', $tab_manifests);
		
		// Macros
		
		$macros = DAO_TriggerEvent::getReadableByActor(
			$active_worker,
			'event.macro.org'
		);
		$tpl->assign('macros', $macros);

		// Template
		$tpl->display('devblocks:cerberusweb.core::profiles/organization.tpl');
	}
	
	function showBulkPopupAction() {
		@$ids = DevblocksPlatform::importGPC($_REQUEST['ids']);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id']);

		$active_worker = CerberusApplication::getActiveWorker();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('view_id', $view_id);

		if(!empty($ids)) {
			$org_ids = DevblocksPlatform::parseCsvString($ids);
			$tpl->assign('org_ids', implode(',', $org_ids));
		}
		
		// Custom Fields
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_ORG, false);
		$tpl->assign('custom_fields', $custom_fields);
		
		// Macros
		
		$macros = DAO_TriggerEvent::getReadableByActor(
			$active_worker,
			'event.macro.org'
		);
		$tpl->assign('macros', $macros);
		
		$tpl->display('devblocks:cerberusweb.core::contacts/orgs/bulk.tpl');
	}
	
	function startBulkUpdateJsonAction() {
		// Filter: whole list or check
		@$filter = DevblocksPlatform::importGPC($_REQUEST['filter'],'string','');
		$ids = array();
		
		// View
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		$view = C4_AbstractViewLoader::getView($view_id);
		$view->setAutoPersist(false);
		
		// Org fields
		@$country = trim(DevblocksPlatform::importGPC($_POST['country'],'string',''));

		// Scheduled behavior
		@$behavior_id = DevblocksPlatform::importGPC($_POST['behavior_id'],'string','');
		@$behavior_when = DevblocksPlatform::importGPC($_POST['behavior_when'],'string','');
		@$behavior_params = DevblocksPlatform::importGPC($_POST['behavior_params'],'array',array());
		
		$do = array();
		
		// Do: Country
		if(0 != strlen($country))
			$do['country'] = $country;
			
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
		
		switch($filter) {
			// Checked rows
			case 'checks':
				@$org_ids_str = DevblocksPlatform::importGPC($_REQUEST['org_ids'],'string');
				$ids = DevblocksPlatform::parseCsvString($org_ids_str);
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
			$view->addParam(new DevblocksSearchCriteria(SearchFields_ContactOrg::ID, 'in', $ids));
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