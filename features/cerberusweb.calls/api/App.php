<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2011, WebGroup Media LLC
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

class CallsPage extends CerberusPageExtension {
	function isVisible() {
		// The current session must be a logged-in worker to use this page.
		if(null == ($worker = CerberusApplication::getActiveWorker()))
			return false;
		return true;
	}
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();
		$translate = DevblocksPlatform::getTranslationService();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		
		array_shift($stack); // calls
		
		$module = array_shift($stack); // 123
		
		@$id = intval($module);
		
		if(is_numeric($id)) {
			if(null == ($call = DAO_CallEntry::get($id))) {
				break; // [TODO] Not found
			}
			$tpl->assign('call', $call);						

//			if(null == (@$tab_selected = $stack[0])) {
//				$tab_selected = $visit->get(self::SESSION_CALLS_TAB, '');
//			}
//			$tpl->assign('tab_selected', $tab_selected);

			// Custom fields
			
			$custom_fields = DAO_CustomField::getAll();
			$tpl->assign('custom_fields', $custom_fields);
			
			// Properties
			
			$properties = array();
			
			$properties['is_closed'] = array(
				'label' => ucfirst($translate->_('call_entry.model.is_closed')),
				'type' => Model_CustomField::TYPE_CHECKBOX,
				'value' => $call->is_closed,
			);
			
			$properties['is_outgoing'] = array(
				'label' => ucfirst($translate->_('call_entry.model.is_outgoing')),
				'type' => Model_CustomField::TYPE_CHECKBOX,
				'value' => $call->is_outgoing,
			);
			
			$properties['phone'] = array(
				'label' => ucfirst($translate->_('call_entry.model.phone')),
				'type' => Model_CustomField::TYPE_SINGLE_LINE,
				'value' => $call->phone,
			);
			
			$properties['created'] = array(
				'label' => ucfirst($translate->_('common.created')),
				'type' => Model_CustomField::TYPE_DATE,
				'value' => $call->created_date,
			);
			
			$properties['updated'] = array(
				'label' => ucfirst($translate->_('common.updated')),
				'type' => Model_CustomField::TYPE_DATE,
				'value' => $call->updated_date,
			);
			
			@$values = array_shift(DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_CALL, $call->id)) or array();
	
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
			
			// Macros
			
			$macros = DAO_TriggerEvent::getByOwner(CerberusContexts::CONTEXT_WORKER, $active_worker->id, 'event.macro.call');
			$tpl->assign('macros', $macros);
			
			// Template
			
