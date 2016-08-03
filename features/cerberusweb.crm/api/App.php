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

// [TODO] Move to profile
class CrmPage extends CerberusPageExtension {
	function render() {
	}
	
	function saveOppPanelAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		
		@$opp_id = DevblocksPlatform::importGPC($_REQUEST['opp_id'],'integer',0);
		@$name = DevblocksPlatform::importGPC($_REQUEST['name'],'string','');
		@$status = DevblocksPlatform::importGPC($_REQUEST['status'],'integer',0);
		@$amount = DevblocksPlatform::importGPC($_REQUEST['amount'],'string','0.00');
		@$email_id = DevblocksPlatform::importGPC($_REQUEST['email_id'],'integer',0);
		@$comment = DevblocksPlatform::importGPC($_REQUEST['comment'],'string','');
		@$created_date_str = DevblocksPlatform::importGPC($_REQUEST['created_date'],'string','');
		@$closed_date_str = DevblocksPlatform::importGPC($_REQUEST['closed_date'],'string','');
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'],'integer',0);
		
		// State
		$is_closed = (0==$status) ? 0 : 1;
		$is_won = (1==$status) ? 1 : 0;
		
		// Strip currency formatting symbols
		$amount = floatval(str_replace(array(',','$','¢','£','€'),'',$amount));
		
		// Dates
		if(false === ($created_date = strtotime($created_date_str)))
			$created_date = time();
			
		if(false === ($closed_date = strtotime($closed_date_str)))
			$closed_date = ($is_closed) ? time() : 0;

		if(!$is_closed)
			$closed_date = 0;
			
		// Worker
		$active_worker = CerberusApplication::getActiveWorker();

		// Save
		if($do_delete) {
			if(null != ($opp = DAO_CrmOpportunity::get($opp_id)) && $active_worker->hasPriv('crm.opp.actions.create')) {
				DAO_CrmOpportunity::delete($opp_id);
				$opp_id = null;
			}
			
		} elseif(empty($opp_id)) {
			// Check privs
			if(!$active_worker->hasPriv('crm.opp.actions.create'))
				return;
			
			// One opportunity per provided e-mail address
			if(null == ($address = DAO_Address::get($email_id)))
				return;
				
			$fields = array(
				DAO_CrmOpportunity::NAME => $name,
				DAO_CrmOpportunity::AMOUNT => $amount,
				DAO_CrmOpportunity::PRIMARY_EMAIL_ID => $address->id,
				DAO_CrmOpportunity::CREATED_DATE => intval($created_date),
				DAO_CrmOpportunity::UPDATED_DATE => time(),
				DAO_CrmOpportunity::CLOSED_DATE => intval($closed_date),
				DAO_CrmOpportunity::IS_CLOSED => $is_closed,
				DAO_CrmOpportunity::IS_WON => $is_won,
			);
			$opp_id = DAO_CrmOpportunity::create($fields);
			
			// Watchers
			@$add_watcher_ids = DevblocksPlatform::sanitizeArray(DevblocksPlatform::importGPC($_REQUEST['add_watcher_ids'],'array',array()),'integer',array('unique','nonzero'));
			if(!empty($add_watcher_ids))
				CerberusContexts::addWatchers(CerberusContexts::CONTEXT_OPPORTUNITY, $opp_id, $add_watcher_ids);
			
			// Context Link (if given)
			@$link_context = DevblocksPlatform::importGPC($_REQUEST['link_context'],'string','');
			@$link_context_id = DevblocksPlatform::importGPC($_REQUEST['link_context_id'],'integer','');
			if(!empty($opp_id) && !empty($link_context) && !empty($link_context_id)) {
				DAO_ContextLink::setLink(CerberusContexts::CONTEXT_OPPORTUNITY, $opp_id, $link_context, $link_context_id);
			}
			
			// View marquee
			if(!empty($opp_id) && !empty($view_id)) {
				C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_OPPORTUNITY, $opp_id);
			}
			
		} else {
			if(empty($opp_id))
				return;
			
			// Check privs
			if(!$active_worker->hasPriv('crm.opp.actions.update_all'))
				return;
			
			if(null == ($address = DAO_Address::get($email_id)))
				return;

			$fields = array(
				DAO_CrmOpportunity::NAME => $name,
				DAO_CrmOpportunity::AMOUNT => $amount,
				DAO_CrmOpportunity::PRIMARY_EMAIL_ID => $address->id,
				DAO_CrmOpportunity::CREATED_DATE => intval($created_date),
				DAO_CrmOpportunity::UPDATED_DATE => time(),
				DAO_CrmOpportunity::CLOSED_DATE => intval($closed_date),
				DAO_CrmOpportunity::IS_CLOSED => $is_closed,
				DAO_CrmOpportunity::IS_WON => $is_won,
			);
			
			// Check privs
			if(null != ($opp = DAO_CrmOpportunity::get($opp_id)) && $active_worker->hasPriv('crm.opp.actions.create')) {
				DAO_CrmOpportunity::update($opp_id, $fields);
			}
		}
		
		if(!empty($opp_id)) {
			// Custom fields
			@$field_ids = DevblocksPlatform::importGPC($_REQUEST['field_ids'], 'array', array());
			DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_OPPORTUNITY, $opp_id, $field_ids);
			
			// If we're adding a comment
			if(!empty($comment)) {
				$also_notify_worker_ids = array_keys(CerberusApplication::getWorkersByAtMentionsText($comment));
				
				$fields = array(
					DAO_Comment::CREATED => time(),
					DAO_Comment::CONTEXT => CerberusContexts::CONTEXT_OPPORTUNITY,
					DAO_Comment::CONTEXT_ID => $opp_id,
					DAO_Comment::COMMENT => $comment,
					DAO_Comment::OWNER_CONTEXT => CerberusContexts::CONTEXT_WORKER,
					DAO_Comment::OWNER_CONTEXT_ID => $active_worker->id,
				);
				$comment_id = DAO_Comment::create($fields, $also_notify_worker_ids);
			}
		}
		
		exit;
	}
	
	function viewOppsExploreAction() {
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
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=search&type=opportunity', true),
//					'toolbar_extension_id' => 'cerberusweb.explorer.toolbar.',
				);
				$models[] = $model;
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
			foreach($results as $opp_id => $row) {
				if($opp_id==$explore_from)
					$orig_pos = $pos;
				
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'id' => $row[SearchFields_CrmOpportunity::ID],
					'url' => $url_writer->writeNoProxy(sprintf("c=profiles&tab=opportunity&id=%d-%s", $row[SearchFields_CrmOpportunity::ID], DevblocksPlatform::strToPermalink($row[SearchFields_CrmOpportunity::NAME])), true),
				);
				$models[] = $model;
			}
			
			DAO_ExplorerSet::createFromModels($models);
			
			$view->renderPage++;
			
		} while(!empty($results));
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('explore',$hash,$orig_pos)));
	}
};

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