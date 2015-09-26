<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2015, Webgroup Media LLC
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

class PageSection_ProfilesOpportunity extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$request = DevblocksPlatform::getHttpRequest();
		$translate = DevblocksPlatform::getTranslationService();
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		$stack = $request->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // opportunity
		@$opp_id = intval(array_shift($stack));
		
		if(null == ($opp = DAO_CrmOpportunity::get($opp_id))) {
			return;
		}
		$tpl->assign('opp', $opp);	/* @var $opp Model_CrmOpportunity */
		
		$point = 'cerberusweb.profiles.opportunity';
		$tpl->assign('point', $point);
		
		// Properties
		
		$properties = array();
		
		$properties['status'] = array(
			'label' => ucfirst($translate->_('common.status')),
			'type' => null,
			'is_closed' => $opp->is_closed,
			'is_won' => $opp->is_won,
		);
		
		if(!empty($opp->primary_email_id)) {
			if(null != ($address = DAO_Address::get($opp->primary_email_id))) {
				$properties['lead'] = array(
					'label' => ucfirst($translate->_('common.email')),
					'type' => null,
					'address' => $address,
				);
			}
				
			if(!empty($address->contact_org_id) && null != ($org = DAO_ContactOrg::get($address->contact_org_id))) {
				$properties['org'] = array(
					'label' => ucfirst($translate->_('common.organization')),
					'type' => null,
					'org' => $org,
				);
			}
		}
		
		if(!empty($opp->is_closed))
			if(!empty($opp->closed_date))
			$properties['closed_date'] = array(
				'label' => ucfirst($translate->_('crm.opportunity.closed_date')),
				'type' => Model_CustomField::TYPE_DATE,
				'value' => $opp->closed_date,
			);
			
		if(!empty($opp->amount))
			$properties['amount'] = array(
				'label' => ucfirst($translate->_('crm.opportunity.amount')),
				'type' => Model_CustomField::TYPE_NUMBER,
				'value' => $opp->amount,
			);
			
		$properties['created_date'] = array(
			'label' => ucfirst($translate->_('common.created')),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $opp->created_date,
		);
		
		$properties['updated_date'] = array(
			'label' => ucfirst($translate->_('common.updated')),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $opp->updated_date,
		);
		
		// Custom Fields

		@$values = array_shift(DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_OPPORTUNITY, $opp->id)) or array();
		$tpl->assign('custom_field_values', $values);
		
		$properties_cfields = Page_Profiles::getProfilePropertiesCustomFields(CerberusContexts::CONTEXT_OPPORTUNITY, $values);
		
		if(!empty($properties_cfields))
			$properties = array_merge($properties, $properties_cfields);
		
		// Custom Fieldsets

		$properties_custom_fieldsets = Page_Profiles::getProfilePropertiesCustomFieldsets(CerberusContexts::CONTEXT_OPPORTUNITY, $opp->id, $values);
		$tpl->assign('properties_custom_fieldsets', $properties_custom_fieldsets);
		
		// Link counts
		
		$properties_links = array(
			CerberusContexts::CONTEXT_OPPORTUNITY => array(
				$opp->id => 
					DAO_ContextLink::getContextLinkCounts(
						CerberusContexts::CONTEXT_OPPORTUNITY,
						$opp->id,
						array(CerberusContexts::CONTEXT_WORKER, CerberusContexts::CONTEXT_CUSTOM_FIELDSET)
					),
			),
		);
		
		if(isset($opp->primary_email_id)) {
			$properties_links[CerberusContexts::CONTEXT_ADDRESS] = array(
				$opp->primary_email_id => 
					DAO_ContextLink::getContextLinkCounts(
						CerberusContexts::CONTEXT_ADDRESS,
						$opp->primary_email_id,
						array(CerberusContexts::CONTEXT_WORKER, CerberusContexts::CONTEXT_CUSTOM_FIELDSET)
					),
			);
		}
		
		$tpl->assign('properties_links', $properties_links);
		
		// Properties
		
		$tpl->assign('properties', $properties);

		// Workers
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		// Macros
		
		$macros = DAO_TriggerEvent::getReadableByActor(
			$active_worker,
			'event.macro.crm.opportunity'
		);
		$tpl->assign('macros', $macros);
		
		// Tabs
		$tab_manifests = Extension_ContextProfileTab::getExtensions(false, CerberusContexts::CONTEXT_OPPORTUNITY);
		$tpl->assign('tab_manifests', $tab_manifests);
		
		// Template
		$tpl->display('devblocks:cerberusweb.crm::crm/opps/profile.tpl');
	}
};