			$tpl->display('devblocks:cerberusweb.calls::calls/display/index.tpl');
			
		} else {
			switch($module) {
				default:
				case 'placeholder':
//					$tpl->display('devblocks:cerberusweb.crm::crm/opps/display/index.tpl');
					break;
			}		
		}
	}
	
	/*
	 * Request Overload
	 */
	function handleRequest(DevblocksHttpRequest $request) {
		if(!$this->isVisible())
			return;
		
	    $path = $request->path;
		$controller = array_shift($path); // calls

	    @$action = DevblocksPlatform::strAlphaNum(array_shift($path), '\_') . 'Action';

	    switch($action) {
	        case NULL:
	            // [TODO] Index/page render
	            break;
	            
	        default:
			    // Default action, call arg as a method suffixed with Action
				if(method_exists($this,$action)) {
					call_user_func(array(&$this, $action));
				}
	            break;
	    }
	}
	
	function showEntryAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'], 'string', '');

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('view_id', $view_id);
		
		// Handle context links ([TODO] as an optional array)
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer','');
		$tpl->assign('context', $context);
		$tpl->assign('context_id', $context_id);
		
		if(!empty($id) && null != ($call = DAO_CallEntry::get($id))) {
			$tpl->assign('model', $call);
		}
		
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_CALL);
		$tpl->assign('custom_fields', $custom_fields);
		
		if(!empty($id)) {
			$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_CALL, $id);
			if(isset($custom_field_values[$id]))
				$tpl->assign('custom_field_values', $custom_field_values[$id]);
		}

		// Comments
		$comments = DAO_Comment::getByContext(CerberusContexts::CONTEXT_CALL, $id);
		$last_comment = array_shift($comments);
		unset($comments);
		$tpl->assign('last_comment', $last_comment);
		
		$tpl->display('devblocks:cerberusweb.calls::calls/ajax/call_entry_panel.tpl');
	}
	
	function saveEntryAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'], 'string', '');
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		@$is_outgoing = DevblocksPlatform::importGPC($_REQUEST['is_outgoing'], 'integer', 0);
		@$is_closed = DevblocksPlatform::importGPC($_REQUEST['is_closed'], 'integer', 0);
		@$subject = DevblocksPlatform::importGPC($_REQUEST['subject'], 'string', '');
		@$phone = DevblocksPlatform::importGPC($_REQUEST['phone'], 'string', '');
		@$comment = DevblocksPlatform::importGPC($_REQUEST['comment'], 'string', '');
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'], 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!empty($id) && !empty($do_delete)) { // Delete
			DAO_CallEntry::delete($id);
			
		} else {
			if(empty($id)) { // New
				$fields = array(
					DAO_CallEntry::CREATED_DATE => time(),
					DAO_CallEntry::UPDATED_DATE => time(),
					DAO_CallEntry::SUBJECT => $subject,
					DAO_CallEntry::PHONE => $phone,
					DAO_CallEntry::IS_OUTGOING => $is_outgoing,
					DAO_CallEntry::IS_CLOSED => $is_closed,
				);
				$id = DAO_CallEntry::create($fields);
				
				@$is_watcher = DevblocksPlatform::importGPC($_REQUEST['is_watcher'],'integer',0);
				if($is_watcher)
					CerberusContexts::addWatchers(CerberusContexts::CONTEXT_CALL, $id, $active_worker->id);
				
				// Context Link (if given)
				@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
				@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer','');
				if(!empty($id) && !empty($context) && !empty($context_id)) {
					DAO_ContextLink::setLink(CerberusContexts::CONTEXT_CALL, $id, $context, $context_id);
				}
				
			} else { // Edit
				$fields = array(
					DAO_CallEntry::UPDATED_DATE => time(),
					DAO_CallEntry::SUBJECT => $subject,
					DAO_CallEntry::PHONE => $phone,
					DAO_CallEntry::IS_OUTGOING => $is_outgoing,
					DAO_CallEntry::IS_CLOSED => $is_closed,
				);
				DAO_CallEntry::update($id, $fields);
				
			}

			// If we're adding a comment
			if(!empty($comment)) {
				@$also_notify_worker_ids = DevblocksPlatform::importGPC($_REQUEST['notify_worker_ids'],'array',array());
				
				$fields = array(
					DAO_Comment::CREATED => time(),
					DAO_Comment::CONTEXT => CerberusContexts::CONTEXT_CALL,
					DAO_Comment::CONTEXT_ID => $id,
					DAO_Comment::COMMENT => $comment,
					DAO_Comment::ADDRESS_ID => $active_worker->getAddress()->id,
				);
				$comment_id = DAO_Comment::create($fields, $also_notify_worker_ids);
			}
			
			// Custom fields
			@$field_ids = DevblocksPlatform::importGPC($_REQUEST['field_ids'], 'array', array());
			DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_CALL, $id, $field_ids);
		}
		
		// Reload view (if linked)
		if(!empty($view_id) && null != ($view = C4_AbstractViewLoader::getView($view_id))) {
			$view->render();
		}
	}
	
	function showCallsBulkPanelAction() {
		@$ids = DevblocksPlatform::importGPC($_REQUEST['ids']);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id']);

		$active_worker = CerberusApplication::getActiveWorker();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('view_id', $view_id);

	    if(!empty($ids)) {
	        $id_list = DevblocksPlatform::parseCsvString($ids);
	        $tpl->assign('ids', implode(',', $id_list));
	    }
		
		// Custom Fields
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_CALL);
		$tpl->assign('custom_fields', $custom_fields);
		
		// Macros
		$macros = DAO_TriggerEvent::getByOwner(CerberusContexts::CONTEXT_WORKER, $active_worker->id, 'event.macro.call');
		$tpl->assign('macros', $macros);
		
		$tpl->display('devblocks:cerberusweb.calls::calls/ajax/bulk.tpl');
	}
	
	function doCallsBulkUpdateAction() {
		// Filter: whole list or check
	    @$filter = DevblocksPlatform::importGPC($_REQUEST['filter'],'string','');
		$ids = array();
	    
	    // View
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		$view = C4_AbstractViewLoader::getView($view_id);
		
		// Call fields
		$is_closed = trim(DevblocksPlatform::importGPC($_POST['is_closed'],'string',''));

		// Scheduled behavior
		@$behavior_id = DevblocksPlatform::importGPC($_POST['behavior_id'],'string','');
		@$behavior_when = DevblocksPlatform::importGPC($_POST['behavior_when'],'string','');
		
		$do = array();
		
		// Do: Due
		if(0 != strlen($is_closed))
			$do['is_closed'] = !empty($is_closed) ? 1 : 0;
			
		// Do: Scheduled Behavior
		if(0 != strlen($behavior_id)) {
			$do['behavior'] = array(
				'id' => $behavior_id,
				'when' => $behavior_when,
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
			    @$ids_str = DevblocksPlatform::importGPC($_REQUEST['ids'],'string');
				$ids = DevblocksPlatform::parseCsvString($ids_str);
				break;
			case 'sample':
				@$sample_size = min(DevblocksPlatform::importGPC($_REQUEST['filter_sample_size'],'integer',0),9999);
				$filter = 'checks';
				$ids = $view->getDataSample($sample_size);
				break;
			default:
				break;
		}
		
		$view->doBulkUpdate($filter, $do, $ids);
		
		$view->render();
		return;
	}	
	
	function viewCallsExploreAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		
		$active_worker = CerberusApplication::getActiveWorker();
		$url_writer = DevblocksPlatform::getUrlService();
		
		// Generate hash
		$hash = md5($view_id.$active_worker->id.time()); 
		
		// Loop through view and get IDs
		$view = C4_AbstractViewLoader::getView($view_id);

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
					//'worker_id' => $active_worker->id,
					'total' => $total,
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=activity&tab=tasks', true),
//					'toolbar_extension_id' => 'cerberusweb.explorer.toolbar.',
				);
				$models[] = $model; 
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
			foreach($results as $id => $row) {
				if($id==$explore_from)
					$orig_pos = $pos;
				
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'id' => $id,
					'url' => $url_writer->writeNoProxy(sprintf("c=calls&id=%d", $row[SearchFields_CallEntry::ID]), true),
				);
				$models[] = $model; 
			}
			
			DAO_ExplorerSet::createFromModels($models);
			
			$view->renderPage++;
			
		} while(!empty($results));
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('explore',$hash,$orig_pos)));
	}	
};

