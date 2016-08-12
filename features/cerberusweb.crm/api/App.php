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

class CrmEventListener extends DevblocksEventListenerExtension {
	/**
	 * @param Model_DevblocksEvent $event
	 */
	function handleEvent(Model_DevblocksEvent $event) {
		switch($event->id) {
			case 'cron.maint':
				DAO_CrmOpportunity::maint();
				break;
		}
	}
};

if (class_exists('Extension_ContextProfileTab')):
class CrmOrgOppTab extends Extension_ContextProfileTab {
	function showTab($context, $context_id) {
		if(0 != strcasecmp($context, CerberusContexts::CONTEXT_ORG))
			return;

		$org_id = $context_id;
		
		$tpl = DevblocksPlatform::getTemplateService();

		if(null == ($org = DAO_ContactOrg::get($org_id)))
			return;
			
		$tpl->assign('org_id', $org_id);
		
		$defaults = C4_AbstractViewModel::loadFromClass('View_CrmOpportunity');
		$defaults->id = 'org_opps';
		
		$view = C4_AbstractViewLoader::getView('org_opps', $defaults);
		
		$view->name = "Org: " . $org->name;
		$view->addParamsRequired(array(
			SearchFields_CrmOpportunity::ORG_ID => new DevblocksSearchCriteria(SearchFields_CrmOpportunity::ORG_ID,'=',$org_id)
		), true);

		$tpl->assign('view', $view);
		
		$tpl->display('devblocks:cerberusweb.core::internal/views/search_and_view.tpl');
	}
};
endif;

if (class_exists('Extension_ContextProfileTab')):
class CrmTicketOppTab extends Extension_ContextProfileTab {
	function showTab($context, $context_id) {
		if($context != CerberusContexts::CONTEXT_TICKET)
			return;

		$ticket_id = $context_id;
		
		$tpl = DevblocksPlatform::getTemplateService();

		$ticket = DAO_Ticket::get($ticket_id);
		$tpl->assign('ticket_id', $ticket_id);
		
		$requesters = DAO_Ticket::getRequestersByTicket($ticket_id);
		
		if(null == ($view = C4_AbstractViewLoader::getView('ticket_opps'))) {
			$view = new View_CrmOpportunity();
			$view->id = 'ticket_opps';
		}

		$view->name = sprintf("Opportunities: %s recipient(s)", count($requesters));
		$view->addParamsRequired(array(
			SearchFields_CrmOpportunity::PRIMARY_EMAIL_ID => new DevblocksSearchCriteria(SearchFields_CrmOpportunity::PRIMARY_EMAIL_ID,'in',array_keys($requesters)),
		), true);
		
		$tpl->assign('view', $view);
		
		$tpl->display('devblocks:cerberusweb.core::internal/views/search_and_view.tpl');
	}
	
	function saveTab() {
	}
};
endif;

if(class_exists('Extension_DevblocksEventAction')):
class VaAction_CreateOpportunity extends Extension_DevblocksEventAction {
	function render(Extension_DevblocksEvent $event, Model_TriggerEvent $trigger, $params=array(), $seq=null) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('params', $params);
		
		if(!is_null($seq))
			$tpl->assign('namePrefix', 'action'.$seq);
		
		$event = $trigger->getEvent();
		$values_to_contexts = $event->getValuesContexts($trigger);
		$tpl->assign('values_to_contexts', $values_to_contexts);
		
		// Custom fields
		DevblocksEventHelper::renderActionCreateRecordSetCustomFields(CerberusContexts::CONTEXT_OPPORTUNITY, $tpl);
		
