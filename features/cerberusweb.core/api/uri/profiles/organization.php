<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
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
		$point = 'cerberusweb.profiles.org';
		$tpl->assign('point', $point);
		
		$visit = CerberusApplication::getVisit();
		if(null == $selected_tab) {
			$selected_tab = $visit->get($point, '');
		}
		$tpl->assign('selected_tab', $selected_tab);
		
		$contact = DAO_ContactOrg::get($id);
		$tpl->assign('contact', $contact);
		
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
		
		// Custom Fields

		@$values = array_shift(DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_ORG, $contact->id)) or array();
		$tpl->assign('custom_field_values', $values);
		
		$properties_cfields = Page_Profiles::getProfilePropertiesCustomFields(CerberusContexts::CONTEXT_ORG, $values);
		
		if(!empty($properties_cfields))
			$properties = array_merge($properties, $properties_cfields);
		
		// Custom Field Groups

		$properties_custom_field_groups = Page_Profiles::getProfilePropertiesCustomFieldSets(CerberusContexts::CONTEXT_ORG, $contact->id, $values);
		$tpl->assign('properties_custom_field_groups', $properties_custom_field_groups);
		
		// Properties
		
		$tpl->assign('properties', $properties);
		
		// Tabs
		$people_count = DAO_Address::getCountByOrgId($contact->id);
		$tpl->assign('people_total', $people_count);
		
		$tab_manifests = Extension_ContextProfileTab::getExtensions(false, CerberusContexts::CONTEXT_ORG);
		$tpl->assign('tab_manifests', $tab_manifests);
		
		// Macros
		$macros = DAO_TriggerEvent::getByOwner(CerberusContexts::CONTEXT_WORKER, $active_worker->id, 'event.macro.org');
		$tpl->assign('macros', $macros);

		// Template
		$tpl->display('devblocks:cerberusweb.core::profiles/organization.tpl');
	}
};