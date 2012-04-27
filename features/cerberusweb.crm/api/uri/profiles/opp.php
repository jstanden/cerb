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

class PageSection_ProfilesOpportunity extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();
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
		
		// Remember the last tab/URL
// 		if(null == (@$selected_tab = $stack[0])) {
// 			$selected_tab = $visit->get(Extension_CrmOpportunityTab::POINT, '');
// 		}
// 		$tpl->assign('selected_tab', $selected_tab);
		
		// Custom fields
		
		$custom_fields = DAO_CustomField::getAll();
		$tpl->assign('custom_fields', $custom_fields);
		
		// Quick search
		
		$ctx = Extension_DevblocksContext::get(CerberusContexts::CONTEXT_OPPORTUNITY);
		$view = $ctx->getChooserView();
		$tpl->assign('view', $view);
		
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
						'label' => ucfirst($translate->_('contact_org.name')),
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
		
		@$values = array_shift(DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_OPPORTUNITY, $opp->id)) or array();
		
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
		
		// Workers
		
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		// Macros
		$macros = DAO_TriggerEvent::getByOwner(CerberusContexts::CONTEXT_WORKER, $active_worker->id, 'event.macro.crm.opportunity');
		$tpl->assign('macros', $macros);
		
		$tpl->display('devblocks:cerberusweb.crm::crm/opps/display/index.tpl');
	}
};