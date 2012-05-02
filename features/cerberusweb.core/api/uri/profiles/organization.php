<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2012, WebGroup Media LLC
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

class PageSection_ProfilesOrganization extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$translate = DevblocksPlatform::getTranslationService();
		$visit = CerberusApplication::getVisit();
		$response = DevblocksPlatform::getHttpResponse();
		$active_worker = CerberusApplication::getActiveWorker();

		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // org
		@$id = intval(array_shift($stack));
		
		@$selected_tab = array_shift($stack);
		
		// Remember the last tab/URL
		$point = 'cerberusweb.org.tab';
		$tpl->assign('point', $point);
		
		$visit = CerberusApplication::getVisit();
		if(null == $selected_tab) {
			$selected_tab = $visit->get($point, '');
		}
		$tpl->assign('selected_tab', $selected_tab);
		
		$tab_manifests = DevblocksPlatform::getExtensions('cerberusweb.org.tab', false);
		$tpl->assign('tab_manifests', $tab_manifests);
		
		$contact = DAO_ContactOrg::get($id);
		$tpl->assign('contact', $contact);
		
		// Custom fields
		
		$custom_fields = DAO_CustomField::getAll();
		$tpl->assign('custom_fields', $custom_fields);
		
		// Properties
		
		$properties = array();
		
		if(!empty($contact->street))
			$properties['street'] = array(
				'label' => ucfirst($translate->_('contact_org.street')),
				'type' => Model_CustomField::TYPE_SINGLE_LINE,
				'value' => $contact->street,
			);
		
		if(!empty($contact->city))
			$properties['city'] = array(
				'label' => ucfirst($translate->_('contact_org.city')),
				'type' => Model_CustomField::TYPE_SINGLE_LINE,
				'value' => $contact->city,
			);
		
		if(!empty($contact->province))
			$properties['province'] = array(
				'label' => ucfirst($translate->_('contact_org.province')),
				'type' => Model_CustomField::TYPE_SINGLE_LINE,
				'value' => $contact->province,
			);
		
		if(!empty($contact->postal))
			$properties['postal'] = array(
				'label' => ucfirst($translate->_('contact_org.postal')),
				'type' => Model_CustomField::TYPE_SINGLE_LINE,
				'value' => $contact->postal,
			);
		
		if(!empty($contact->country))
			$properties['country'] = array(
				'label' => ucfirst($translate->_('contact_org.country')),
				'type' => Model_CustomField::TYPE_SINGLE_LINE,
				'value' => $contact->country,
			);
		
		if(!empty($contact->phone))
			$properties['phone'] = array(
				'label' => ucfirst($translate->_('contact_org.phone')),
				'type' => Model_CustomField::TYPE_SINGLE_LINE,
				'value' => $contact->phone,
			);
		
		if(!empty($contact->website))
			$properties['website'] = array(
				'label' => ucfirst($translate->_('contact_org.website')),
				'type' => Model_CustomField::TYPE_URL,
				'value' => $contact->website,
			);
		
		$properties['created'] = array(
			'label' => ucfirst($translate->_('common.created')),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $contact->created,
		);
		
		@$values = array_shift(DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_ORG, $contact->id)) or array();
		
		foreach($custom_fields as $cf_id => $cfield) {
			if(!isset($values[$cf_id]))
				continue;
		
			$properties['cf_' . $cf_id] = array(
					'label' => $cfield->name,
					'type' => $cfield->type,
					'value' => $values[$cf_id],
			);
		}
		
		$tpl->assign('properties', $properties);
		
		// Tabs
		
		$people_count = DAO_Address::getCountByOrgId($contact->id);
		$tpl->assign('people_total', $people_count);
		
		// Macros
		$macros = DAO_TriggerEvent::getByOwner(CerberusContexts::CONTEXT_WORKER, $active_worker->id, 'event.macro.org');
		$tpl->assign('macros', $macros);
		
		$tpl->display('devblocks:cerberusweb.core::profiles/organization.tpl');		
	}
};