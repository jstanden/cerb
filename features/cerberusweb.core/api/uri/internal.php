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

class ChInternalController extends DevblocksControllerExtension {
	const ID = 'core.controller.internal';

	/*
	 * Request Overload
	 */
	function handleRequest(DevblocksHttpRequest $request) {
		$worker = CerberusApplication::getActiveWorker();
		if(empty($worker)) return;

		$stack = $request->path;
		array_shift($stack); // internal

	    @$action = array_shift($stack) . 'Action';

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

	// Post
	function doStopTourAction() {
		$worker = CerberusApplication::getActiveWorker();
		DAO_WorkerPref::set($worker->id, 'assist_mode', 0);

		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('preferences')));
	}

	// Imposter mode
	
	function suAction() {
		@$worker_id = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'string');
		
		$active_worker = CerberusApplication::getActiveWorker();
		$visit = CerberusApplication::getVisit();
		
		if(!$active_worker->is_superuser)
			return;
		
		if($active_worker->id == $worker_id)
			return;
		
		if(null != ($switch_worker = DAO_Worker::get($worker_id))) {
			// Imposter
			if($visit->isImposter() && $imposter = $visit->getImposter()) {
				if($worker_id == $imposter->id) {
					$visit->setImposter(null);
				}
			} else if(!$visit->isImposter()) {
				$visit->setImposter($active_worker);
			}
			
			$visit->setWorker($switch_worker);
		}
	}
	
	function suRevertAction() {
		$active_worker = CerberusApplication::getActiveWorker();
		$visit = CerberusApplication::getVisit();
		
		if($visit->isImposter()) {
			if(null != ($imposter = $visit->getImposter())) {
				$visit->setWorker($imposter);
				$visit->setImposter(null);
			}
		}
		
	}
	
	// Contexts

	function showTabContextLinksAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer');
		@$point = DevblocksPlatform::importGPC($_REQUEST['point'],'string');

		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();
		$active_worker = CerberusApplication::getActiveWorker();

		$tpl->assign('context', $context);
		$tpl->assign('context_id', $context_id);

		if(!empty($point))
			$visit->set($point, 'links');

		// Context Links

		$contexts = DAO_ContextLink::getDistinctContexts($context, $context_id);
		$all_contexts = Extension_DevblocksContext::getAll(false);
		
		// Only valid extensions
		$contexts = array_intersect($contexts, array_keys($all_contexts));
		
		$contexts = array_diff($contexts, array( // Hide workers
			CerberusContexts::CONTEXT_WORKER,
		));
		
		$tpl->assign('contexts', $contexts);
		
		$tpl->display('devblocks:cerberusweb.core::context_links/tab.tpl');
		
		$tpl->clearAssign('context');
		$tpl->clearAssign('context_id');
		$tpl->clearAssign('contexts');
		
		unset($contexts);
	}

	function initConnectionsViewAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer',0);
		@$to_context = DevblocksPlatform::importGPC($_REQUEST['to_context'],'string');
		
		if(empty($context) || empty($context_id) || empty($to_context))
			return;
			
		if(null == ($ext_context = DevblocksPlatform::getExtension($to_context, true)))
			return;

		if(!$ext_context instanceof Extension_DevblocksContext)
			return;

		$tpl = DevblocksPlatform::getTemplateService();
		$active_worker = CerberusApplication::getActiveWorker();
			
		if(null != ($view = $ext_context->getView($context, $context_id))) {
			$tpl->assign('view', $view);
			$tpl->display('devblocks:cerberusweb.core::internal/views/search_and_view.tpl');
			$tpl->clearAssign('view');
		}

		unset($view);
		unset($ext_content);
	}
	
	function chooserOpenAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string');
		@$layer = DevblocksPlatform::importGPC($_REQUEST['layer'],'string');

		if(null != ($context_extension = DevblocksPlatform::getExtension($context, true))) {
			$tpl = DevblocksPlatform::getTemplateService();
			$tpl->assign('context', $context_extension);
			$tpl->assign('layer', $layer);
			$tpl->assign('view', $context_extension->getChooserView());
			$tpl->display('devblocks:cerberusweb.core::context_links/choosers/__generic.tpl');
		}
	}
	
	function chooserOpenSnippetAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string');
		@$layer = DevblocksPlatform::importGPC($_REQUEST['layer'],'string');

		if(null != ($context_extension = DevblocksPlatform::getExtension($context, true))) {
			$tpl = DevblocksPlatform::getTemplateService();
			$tpl->assign('context', $context_extension);
			$tpl->assign('layer', $layer);
			$tpl->assign('view', $context_extension->getChooserView());
			$tpl->display('devblocks:cerberusweb.core::context_links/choosers/__snippet.tpl');
		}
	}

	function chooserOpenFileAction() {
		@$layer = DevblocksPlatform::importGPC($_REQUEST['layer'],'string');

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('layer', $layer);
		$tpl->display('devblocks:cerberusweb.core::context_links/choosers/__file.tpl');
	}

	function chooserOpenFileUploadAction() {
		@$file = $_FILES['file_data'];

		// [TODO] Return false in JSON if file is empty, etc.

		// Create a record w/ timestamp + ID
		$fields = array(
			DAO_Attachment::DISPLAY_NAME => $file['name'],
			DAO_Attachment::MIME_TYPE => $file['type'],
		);
		$file_id = DAO_Attachment::create($fields);

		// Save the file
		if(null !== ($fp = fopen($file['tmp_name'], 'rb'))) {
            Storage_Attachments::put($file_id, $fp);
			fclose($fp);
            unlink($file['tmp_name']);
		}

		// [TODO] Unlinked records should expire

		echo json_encode(array(
			'name' => $file['name'],
			'size' => $file['size'],
			'id' => $file_id,
		));
	}

	function contextAddLinksAction() {
		@$from_context = DevblocksPlatform::importGPC($_REQUEST['from_context'],'string','');
		@$from_context_id = DevblocksPlatform::importGPC($_REQUEST['from_context_id'],'integer',0);
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$context_ids = DevblocksPlatform::importGPC($_REQUEST['context_id'],'array',array());

		if(is_array($context_ids))
		foreach($context_ids as $context_id)
			DAO_ContextLink::setLink($context, $context_id, $from_context, $from_context_id);
	}

	function contextDeleteLinksAction() {
		@$from_context = DevblocksPlatform::importGPC($_REQUEST['from_context'],'string','');
		@$from_context_id = DevblocksPlatform::importGPC($_REQUEST['from_context_id'],'integer',0);
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$context_ids = DevblocksPlatform::importGPC($_REQUEST['context_id'],'array',array());

		if(is_array($context_ids))
		foreach($context_ids as $context_id)
			DAO_ContextLink::deleteLink($context, $context_id, $from_context, $from_context_id);
	}

	// Context Activity Log
	
	function showTabActivityLogAction() {
		@$scope = DevblocksPlatform::importGPC($_REQUEST['scope'],'string','target');
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer');
		@$point = DevblocksPlatform::importGPC($_REQUEST['point'],'string');
		
		$visit = CerberusApplication::getVisit();
		$tpl = DevblocksPlatform::getTemplateService();

		if(empty($context) || empty($context_id))
			return;
		
		// Remember tab
		if(!empty($point))
			$visit->set($point, 'activity');
		
		if(0 == strcasecmp('target',$scope)) {
			$params = array(
				SearchFields_ContextActivityLog::TARGET_CONTEXT => new DevblocksSearchCriteria(SearchFields_ContextActivityLog::TARGET_CONTEXT,'=',$context),
				SearchFields_ContextActivityLog::TARGET_CONTEXT_ID => new DevblocksSearchCriteria(SearchFields_ContextActivityLog::TARGET_CONTEXT_ID,'=',$context_id),
			);
		} else { // actor
			$params = array(
				SearchFields_ContextActivityLog::ACTOR_CONTEXT => new DevblocksSearchCriteria(SearchFields_ContextActivityLog::ACTOR_CONTEXT,'=',$context),
				SearchFields_ContextActivityLog::ACTOR_CONTEXT_ID => new DevblocksSearchCriteria(SearchFields_ContextActivityLog::ACTOR_CONTEXT_ID,'=',$context_id),
			);
		}

		$defaults = new C4_AbstractViewModel();
		$defaults->id = 'context_activity_log_'.str_replace('.','_',$context.'_'.$context_id);
		$defaults->is_ephemeral = true;
		$defaults->class_name = 'View_ContextActivityLog';
		$defaults->view_columns = array(
			SearchFields_ContextActivityLog::CREATED
		);
		$defaults->renderLimit = 10;
		$defaults->renderSortBy = SearchFields_ContextActivityLog::CREATED;
		$defaults->renderSortAsc = false;
		
		if(null != ($view = C4_AbstractViewLoader::getView($defaults->id, $defaults))) {
			$view->addColumnsHidden(array(
				SearchFields_ContextActivityLog::ACTOR_CONTEXT_ID,
				SearchFields_ContextActivityLog::TARGET_CONTEXT_ID,
				SearchFields_ContextActivityLog::ID,
			), true);
			
			$view->addParamsHidden(array(
				SearchFields_ContextActivityLog::ACTOR_CONTEXT_ID,
				SearchFields_ContextActivityLog::TARGET_CONTEXT_ID,
				SearchFields_ContextActivityLog::ID,
			), true);
			
			$view->addParamsRequired($params, true);
			
			C4_AbstractViewLoader::setView($view->id, $view);
			
			$tpl->assign('view', $view);
		}
		
		$tpl->display('devblocks:cerberusweb.core::internal/activity_log/tab.tpl');
	}
	
	// Autocomplete

	function autocompleteAction() {
		@$callback = DevblocksPlatform::importGPC($_REQUEST['callback'],'string','');
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$term = DevblocksPlatform::importGPC($_REQUEST['term'],'string','');

		$active_worker = CerberusApplication::getActiveWorker();
		
		$list = array();

		// [TODO] This should be handled by the context extension
		switch($context) {
			case CerberusContexts::CONTEXT_ADDRESS:
				list($results, $null) = DAO_Address::search(
					array(),
					array(
						array(
							DevblocksSearchCriteria::GROUP_OR,
							new DevblocksSearchCriteria(SearchFields_Address::LAST_NAME,DevblocksSearchCriteria::OPER_LIKE,$term.'%'),
							new DevblocksSearchCriteria(SearchFields_Address::FIRST_NAME,DevblocksSearchCriteria::OPER_LIKE,$term.'%'),
							new DevblocksSearchCriteria(SearchFields_Address::EMAIL,DevblocksSearchCriteria::OPER_LIKE,$term.'%'),
						),
					),
					25,
					0,
					SearchFields_Address::NUM_NONSPAM,
					false,
					false
				);

				foreach($results AS $row){
					$entry = new stdClass();
					$entry->label = trim($row[SearchFields_Address::FIRST_NAME] . ' ' . $row[SearchFields_Address::LAST_NAME] . ' <' .$row[SearchFields_Address::EMAIL] . '>');
					$entry->value = $row[SearchFields_Address::ID];
					$list[] = $entry;
				}
				break;

			case CerberusContexts::CONTEXT_GROUP:
				list($results, $null) = DAO_Group::search(
					array(),
					array(
						new DevblocksSearchCriteria(SearchFields_Group::NAME,DevblocksSearchCriteria::OPER_LIKE,$term.'%'),
					),
					25,
					0,
					DAO_Group::TEAM_NAME,
					true,
					false
				);

				foreach($results AS $row){
					$entry = new stdClass();
					$entry->label = $row[SearchFields_Group::NAME];
					$entry->value = $row[SearchFields_Group::ID];
					$list[] = $entry;
				}
				break;

			case CerberusContexts::CONTEXT_ORG:
				list($results, $null) = DAO_ContactOrg::search(
					array(),
					array(
						new DevblocksSearchCriteria(SearchFields_ContactOrg::NAME,DevblocksSearchCriteria::OPER_LIKE,$term.'%'),
					),
					25,
					0,
					SearchFields_ContactOrg::NAME,
					true,
					false
				);

				foreach($results AS $row){
					$entry = new stdClass();
					$entry->label = $row[SearchFields_ContactOrg::NAME];
					$entry->value = $row[SearchFields_ContactOrg::ID];
					$list[] = $entry;
				}
				break;

			case CerberusContexts::CONTEXT_SNIPPET:
				$contexts = DevblocksPlatform::getExtensions('devblocks.context', false);
				
				// Restrict owners
				$param_ownership = array(
					DevblocksSearchCriteria::GROUP_OR,
					array(
						DevblocksSearchCriteria::GROUP_AND,
						SearchFields_Snippet::OWNER_CONTEXT => new DevblocksSearchCriteria(SearchFields_Snippet::OWNER_CONTEXT,DevblocksSearchCriteria::OPER_EQ,CerberusContexts::CONTEXT_WORKER),
						SearchFields_Snippet::OWNER_CONTEXT_ID => new DevblocksSearchCriteria(SearchFields_Snippet::OWNER_CONTEXT_ID,DevblocksSearchCriteria::OPER_EQ,$active_worker->id),
					),
					array(
						DevblocksSearchCriteria::GROUP_AND,
						SearchFields_Snippet::OWNER_CONTEXT => new DevblocksSearchCriteria(SearchFields_Snippet::OWNER_CONTEXT,DevblocksSearchCriteria::OPER_EQ,CerberusContexts::CONTEXT_GROUP),
						SearchFields_Snippet::OWNER_CONTEXT_ID => new DevblocksSearchCriteria(SearchFields_Snippet::OWNER_CONTEXT_ID,DevblocksSearchCriteria::OPER_IN,array_keys($active_worker->getMemberships())),
					),
				);
				
				$params = array(
					new DevblocksSearchCriteria(SearchFields_Snippet::TITLE,DevblocksSearchCriteria::OPER_LIKE,'%'.$term.'%'),
					$param_ownership,
				);
				
				@$context_list = DevblocksPlatform::importGPC($_REQUEST['contexts'],'array',array());
				if(is_array($context_list))
				foreach($context_list as $k => $v) {
					if(!isset($contexts[$v]))
						unset($context_list[$k]);
				}

				$context_list[] = ''; // plaintext
				
				// Filter contexts
				$params[SearchFields_Snippet::CONTEXT] = 
					new DevblocksSearchCriteria(SearchFields_Snippet::CONTEXT,DevblocksSearchCriteria::OPER_IN,$context_list)
					;
				
				list($results, $null) = DAO_Snippet::search(
					array(
						SearchFields_Snippet::TITLE,
						SearchFields_Snippet::USAGE_HITS,
					),
					$params,
					25,
					0,
					SearchFields_Snippet::USAGE_HITS,
					false,
					false
				);

				foreach($results AS $row){
					$entry = new stdClass();
					$entry->label = sprintf("%s -- used %s",
						$row[SearchFields_Snippet::TITLE],
						((1 != $row[SearchFields_Snippet::USAGE_HITS]) ? (intval($row[SearchFields_Snippet::USAGE_HITS]) . ' times') : 'once')
					);
					$entry->value = $row[SearchFields_Snippet::ID];
					$entry->context = $row[SearchFields_Snippet::CONTEXT];
					$list[] = $entry;
				}
				break;

			case CerberusContexts::CONTEXT_WORKER:
				$results = DAO_Worker::autocomplete($term);

				if(is_array($results))
				foreach($results as $worker_id => $worker){
					$entry = new stdClass();
					$entry->label = $worker->getName();
					$entry->value = sprintf("%d", $worker_id);
					$list[] = $entry;
				}
				break;
		}

		echo sprintf("%s%s%s",
			!empty($callback) ? ($callback.'(') : '',
			json_encode($list),
			!empty($callback) ? (')') : ''
		);
		exit;
	}

	function toggleContextWatcherAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer',0);
		@$follow = DevblocksPlatform::importGPC($_REQUEST['follow'],'integer',0);
		@$full = DevblocksPlatform::importGPC($_REQUEST['full'],'integer',0);
		
		// [TODO] Verify context + context_id
		
		$tpl = DevblocksPlatform::getTemplateService();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$tpl->assign('context', $context);
		$tpl->assign('context_id', $context_id);
		$tpl->assign('full', $full);
		
		// Add or remove watcher as current worker
		if($follow) {
			CerberusContexts::addWatchers($context, $context_id, $active_worker->id);
		} else {
			CerberusContexts::removeWatchers($context, $context_id, $active_worker->id);
		}
		
		// Watchers
		$object_watchers = DAO_ContextLink::getContextLinks($context, array($context_id), CerberusContexts::CONTEXT_WORKER);
		$tpl->assign('object_watchers', $object_watchers);
		
		// Workers
		$tpl->assign('workers', DAO_Worker::getAll());
		
		$tpl->display('devblocks:cerberusweb.core::internal/watchers/context_follow_button.tpl');
		
		$tpl->clearAssign('context');
		$tpl->clearAssign('context_id');
		$tpl->clearAssign('object_watchers');
	}
	
	function showContextWatchersAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer',0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$active_worker = CerberusApplication::getActiveWorker();

		if(null != ($extension = DevblocksPlatform::getExtension($context, true))) { /* @var $extension Extension_DevblocksContext */
			$tpl->assign('extension', $extension);
			$meta = $extension->getMeta($context_id);
			$tpl->assign('meta', $meta);
		}
		
		$tpl->assign('context', $context);
		$tpl->assign('context_id', $context_id);
		
		// Watchers
		$object_watchers = DAO_ContextLink::getContextLinks($context, array($context_id), CerberusContexts::CONTEXT_WORKER);
		$tpl->assign('object_watchers', $object_watchers);
		
		// Workers
		$tpl->assign('workers', DAO_Worker::getAll());
		
		$tpl->display('devblocks:cerberusweb.core::internal/watchers/context_follow_popup.tpl');
	}
	
	function addContextWatchersAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer',0);
		@$add_worker_ids_str = DevblocksPlatform::importGPC($_REQUEST['add_worker_ids'],'string','');
		@$delete_worker_ids_str = DevblocksPlatform::importGPC($_REQUEST['delete_worker_ids'],'string','');
		@$full = DevblocksPlatform::importGPC($_REQUEST['full'],'integer',0);
		
		$add_worker_ids = DevblocksPlatform::parseCsvString($add_worker_ids_str, false, 'integer');
		$delete_worker_ids = DevblocksPlatform::parseCsvString($delete_worker_ids_str, false, 'integer');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$active_worker = CerberusApplication::getActiveWorker();

		$tpl->assign('context', $context);
		$tpl->assign('context_id', $context_id);
		$tpl->assign('full', $full);
		
		// Add
		if(!empty($add_worker_ids) && $active_worker->hasPriv('core.watchers.assign'))
			CerberusContexts::addWatchers($context, $context_id, $add_worker_ids);
			
		// Remove
		if(!empty($delete_worker_ids) && $active_worker->hasPriv('core.watchers.unassign'))
			CerberusContexts::removeWatchers($context, $context_id, $delete_worker_ids);
		
		// Watchers
		$object_watchers = DAO_ContextLink::getContextLinks($context, array($context_id), CerberusContexts::CONTEXT_WORKER);
		$tpl->assign('object_watchers', $object_watchers);
		
		// Workers
		$tpl->assign('workers', DAO_Worker::getAll());
		
		$tpl->display('devblocks:cerberusweb.core::internal/watchers/context_follow_button.tpl');
		
		$tpl->clearAssign('context');
		$tpl->clearAssign('context_id');
		$tpl->clearAssign('object_watchers');		
	}
	
	// Snippets

	function showTabSnippetsAction() {
		@$point = DevblocksPlatform::importGPC($_REQUEST['point'],'string','');
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer',null);
		
		$active_worker = CerberusApplication::getActiveWorker();
		$visit = CerberusApplication::getVisit();
		$tpl = DevblocksPlatform::getTemplateService();

		$tpl->assign('owner_context', $context);
		$tpl->assign('owner_context_id', $context_id);
		
		// Remember the tab
		$visit->set($point, 'snippets');

		$view_id = str_replace('.','_',$point) . '_snippets';
		
		$view = C4_AbstractViewLoader::getView($view_id);
		
		if(null == $view) {
			$view = new View_Snippet();
			$view->id = $view_id;
			$view->name = 'Snippets';
		}
		
		$view->addParamsRequired(array(
			SearchFields_Snippet::OWNER_CONTEXT => new DevblocksSearchCriteria(SearchFields_Snippet::OWNER_CONTEXT, DevblocksSearchCriteria::OPER_EQ, $context),
			SearchFields_Snippet::OWNER_CONTEXT_ID => new DevblocksSearchCriteria(SearchFields_Snippet::OWNER_CONTEXT_ID, DevblocksSearchCriteria::OPER_EQ, $context_id),
		), true);
		
		C4_AbstractViewLoader::setView($view->id,$view);
		$tpl->assign('view', $view);
		
		$tpl->display('devblocks:cerberusweb.core::internal/snippets/index.tpl');
	}	
	
	function showSnippetsPeekAction() {
		@$snippet_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		
		$tpl = DevblocksPlatform::getTemplateService();
		
		$tpl->assign('view_id', $view_id);
		
		if(empty($snippet_id) || null == ($snippet = DAO_Snippet::get($snippet_id))) {
			@$owner_context = DevblocksPlatform::importGPC($_REQUEST['owner_context'],'string','');
			@$owner_context_id = DevblocksPlatform::importGPC($_REQUEST['owner_context_id'],'integer',0);
		
			$snippet = new Model_Snippet();
			$snippet->id = 0;
			$snippet->owner_context = !empty($owner_context) ? $owner_context : '';
			$snippet->owner_context_id = $owner_context_id;
		}
		
		$tpl->assign('snippet', $snippet);
		
		$contexts = Extension_DevblocksContext::getAll(false);
		$tpl->assign('contexts', $contexts);

		// Custom fields
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_SNIPPET); 
		$tpl->assign('custom_fields', $custom_fields);

		$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_SNIPPET, $snippet_id);
		if(isset($custom_field_values[$snippet_id]))
			$tpl->assign('custom_field_values', $custom_field_values[$snippet_id]);
		
		$types = Model_CustomField::getTypes();
		$tpl->assign('types', $types);
		
		$tpl->display('devblocks:cerberusweb.core::internal/snippets/peek.tpl');
	}
	
	function showSnippetsPeekToolbarAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('context', $context);
		
		if(!empty($context)) {
			$token_labels = array();
			$token_values = array();
			
			CerberusContexts::getContext($context, null, $token_labels, $token_values);
			
			$tpl->assign('token_labels', $token_labels);
		}
		
		$tpl->display('devblocks:cerberusweb.core::internal/snippets/peek_toolbar.tpl');
	}
	
	function saveSnippetsPeekAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$title = DevblocksPlatform::importGPC($_REQUEST['title'],'string','');
		@$owner_context = DevblocksPlatform::importGPC($_REQUEST['owner_context'],'string','');
		@$owner_context_id = DevblocksPlatform::importGPC($_REQUEST['owner_context_id'],'integer',0);
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$content = DevblocksPlatform::importGPC($_REQUEST['content'],'string','');
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'],'integer',0);

		$active_worker = CerberusApplication::getActiveWorker();

		$fields = array(
			DAO_Snippet::TITLE => $title,
			DAO_Snippet::CONTEXT => $context,
			DAO_Snippet::CONTENT => $content,
		);

		if($do_delete) {
			if(null != ($snippet = DAO_Snippet::get($id))) { /* @var $snippet Model_Snippet */
// 				if($active_worker->hasPriv('core.snippets.actions.update_all') 
// 					|| $snippet->created_by == $active_worker->id
// 				) {
					DAO_Snippet::delete($id);
// 				}
			}
			
		} else { // Create || Update
			if(empty($id)) {
				if($active_worker->hasPriv('core.snippets.actions.create')) {
					$fields[DAO_Snippet::OWNER_CONTEXT] = $owner_context;
					$fields[DAO_Snippet::OWNER_CONTEXT_ID] = $owner_context_id;
					
					$id = DAO_Snippet::create($fields);
					
					// Custom field saves
					@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', array());
					DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_SNIPPET, $id, $field_ids);
				} 
				
			} else {
				if(null != ($snippet = DAO_Snippet::get($id))) { /* @var $snippet Model_Snippet */
// 					if($active_worker->hasPriv('core.snippets.actions.update_all') 
// 						|| $snippet->created_by == $active_worker->id
// 					) {
						DAO_Snippet::update($id, $fields);
						
						// Custom field saves
						@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', array());
						DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_SNIPPET, $id, $field_ids);
// 					}
				}
			}
		}
		
		
		if(null !== ($view = C4_AbstractViewLoader::getView($view_id))) {
			$view->render();
		}
	}	
	
	function snippetPasteAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer',0);

		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		$active_worker = CerberusApplication::getActiveWorker();

		if(null != ($snippet = DAO_Snippet::get($id))) {
			// Make sure the worker is allowed to view this context+ID
			if(!empty($snippet->context)) {
				if(null == ($context = Extension_DevblocksContext::get($snippet->context))) /* @var $context Extension_DevblocksContext */
					exit;
				if(!$context->authorize($context_id, $active_worker))
					exit;
			}
			
			CerberusContexts::getContext($snippet->context, $context_id, $token_labels, $token_values);

			$snippet->incrementUse($active_worker->id);
		}

		if(!empty($context_id)) {
			$output = $tpl_builder->build($snippet->content, $token_values);
		} else {
			$output = $snippet->content;
		}

		if(!empty($output))
			echo rtrim($output,"\r\n"),"\n";
	}

	function snippetTestAction() {
		@$snippet_context = DevblocksPlatform::importGPC($_REQUEST['snippet_context'],'string','');
		@$snippet_context_id = DevblocksPlatform::importGPC($_REQUEST['snippet_context_id'],'integer',0);
		@$snippet_field = DevblocksPlatform::importGPC($_REQUEST['snippet_field'],'string','');

		$content = '';
		if(isset($_REQUEST[$snippet_field]))
			$content = DevblocksPlatform::importGPC($_REQUEST[$snippet_field]);

		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		$tpl = DevblocksPlatform::getTemplateService();

		$token_labels = array();
		$token_value = array();

		$ctx = Extension_DevblocksContext::get($snippet_context);

		// If no ID is given, randomize one
		if(empty($snippet_context_id) && method_exists($ctx, 'getRandom'))
			$snippet_context_id = $ctx->getRandom();
		
		CerberusContexts::getContext($snippet_context, $snippet_context_id, $token_labels, $token_values);
		
		$success = false;
		$output = '';

		if(!empty($token_values)) {
			// Tokenize
			$tokens = $tpl_builder->tokenize($content);
			
			// Test legal values
			$unknown_tokens = array_diff($tokens,array_keys($token_labels));
			$valid_tokens = array_intersect($tokens,array_keys($token_labels));
			
			if(!empty($unknown_tokens)) {
				$success = false;
				$output = "The following placeholders are unknown: ".
					implode(', ', $unknown_tokens);
				
			} else {
				// Try to build the template
				if(false === ($out = $tpl_builder->build($content, $token_values))) {
					// If we failed, show the compile errors
					$errors = $tpl_builder->getErrors();
					$success= false;
					$output = @array_shift($errors);
				} else {
					// If successful, return the parsed template
					$success = true;
					$output = $out;
				}
			}
		}

		$tpl->assign('success', $success);
		$tpl->assign('output', $output);
		$tpl->display('devblocks:cerberusweb.core::internal/renderers/test_results.tpl');
	}

	// Views

	function viewRefreshAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		$view = C4_AbstractViewLoader::getView($id);
		$view->render();
	}

	function viewSortByAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		@$sortBy = DevblocksPlatform::importGPC($_REQUEST['sortBy']);

		$view = C4_AbstractViewLoader::getView($id);
		$view->doSortBy($sortBy);
		C4_AbstractViewLoader::setView($id, $view);

		$view->render();
	}

	function viewPageAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		@$page = DevblocksPlatform::importGPC(DevblocksPlatform::importGPC($_REQUEST['page']));

		$view = C4_AbstractViewLoader::getView($id);
		$view->doPage($page);
		C4_AbstractViewLoader::setView($id, $view);

		$view->render();
	}

	function viewGetCriteriaAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		@$field = DevblocksPlatform::importGPC($_REQUEST['field']);

		$view = C4_AbstractViewLoader::getView($id);
		$view->renderCriteria($field);
	}

	private function _viewRenderInlineFilters($view, $is_custom=false) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('view', $view);
		
		if($is_custom)
			$tpl->assign('is_custom', true);
			
		$tpl->display('devblocks:cerberusweb.core::internal/views/customize_view_criteria.tpl');
	}

	// Ajax
	
	function viewToggleFiltersAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'string','');
		@$show = DevblocksPlatform::importGPC($_REQUEST['show'],'integer',0);
		
		$view = C4_AbstractViewLoader::getView($id);
		$view->renderFilters = !empty($show) ? 1 : 0;
		C4_AbstractViewLoader::setView($view->id, $view);
	}
	
	function viewAddFilterAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		@$is_custom = DevblocksPlatform::importGPC($_REQUEST['is_custom'],'integer',0);

		@$field = DevblocksPlatform::importGPC($_REQUEST['field']);
		@$oper = DevblocksPlatform::importGPC($_REQUEST['oper']);
		@$value = DevblocksPlatform::importGPC($_REQUEST['value']);
		@$field_deletes = DevblocksPlatform::importGPC($_REQUEST['field_deletes'],'array',array());

		$view = C4_AbstractViewLoader::getView($id);

		if($is_custom && 0 != strcasecmp('cust_',substr($id,0,5)))
			$is_custom = 0;
		
		// If this is a custom worklist we want to swap the req+editable params
		if($is_custom) {
			$original_params = $view->getEditableParams();
			$view->addParams($view->getParamsRequired(), true);
		}
			
		// Nuke criteria
		if(is_array($field_deletes) && !empty($field_deletes)) {
			foreach($field_deletes as $field_delete) {
				$view->doRemoveCriteria($field_delete);
			}
		}
		
		// Add
		if(!empty($field)) {
			$view->doSetCriteria($field, $oper, $value);
		}

		// If this is a custom worklist we want to swap the req+editable params back
		if($is_custom) {
			$view->addParamsRequired($view->getEditableParams(), true);
			$view->addParams($original_params, true);
		}
		
		C4_AbstractViewLoader::setView($view->id, $view);

		$this->_viewRenderInlineFilters($view, $is_custom);
	}

	function viewResetFiltersAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);

		$view = C4_AbstractViewLoader::getView($id);

		$view->doResetCriteria();

		C4_AbstractViewLoader::setView($view->id, $view);

		$this->_viewRenderInlineFilters($view);
	}

	function viewLoadPresetAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		@$preset_id = DevblocksPlatform::importGPC($_REQUEST['_preset'],'integer',0);

		$view = C4_AbstractViewLoader::getView($id);

		$view->removeAllParams();

		if(null != ($preset = DAO_ViewFiltersPreset::get($preset_id))) {
			$view->addParams($preset->params);
			if(!is_null($preset->sort_by)) {
				$view->renderSortBy = $preset->sort_by;
				$view->renderSortAsc = !empty($preset->sort_asc);
			}
		}

		C4_AbstractViewLoader::setView($view->id, $view);

		$this->_viewRenderInlineFilters($view);
	}

	function viewAddPresetAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		@$preset_name = DevblocksPlatform::importGPC($_REQUEST['_preset_name'],'string','');
		@$preset_replace_id = DevblocksPlatform::importGPC($_REQUEST['_preset_replace'],'integer',0);

		$active_worker = CerberusApplication::getActiveWorker();

		$view = C4_AbstractViewLoader::getView($id);
		$params_json = json_encode($view->getEditableParams());

		if(!empty($preset_replace_id)) {
			$fields = array(
				DAO_ViewFiltersPreset::NAME => !empty($preset_name) ? $preset_name : 'New Preset',
				DAO_ViewFiltersPreset::PARAMS_JSON => $params_json,
				DAO_ViewFiltersPreset::SORT_JSON => json_encode(array(
					'by' => $view->renderSortBy,
					'asc' => !empty($view->renderSortAsc),
				)),
			);

			DAO_ViewFiltersPreset::update($preset_replace_id, $fields);

		} else { // new
			$fields = array(
				DAO_ViewFiltersPreset::NAME => !empty($preset_name) ? $preset_name : 'New Preset',
				DAO_ViewFiltersPreset::VIEW_CLASS => get_class($view),
				DAO_ViewFiltersPreset::WORKER_ID => $active_worker->id,
				DAO_ViewFiltersPreset::PARAMS_JSON => $params_json,
				DAO_ViewFiltersPreset::SORT_JSON => json_encode(array(
					'by' => $view->renderSortBy,
					'asc' => !empty($view->renderSortAsc),
				)),
			);

			DAO_ViewFiltersPreset::create($fields);
		}

		$this->_viewRenderInlineFilters($view);
	}

	function viewEditPresetsAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		@$preset_dels = DevblocksPlatform::importGPC($_REQUEST['_preset_del'],'array',array());

		$view = C4_AbstractViewLoader::getView($id);

		DAO_ViewFiltersPreset::delete($preset_dels);

		$this->_viewRenderInlineFilters($view);
	}

	function viewCustomizeAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $id);

		$view = C4_AbstractViewLoader::getView($id);
		$tpl->assign('view', $view);

		$custom_fields = DAO_CustomField::getAll();
		$tpl->assign('custom_fields', $custom_fields);

		$tpl->display('devblocks:cerberusweb.core::internal/views/customize_view.tpl');
	}

	function viewShowCopyAction() {
        @$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');

		$active_worker = CerberusApplication::getActiveWorker();
		$tpl = DevblocksPlatform::getTemplateService();

        $view = C4_AbstractViewLoader::getView($view_id);

		$workspaces = DAO_Workspace::getByOwner(CerberusContexts::CONTEXT_WORKER, $active_worker->id);
		$tpl->assign('workspaces', $workspaces);

        $tpl->assign('view_id', $view_id);
		$tpl->assign('view', $view);

        $tpl->display('devblocks:cerberusweb.core::internal/views/copy.tpl');
	}

	// Ajax
	function viewDoCopyAction() {
		$translate = DevblocksPlatform::getTranslationService();
		$active_worker = CerberusApplication::getActiveWorker();

	    @$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string');
		$view = C4_AbstractViewLoader::getView($view_id);

		@$list_title = DevblocksPlatform::importGPC($_POST['list_title'],'string', '');
		@$workspace_id = DevblocksPlatform::importGPC($_POST['workspace_id'],'integer', 0);
		@$new_workspace = DevblocksPlatform::importGPC($_POST['new_workspace'],'string', '');

		if(empty($workspace_id)) {
			$fields = array(
				DAO_Workspace::NAME => (!empty($new_workspace) ? $new_workspace : $translate->_('mail.workspaces.new')),
				DAO_Workspace::OWNER_CONTEXT => CerberusContexts::CONTEXT_WORKER,
				DAO_Workspace::OWNER_CONTEXT_ID => $active_worker->id,
			);
			$workspace_id = DAO_Workspace::create($fields);
		}

		if(null == ($workspace = DAO_Workspace::get($workspace_id)))
			return;

		if(empty($list_title))
			$list_title = $translate->_('mail.workspaces.new_list');

		// Find the context
		$contexts = Extension_DevblocksContext::getAll();
		$workspace_context = '';
		$view_class = get_class($view);
		foreach($contexts as $context_id => $context) {
			if(0 == strcasecmp($context->params['view_class'], $view_class))
				$workspace_context = $context_id;
		}

		if(empty($workspace_context))
			return;

		// View params inside the list for quick render overload
		$list_view = new Model_WorkspaceListView();
		$list_view->title = $list_title;
		$list_view->num_rows = $view->renderLimit;
		$list_view->columns = $view->view_columns;
		$list_view->params = $view->getEditableParams();
		$list_view->params_required = $view->getParamsRequired();
		$list_view->sort_by = $view->renderSortBy;
		$list_view->sort_asc = $view->renderSortAsc;

		// Save the new worklist
		$fields = array(
			DAO_WorkspaceList::WORKSPACE_ID => $workspace_id,
			DAO_WorkspaceList::CONTEXT => $workspace_context,
			DAO_WorkspaceList::LIST_VIEW => serialize($list_view),
			DAO_WorkspaceList::LIST_POS => 99,
		);
		$list_id = DAO_WorkspaceList::create($fields);

		$view->render();
	}

	function viewShowExportAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['id']);

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('view_id', $view_id);

		$view = C4_AbstractViewLoader::getView($view_id);
		$tpl->assign('view', $view);

		$model_columns = $view->getColumnsAvailable();
		$tpl->assign('model_columns', $model_columns);

		$view_columns = $view->view_columns;
		$tpl->assign('view_columns', $view_columns);

		$tpl->display('devblocks:cerberusweb.core::internal/views/view_export.tpl');
	}

	function viewDoExportAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id']);
		@$columns = DevblocksPlatform::importGPC($_REQUEST['columns'],'array',array());
		@$export_as = DevblocksPlatform::importGPC($_REQUEST['export_as'],'string','csv');

		// Scan through the columns and remove any blanks
		if(is_array($columns))
		foreach($columns as $idx => $col) {
			if(empty($col))
				unset($columns[$idx]);
		}

		$view = C4_AbstractViewLoader::getView($view_id);
		$column_manifests = $view->getColumnsAvailable();

		// Override display
		$view->view_columns = $columns;
		$view->renderPage = 0;
		$view->renderLimit = -1;

		if('csv' == $export_as) {
			header("Pragma: public");
			header("Expires: 0");
			header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			header("Content-Type: text/plain; charset=".LANG_CHARSET_CODE);

			// Column headers
			if(is_array($columns)) {
				$cols = array();
				foreach($columns as $col) {
					$cols[] = sprintf("\"%s\"",
						str_replace('"','\"',mb_convert_case($column_manifests[$col]->db_label,MB_CASE_TITLE))
					);
				}
				echo implode(',', $cols) . "\r\n";
			}

			// Get data
			list($results, $null) = $view->getData();
			if(is_array($results))
			foreach($results as $row) {
				if(is_array($row)) {
					$cols = array();
					if(is_array($columns))
					foreach($columns as $col) {
						$cols[] = sprintf("\"%s\"",
							str_replace('"','\"',$row[$col])
						);
					}
					echo implode(',', $cols) . "\r\n";
				}
			}

		} elseif('xml' == $export_as) {
			header("Pragma: public");
			header("Expires: 0");
			header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			header("Content-Type: text/plain; charset=".LANG_CHARSET_CODE);

			$xml = simplexml_load_string("<results/>"); /* @var $xml SimpleXMLElement */

			// Get data
			list($results, $null) = $view->getData();
			if(is_array($results))
			foreach($results as $row) {
				$result =& $xml->addChild("result");
				if(is_array($columns))
				foreach($columns as $col) {
					$field =& $result->addChild("field",htmlspecialchars($row[$col],null,LANG_CHARSET_CODE));
					$field->addAttribute("id", $col);
				}
			}

			// Pretty format and output
			$doc = new DOMDocument('1.0');
			$doc->preserveWhiteSpace = false;
			$doc->loadXML($xml->asXML());
			$doc->formatOutput = true;
			echo $doc->saveXML();
		}

		exit;
	}

	function viewSaveCustomizeAction() {
		$translate = DevblocksPlatform::getTranslationService();
		$active_worker = CerberusApplication::getActiveWorker();

		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		@$columns = DevblocksPlatform::importGPC($_REQUEST['columns'],'array', array());
		@$num_rows = DevblocksPlatform::importGPC($_REQUEST['num_rows'],'integer',10);

		$num_rows = max($num_rows, 1); // make 1 the minimum

		// [Security] Filter custom fields
		$custom_fields = DAO_CustomField::getAll();
		foreach($columns as $idx => $column) {
			if(substr($column, 0, 3)=="cf_") {
				$field_id = intval(substr($column, 3));
				@$field = $custom_fields[$field_id]; /* @var $field Model_CustomField */

				// Is this a valid custom field?
				if(empty($field)) {
					unset($columns[$idx]);
					continue;
				}

				// Do we have permission to see it?
				if(!empty($field->group_id)
					&& !$active_worker->isTeamMember($field->group_id)) {
						unset($columns[$idx]);
						continue;
				}
			}
		}

		$view = C4_AbstractViewLoader::getView($id);
		$view->doCustomize($columns, $num_rows);

		// Handle worklists specially
		if(substr($id,0,5)=="cust_") { // custom workspace
			$list_view_id = intval(substr($id,5));

			// Special custom view fields
			@$title = DevblocksPlatform::importGPC($_REQUEST['title'],'string', $translate->_('views.new_list'));

			$view->name = $title;

			// Persist Object
			// [TODO] The list view can auto-persist in the 'worker_view_model' table
			$list_view = new Model_WorkspaceListView();
			$list_view->title = $title;
			$list_view->columns = $view->view_columns;
			$list_view->num_rows = $view->renderLimit;
			$list_view->params = $view->getEditableParams();
			$list_view->sort_by = $view->renderSortBy;
			$list_view->sort_asc = $view->renderSortAsc;

			DAO_WorkspaceList::update($list_view_id, array(
				DAO_WorkspaceList::LIST_VIEW => serialize($list_view)
			));
		}

		C4_AbstractViewLoader::setView($id, $view);

		$view->render();
	}

	function viewSubtotalAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		@$toggle = DevblocksPlatform::importGPC($_REQUEST['toggle'],'integer',0);
		@$category = DevblocksPlatform::importGPC($_REQUEST['category'],'string','');

		if(null == ($view = C4_AbstractViewLoader::getView($view_id)))
			return;
			
		// Check the interface
		if(!$view instanceof IAbstractView_Subtotals)
			return;
			
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('view_id', $view_id);
		$tpl->assign('view', $view);

		$fields = $view->getSubtotalFields();
		$tpl->assign('subtotal_fields', $fields);

		// If we're toggling on/off, persist our preference
		if($toggle) {
			// hidden->shown
			if(empty($view->renderSubtotals)) {
				$view->renderSubtotals = key($fields);
				
			// hidden->shown ('__' prefix means hidden w/ pref)
			} elseif('__'==substr($view->renderSubtotals,0,2)) {
				$key = ltrim($view->renderSubtotals,'_');
				// Make sure the desired key still exists
				$view->renderSubtotals = isset($fields[$key]) ? $key : key($fields);
				
			} else { // shown->hidden
				$view->renderSubtotals = '__' . $view->renderSubtotals;
				
			}
			
		} else {
			$view->renderSubtotals = $category;
			
		}
		
		C4_AbstractViewLoader::setView($view->id, $view);

		// If hidden, no need to draw template
		if(empty($view->renderSubtotals) || '__'==substr($view->renderSubtotals,0,2))
			return;

		$view->renderSubtotals();
	}

	// Workspace

	function showAddTabAction() {
		@$point = DevblocksPlatform::importGPC($_REQUEST['point'],'string', '');
		@$request = DevblocksPlatform::importGPC($_REQUEST['request'],'string', '');

		$tpl = DevblocksPlatform::getTemplateService();
		$active_worker = CerberusApplication::getActiveWorker();

		// Endpoint
		$tpl->assign('point', $point);
		$tpl->assign('request', $request);

		// Workspaces
		$enabled_workspaces = DAO_Workspace::getByEndpoint($point, CerberusContexts::CONTEXT_WORKER, $active_worker->id);
		$workspaces = $enabled_workspaces + array_diff_key(DAO_Workspace::getByOwner(CerberusContexts::CONTEXT_WORKER, $active_worker->id), $enabled_workspaces);

		$tpl->assign('enabled_workspaces', $enabled_workspaces);
		$tpl->assign('workspaces', $workspaces);

		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/tab.tpl');
	}

	function doAddTabAction() {
		@$point = DevblocksPlatform::importGPC($_REQUEST['point'],'string', '');
		@$workspace_ids = DevblocksPlatform::importGPC($_REQUEST['workspace_ids'],'array', array());
		@$new_workspace = DevblocksPlatform::importGPC($_REQUEST['new_workspace'],'string', '');
		@$point = DevblocksPlatform::importGPC($_REQUEST['point'],'string', '');
		@$request = DevblocksPlatform::importGPC($_REQUEST['request'],'string', '');

		$active_worker = CerberusApplication::getActiveWorker();
		$visit = CerberusApplication::getVisit();
		$is_focused_tab = false;

		// Are we adding any new workspaces?
		foreach($workspace_ids as $idx => $workspace_id) {
			// Insert and replace the $id
			if(!is_numeric($workspace_id)) {
				$fields = array(
					DAO_Workspace::NAME => $workspace_id,
					DAO_Workspace::OWNER_CONTEXT => CerberusContexts::CONTEXT_WORKER,
					DAO_Workspace::OWNER_CONTEXT_ID => $active_worker->id,
				);
				$workspace_id = DAO_Workspace::create($fields);
				$workspace_ids[$idx] = $workspace_id;
			}

			// Only focus the first new tab we add
			if(!empty($point) && !$is_focused_tab) {
				$visit->set($point, 'w_' . $workspace_id);
				$is_focused_tab = true;
			}
		}

		// Replace links for this endpoint
		DAO_Workspace::setEndpointWorkspaces($point, CerberusContexts::CONTEXT_WORKER, $active_worker->id, $workspace_ids);

		if(empty($request))
			$request = 'tickets';

		DevblocksPlatform::redirect(new DevblocksHttpResponse(explode('/',$request)));
	}

	function showWorkspaceTabAction() {
		@$workspace_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer', 0);
		@$point = DevblocksPlatform::importGPC($_REQUEST['point'],'string', '');
		@$request = DevblocksPlatform::importGPC($_REQUEST['request'],'string', '');

		$tpl = DevblocksPlatform::getTemplateService();
		$active_worker = CerberusApplication::getActiveWorker();

		$visit = CerberusApplication::getVisit();
		$visit->set($point, 'w_'.$workspace_id);

		if(null == ($workspace = DAO_Workspace::get($workspace_id))
			|| !$workspace->isReadableByWorker($active_worker)
			)
			return;

		$tpl->assign('workspace', $workspace);
		$tpl->assign('request', $request);
		
		$lists = $workspace->getWorklists();
		$list_ids = array_keys($lists);
		unset($lists);
		
		$tpl->assign('list_ids', $list_ids);
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/index.tpl');
			
		$tpl->clearAssign('request');
		$tpl->clearAssign('workspace');
		$tpl->clearAssign('view_ids');
		
		// Log activity
		DAO_Worker::logActivity(
			new Model_Activity(
				'activity.mail.workspaces',
				array(
					'<i>'.$workspace->name.'</i>'
				)
			)
		);
	}

	function initWorkspaceListAction() {
		@$list_id = DevblocksPlatform::importGPC($_REQUEST['list_id'],'integer', 0);
		
		if(empty($list_id))
			return;
			
		if(null == ($list = DAO_WorkspaceList::get($list_id)))
			return;
			
		$tpl = DevblocksPlatform::getTemplateService();
		$active_worker = CerberusApplication::getActiveWorker();

		if(null == ($workspace = DAO_Workspace::get($list->workspace_id))
			|| !$workspace->isReadableByWorker($active_worker)
			)
			return;			
		
		$view_id = 'cust_' . $list->id;
		
		if(null == ($view = C4_AbstractViewLoader::getView($view_id))) {
			$list_view = $list->list_view; /* @var $list_view Model_WorkspaceListView */

			// Make sure our workspace source has a valid renderer class
			if(null == ($ext = DevblocksPlatform::getExtension($list->context, true))) { /* @var $ext Extension_DevblocksContext */
				continue;
			}
			
			$view_class = $ext->getViewClass();
			if(!class_exists($view_class))
				continue;

			$view = new $view_class; /* @var $view C4_AbstractView */
			$view->id = $view_id;
			$view->name = $list_view->title;
			$view->renderLimit = $list_view->num_rows;
			$view->renderPage = 0;
			$view->view_columns = $list_view->columns;
			$view->addParams($list_view->params, true);
			if(property_exists($list_view, 'params_required'))
				$view->addParamsRequired($list_view->params_required, true);
			$view->renderSortBy = $list_view->sort_by;
			$view->renderSortAsc = $list_view->sort_asc;
			C4_AbstractViewLoader::setView($view_id, $view);
			
			unset($ext);
			unset($list_view);
			unset($view_class);
		}

		if(!empty($view)) {
			$tpl->assign('view', $view);
			$tpl->display('devblocks:cerberusweb.core::internal/views/search_and_view.tpl');
			$tpl->clearAssign('view');
		}
		
		unset($list);
		unset($list_id);
		unset($view_id);
	}
	
	function showEditWorkspacePanelAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer', 0);
		@$request = DevblocksPlatform::importGPC($_REQUEST['request'],'string', '');

		$tpl = DevblocksPlatform::getTemplateService();
		$active_worker = CerberusApplication::getActiveWorker();

		// Workspace
		if(null == ($workspace = DAO_Workspace::get($id)))
			return;

		$tpl->assign('workspace', $workspace);
		$tpl->assign('request', $request);

		// Worklist
		$worklists = $workspace->getWorklists();
		$tpl->assign('worklists', $worklists);

		// Contexts
		$contexts = Extension_DevblocksContext::getAll();
		$tpl->assign('contexts', $contexts);

		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/edit_workspace_panel.tpl');
	}

	function doEditWorkspaceAction() {
		@$workspace_id = DevblocksPlatform::importGPC($_POST['id'],'integer', 0);
		@$rename_workspace = DevblocksPlatform::importGPC($_POST['rename_workspace'],'string', '');
		@$ids = DevblocksPlatform::importGPC($_POST['ids'],'array', array());
		@$names = DevblocksPlatform::importGPC($_POST['names'],'array', array());
		@$do_delete = DevblocksPlatform::importGPC($_POST['do_delete'],'integer', '0');

		@$request = DevblocksPlatform::importGPC($_REQUEST['request'],'string', '');

		$active_worker = CerberusApplication::getActiveWorker();

		if(!empty($workspace_id) && 
			(null == ($workspace = DAO_Workspace::get($workspace_id)) 
			|| !$workspace->isWriteableByWorker($active_worker)
			))
			return;

		if($do_delete) { // Delete
			DAO_Workspace::delete($workspace_id);

		} else { // Edit
			// Rename workspace
			if(0 != strcmp($workspace->name, $rename_workspace)) {
				$fields = array(
					DAO_Workspace::NAME => $rename_workspace
				);
				DAO_Workspace::update($workspace->id, $fields);
			}

			// Create any new worklists
			if(is_array($ids) && !empty($ids))
			foreach($ids as $idx => $id) {
				if(!is_numeric($id)) { // Create
					if(null == ($context_ext = DevblocksPlatform::getExtension($id, true))) /* @var $context_ext Extension_DevblocksContext */
						continue;
					if(null == (@$class = $context_ext->getViewClass()))
						continue;
					if(!class_exists($class, true) || null == ($view = new $class))
						continue;

					// Context-specific defaults
					switch($context_ext->manifest->id) {
						case CerberusContexts::CONTEXT_TICKET:
							$view->addParamsRequired(array(
								SearchFields_Ticket::VIRTUAL_GROUPS_OF_WORKER => new DevblocksSearchCriteria(SearchFields_Ticket::VIRTUAL_GROUPS_OF_WORKER,'=',$active_worker->id),
							));
							break;
					}
						
					// Build the list model
					$list = new Model_WorkspaceListView();
					$list->title = $names[$idx];
					$list->columns = $view->view_columns;
					$list->params = $view->getEditableParams();
					$list->params_required = $view->getParamsRequired();
					$list->num_rows = 5;
					$list->sort_by = $view->renderSortBy;
					$list->sort_asc = $view->renderSortAsc;

					// Add the worklist
					$fields = array(
						DAO_WorkspaceList::LIST_POS => $idx,
						DAO_WorkspaceList::LIST_VIEW => serialize($list),
						DAO_WorkspaceList::WORKSPACE_ID => $workspace_id,
						DAO_WorkspaceList::CONTEXT => $id,
					);
					$ids[$idx] = DAO_WorkspaceList::create($fields);
				}
			}

			$worklists = $workspace->getWorklists();

			// Deletes
			$delete_ids = array_diff(array_keys($worklists), $ids);
			if(is_array($delete_ids) && !empty($delete_ids))
				DAO_WorkspaceList::delete($delete_ids);

			// Reorder worklists, rename lists, on workspace
			if(is_array($ids) && !empty($ids))
			foreach($ids as $idx => $id) {
				if(null == ($worklist = DAO_WorkspaceList::get($id)))
					continue;

				$list_view = $worklists[$id]->list_view; /* @var $list_view Model_WorkspaceListView */

				// If the name changed
				if(isset($names[$idx]) && 0 != strcmp($list_view->title,$names[$idx])) {
					$list_view->title = $names[$idx];

					// Save the view in the session
					$view = C4_AbstractViewLoader::getView('cust_'.$id);
					$view->name = $list_view->title;
					C4_AbstractViewLoader::setView('cust_'.$id, $view);
				}

				DAO_WorkspaceList::update($id,array(
					DAO_WorkspaceList::LIST_POS => intval($idx),
					DAO_WorkspaceList::LIST_VIEW => serialize($list_view),
				));
			}
		}

		if(empty($request))
			$request = 'tickets';

		DevblocksPlatform::redirect(new DevblocksHttpResponse(explode('/', $request)));
		return;
	}

	/**
	 * Triggers
	 */

	function applyMacroAction() {
		@$macro_id = DevblocksPlatform::importGPC($_REQUEST['macro'],'integer',0);
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer',0);
		@$run_date = DevblocksPlatform::importGPC($_REQUEST['run_date'],'string','');
		@$return_url = DevblocksPlatform::importGPC($_REQUEST['return_url'],'string','');

		$active_worker = CerberusApplication::getActiveWorker();

		if(empty($return_url) && isset($_SERVER['http_referer']))
			$return_url = $_SERVER['http_referer'];
		
		try {
			if(empty($context) || empty($context_id) || empty($macro_id))
				return;
	
			// Load context
			if(null == ($context_ext = DevblocksPlatform::getExtension($context, true)))
				throw new Exception("Invalid context.");
	
			// ACL: Ensure access to the context object
			if(!$context_ext->authorize($context_id, $active_worker))
				throw new Exception("Access denied to context.");
			
			// Load macro
			if(null == ($macro = DAO_TriggerEvent::get($macro_id))) /* @var $macro Model_TriggerEvent */
				throw new Exception("Invalid macro.");
			
			// ACL: Ensure the worker owns the macro
			if(false == ($macro->owner_context == CerberusContexts::CONTEXT_WORKER && $macro->owner_context_id == $active_worker->id))
				throw new Exception("Access denied to macro.");
				
			// Load event manifest
			if(null == ($ext = DevblocksPlatform::getExtension($macro->event_point, false))) /* @var $ext DevblocksExtensionManifest */
				throw new Exception("Invalid event.");

			$run_timestamp = @strtotime($run_date) or time();

			if($run_timestamp > time()) {
				DAO_ContextScheduledBehavior::create(array(
					DAO_ContextScheduledBehavior::BEHAVIOR_ID => $macro->id,
					DAO_ContextScheduledBehavior::CONTEXT => $context,
					DAO_ContextScheduledBehavior::CONTEXT_ID => $context_id,
					DAO_ContextScheduledBehavior::RUN_DATE => $run_timestamp,
				));
				
			} else {
				// Execute now
				call_user_func(array($ext->class, 'trigger'), $macro->id, $context_id);
				
			}
			
			
		} catch (Exception $e) {
			// System log error?
		}
		
		// Redirect
		DevblocksPlatform::redirectURL($return_url);
		exit;
	}
	
	function renderContextScheduledBehaviorAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer',0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		
		$tpl->assign('context', $context);
		$tpl->assign('context_id', $context_id);
		$tpl->assign('expanded', true);
		
		$tpl->display('devblocks:cerberusweb.core::internal/macros/behavior/scheduled_behavior_profile.tpl');
	}
	
	function showMacroSchedulerPopupAction() {
		@$job_id = DevblocksPlatform::importGPC($_REQUEST['job_id'],'integer',0);
		@$macro_id = DevblocksPlatform::importGPC($_REQUEST['macro'],'integer',0);
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer',0);
		@$return_url = DevblocksPlatform::importGPC($_REQUEST['return_url'],'string','');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(empty($job_id)) {
			$tpl->assign('context', $context);
			$tpl->assign('context_id', $context_id);
			$tpl->assign('return_url', $return_url);
			
			try {
				if(null == ($macro = DAO_TriggerEvent::get($macro_id)))
					throw new Exception("Missing macro.");
				
				$tpl->assign('macro', $macro);
				
				// Verify permission
				
				if(null == ($ctx = DevblocksPlatform::getExtension($context, true))) /* @var $ctx Extension_DevblocksContext */
					throw new Exception("Permission denied.");
				
				// Verify permission
				if(!$ctx->authorize($context_id, $active_worker))
					throw new Exception("Permission denied.");
				
				$tpl->assign('ctx', $ctx);
				
			} catch(Exception $e) {
				DevblocksPlatform::redirectURL($return_url);
				exit;
			}
			
		} else { // Update
			$job = DAO_ContextScheduledBehavior::get($job_id);
			$tpl->assign('job', $job);

			// Verify permission
			
			if(null == ($ctx = DevblocksPlatform::getExtension($job->context, true))) /* @var $ctx Extension_DevblocksContext */
				return;
			
			// Verify permission
			$editable = $ctx->authorize($job->context_id, $active_worker);
			$tpl->assign('editable', $editable);
			
			$macro = DAO_TriggerEvent::get($job->behavior_id);
			$tpl->assign('macro', $macro);
			
		}
		
		$tpl->display('devblocks:cerberusweb.core::internal/macros/display/scheduler_popup.tpl');
	}
	
	function saveMacroSchedulerPopupAction() {
		@$job_id = DevblocksPlatform::importGPC($_REQUEST['job_id'],'integer',0);
		@$run_date = DevblocksPlatform::importGPC($_REQUEST['run_date'],'string','');
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'],'integer',0);
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(null == ($job = DAO_ContextScheduledBehavior::get($job_id)))
			return;
		
		if(null == ($ctx = DevblocksPlatform::getExtension($job->context, true))) /* @var $ctx Extension_DevblocksContext */
			return;
		
		// Verify permission
		if(!$ctx->authorize($job->context_id, $active_worker))
			return;
		
		if($do_delete) {
			DAO_ContextScheduledBehavior::delete($job->id);
			
		} else {
			$run_timestamp = @strtotime($run_date) or time();
			
			DAO_ContextScheduledBehavior::update($job->id, array(
				DAO_ContextScheduledBehavior::RUN_DATE => $run_timestamp, 
			));
			
		}
		
		exit;
	}
	
	function showAttendantTabAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer',0);
		@$point = DevblocksPlatform::importGPC($_REQUEST['point'],'string','');
		
		$translate = DevblocksPlatform::getTranslationService();
		$active_worker = CerberusApplication::getActiveWorker();
		$visit = CerberusApplication::getVisit();
		$tpl = DevblocksPlatform::getTemplateService();

		if(empty($context) || empty($context_id))
			return;
			
		/*
		 * Secure looking at other worker tabs (check superuser, worker_id)
		 */
		if(null == ($ctx = Extension_DevblocksContext::get($context)))
			return;
		
		if(!$ctx->authorize($context_id, $active_worker))
			return;
		
		// Remember tab
		if(!empty($point))
			$visit->set($point, 'attendant');

		$tpl->assign('context', $context);
		$tpl->assign('context_id', $context_id);
			
		// Events
		$events = Extension_DevblocksEvent::getByContext($context, false);
		$tpl->assign('events', $events);
		
		// Triggers
		$triggers = DAO_TriggerEvent::getByOwner($context, $context_id, null, true);
		$tpl->assign('triggers', $triggers);
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/assistant/tab.tpl');
	}

	function reparentNodeAction() {
		@$child_id = DevblocksPlatform::importGPC($_REQUEST['child_id'],'integer', 0);
		@$parent_id = DevblocksPlatform::importGPC($_REQUEST['parent_id'],'integer', 0);
		
		if(null == ($child_node = DAO_DecisionNode::get($child_id)))
			exit;
		
		$nodes = DAO_DecisionNode::getByTriggerParent($child_node->trigger_id, $parent_id);
		
		// Remove current node if exists
		unset($nodes[$child_node->id]);
		
		$pos = 0;
		
		// Insert child at top of parent
		DAO_DecisionNode::update($child_id, array(
			DAO_DecisionNode::PARENT_ID => $parent_id,
			DAO_DecisionNode::POS => $pos++,
		));
		
		// Renumber children
		foreach($nodes as $node_id => $node) {
			DAO_DecisionNode::update($node_id, array(
				DAO_DecisionNode::PARENT_ID => $parent_id,
				DAO_DecisionNode::POS => $pos++,
			));
		}
		
		exit;
	}
	
	function showDecisionMovePopupAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer', 0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		
		if(!empty($id) && null != ($node = DAO_DecisionNode::get($id))) {
			$tpl->assign('node', $node);
			
		} elseif(isset($_REQUEST['trigger_id'])) {
			@$trigger_id = DevblocksPlatform::importGPC($_REQUEST['trigger_id'],'integer', 0);
			if(null != ($trigger = DAO_TriggerEvent::get($trigger_id))) {
				$tpl->assign('trigger', $trigger);
			}
		}
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/editors/_move.tpl');
	}
	
	function showDecisionReorderPopupAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer', 0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		
		if(!empty($id) && null != ($node = DAO_DecisionNode::get($id))) {
			$trigger_id = $node->trigger_id;
			$tpl->assign('node', $node);
			
		} elseif(isset($_REQUEST['trigger_id'])) {
			@$trigger_id = DevblocksPlatform::importGPC($_REQUEST['trigger_id'],'integer', 0);
			if(null != ($trigger = DAO_TriggerEvent::get($trigger_id))) {
				$tpl->assign('trigger', $trigger);
			}
		}
		
		$children = DAO_DecisionNode::getByTriggerParent($trigger_id, $id);
		$tpl->assign('children', $children);
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/editors/_reorder.tpl');
	}
	
	function saveDecisionReorderPopupAction() {
		@$child_ids = DevblocksPlatform::importGPC($_REQUEST['child_id'],'array', array());
		
		if(!empty($child_ids))
		foreach($child_ids as $pos => $child_id) {
			DAO_DecisionNode::update($child_id, array(
				DAO_DecisionNode::POS => $pos,
			));
		}
	}
	
	function saveDecisionDeletePopupAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer', 0);

		if(!empty($id) && null != ($node = DAO_DecisionNode::get($id))) {
			if(null != ($trigger = DAO_TriggerEvent::get($node->trigger_id))) {
				// Load the trigger's tree so we can delete all children from this node
				$data = $trigger->getDecisionTreeData();
				$depths = $data['depths'];
				
				$ids_to_delete = array();

				$found = false;
				foreach($depths as $node_id => $depth) {
					if($node_id == $id) {
						$found = true;
						$ids_to_delete[] = $id;
						continue;
					}
						
					if(!$found)
						continue;
						
					// Continue deleting (queuing IDs) while depth > origin
					if($depth > $depths[$id]) {
						$ids_to_delete[] = $node_id;
					} else {
						$found = false;
					}
				}
				
				DAO_DecisionNode::delete($ids_to_delete);
			} 
			
		} elseif(isset($_REQUEST['trigger_id'])) {
			@$trigger_id = DevblocksPlatform::importGPC($_REQUEST['trigger_id'],'integer', 0);
			DAO_TriggerEvent::delete($trigger_id);
			
		}
	}
	
	function showDecisionPopupAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string', '');
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer', 0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		
		if(!empty($id)) { // Edit node
			// Model
			if(null != ($model = DAO_DecisionNode::get($id))) {
				$tpl->assign('id', $id);
				$tpl->assign('model', $model);
				$tpl->assign('trigger_id', $model->trigger_id);
				$type = $model->node_type;
				$trigger_id = $model->trigger_id;
				//echo $model->params_json;
			}
			
		} elseif(isset($_REQUEST['parent_id'])) { // Add child node
			@$parent_id = DevblocksPlatform::importGPC($_REQUEST['parent_id'],'integer', 0);
			@$type = DevblocksPlatform::importGPC($_REQUEST['type'],'string', '');
			@$trigger_id = DevblocksPlatform::importGPC($_REQUEST['trigger_id'],'integer', 0);
			
			$tpl->assign('parent_id', $parent_id);
			$tpl->assign('type', $type);
			$tpl->assign('trigger_id', $trigger_id);
			
		} elseif(isset($_REQUEST['trigger_id'])) { // Add child node
			@$trigger_id = DevblocksPlatform::importGPC($_REQUEST['trigger_id'],'integer', 0);
			
			$tpl->assign('trigger_id', $trigger_id);
			$type = 'trigger';
			
			if(empty($trigger_id)) {
				@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer', 0);
				
				$trigger = null;
				$tpl->assign('context', $context);
				$tpl->assign('context_id', $context_id);
				
				$events = Extension_DevblocksEvent::getByContext($context, false);
				$tpl->assign('events', $events);
				
			} else {
				if(null != ($trigger = DAO_TriggerEvent::get($trigger_id))) {
				}
			}
			
		}

		if(!isset($trigger) && !empty($trigger_id))
			if(null == ($trigger = DAO_TriggerEvent::get($trigger_id)))
				return;
				
		$tpl->assign('trigger', $trigger);
		
		$event = null;
		if(!empty($trigger))
			if(null == ($event = DevblocksPlatform::getExtension($trigger->event_point, true)))
				return;
				
		$tpl->assign('event', $event);
		
		// Template
		switch($type) {
			case 'switch':
				$tpl->display('devblocks:cerberusweb.core::internal/decisions/editors/switch.tpl');
				break;
				
			case 'outcome':
				if(null != ($ext = DevblocksPlatform::getExtension($trigger->event_point, true)))
					$tpl->assign('conditions', $ext->getConditions());
					
				$tpl->display('devblocks:cerberusweb.core::internal/decisions/editors/outcome.tpl');
				break;
				
			case 'action':
				if(null != ($ext = DevblocksPlatform::getExtension($trigger->event_point, true)))
					$tpl->assign('actions', $ext->getActions());
					
				// Workers
				$tpl->assign('workers', DAO_Worker::getAll());
					
				$tpl->display('devblocks:cerberusweb.core::internal/decisions/editors/action.tpl');
				break;
				
			case 'trigger':
				if(!empty($trigger)) {
					$ext = DevblocksPlatform::getExtension($trigger->event_point, false);
					$tpl->assign('ext', $ext);
				}
				$tpl->display('devblocks:cerberusweb.core::internal/decisions/editors/trigger.tpl');
				break;
		}
		
		// Free
		$tpl->clearAssign('actions');
		$tpl->clearAssign('conditions');
		$tpl->clearAssign('event');
		$tpl->clearAssign('ext');
		$tpl->clearAssign('id');
		$tpl->clearAssign('model');
		$tpl->clearAssign('parent_id');
		$tpl->clearAssign('trigger');
		$tpl->clearAssign('trigger_id');
		$tpl->clearAssign('type');
	}

	function doDecisionAddConditionAction() {
		@$condition = DevblocksPlatform::importGPC($_REQUEST['condition'],'string', '');
		@$trigger_id = DevblocksPlatform::importGPC($_REQUEST['trigger_id'],'integer', 0);
		@$seq = DevblocksPlatform::importGPC($_REQUEST['seq'],'integer', 0);

		$tpl = DevblocksPlatform::getTemplateService();

		if(null == ($trigger = DAO_TriggerEvent::get($trigger_id)))
			return;
			
		if(null == ($event = DevblocksPlatform::getExtension($trigger->event_point, true)))
			return; /* @var $event Extension_DevblocksEvent */
		
		$tpl->assign('trigger', $trigger);
		$tpl->assign('event', $event);
		$tpl->assign('seq', $seq);
			
		$event->renderCondition($condition, $trigger, null, $seq);
	}
	
	function doDecisionAddActionAction() {
		@$action = DevblocksPlatform::importGPC($_REQUEST['action'],'string', '');
		@$trigger_id = DevblocksPlatform::importGPC($_REQUEST['trigger_id'],'integer', 0);
		@$seq = DevblocksPlatform::importGPC($_REQUEST['seq'],'integer', 0);

		$tpl = DevblocksPlatform::getTemplateService();

		if(null == ($trigger = DAO_TriggerEvent::get($trigger_id)))
			return;
			
		if(null == ($event = DevblocksPlatform::getExtension($trigger->event_point, true)))
			return; /* @var $event Extension_DevblocksEvent */
			
		$tpl->assign('trigger', $trigger);
		$tpl->assign('event', $event);
		$tpl->assign('seq', $seq);
			
		$event->renderAction($action, $trigger, null, $seq);
	}

	function showDecisionEventBehaviorAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string', '');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer', 0);
		@$event_point = DevblocksPlatform::importGPC($_REQUEST['event_point'],'string', '');

		// [TODO] Verify context
		
		$tpl = DevblocksPlatform::getTemplateService();
		
		$events = DevblocksPlatform::getExtensions(Extension_DevblocksEvent::POINT, false);
		$tpl->assign('events', $events);

		if(empty($event_point))
			$event_point = null;
		
		$triggers = DAO_TriggerEvent::getByOwner($context, $context_id, $event_point, true);
		$tpl->assign('triggers', $triggers);
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/assistant/behavior.tpl');
	}
	
	function showDecisionTreeAction() {
		@$trigger_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer', 0);
		
		$tpl = DevblocksPlatform::getTemplateService();

		if(null == ($trigger = DAO_TriggerEvent::get($trigger_id)))
			return;
		
		if(null == ($event = DevblocksPlatform::getExtension($trigger->event_point, false)))
			return; /* @var $event Extension_DevblocksEvent */
			
		$tpl->assign('trigger', $trigger);
		$tpl->assign('event', $event);
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/tree.tpl');
	}
	
	function saveDecisionPopupAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer', 0);
		@$title = DevblocksPlatform::importGPC($_REQUEST['title'],'string', '');

		$fields = array();
		
		if(!empty($id)) { // Edit
			if(null != ($model = DAO_DecisionNode::get($id))) {
				$type = $model->node_type;
				
				// Title changed
				if(0 != strcmp($model->title, $title) && !empty($title))
					DAO_DecisionNode::update($id, array(
						DAO_DecisionNode::TITLE => $title,
					));
			}
			
		} elseif(isset($_REQUEST['parent_id'])) { // Create
			@$parent_id = DevblocksPlatform::importGPC($_REQUEST['parent_id'],'integer', 0);
			@$trigger_id = DevblocksPlatform::importGPC($_REQUEST['trigger_id'],'integer', 0);
			@$type = DevblocksPlatform::importGPC($_REQUEST['type'],'string', '');
			
			$id = DAO_DecisionNode::create(array(
				DAO_DecisionNode::TITLE => $title,
				DAO_DecisionNode::PARENT_ID => $parent_id,
				DAO_DecisionNode::TRIGGER_ID => $trigger_id,
				DAO_DecisionNode::NODE_TYPE => $type,
				DAO_DecisionNode::PARAMS_JSON => '',
			));
			
		} elseif(isset($_REQUEST['trigger_id'])) { // Trigger
			@$trigger_id = DevblocksPlatform::importGPC($_REQUEST['trigger_id'],'integer', 0);
			@$title = DevblocksPlatform::importGPC($_REQUEST['title'],'string', '');
			@$is_disabled = DevblocksPlatform::importGPC($_REQUEST['is_disabled'],'integer', 0);
			@$json = DevblocksPlatform::importGPC($_REQUEST['json'],'integer', 0);

			// Create trigger
			if(empty($trigger_id)) {
				@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string', '');
				@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer', 0);
				@$event_point = DevblocksPlatform::importGPC($_REQUEST['event_point'],'string', '');
				
				$type = 'trigger';
				
				$trigger_id = DAO_TriggerEvent::create(array(
					DAO_TriggerEvent::OWNER_CONTEXT => $context,
					DAO_TriggerEvent::OWNER_CONTEXT_ID => $context_id,
					DAO_TriggerEvent::EVENT_POINT => $event_point,
					DAO_TriggerEvent::TITLE => $title,
					DAO_TriggerEvent::IS_DISABLED => !empty($is_disabled) ? 1 : 0,
				));
				
				if($json) {
					header("Content-Type: text/json;");
					echo json_encode(array(
						'trigger_id' => $trigger_id,
						'event_point' => $event_point,
					));
					exit;
				}
				
			// Update trigger
			} else {
				if(null != ($trigger = DAO_TriggerEvent::get($trigger_id))) {
					$type = 'trigger';

					if(empty($title)) {
						if(null != ($ext = DevblocksPlatform::getExtension($trigger->event_point, false)))
							$title = $ext->name;
					}
					
					DAO_TriggerEvent::update($trigger->id, array(
						DAO_TriggerEvent::TITLE => $title,
						DAO_TriggerEvent::IS_DISABLED => !empty($is_disabled) ? 1 : 0,
					));
				}
			}
			
		}

		// Type-specific properties
		switch($type) {
			case 'switch':
				// Nothing
				break;
				
			case 'outcome':
				@$nodes = DevblocksPlatform::importGPC($_REQUEST['nodes'],'array',array());

				$groups = array();
				$group_key = null;
				
				foreach($nodes as $k) {
					switch($k) {
						case 'any':
						case 'all':
							$groups[] = array(
								'any' => ($k=='any'?1:0),
								'conditions' => array(),
							);
							end($groups);
							$group_key = key($groups);
							break;
							
						default:
							if(!is_numeric($k))
								continue;
							
							$condition = DevblocksPlatform::importGPC($_POST['condition'.$k],'array',array());
							$groups[$group_key]['conditions'][] = $condition;
							break;
					}
				}
				
				DAO_DecisionNode::update($id, array(
					DAO_DecisionNode::PARAMS_JSON => json_encode(array('groups'=>$groups)), 
				));
				break;
				
			case 'action':
				@$action_ids = DevblocksPlatform::importGPC($_REQUEST['actions'],'array',array());
				$params = array();
				$params['actions'] = $this->_parseActions($action_ids, $_POST);
				DAO_DecisionNode::update($id, array(
					DAO_DecisionNode::PARAMS_JSON => json_encode($params), 
				));
				break;
				
			case 'trigger':
				break;
		}
	}
	
	private function _parseActions($action_ids, $scope) {
		$objects = array();
		
		foreach($action_ids as $action_id) {
			$objects[] = DevblocksPlatform::importGPC($scope['action'.$action_id],'array',array());
		}
		
		return $objects;
	}

	function showDecisionNodeMenuAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer', 0);

		$tpl = DevblocksPlatform::getTemplateService();
		
		if(null != ($node = DAO_DecisionNode::get($id)))
			$tpl->assign('node', $node);
		
		@$trigger_id = DevblocksPlatform::importGPC($_REQUEST['trigger_id'],'integer', 0);
		if(!empty($trigger_id))
			$tpl->assign('trigger_id', $trigger_id);
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/menu.tpl');
	}
	
	function deleteDecisionNodeAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer', 0);
		
		if(!empty($id)) {
			DAO_DecisionNode::delete($id);
			
		} elseif(isset($_REQUEST['trigger_id'])) {
			@$trigger_id = DevblocksPlatform::importGPC($_REQUEST['trigger_id'],'integer', 0);
			// [TODO] Make sure this worker owns the trigger (or is group mgr)
			if(!empty($trigger_id))
				DAO_TriggerEvent::delete($trigger_id);
		}
	}
	
	function testDecisionEventSnippetsAction() {
		@$prefix = DevblocksPlatform::importGPC($_REQUEST['prefix'],'string','');
		@$trigger_id = DevblocksPlatform::importGPC($_REQUEST['trigger_id'],'integer',0);

		$content = '';
		
		if(is_array($_REQUEST['field'])) {
			@$fields = DevblocksPlatform::importGPC($_REQUEST['field'],'array',array());
		
			if(is_array($fields))
			foreach($fields as $field) {
				@$append = DevblocksPlatform::importGPC($_REQUEST[$prefix][$field],'string','');
				$content .= !empty($append) ? ('[' . $field . ']: ' . PHP_EOL . $append . PHP_EOL . PHP_EOL) : '';
			}
			
		} else {
			@$field = DevblocksPlatform::importGPC($_REQUEST['field'],'string','');
			@$content = DevblocksPlatform::importGPC($_REQUEST[$prefix][$field],'string','');
		}
		
		if(null == ($trigger = DAO_TriggerEvent::get($trigger_id)))
			return;
			
		$event = $trigger->getEvent();
		$event_model = $event->generateSampleEventModel();
		$event->setEvent($event_model);
		$values = $event->getValues();
		
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		$tpl = DevblocksPlatform::getTemplateService();
		
		$success = false;
		$output = '';

		if(isset($values)) {
			// Try to build the template
			if(false === ($out = $tpl_builder->build($content, $values))) {
				// If we failed, show the compile errors
				$errors = $tpl_builder->getErrors();
				$success= false;
				$output = @array_shift($errors);
			} else {
				// If successful, return the parsed template
				$success = true;
				$output = $out;
			}
		}

		$tpl->assign('success', $success);
		$tpl->assign('output', $output);
		$tpl->display('devblocks:cerberusweb.core::internal/renderers/test_results.tpl');
	}
	
	// Utils

	function startAutoRefreshAction() {
		$url = DevblocksPlatform::importGPC($_REQUEST['url'],'string', '');
		$secs = DevblocksPlatform::importGPC($_REQUEST['secs'],'integer', 300);

		$_SESSION['autorefresh'] = array(
			'url' => $url,
			'started' => time(),
			'secs' => $secs,
		);
	}

	function stopAutoRefreshAction() {
		unset($_SESSION['autorefresh']);
	}

	function transformMarkupToHTMLAction() {
		$format = DevblocksPlatform::importGPC($_REQUEST['format'],'string', '');
		$data = DevblocksPlatform::importGPC($_REQUEST['data'],'string', '');

		switch($format) {
			case 'markdown':
				echo DevblocksPlatform::parseMarkdown($data);
				break;
			case 'html':
			default:
				echo $data;
				break;
		}
	}

	// Comments

	function showTabContextCommentsAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer');
		@$point = DevblocksPlatform::importGPC($_REQUEST['point'],'string','');

		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();

		if(!empty($point))
			$visit->set($point, 'comments');

		$tpl->assign('context', $context);
		$tpl->assign('context_id', $context_id);

		$comments = DAO_Comment::getByContext($context, $context_id);
		$tpl->assign('comments', $comments);

		$tpl->display('devblocks:cerberusweb.core::internal/comments/tab.tpl');
	}

	function commentShowPopupAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer',0);

		$tpl = DevblocksPlatform::getTemplateService();
		$active_worker = CerberusApplication::getActiveWorker();

		$tpl->assign('context', $context);
		$tpl->assign('context_id', $context_id);

		// Automatically tell anybody associated with this context object