if (class_exists('Extension_ActivityTab')):
class CallsActivityTab extends Extension_ActivityTab {
	const VIEW_ACTIVITY_CALLS = 'activity_calls';
	
	function showTab() {
		$tpl = DevblocksPlatform::getTemplateService();
		
		$defaults = new C4_AbstractViewModel();
		$defaults->class_name = 'View_CallEntry';
		$defaults->id = self::VIEW_ACTIVITY_CALLS;
		$defaults->renderSortBy = SearchFields_CallEntry::UPDATED_DATE;
		$defaults->renderSortAsc = 0;
		$defaults->paramsDefault = array(
			SearchFields_CallEntry::IS_CLOSED => new DevblocksSearchCriteria(SearchFields_CallEntry::IS_CLOSED,DevblocksSearchCriteria::OPER_EQ,0),
		);
		
		if(null == ($view = C4_AbstractViewLoader::getView(self::VIEW_ACTIVITY_CALLS, $defaults))) {
			$view->name = "Calls";
			C4_AbstractViewLoader::setView($view->id, $view);
		}

		$tpl->assign('view', $view);
		
		$tpl->display('devblocks:cerberusweb.calls::activity_tab/index.tpl');		
	}
};
endif;

if (class_exists('DevblocksEventListenerExtension')):
class CallsEventListener extends DevblocksEventListenerExtension {
	/**
	 * @param Model_DevblocksEvent $event
	 */
	function handleEvent(Model_DevblocksEvent $event) {
		switch($event->id) {
			case 'cron.maint':
				DAO_CallEntry::maint();
				break;
		}
	}
};
endif;