		// Template
		$tpl->display('devblocks:cerberusweb.crm::events/action_create_opportunity.tpl');
	}
	
	function simulate($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();

		$out = null;
		
		@$name = $tpl_builder->build($params['name'], $dict);
		@$email = $tpl_builder->build($params['email'], $dict);
		@$status = $params['status'];
		@$amount = floatval($tpl_builder->build($params['amount'], $dict));
		
		@$notify_worker_ids = DevblocksPlatform::importVar($params['notify_worker_id'],'array',array());
		$notify_worker_ids = DevblocksEventHelper::mergeWorkerVars($notify_worker_ids, $dict);
		
		if(empty($name))
			return "[ERROR] Name is required.";
		
		if(empty($email))
			return "[ERROR] Email is required.";
		
		if(false == ($email_model = DAO_Address::lookupAddress($email, true)))
			return "[ERROR] The email address is invalid.";
		
		if(!in_array($status, array('open', 'closed_won', 'closed_lost')))
			return "[ERROR] Status has an invalid value.";
		
		if(!is_float($amount))
			return "[ERROR] Amount should be a numeric value.";
		
		$comment = $tpl_builder->build($params['comment'], $dict);
		
		$out = sprintf(">>> Creating opportunity: %s\nEmail: %s\nStatus: %s\nAmount: %0.2f\n",
			$name,
			$email,
			$status,
			$amount
		);
		
		// Custom fields
		$out .= DevblocksEventHelper::simulateActionCreateRecordSetCustomFields($params, $dict);
		
		$out .= "\n";
		
		// Comment content
		if(!empty($comment)) {
			$out .= sprintf(">>> Writing comment on opportunity\n\n".
				"%s\n\n",
				$comment
			);
			
			if(!empty($notify_worker_ids) && is_array($notify_worker_ids)) {
				$out .= ">>> Notifying\n";
				foreach($notify_worker_ids as $worker_id) {
					if(null != ($worker = DAO_Worker::get($worker_id))) {
						$out .= ' * ' . $worker->getName() . "\n";
					}
				}
				$out .= "\n";
			}
		}
		
		// Links
		$out .= DevblocksEventHelper::simulateActionCreateRecordSetLinks($params, $dict);
		
		// Set object variable
		$out .= DevblocksEventHelper::simulateActionCreateRecordSetVariable($params, $dict);
		
		// Run in simulator
		@$run_in_simulator = !empty($params['run_in_simulator']);
		if($run_in_simulator) {
			$this->run($token, $trigger, $params, $dict);
		}
		
		return $out;
	}
	
	function run($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		
		@$name = $tpl_builder->build($params['name'], $dict);
		@$email = $tpl_builder->build($params['email'], $dict);
		@$status = $params['status'];
		@$amount = floatval($tpl_builder->build($params['amount'], $dict));

		@$notify_worker_ids = DevblocksPlatform::importVar($params['notify_worker_id'],'array',array());
		$notify_worker_ids = DevblocksEventHelper::mergeWorkerVars($notify_worker_ids, $dict);
		
		$comment = $tpl_builder->build($params['comment'], $dict);
		
		if(empty($name))
			return;
		
		if(empty($email))
			return;
		
		if(false == ($email_model = DAO_Address::lookupAddress($email, true)))
			return;
		
		if(!in_array($status, array('open', 'closed_won', 'closed_lost')))
			return ;
		
		if(!is_float($amount))
			return;
		
		$fields = array(
			DAO_CrmOpportunity::NAME => $name,
			DAO_CrmOpportunity::PRIMARY_EMAIL_ID => $email_model->id,
			DAO_CrmOpportunity::AMOUNT => sprintf("%0.2f", $amount),
		);
		
		switch($status) {
			case 'open':
				$fields[DAO_CrmOpportunity::IS_CLOSED] = 0;
				$fields[DAO_CrmOpportunity::IS_WON] = 0;
				break;
			case 'closed_won':
				$fields[DAO_CrmOpportunity::IS_CLOSED] = 1;
				$fields[DAO_CrmOpportunity::IS_WON] = 1;
				break;
			case 'closed_lost':
				$fields[DAO_CrmOpportunity::IS_CLOSED] = 1;
				$fields[DAO_CrmOpportunity::IS_WON] = 0;
				break;
		}
			
		if(false == ($opp_id = DAO_CrmOpportunity::create($fields)))
			return;
		
		// Custom fields
		DevblocksEventHelper::runActionCreateRecordSetCustomFields(CerberusContexts::CONTEXT_OPPORTUNITY, $opp_id, $params, $dict);
		
		// Comment content
		if(!empty($comment)) {
			$fields = array(
				DAO_Comment::OWNER_CONTEXT => CerberusContexts::CONTEXT_VIRTUAL_ATTENDANT,
				DAO_Comment::OWNER_CONTEXT_ID => $trigger->virtual_attendant_id,
				DAO_Comment::COMMENT => $comment,
				DAO_Comment::CONTEXT => CerberusContexts::CONTEXT_OPPORTUNITY,
				DAO_Comment::CONTEXT_ID => $opp_id,
				DAO_Comment::CREATED => time(),
			);
			DAO_Comment::create($fields, $notify_worker_ids);
		}

		// Connection
		DevblocksEventHelper::runActionCreateRecordSetLinks(CerberusContexts::CONTEXT_OPPORTUNITY, $opp_id, $params, $dict);
		
		// Set object variable
		DevblocksEventHelper::runActionCreateRecordSetVariable(CerberusContexts::CONTEXT_OPPORTUNITY, $opp_id, $params, $dict);
	}
	
};
endif;