//		$workers = CerberusContexts::getWatchers($context, $context_id);
//		if(isset($workers[$active_worker->id]))
//			unset($workers[$active_worker->id]);
//		$tpl->assign('notify_workers', $workers);

		$tpl->display('devblocks:cerberusweb.core::internal/comments/peek.tpl');
	}

	function commentSavePopupAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer',0);
		@$comment = DevblocksPlatform::importGPC($_REQUEST['comment'],'string','');
		@$file_ids = DevblocksPlatform::importGPC($_REQUEST['file_ids'],'array',array());

		$translate = DevblocksPlatform::getTranslationService();

		// Worker is logged in
		if(null === ($active_worker = CerberusApplication::getActiveWorker()))
			return;

		// [TODO] Validate context + ID
		// [TODO] Validate ACL

		// Form was filled in
		if(empty($context) || empty($context_id) || empty($comment))
			return;

		@$also_notify_worker_ids = DevblocksPlatform::importGPC($_REQUEST['notify_worker_ids'],'array',array());
		
		$fields = array(
			DAO_Comment::CONTEXT => $context,
			DAO_Comment::CONTEXT_ID => $context_id,
			DAO_Comment::ADDRESS_ID => $active_worker->getAddress()->id,
			DAO_Comment::COMMENT => $comment,
			DAO_Comment::CREATED => time(),
		);
		$comment_id = DAO_Comment::create($fields, $also_notify_worker_ids);

		// Attachments
		if(!empty($file_ids))
		foreach($file_ids as $file_id) {
			DAO_AttachmentLink::create(intval($file_id), CerberusContexts::CONTEXT_COMMENT, $comment_id);
		}
	}

	function commentDeleteAction() {
		@$comment_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		DAO_Comment::delete($comment_id);
	}
};
