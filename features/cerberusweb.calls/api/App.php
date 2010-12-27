<?php
class CallsPage extends CerberusPageExtension {
	function isVisible() {
		// check login
		$session = DevblocksPlatform::getSessionService();
		$visit = $session->getVisit();
		
		if(!empty($visit))
			return true;
			
		return false;
	}
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();

		$visit = CerberusApplication::getVisit();
		$translate = DevblocksPlatform::getTranslationService();
		
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		
		array_shift($stack); // calls
		
		$module = array_shift($stack); // 123
		
		if(is_numeric($module)) {
			@$id = intval($module);
			if(null == ($call = DAO_CallEntry::get($id))) {
				break; // [TODO] Not found
			}
			$tpl->assign('call', $call);						

//			if(null == (@$tab_selected = $stack[0])) {
//				$tab_selected = $visit->get(self::SESSION_CALLS_TAB, '');
//			}
//			$tpl->assign('tab_selected', $tab_selected);

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

	    @$action = DevblocksPlatform::strAlphaNumDash(array_shift($path)) . 'Action';

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
		
		// Workers
		$context_workers = CerberusContexts::getWorkers(CerberusContexts::CONTEXT_CALL, $id);
		$tpl->assign('context_workers', $context_workers);
		
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
				$fields = array(
					DAO_Comment::CREATED => time(),
					DAO_Comment::CONTEXT => CerberusContexts::CONTEXT_CALL,
					DAO_Comment::CONTEXT_ID => $id,
					DAO_Comment::COMMENT => $comment,
					DAO_Comment::ADDRESS_ID => $active_worker->getAddress()->id,
				);
				$comment_id = DAO_Comment::create($fields);
			}
			
			// Custom fields
			@$field_ids = DevblocksPlatform::importGPC($_REQUEST['field_ids'], 'array', array());
			DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_CALL, $id, $field_ids);
			
			// Owners
			@$worker_ids = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'array',array());
			CerberusContexts::setWorkers(CerberusContexts::CONTEXT_CALL, $id, $worker_ids);
		}
		
		// Reload view (if linked)
		if(!empty($view_id) && null != ($view = C4_AbstractViewLoader::getView($view_id))) {
			$view->render();
		}
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
		$view->renderLimit = 25;
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
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->write('c=activity&tab=tasks', true),
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
					'url' => $url_writer->write(sprintf("c=calls&id=%d", $row[SearchFields_CallEntry::ID]), true),
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
		// Remember the tab
		$visit = CerberusApplication::getVisit();
		$visit->set(CerberusVisit::KEY_ACTIVITY_TAB, 'calls');
		
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
}
endif;

// Workspace Sources

class ChWorkspaceSource_Call extends Extension_WorkspaceSource {
	const ID = 'calls.workspace.source.call';
};

