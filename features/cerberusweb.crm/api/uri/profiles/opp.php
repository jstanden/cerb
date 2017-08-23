<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2017, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerb.ai/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://cerb.ai	    http://webgroup.media
***********************************************************************/

class PageSection_ProfilesOpportunity extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::services()->template();
		$request = DevblocksPlatform::getHttpRequest();
		$translate = DevblocksPlatform::getTranslationService();
		
		$context = CerberusContexts::CONTEXT_OPPORTUNITY;
		$active_worker = CerberusApplication::getActiveWorker();
		
		$stack = $request->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // opportunity
		@$opp_id = intval(array_shift($stack));
		
		if(null == ($opp = DAO_CrmOpportunity::get($opp_id))) {
			return;
		}
		$tpl->assign('opp', $opp);	/* @var $opp Model_CrmOpportunity */
		
		// Dictionary
		$labels = array();
		$values = array();
		CerberusContexts::getContext($context, $opp, $labels, $values, '', true, false);
		$dict = DevblocksDictionaryDelegate::instance($values);
		$tpl->assign('dict', $dict);
		
		$point = 'cerberusweb.profiles.opportunity';
		$tpl->assign('point', $point);
		
		// Properties
		
		$properties = array();
		
		$properties['status'] = array(
			'label' => mb_ucfirst($translate->_('common.status')),
			'type' => null,
			'is_closed' => $opp->is_closed,
			'is_won' => $opp->is_won,
		);
		
		if(!empty($opp->primary_email_id)) {
			if(null != ($address = DAO_Address::get($opp->primary_email_id))) {
				$properties['lead'] = array(
					'label' => mb_ucfirst($translate->_('common.email')),
					'type' => Model_CustomField::TYPE_LINK,
					'value' => $address->id,
					'params' => array(
						'context' => CerberusContexts::CONTEXT_ADDRESS,
					),
				);
			}
				
			if(!empty($address->contact_org_id) && null != ($org = DAO_ContactOrg::get($address->contact_org_id))) {
				$properties['org'] = array(
					'label' => mb_ucfirst($translate->_('common.organization')),
					'type' => Model_CustomField::TYPE_LINK,
					'value' => $org->id,
					'params' => array(
						'context' => CerberusContexts::CONTEXT_ORG,
					),
				);
			}
		}
		
		if(!empty($opp->is_closed))
			if(!empty($opp->closed_date))
			$properties['closed_date'] = array(
				'label' => mb_ucfirst($translate->_('crm.opportunity.closed_date')),
				'type' => Model_CustomField::TYPE_DATE,
				'value' => $opp->closed_date,
			);
			
		if(!empty($opp->amount))
			$properties['amount'] = array(
				'label' => mb_ucfirst($translate->_('crm.opportunity.amount')),
				'type' => Model_CustomField::TYPE_NUMBER,
				'value' => number_format(floatval($opp->amount),2),
			);
			
		$properties['created_date'] = array(
			'label' => mb_ucfirst($translate->_('common.created')),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $opp->created_date,
		);
		
		$properties['updated_date'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.updated'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $opp->updated_date,
		);
		
		// Custom Fields

		@$values = array_shift(DAO_CustomFieldValue::getValuesByContextIds($context, $opp->id)) or array();
		$tpl->assign('custom_field_values', $values);
		
		$properties_cfields = Page_Profiles::getProfilePropertiesCustomFields($context, $values);
		
		if(!empty($properties_cfields))
			$properties = array_merge($properties, $properties_cfields);
		
		// Custom Fieldsets

		$properties_custom_fieldsets = Page_Profiles::getProfilePropertiesCustomFieldsets($context, $opp->id, $values);
		$tpl->assign('properties_custom_fieldsets', $properties_custom_fieldsets);
		
		// Link counts
		
		$properties_links = array(
			$context => array(
				$opp->id => 
					DAO_ContextLink::getContextLinkCounts(
						$context,
						$opp->id,
						array(CerberusContexts::CONTEXT_CUSTOM_FIELDSET)
					),
			),
		);
		
		if(!empty($opp->primary_email_id)) {
			$properties_links[CerberusContexts::CONTEXT_ADDRESS] = array(
				$opp->primary_email_id => 
					DAO_ContextLink::getContextLinkCounts(
						CerberusContexts::CONTEXT_ADDRESS,
						$opp->primary_email_id,
						array(CerberusContexts::CONTEXT_CUSTOM_FIELDSET)
					),
			);
		}
		
		$tpl->assign('properties_links', $properties_links);
		
		// Properties
		
		$tpl->assign('properties', $properties);

		// Workers
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		// Tabs
		$tab_manifests = Extension_ContextProfileTab::getExtensions(false, $context);
		$tpl->assign('tab_manifests', $tab_manifests);
		
		// Interactions
		$interactions = Event_GetInteractionsForWorker::getInteractionsByPointAndWorker('record:' . $context, $dict, $active_worker);
		$interactions_menu = Event_GetInteractionsForWorker::getInteractionMenu($interactions);
		$tpl->assign('interactions_menu', $interactions_menu);
		
		// Template
		$tpl->display('devblocks:cerberusweb.crm::crm/opps/profile.tpl');
	}
	
	function savePeekJsonAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['opp_id'],'integer',0);
		@$name = DevblocksPlatform::importGPC($_REQUEST['name'],'string','');
		@$status = DevblocksPlatform::importGPC($_REQUEST['status'],'integer',0);
		@$amount = DevblocksPlatform::importGPC($_REQUEST['amount'],'string','0.00');
		@$email_id = DevblocksPlatform::importGPC($_REQUEST['email_id'],'integer',0);
		@$comment = DevblocksPlatform::importGPC($_REQUEST['comment'],'string','');
		@$closed_date_str = DevblocksPlatform::importGPC($_REQUEST['closed_date'],'string','');
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'],'integer',0);
		
		// State
		$is_closed = (0==$status) ? 0 : 1;
		$is_won = (1==$status) ? 1 : 0;
		
		// Strip currency formatting symbols
		$amount = floatval(str_replace(array(',','$','¢','£','€'),'',$amount));
		
		if(false === ($closed_date = strtotime($closed_date_str)))
			$closed_date = ($is_closed) ? time() : 0;

		if(!$is_closed)
			$closed_date = 0;
			
		// Worker
		$active_worker = CerberusApplication::getActiveWorker();
		
		header('Content-Type: application/json; charset=utf-8');

		try {
			if(!empty($id) && !empty($do_delete)) { // delete
				// [TODO] Delete ACL
				if(!$active_worker->hasPriv('contexts.cerberusweb.contexts.opportunity.delete'))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				if(false == ($opp = DAO_CrmOpportunity::get($id)))
					throw new Exception_DevblocksAjaxValidationError("Failed to delete the record.");
				
				DAO_CrmOpportunity::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
				
			// Create/Edit
			} else {
				// Load the existing model
				if($id && false == ($opp = DAO_CrmOpportunity::get($id)))
					throw new Exception_DevblocksAjaxValidationError("There was an unexpected error when loading this record.");
				
				if(empty($name))
					throw new Exception_DevblocksAjaxValidationError("'Title' is required", 'name');
				
				if(false == ($address = DAO_Address::get($email_id)))
					throw new Exception_DevblocksAjaxValidationError("Invalid email address.");
			
				$fields = array(
					DAO_CrmOpportunity::NAME => $name,
					DAO_CrmOpportunity::AMOUNT => $amount,
					DAO_CrmOpportunity::PRIMARY_EMAIL_ID => $address->id,
					DAO_CrmOpportunity::UPDATED_DATE => time(),
					DAO_CrmOpportunity::CLOSED_DATE => intval($closed_date),
					DAO_CrmOpportunity::IS_CLOSED => $is_closed,
					DAO_CrmOpportunity::IS_WON => $is_won,
				);
				
				// Create
				if(empty($id)) {
					if(empty($id) && !$active_worker->hasPriv('contexts.cerberusweb.contexts.opportunity.create'))
						throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.create'));
					
					$fields[DAO_CrmOpportunity::CREATED_DATE] = time();
					
					$id = DAO_CrmOpportunity::create($fields);
					
					// View marquee
					if(!empty($id) && !empty($view_id)) {
						C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_OPPORTUNITY, $id);
					}
					
				// Update
				} else {
					if(empty($id) && !$active_worker->hasPriv('contexts.cerberusweb.contexts.opportunity.update'))
						throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.edit'));
					
					DAO_CrmOpportunity::update($id, $fields);
				}
				
				if($id) {
					// Custom fields
					@$field_ids = DevblocksPlatform::importGPC($_REQUEST['field_ids'], 'array', array());
					DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_OPPORTUNITY, $id, $field_ids);
					
					// If we're adding a comment
					if(!empty($comment)) {
						$also_notify_worker_ids = array_keys(CerberusApplication::getWorkersByAtMentionsText($comment));
						
						$fields = array(
							DAO_Comment::CREATED => time(),
							DAO_Comment::CONTEXT => CerberusContexts::CONTEXT_OPPORTUNITY,
							DAO_Comment::CONTEXT_ID => $id,
							DAO_Comment::COMMENT => $comment,
							DAO_Comment::OWNER_CONTEXT => CerberusContexts::CONTEXT_WORKER,
							DAO_Comment::OWNER_CONTEXT_ID => $active_worker->id,
						);
						$comment_id = DAO_Comment::create($fields, $also_notify_worker_ids);
					}
				}
			
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'label' => $name,
					'view_id' => $view_id,
				));
				return;
			}
				
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
		$url_writer = DevblocksPlatform::services()->url();
		
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
	
	function showBulkPopupAction() {
		@$ids = DevblocksPlatform::importGPC($_REQUEST['ids']);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id']);

		$active_worker = CerberusApplication::getActiveWorker();
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('view_id', $view_id);

		if(!empty($ids)) {
			$id_list = DevblocksPlatform::parseCsvString($ids);
			$tpl->assign('opp_ids', implode(',', $id_list));
		}
		
		// Workers
		$workers = DAO_Worker::getAllActive();
		$tpl->assign('workers', $workers);
		
		// Custom Fields
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_OPPORTUNITY, false);
		$tpl->assign('custom_fields', $custom_fields);
		
		// Groups
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		// HTML templates
		$html_templates = DAO_MailHtmlTemplate::getAll();
		$tpl->assign('html_templates', $html_templates);
		
		// Broadcast
		CerberusContexts::getContext(CerberusContexts::CONTEXT_OPPORTUNITY, null, $token_labels, $token_values);
		
		$placeholders = Extension_DevblocksContext::getPlaceholderTree($token_labels);
		$tpl->assign('placeholders', $placeholders);
		
		$tpl->display('devblocks:cerberusweb.crm::crm/opps/bulk.tpl');
	}
	
	function startBulkUpdateJsonAction() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		// Filter: whole list or check
		@$filter = DevblocksPlatform::importGPC($_REQUEST['filter'],'string','');
		$ids = array();
		
		// View
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		
		$view = C4_AbstractViewLoader::getView($view_id); /* @var $view View_CrmOpportunity */
		$view->setAutoPersist(false);
		
		// Opp fields
		@$status = trim(DevblocksPlatform::importGPC($_POST['status'],'string',''));
		@$closed_date = trim(DevblocksPlatform::importGPC($_POST['closed_date'],'string',''));
		@$worker_id = trim(DevblocksPlatform::importGPC($_POST['worker_id'],'string',''));

		// Scheduled behavior
		@$behavior_id = DevblocksPlatform::importGPC($_POST['behavior_id'],'string','');
		@$behavior_when = DevblocksPlatform::importGPC($_POST['behavior_when'],'string','');
		@$behavior_params = DevblocksPlatform::importGPC($_POST['behavior_params'],'array',array());
		
		$do = array();
		
		// Do: Status
		if(0 != strlen($status)) {
			if('deleted' == $status && !($active_worker && $active_worker->hasPriv('contexts.cerberusweb.contexts.opportunity.delete'))) {
				// Do nothing if we don't have delete permission
			} else {
				$do['status'] = $status;
			}
		}
		
		// Do: Closed Date
		if(0 != strlen($closed_date))
			@$do['closed_date'] = intval(strtotime($closed_date));
		
		// Do: Worker
		if(0 != strlen($worker_id))
			$do['worker_id'] = $worker_id;

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
			
		// Broadcast: Mass Reply
		if($active_worker->hasPriv('contexts.cerberusweb.contexts.opportunity.broadcast')) {
			@$do_broadcast = DevblocksPlatform::importGPC($_REQUEST['do_broadcast'],'string',null);
			@$broadcast_group_id = DevblocksPlatform::importGPC($_REQUEST['broadcast_group_id'],'integer',0);
			@$broadcast_subject = DevblocksPlatform::importGPC($_REQUEST['broadcast_subject'],'string',null);
			@$broadcast_message = DevblocksPlatform::importGPC($_REQUEST['broadcast_message'],'string',null);
			@$broadcast_format = DevblocksPlatform::importGPC($_REQUEST['broadcast_format'],'string',null);
			@$broadcast_html_template_id = DevblocksPlatform::importGPC($_REQUEST['broadcast_html_template_id'],'integer',0);
			@$broadcast_is_queued = DevblocksPlatform::importGPC($_REQUEST['broadcast_is_queued'],'integer',0);
			@$broadcast_status_id = DevblocksPlatform::importGPC($_REQUEST['broadcast_status_id'],'integer',0);
			@$broadcast_file_ids = DevblocksPlatform::sanitizeArray(DevblocksPlatform::importGPC($_REQUEST['broadcast_file_ids'],'array',array()), 'integer', array('nonzero','unique'));
			
			if(0 != strlen($do_broadcast) && !empty($broadcast_subject) && !empty($broadcast_message)) {
				$do['broadcast'] = array(
					'subject' => $broadcast_subject,
					'message' => $broadcast_message,
					'format' => $broadcast_format,
					'html_template_id' => $broadcast_html_template_id,
					'is_queued' => $broadcast_is_queued,
					'status_id' => $broadcast_status_id,
					'group_id' => $broadcast_group_id,
					'worker_id' => $active_worker->id,
					'file_ids' => $broadcast_file_ids,
				);
			}
		}
		
		switch($filter) {
			// Checked rows
			case 'checks':
				@$opp_ids_str = DevblocksPlatform::importGPC($_REQUEST['opp_ids'],'string');
				$ids = DevblocksPlatform::parseCsvString($opp_ids_str);
				break;
				
			case 'sample':
				@$sample_size = min(DevblocksPlatform::importGPC($_REQUEST['filter_sample_size'],'integer',0),9999);
				$filter = 'checks';
				$ids = $view->getDataSample($sample_size);
				break;
				
			default:
				break;
		}
		
		// Do: Custom fields
		$do = DAO_CustomFieldValue::handleBulkPost($do);
		
		// If we have specific IDs, add a filter for those too
		if(!empty($ids)) {
			$view->addParam(new DevblocksSearchCriteria(SearchFields_CrmOpportunity::ID, 'in', $ids));
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