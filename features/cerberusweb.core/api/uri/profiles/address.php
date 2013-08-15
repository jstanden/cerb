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

class PageSection_ProfilesAddress extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$translate = DevblocksPlatform::getTranslationService();
		$visit = CerberusApplication::getVisit();
		$response = DevblocksPlatform::getHttpResponse();
		$active_worker = CerberusApplication::getActiveWorker();

		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // address
		@$id = intval(array_shift($stack));
		
		$address = DAO_Address::get($id);
		$tpl->assign('address', $address);
		
		// Remember the last tab/URL
		
		@$selected_tab = array_shift($stack);
		
		$point = 'cerberusweb.profiles.address';
		$tpl->assign('point', $point);
		
		if(null == $selected_tab) {
			$selected_tab = $visit->get($point, '');
		}
		$tpl->assign('selected_tab', $selected_tab);
		
		// Properties
		
		$properties = array();
		
		if(!empty($address->contact_org_id)) {
			if(null != ($org = DAO_ContactOrg::get($address->contact_org_id))) {
				$properties['org'] = array(
					'label' => ucfirst($translate->_('contact_org.name')),
					'type' => null,
					'org_id' => $address->contact_org_id,
					'org' => $org,
				);
			}
		}
		
		$properties['num_spam'] = array(
			'label' => ucfirst($translate->_('address.num_spam')),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $address->num_spam,
		);
		
		$properties['num_nonspam'] = array(
			'label' => ucfirst($translate->_('address.num_nonspam')),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $address->num_nonspam,
		);
		
		$properties['is_banned'] = array(
			'label' => ucfirst($translate->_('address.is_banned')),
			'type' => Model_CustomField::TYPE_CHECKBOX,
			'value' => $address->is_banned,
		);
		
		$properties['is_defunct'] = array(
			'label' => ucfirst($translate->_('address.is_defunct')),
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
		
		// Properties
		
		$tpl->assign('properties', $properties);
		
		// Macros
		$macros = DAO_TriggerEvent::getByVirtualAttendantOwners(
			array(
				array(CerberusContexts::CONTEXT_APPLICATION, 0),
				array(CerberusContexts::CONTEXT_WORKER, $active_worker->id),
			),
			'event.macro.address'
		);
		$tpl->assign('macros', $macros);
		
		// Tabs
		$tab_manifests = Extension_ContextProfileTab::getExtensions(false, CerberusContexts::CONTEXT_ADDRESS);
		$tpl->assign('tab_manifests', $tab_manifests);

		// Template
		$tpl->display('devblocks:cerberusweb.core::profiles/address.tpl');
	}
};