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

	function chooserOpenParamsAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string');
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		@$layer = DevblocksPlatform::importGPC($_REQUEST['layer'],'string');
		@$trigger_id = DevblocksPlatform::importGPC($_REQUEST['trigger_id'],'integer',0);
		
		if(null != ($view = C4_AbstractViewLoader::getView($view_id))) {
			if(!empty($trigger_id) && null != ($trigger = DAO_TriggerEvent::get($trigger_id))) {
				$event = $trigger->getEvent();
				
				if(method_exists($event,'generateSampleEventModel')) {
					$event_model = $event->generateSampleEventModel();
					$event->setEvent($event_model);
					$values = $event->getValues();
					$view->setPlaceholderValues($values);
				}
				
				$conditions = $event->getConditions($trigger);
				$valctx = $event->getValuesContexts($trigger);
				foreach($valctx as $token => $vtx) {
					$conditions[$token] = $vtx;
				}

				foreach($conditions as $cond_id => $cond) {
					if(substr($cond_id,0,1) == '_')
						unset($conditions[$cond_id]);
				}
				
				$view->setPlaceholderLabels($conditions);
				
				C4_AbstractViewLoader::setView($view->id, $view);
			}
			
			$tpl = DevblocksPlatform::getTemplateService();
			$tpl->assign('context', $context);
			$tpl->assign('layer', $layer);
			$tpl->assign('view', $view);
			$tpl->display('devblocks:cerberusweb.core::internal/choosers/__worklist.tpl');
		}
	}
	
	function serializeViewAction() {
		header("Content-type: application/json");
		
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		
		if(null != ($view = C4_AbstractViewLoader::getView($view_id))) {
			echo json_encode(array(
				'view_name' => $view->name,
				'view_model' => base64_encode(serialize(C4_AbstractViewLoader::serializeAbstractView($view))),
			));
		}
		
		exit;
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
					DAO_Group::NAME,
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
		
		if($active_worker->is_superuser && 0 == strcasecmp($context, 'all')) {
			$view->addParamsRequired(array(), true);
		} else {
			$view->addParamsRequired(array(
				SearchFields_Snippet::OWNER_CONTEXT => new DevblocksSearchCriteria(SearchFields_Snippet::OWNER_CONTEXT, DevblocksSearchCriteria::OPER_EQ, $context),
				SearchFields_Snippet::OWNER_CONTEXT_ID => new DevblocksSearchCriteria(SearchFields_Snippet::OWNER_CONTEXT_ID, DevblocksSearchCriteria::OPER_EQ, $context_id),
			), true);
		}
		
		C4_AbstractViewLoader::setView($view->id,$view);
		$tpl->assign('view', $view);
		
		$tpl->display('devblocks:cerberusweb.core::internal/snippets/index.tpl');
	}	
	
	function showSnippetsPeekAction() {
		@$snippet_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
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

		if(!empty($custom_fields)) {
			$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_SNIPPET, $snippet_id);
			if(isset($custom_field_values[$snippet_id]))
				$tpl->assign('custom_field_values', $custom_field_values[$snippet_id]);
		}
		
		$types = Model_CustomField::getTypes();
		$tpl->assign('types', $types);
		
		// Owners
		$roles = DAO_WorkerRole::getAll();
		$tpl->assign('roles', $roles);
		
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);

		$owner_groups = array();
		foreach($groups as $k => $v) {
			if($active_worker->is_superuser || $active_worker->isGroupManager($k))
				$owner_groups[$k] = $v;
		}
		$tpl->assign('owner_groups', $owner_groups);
		
		$owner_roles = array();
		foreach($roles as $k => $v) { /* @var $v Model_WorkerRole */
			if($active_worker->is_superuser)
				$owner_roles[$k] = $v;
		}
		$tpl->assign('owner_roles', $owner_roles);
		
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
				if($snippet->isWriteableByWorker($active_worker)) {
					DAO_Snippet::delete($id);
				}
			}
			
		} else { // Create || Update
			@list($owner_type, $owner_id) = explode('_', DevblocksPlatform::importGPC($_REQUEST['owner'],'string',''));
		
			switch($owner_type) {
				// Role
				case 'r':
					$owner_context = CerberusContexts::CONTEXT_ROLE;
					$owner_context_id = $owner_id;
					break;
				// Group
				case 'g':
					$owner_context = CerberusContexts::CONTEXT_GROUP;
					$owner_context_id = $owner_id;
					break;
				// Worker
				case 'w':
					$owner_context = CerberusContexts::CONTEXT_WORKER;
					$owner_context_id = $owner_id;
					break;
				// Default
				default:
					$owner_context = null;
					$owner_context_id = null;
					break;
			}
			
			if(empty($owner_context) || empty($owner_context_id)) {
				$owner_context = CerberusContexts::CONTEXT_WORKER;
				$owner_context_id = $active_worker->id;
			}
			
			$fields[DAO_Snippet::OWNER_CONTEXT] = $owner_context;
			$fields[DAO_Snippet::OWNER_CONTEXT_ID] = $owner_context_id;
			
			if(empty($id)) {
				if($active_worker->hasPriv('core.snippets.actions.create')) {
					$id = DAO_Snippet::create($fields);
					
					// Custom field saves
					@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', array());
					DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_SNIPPET, $id, $field_ids);
				} 
				
			} else {
				if(null != ($snippet = DAO_Snippet::get($id))) { /* @var $snippet Model_Snippet */
					if($snippet->isWriteableByWorker($active_worker)) {
						DAO_Snippet::update($id, $fields);
						
						// Custom field saves
						@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', array());
						DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_SNIPPET, $id, $field_ids);
					}
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
			$valid_tokens = $tpl_builder->stripModifiers(array_keys($token_labels));
			
			// Test legal values
			$unknown_tokens = array_diff($tokens, $valid_tokens);
			$matching_tokens = array_intersect($tokens, $valid_tokens);
			
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

	function showSnippetBulkPanelAction() {
		@$ids = DevblocksPlatform::importGPC($_REQUEST['ids']);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id']);

		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker->is_superuser)
			return;
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('view_id', $view_id);

	    if(!empty($ids)) {
	        $id_list = DevblocksPlatform::parseCsvString($ids);
	        $tpl->assign('ids', implode(',', $id_list));
	    }
		
	    $workers = DAO_Worker::getAllActive();
	    $tpl->assign('workers', $workers);
	    
	    $roles = DAO_WorkerRole::getAll();
	    $tpl->assign('roles', $roles);
	    
	    $groups = DAO_Group::getAll();
	    $tpl->assign('groups', $groups);
	    
		// Custom Fields
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_SNIPPET);
		$tpl->assign('custom_fields', $custom_fields);
		
		$tpl->display('devblocks:cerberusweb.core::internal/snippets/bulk.tpl');
	}
	
	function doSnippetBulkUpdateAction() {
		// Filter: whole list or check
	    @$filter = DevblocksPlatform::importGPC($_REQUEST['filter'],'string','');
		$ids = array();
	    
	    // View
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		$view = C4_AbstractViewLoader::getView($view_id);
		
		// Snippet fields
		@$owner = trim(DevblocksPlatform::importGPC($_POST['owner'],'string',''));

		$do = array();
		$active_worker = CerberusApplication::getActiveWorker();
		
		// Do: Due
		if(0 != strlen($owner))
			$do['owner'] = $owner;
			
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
	
	// Views

	function viewRefreshAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		if(null != ($view = C4_AbstractViewLoader::getView($id))) {
			$view->render();
		}
	}

	function viewSortByAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		@$sortBy = DevblocksPlatform::importGPC($_REQUEST['sortBy']);

		if(null != ($view = C4_AbstractViewLoader::getView($id))) {
			$view->doSortBy($sortBy);
			C4_AbstractViewLoader::setView($id, $view);
			$view->render();
		}
	}

	function viewPageAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		@$page = DevblocksPlatform::importGPC(DevblocksPlatform::importGPC($_REQUEST['page']));

		if(null != ($view = C4_AbstractViewLoader::getView($id))) {
			$view->doPage($page);
			C4_AbstractViewLoader::setView($id, $view);
			$view->render();
		}
	}

	function viewGetCriteriaAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		@$field = DevblocksPlatform::importGPC($_REQUEST['field']);

		$tpl = DevblocksPlatform::getTemplateService();
		
		if(null != ($view = C4_AbstractViewLoader::getView($id))) {
			$tpl->assign('view', $view);
			
			// Do we already have this filter to re-edit?
			$params = $view->getEditableParams();
			if(isset($params[$field])) {
				$tpl->assign('param', $params[$field]);
			}
			
			// Render from the View_* implementation.
			$view->renderCriteria($field);
		}
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
		
		if(null != ($view = C4_AbstractViewLoader::getView($id))) {
			$view->renderFilters = !empty($show) ? 1 : 0;
			C4_AbstractViewLoader::setView($view->id, $view);
		}
	}
	
	function viewAddFilterAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		@$is_custom = DevblocksPlatform::importGPC($_REQUEST['is_custom'],'integer',0);

		@$field = DevblocksPlatform::importGPC($_REQUEST['field']);
		@$oper = DevblocksPlatform::importGPC($_REQUEST['oper']);
		@$value = DevblocksPlatform::importGPC($_REQUEST['value']);
		@$field_deletes = DevblocksPlatform::importGPC($_REQUEST['field_deletes'],'array',array());

		if(null == ($view = C4_AbstractViewLoader::getView($id)))
			return;

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

		if(null == ($view = C4_AbstractViewLoader::getView($id)))
			return;

		$view->doResetCriteria();

		C4_AbstractViewLoader::setView($view->id, $view);

		$this->_viewRenderInlineFilters($view);
	}

	function viewLoadPresetAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		@$preset_id = DevblocksPlatform::importGPC($_REQUEST['_preset'],'integer',0);

		if(null == ($view = C4_AbstractViewLoader::getView($id)))
			return;

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

		if(null == ($view = C4_AbstractViewLoader::getView($id)))
			return;
		
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

		if(null != ($view = C4_AbstractViewLoader::getView($id))) {
			DAO_ViewFiltersPreset::delete($preset_dels);
			$this->_viewRenderInlineFilters($view);
		}
	}

	function viewCustomizeAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);

		$active_worker = CerberusApplication::getActiveWorker();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $id);

		if(null == ($view = C4_AbstractViewLoader::getView($id)))
			return;
		
		// Custom worklists
		if('cust_' == substr($view->id,0,5)) {
			try {
				$worklist_id = substr($view->id,5);
				
				if(!is_numeric($worklist_id))
					throw new Exception("Invalid worklist ID.");
				
				if(null == ($worklist = DAO_WorkspaceList::get($worklist_id)))
					throw new Exception("Can't load worklist.");
				
				if(null == ($workspace = DAO_Workspace::get($worklist->workspace_id)))
					throw new Exception("Can't load workspace.");
				
				if(!$workspace->isWriteableByWorker($active_worker)) {
					$tpl->display('devblocks:cerberusweb.core::internal/workspaces/customize_no_acl.tpl');
					return;
				}
				
			} catch(Exception $e) {
				// [TODO] Logger
				return;
			}
		}

		$custom_fields = DAO_CustomField::getAll();
		$tpl->assign('custom_fields', $custom_fields);

		$tpl->assign('view', $view);
		
		$tpl->display('devblocks:cerberusweb.core::internal/views/customize_view.tpl');
	}

	function viewShowCopyAction() {
        @$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');

		$active_worker = CerberusApplication::getActiveWorker();
		$tpl = DevblocksPlatform::getTemplateService();

        if(null == ($view = C4_AbstractViewLoader::getView($view_id)))
        	return;

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
	    
		if(null == ($view = C4_AbstractViewLoader::getView($view_id)))
			return;

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

		if(null == ($view = C4_AbstractViewLoader::getView($view_id)))
			return;
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

		if(null == ($view = C4_AbstractViewLoader::getView($view_id)))
			return;
		
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
					&& !$active_worker->isGroupMember($field->group_id)) {
						unset($columns[$idx]);
						continue;
				}
			}
		}

		if(null == ($view = C4_AbstractViewLoader::getView($id)))
			return;
		
		$view->doCustomize($columns, $num_rows);

		$is_custom = substr($id,0,5)=='cust_';
		$is_trigger = substr($id,0,9)=='_trigger_';
		
		if($is_custom || $is_trigger) {
			@$title = DevblocksPlatform::importGPC($_REQUEST['title'],'string', $translate->_('views.new_list'));
			$view->name = $title;
		}
		
		// Handle worklists specially
		if($is_custom) { // custom workspace
			// Check the custom workspace

			try {
				$list_view_id = intval(substr($id,5));
				
				if(empty($list_view_id))
					throw new Exception("Invalid worklist ID.");
				
				if(null == ($list_model = DAO_WorkspaceList::get($list_view_id)))
					throw new Exception("Can't load worklist.");
				
				if(null == ($workspace = DAO_Workspace::get($list_model->workspace_id)))
					throw new Exception("Can't load workspace.");
				
				if(!$workspace->isWriteableByWorker($active_worker)) {
					throw new Exception("Permission denied to edit workspace.");
				}
				
			} catch(Exception $e) {
				// [TODO] Logger
				$view->render();
				return;
			}
			
			// Persist Object
			$list_view = new Model_WorkspaceListView();
			$list_view->title = $title;
			$list_view->columns = $view->view_columns;
			$list_view->num_rows = $view->renderLimit;
			$list_view->params = array();
			$list_view->params_required = $view->getParamsRequired();
			$list_view->sort_by = $view->renderSortBy;
			$list_view->sort_asc = $view->renderSortAsc;

			DAO_WorkspaceList::update($list_view_id, array(
				DAO_WorkspaceList::LIST_VIEW => serialize($list_view)
			));

			// Syndicate
			$worker_views = DAO_WorkerViewModel::getWhere(sprintf("view_id = %s", C4_ORMHelper::qstr($id)));

			// Update any instances of this view with the new required columns + params
			foreach($worker_views as $worker_view) { /* @var $worker_view C4_AbstractViewModel */
				$worker_view->name = $view->name;
				$worker_view->view_columns = $view->view_columns;
				$worker_view->paramsRequired = $view->getParamsRequired();
				$worker_view->renderLimit = $view->renderLimit;
				DAO_WorkerViewModel::setView($worker_view->worker_id, $worker_view->id, $worker_view);
			}
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

		$tpl = DevblocksPlatform::getTemplateService();
		$active_worker = CerberusApplication::getActiveWorker();

		// Groups
		$tpl->assign('groups', DAO_Group::getAll());
		
		// Roles
		$tpl->assign('roles', DAO_WorkerRole::getAll());
		
		// Workers
		$tpl->assign('workers', DAO_Worker::getAll());
		
		// Endpoint
		$tpl->assign('point', $point);

		// Workspaces
		$enabled_workspaces = DAO_Workspace::getByEndpoint($point, $active_worker);
		$available_workspaces = DAO_Workspace::getByWorker($active_worker);
		$workspaces = $enabled_workspaces + array_diff_key($available_workspaces, $enabled_workspaces);

		$tpl->assign('enabled_workspaces', $enabled_workspaces);
		$tpl->assign('workspaces', $workspaces);

		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/tab.tpl');
	}

	function doAddTabAction() {
		@$point = DevblocksPlatform::importGPC($_REQUEST['point'],'string', '');
		@$workspace_ids = DevblocksPlatform::importGPC($_REQUEST['workspace_ids'],'array', array());
		@$new_workspace = DevblocksPlatform::importGPC($_REQUEST['new_workspace'],'string', '');
		@$point = DevblocksPlatform::importGPC($_REQUEST['point'],'string', '');

		$active_worker = CerberusApplication::getActiveWorker();
		$visit = CerberusApplication::getVisit();
		$is_focused_tab = false;

		// Are we adding any new workspaces?
		foreach($workspace_ids as $idx => $workspace_id) {
			// Only focus the first new tab we add
			if(!empty($point) && !$is_focused_tab) {
				$visit->set($point, 'w_' . $workspace_id);
				$is_focused_tab = true;
			}
		}

		// Replace links for this endpoint
		DAO_Workspace::setEndpointWorkspaces($point, $active_worker->id, $workspace_ids);
		
		exit;
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

		$tpl = DevblocksPlatform::getTemplateService();
		$active_worker = CerberusApplication::getActiveWorker();

		if(!empty($id)) { // Edit
			// Workspace
			if(null == ($workspace = DAO_Workspace::get($id)))
				return;
	
			$tpl->assign('workspace', $workspace);
			
			// Worklist
			$worklists = $workspace->getWorklists();
			$tpl->assign('worklists', $worklists);
		}
		
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);

		$roles = DAO_WorkerRole::getAll();
		$tpl->assign('roles', $roles);
		
		$owner_groups = $groups;
		foreach($groups as $k => $v) {
			if($active_worker->is_superuser || $active_worker->isGroupManager($k))
				$owner_groups[$k] = $v;
		}
		$tpl->assign('owner_groups', $owner_groups);
		
		if($active_worker->is_superuser) {
			$tpl->assign('owner_roles', $roles);
		}
		
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

		$active_worker = CerberusApplication::getActiveWorker();

		if(!empty($workspace_id) && 
			(null == ($workspace = DAO_Workspace::get($workspace_id)) 
			|| !$workspace->isWriteableByWorker($active_worker)
			))
			return;

		if($do_delete) { // Delete
			DAO_Workspace::delete($workspace_id);

		} else { // Create/Edit
			@list($owner_type, $owner_id) = explode('_', DevblocksPlatform::importGPC($_REQUEST['owner'],'string',''));
			
			switch($owner_type) {
				// Group
				case 'g':
					$owner_context = CerberusContexts::CONTEXT_GROUP;
					$owner_context_id = $owner_id;
					break;
				// Role
				case 'r':
					$owner_context = CerberusContexts::CONTEXT_ROLE;
					$owner_context_id = $owner_id;
					break;
				// Worker
				case 'w':
					$owner_context = CerberusContexts::CONTEXT_WORKER;
					$owner_context_id = $owner_id;
					break;
				// Default
				default:
					$owner_context = null;
					$owner_context_id = null;
					break;
			}
					
			if(empty($workspace_id)) {
				if(empty($owner_context) || empty($owner_context_id)) {
					$owner_context = CerberusContexts::CONTEXT_WORKER;
					$owner_context_id = $active_worker->id;
				}
				
				$fields = array(
					DAO_Workspace::NAME => $rename_workspace,
					DAO_Workspace::OWNER_CONTEXT => $owner_context, 
					DAO_Workspace::OWNER_CONTEXT_ID => $owner_context_id,
				);
				$workspace_id = DAO_Workspace::create($fields);
				$workspace = DAO_Workspace::get($workspace_id);
				
			} else {
				$fields = array();
				
				// Rename workspace
				if(0 != strcmp($workspace->name, $rename_workspace)) {
					$fields[DAO_Workspace::NAME] = $rename_workspace;
				}
				
				if(!empty($owner_context)) {
					$fields[DAO_Workspace::OWNER_CONTEXT] = $owner_context;
					$fields[DAO_Workspace::OWNER_CONTEXT_ID] = $owner_context_id;
				}

				if(!empty($fields))
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

		exit;
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
			
			// ACL: Ensure the worker has access to the macro
			switch($macro->owner_context) {
				case CerberusContexts::CONTEXT_WORKER:
					if($macro->owner_context_id != $active_worker->id)
						throw new Exception("Access denied to macro.");
					break;
				case CerberusContexts::CONTEXT_GROUP:
					if(!$active_worker->isGroupMember($macro->owner_context_id))
						throw new Exception("Access denied to macro.");
					break;
			}
			
			// Load event manifest
			if(null == ($ext = DevblocksPlatform::getExtension($macro->event_point, false))) /* @var $ext DevblocksExtensionManifest */
				throw new Exception("Invalid event.");

			$run_timestamp = @strtotime($run_date) or time();

			// Variables
			@$var_keys = DevblocksPlatform::importGPC($_REQUEST['var_keys'],'array',array());
			@$var_vals = DevblocksPlatform::importGPC($_REQUEST['var_vals'],'array',array());

			$vars = DAO_ContextScheduledBehavior::buildVariables($var_keys, $var_vals, $macro);			
			
			if($run_timestamp > time()) {
				DAO_ContextScheduledBehavior::create(array(
					DAO_ContextScheduledBehavior::BEHAVIOR_ID => $macro->id,
					DAO_ContextScheduledBehavior::CONTEXT => $context,
					DAO_ContextScheduledBehavior::CONTEXT_ID => $context_id,
					DAO_ContextScheduledBehavior::RUN_DATE => $run_timestamp,
					DAO_ContextScheduledBehavior::VARIABLES_JSON => json_encode($vars),
				));
				
			} else {
				// Execute now
				call_user_func(array($ext->class, 'trigger'), $macro->id, $context_id, $vars);
				
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

			if(null == $job)
				return;
			
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
		
		if(null == ($trigger = DAO_TriggerEvent::get($job->behavior_id)))
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
			
			// Variables
			@$var_keys = DevblocksPlatform::importGPC($_REQUEST['var_keys'],'array',array());
			@$var_vals = DevblocksPlatform::importGPC($_REQUEST['var_vals'],'array',array());
			
			$vars = DAO_ContextScheduledBehavior::buildVariables($var_keys, $var_vals, $trigger);			
			
			DAO_ContextScheduledBehavior::update($job->id, array(
				DAO_ContextScheduledBehavior::RUN_DATE => $run_timestamp,
				DAO_ContextScheduledBehavior::VARIABLES_JSON => json_encode($vars), 
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

		$triggers_by_event = array();
		
		foreach($triggers as $trigger) {
			if(!isset($triggers_by_event[$trigger->event_point]))
				$triggers_by_event[$trigger->event_point] = array();
			
			$triggers_by_event[$trigger->event_point][$trigger->id] = $trigger;
		}
		
		$tpl->assign('triggers_by_event', $triggers_by_event);
		
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
	
	function reorderTriggersAction() {
		@$trigger_ids = DevblocksPlatform::importGPC($_REQUEST['trigger_id'], 'array', array());
		
		$trigger_ids = DevblocksPlatform::sanitizeArray($trigger_ids, 'integer');

		DAO_TriggerEvent::setTriggersOrder($trigger_ids);
		
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
					$tpl->assign('conditions', $ext->getConditions($trigger));
					
				$tpl->display('devblocks:cerberusweb.core::internal/decisions/editors/outcome.tpl');
				break;
				
			case 'action':
				if(null != ($evt = $trigger->getEvent()))
					$tpl->assign('actions', $evt->getActions($trigger));
					
				// Workers
				$tpl->assign('workers', DAO_Worker::getAll());
				
				// Action labels
				$labels = $evt->getLabels($trigger);
				$tpl->assign('labels', $labels);

				// Template
				$tpl->display('devblocks:cerberusweb.core::internal/decisions/editors/action.tpl');
				break;
				
			case 'trigger':
				if(!empty($trigger)) {
					$ext = DevblocksPlatform::getExtension($trigger->event_point, false);
					$tpl->assign('ext', $ext);
				}
				
				// Contexts that can show up in VA vars
				$list_contexts = Extension_DevblocksContext::getAll(false, 'va_variable');
				$tpl->assign('list_contexts', $list_contexts);
				
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

	function showBehaviorSimulatorPopupAction() {
		@$trigger_id = DevblocksPlatform::importGPC($_REQUEST['trigger_id'],'integer', 0);
		
		$tpl = DevblocksPlatform::getTemplateService();

		if(null == ($trigger = DAO_TriggerEvent::get($trigger_id)))
			return;
		
		$tpl->assign('trigger', $trigger);

		if(null == ($ext_event = DevblocksPlatform::getExtension($trigger->event_point, true))) /* @var $ext_event Extension_DevblocksEvent */
			return;
		
		$event_model = $ext_event->generateSampleEventModel();
		
		$ext_event->setEvent($event_model);
		
		$event_params_json = json_encode($event_model->params);
		$tpl->assign('event_params_json', $event_params_json);

		$labels = $ext_event->getLabels($trigger);
		$values = $ext_event->getValues();

		$conditions = $ext_event->getConditions($trigger);
		
		$dictionary = array();
		
		foreach($conditions as $k => $v) {
			if(isset($values[$k])) {
				$dictionary[$k] = array(
					'label' => $v['label'],
					'type' => $v['type'],
					'value' => $values[$k],
				);
			}
		}
		
		$tpl->assign('dictionary', $dictionary);
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/editors/simulate.tpl');
	}
	
	function runBehaviorSimulatorAction() {
		@$trigger_id = DevblocksPlatform::importGPC($_POST['trigger_id'],'integer', 0);
		@$event_params_json = DevblocksPlatform::importGPC($_POST['event_params_json'],'string', '');
		@$custom_values = DevblocksPlatform::importGPC($_POST['values'],'array', array());
		
		$tpl = DevblocksPlatform::getTemplateService();
		
		$tpl->assign('trigger_id', $trigger_id);
		
		if(null == ($trigger = DAO_TriggerEvent::get($trigger_id)))
			return;
		
		$tpl->assign('trigger', $trigger);
		
 		if(null == ($ext_event = DevblocksPlatform::getExtension($trigger->event_point, true))) /* @var $ext_event Extension_DevblocksEvent */
 			return;

 		// Reconstruct the event scope
 		
 		$event_model = new Model_DevblocksEvent();
 		$event_model->id = $trigger->event_point;
 		$event_model_params = json_decode($event_params_json, true);
 		$event_model->params = is_array($event_model_params) ? $event_model_params : array();
 		$ext_event->setEvent($event_model);

 		$tpl->assign('event', $ext_event);
 		
 		// Merge baseline values with user overrides
 		
 		$values = $ext_event->getValues();
 		$values = array_merge($values, $custom_values);
 		
 		// Get conditions
 		
 		$conditions = $ext_event->getConditions($trigger);
 		
 		// Sanitize values
 		
 		foreach($values as $k => $v) {
 			if(
 				(isset($conditions[$k]) && $conditions[$k]['type'] == Model_CustomField::TYPE_DATE)
 				|| $k == '_current_time'
 			)
 				$values[$k] = strtotime($v);
 		} 		
 		
 		// Behavior data

		$behavior_data = $trigger->getDecisionTreeData();
		$tpl->assign('behavior_data', $behavior_data);
		
		$behavior_path = $trigger->runDecisionTree($values, true);
		$tpl->assign('behavior_path', $behavior_path);
		
		if(isset($values['_simulator_output']))
			$tpl->assign('simulator_output', $values['_simulator_output']);
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/simulator/results.tpl');
	}
	
	function showScheduleBehaviorParamsAction() {
		@$name_prefix = DevblocksPlatform::importGPC($_REQUEST['name_prefix'],'string', '');
		@$trigger_id = DevblocksPlatform::importGPC($_REQUEST['trigger_id'],'integer', 0);

		$tpl = DevblocksPlatform::getTemplateService();
		
		$tpl->assign('namePrefix', $name_prefix);
		
		$trigger = DAO_TriggerEvent::get($trigger_id);
		$tpl->assign('macro_params', $trigger->variables);
		
		$tpl->display('devblocks:cerberusweb.core::events/action_schedule_behavior_params.tpl');
	}
	
	function showScheduleBehaviorBulkParamsAction() {
		@$trigger_id = DevblocksPlatform::importGPC($_REQUEST['trigger_id'],'integer', 0);

		$tpl = DevblocksPlatform::getTemplateService();
		
		$trigger = DAO_TriggerEvent::get($trigger_id);
		$tpl->assign('macro_params', $trigger->variables);
		
		$tpl->display('devblocks:cerberusweb.core::internal/macros/behavior/bulk_params.tpl');
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

			@$var_keys = DevblocksPlatform::importGPC($_REQUEST['var_key'],'array',array());
			@$var_types = DevblocksPlatform::importGPC($_REQUEST['var_type'],'array',array());
			@$var_labels = DevblocksPlatform::importGPC($_REQUEST['var_label'],'array',array());
			@$var_is_private = DevblocksPlatform::importGPC($_REQUEST['var_is_private'],'array',array());
			
			$variables = array();
			
			foreach($var_labels as $idx => $v) {
				if(empty($var_labels[$idx]))
					continue;
				
				$var_name = 'var_' . DevblocksPlatform::strAlphaNum(DevblocksPlatform::strToPermalink($v),'_');
				$key = strtolower(!empty($var_keys[$idx]) ? $var_keys[$idx] : $var_name);
				$variables[$key] = array(
					'key' => $key,
					'label' => $v,
					'type' => $var_types[$idx],
					'is_private' => $var_is_private[$idx],
				);
			}
			
			// Create trigger
			if(empty($trigger_id)) {
				@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string', '');
				@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer', 0);
				@$event_point = DevblocksPlatform::importGPC($_REQUEST['event_point'],'string', '');
				
				$type = 'trigger';
				
				$pos = DAO_TriggerEvent::getNextPosByOwnerAndEvent($context, $context_id, $event_point);
				
				$trigger_id = DAO_TriggerEvent::create(array(
					DAO_TriggerEvent::OWNER_CONTEXT => $context,
					DAO_TriggerEvent::OWNER_CONTEXT_ID => $context_id,
					DAO_TriggerEvent::EVENT_POINT => $event_point,
					DAO_TriggerEvent::TITLE => $title,
					DAO_TriggerEvent::IS_DISABLED => !empty($is_disabled) ? 1 : 0,
					DAO_TriggerEvent::POS => $pos,
					DAO_TriggerEvent::VARIABLES_JSON => json_encode($variables),
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
					
					// Handle deletes
					if(is_array($trigger->variables))
					foreach($trigger->variables as $var => $data) {
						if(!isset($variables[$var])) {
							DAO_DecisionNode::deleteTriggerVar($trigger->id, $var);
						}
					}
					
					DAO_TriggerEvent::update($trigger->id, array(
						DAO_TriggerEvent::TITLE => $title,
						DAO_TriggerEvent::IS_DISABLED => !empty($is_disabled) ? 1 : 0,
						DAO_TriggerEvent::VARIABLES_JSON => json_encode($variables),
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
	
	// Scheduled Behavior
	
	function showScheduledBehaviorTabAction() {
		Subcontroller_Internal_VirtualAttendants::showScheduledBehaviorAction();
	}	
	
	// Calendars
	
	function showCalendarTabAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer');
		@$point = DevblocksPlatform::importGPC($_REQUEST['point'],'string','');
		@$month = DevblocksPlatform::importGPC($_REQUEST['month'],'integer', 0);
		@$year = DevblocksPlatform::importGPC($_REQUEST['year'],'integer', 0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();

		if(!empty($point))
			$visit->set($point, 'calendar');
		
		// [TODO] Validate month/year
		
		// [TODO] gmdate + gmmktime?

		if(empty($month) || empty($year)) {
			$month = date('m');
			$year = date('Y');
		}
		
		$calendar_date = mktime(0,0,0,$month,1,$year);
		
		$num_days = date('t', $calendar_date);
		$first_dow = date('w', $calendar_date);
		
		$prev_month_date = mktime(0,0,0,$month,0,$year);
		$prev_month = date('m', $prev_month_date);
		$prev_year = date('Y', $prev_month_date);

		$next_month_date = mktime(0,0,0,$month+1,1,$year);
		$next_month = date('m', $next_month_date);
		$next_year = date('Y', $next_month_date);
		
		$days = array();

		for($day = 1; $day <= $num_days; $day++) {
			$timestamp = mktime(0,0,0,$month,$day,$year);
			
			$days[$timestamp] = array(
				'dom' => $day,
				'dow' => (($first_dow+$day-1) % 7),
				'is_padding' => false,
				'timestamp' => $timestamp,
			);
		}
		
		// How many cells do we need to pad the first and last weeks?
		$first_day = reset($days);
		$left_pad = $first_day['dow'];
		$last_day = end($days);
		$right_pad = 6-$last_day['dow'];

		$calendar_cells = $days;
		
		if($left_pad > 0) {
			$prev_month_days = date('t', $prev_month_date);
			
			for($i=1;$i<=$left_pad;$i++) {
				$dom = $prev_month_days - ($i-1);
				$timestamp = mktime(0,0,0,$prev_month,$dom,$prev_year);
				$day = array(
					'dom' => $dom,
					'dow' => $first_dow - $i,
					'is_padding' => true,
					'timestamp' => $timestamp,
				);
				$calendar_cells[$timestamp] = $day;
				//array_unshift($calendar_cells, $day);
			}
		}
		
		if($right_pad > 0) {
			for($i=1;$i<=$right_pad;$i++) {
				$timestamp = mktime(0,0,0,$next_month,$i,$next_year);
				
				$day = array(
					'dom' => $i,
					'dow' => (($first_dow + $num_days + $i - 1) % 7),
					'is_padding' => true,
					'timestamp' => $timestamp,
				);
				$calendar_cells[$timestamp] = $day;
				//array_push($calendar_cells, $day);
			}
		}
		
		// Sort calendar
		ksort($calendar_cells);
		
		// Break into weeks
		$calendar_weeks = array_chunk($calendar_cells, 7, true);

		// Events
		$range_from = array_shift(array_slice($calendar_cells, 0, 1, false));
		$range_to = array_shift(array_slice($calendar_cells, -1, 1, false));
		
		unset($days);
		unset($calendar_cells);
		
		// [TODO] Convert to DAO
		$db = DevblocksPlatform::getDatabaseService();
		$sql = sprintf(
			"SELECT id, name, recurring_id, is_available, date_start, date_end ".
			"FROM calendar_event ".
			"WHERE owner_context = %s ".
			"AND owner_context_id = %d ".
			"AND date_start >= %d AND date_end <= %d ".
			"ORDER BY is_available DESC, date_start ASC",
			$db->qstr($context),
			$context_id,
			strtotime('00:00', $range_from['timestamp']),
			strtotime('23:59', $range_to['timestamp'])
		);
		$results = $db->GetArray($sql);

		$calendar_events = array();
		
		foreach($results as $row) {
			$day_range = range(strtotime('midnight', $row['date_start']), strtotime('midnight', $row['date_end']), 86400);
			
			foreach($day_range as $epoch) {
				if(!isset($calendar_events[$epoch]))
					$calendar_events[$epoch] = array();
				
				$calendar_events[$epoch][$row['id']] = array(
					'id' => $row['id'],
					'name' => $row['name'],
					'is_available' => $row['is_available'],
				);
			}
		}

		// Template scope
		$tpl->assign('context', $context);
		$tpl->assign('context_id', $context_id);
		$tpl->assign('today', mktime(0,0,0));
		$tpl->assign('prev_month', $prev_month);
		$tpl->assign('prev_year', $prev_year);
		$tpl->assign('next_month', $next_month);
		$tpl->assign('next_year', $next_year);
		$tpl->assign('calendar_date', $calendar_date);
		$tpl->assign('calendar_weeks', $calendar_weeks);
		$tpl->assign('calendar_events', $calendar_events);
		
		$tpl->display('devblocks:cerberusweb.core::internal/calendar/tab.tpl');
	}
	
	function showCalendarEventPopupAction() {
		@$event_id = DevblocksPlatform::importGPC($_REQUEST['event_id'],'integer', 0);
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('context', $context);
		$tpl->assign('context_id', $context_id);

		// [TODO] Check calendar+event ownership
		
		if(!empty($event_id)) {
			if(null != ($event = DAO_CalendarEvent::get($event_id))) {  /* @var $event Model_CalendarEvent */
				$tpl->assign('event', $event);
				
				if(!empty($event->recurring_id)) {
					if(null != ($recurring_profile = DAO_CalendarRecurringProfile::get($event->recurring_id))) {
						$tpl->assign('recurring', $recurring_profile);
					}
				}
			}
		}
		
		if(empty($event_id) || is_null($event)) {
			$event = new Model_CalendarEvent();
			$event->id = 0;
			$event->owner_context = $context;
			$event->owner_context_id = $context_id;
			$event->is_available = 0;
			$event->is_recurring = 0;
			$tpl->assign('event', $event);
		}
		
		$tpl->display('devblocks:cerberusweb.core::internal/calendar/event_popup.tpl');
	}
	
	function saveCalendarEventPopupJsonAction() {
		@$event_id = DevblocksPlatform::importGPC($_REQUEST['event_id'],'integer', 0);
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer');
		@$name = DevblocksPlatform::importGPC($_REQUEST['name'],'string', '');
		@$date_start = DevblocksPlatform::importGPC($_REQUEST['date_start'],'string', '');
		@$date_end = DevblocksPlatform::importGPC($_REQUEST['date_end'],'string', '');
		@$is_available = DevblocksPlatform::importGPC($_REQUEST['is_available'],'integer', 0);
		@$repeat_freq = DevblocksPlatform::importGPC($_REQUEST['repeat_freq'],'string', '');
		@$repeat_end = DevblocksPlatform::importGPC($_REQUEST['repeat_end'],'string', '');
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'],'integer', 0);

		/*
		 * [TODO] When deleting a recurring profile, ask about deleting its children (this/all)
		 */

		header("Content-type: application/json");
		
		// Start/end times

		@$timestamp_start = strtotime($date_start);
		
		if(empty($timestamp_start))
			$timestamp_start = time();
		
		@$timestamp_end = strtotime($date_end, $timestamp_start);

		if(empty($timestamp_end))
			$timestamp_end = $timestamp_start;
			
		// If the second timestamp is smaller, add a day
		if($timestamp_end < $timestamp_start)
			$timestamp_end = strtotime("+1 day", $timestamp_end);
		
		// Recurring events
		
		$event = null;
		$recurring_id = 0;
		
		if(!empty($event_id)) {
			if(null != ($event = DAO_CalendarEvent::get($event_id)))
				$recurring_id = $event->recurring_id;
		}
		
		// Delete
		
		if(!empty($do_delete) && !empty($event_id)) {
			if(!empty($recurring_id)) {
				@$delete_scope = DevblocksPlatform::importGPC($_REQUEST['delete_scope'],'string', '');
				
				switch($delete_scope) {
					case 'future':
					case 'all':
						$starting_date = ($delete_scope == 'future') ? $timestamp_start : 0;
						
						DAO_CalendarEvent::deleteByRecurringIds($recurring_id, $starting_date);
						
						// Remove recurring profile
						DAO_CalendarRecurringProfile::delete($recurring_id);
						
						// Removing recurring profile from remaining events (like deleting it, but not existing events)
						DAO_CalendarEvent::updateWhere(
							array(
								DAO_CalendarEvent::RECURRING_ID => 0,
							),
							sprintf("%s = %d",
								DAO_CalendarEvent::RECURRING_ID,
								$recurring_id
							)
						);
						break;
				}
			}
			
			DAO_CalendarEvent::delete($event_id);
			
			echo json_encode(array(
				'event_id' => intval($event_id),
				'action' => 'delete',
			));			
			return;
		}
		
		// Recurring
		
		if(!empty($repeat_freq)) {
			@$repeat_options = DevblocksPlatform::importGPC($_REQUEST['repeat_options'][$repeat_freq], 'array', array());			
			@$repeat_ends = DevblocksPlatform::importGPC($_REQUEST['repeat_ends'][$repeat_end], 'array', array());

			switch($repeat_end) {
				case 'date':
					if(isset($repeat_ends['on'])) {
						$repeat_ends['on'] = strtotime("11:59pm", @strtotime($repeat_ends['on'], $timestamp_start));
					}
					break;
			}
			
			$params = array(
				'freq' => $repeat_freq,
				'options' => $repeat_options,
				'end' => array(
					'term' => $repeat_end,
					'options' => $repeat_ends,
				),
			);
			
		 	/* 
		 	 * Create recurring profile if this is a new event, otherwise modify the 
		 	 * existing one and all associated events.
		 	 */
			
			$recurring_has_changed = true;
			
			if(empty($recurring_id)) {
				$fields = array(
					DAO_CalendarRecurringProfile::EVENT_NAME => $name,
					DAO_CalendarRecurringProfile::IS_AVAILABLE => (!empty($is_available)) ? 1 : 0,
					DAO_CalendarRecurringProfile::DATE_START => $timestamp_start,
					DAO_CalendarRecurringProfile::DATE_END => $timestamp_end,
					DAO_CalendarRecurringProfile::OWNER_CONTEXT => $context,
					DAO_CalendarRecurringProfile::OWNER_CONTEXT_ID => $context_id,
					DAO_CalendarRecurringProfile::PARAMS_JSON => json_encode($params),
				);
				$recurring_id = DAO_CalendarRecurringProfile::create($fields);
				
			} else {
				@$edit_scope = DevblocksPlatform::importGPC($_REQUEST['edit_scope'],'string', 'future');

				if($edit_scope == 'this') {
					$recurring_has_changed = false;
				}
				
				if(null == ($recurring = DAO_CalendarRecurringProfile::get($recurring_id))) {
					$recurring_has_changed = false;
				}
				
				// Modify all events, or just this one?
				if(!$recurring_has_changed) {
					// Unassign the recurring profile
					$recurring_id = 0;
					
				} else {
					// Delete other events
					DAO_CalendarEvent::delete($event_id);
					DAO_CalendarEvent::deleteByRecurringIds($recurring_id, $timestamp_start);
					$prior_recurring_events = DAO_CalendarEvent::countByRecurringId($recurring_id);
					
					// If we're modifying the recurring profile, branch the profile (past + future)
					// Otherwise just edit the same recurring profile with the new details
					if($prior_recurring_events) {
						// We're closing out an old recurring profile
						$options = $recurring->params;
						
						// Set the end date of the old recurring profile to just before the new one
						if(isset($options['end']))
							unset($options['end']);
						$options['end'] = array(
							'term' => 'date',
							'options' => array(
								'on' => strtotime('yesterday 11:59pm', $timestamp_start),
							),
						);
						
						// Close out the old recurring profile
						$fields = array(
							DAO_CalendarRecurringProfile::PARAMS_JSON => json_encode($options),
						);
						DAO_CalendarRecurringProfile::update($recurring_id, $fields);						
						
						// Create the new recurring profile
						$fields = array(
							DAO_CalendarRecurringProfile::EVENT_NAME => $name,
							DAO_CalendarRecurringProfile::IS_AVAILABLE => (!empty($is_available)) ? 1 : 0,
							DAO_CalendarRecurringProfile::DATE_START => $timestamp_start,
							DAO_CalendarRecurringProfile::DATE_END => $timestamp_end,
							DAO_CalendarRecurringProfile::OWNER_CONTEXT => $context,
							DAO_CalendarRecurringProfile::OWNER_CONTEXT_ID => $context_id,
							DAO_CalendarRecurringProfile::PARAMS_JSON => json_encode($params),
						);
						$recurring_id = DAO_CalendarRecurringProfile::create($fields);
						
					} else {
						$fields = array(
							DAO_CalendarRecurringProfile::EVENT_NAME => $name,
							DAO_CalendarRecurringProfile::IS_AVAILABLE => (!empty($is_available)) ? 1 : 0,
							DAO_CalendarRecurringProfile::DATE_START => $timestamp_start,
							DAO_CalendarRecurringProfile::DATE_END => $timestamp_end,
							DAO_CalendarRecurringProfile::PARAMS_JSON => json_encode($params),
						);
						DAO_CalendarRecurringProfile::update($recurring_id, $fields);
					}
				}
				
			}

			if($recurring_has_changed) {
				if(null != ($recurring = DAO_CalendarRecurringProfile::get($recurring_id)))
					$recurring->createRecurringEvents($timestamp_start);
				
				echo json_encode(array(
					'action' => 'recurring',
					'month' => intval(date('m', $timestamp_start)),
					'year' => intval(date('Y', $timestamp_start)),
				));
				return;
			}
		}
		
		// Fields
		
		$fields = array(
			DAO_CalendarEvent::NAME => $name,
			DAO_CalendarEvent::RECURRING_ID => $recurring_id,
			DAO_CalendarEvent::DATE_START => $timestamp_start,
			DAO_CalendarEvent::DATE_END => $timestamp_end,
			DAO_CalendarEvent::IS_AVAILABLE => (!empty($is_available)) ? 1 : 0,
		);
		
		if(empty($event_id)) {
			$fields[DAO_CalendarEvent::OWNER_CONTEXT] = $context;
			$fields[DAO_CalendarEvent::OWNER_CONTEXT_ID] = $context_id;
			$event_id = DAO_CalendarEvent::create($fields);
		} else {
			DAO_CalendarEvent::update($event_id, $fields);
		}
		
		echo json_encode(array(
			'event_id' => intval($event_id),
			'action' => 'modify',
			'month' => intval(date('m', $timestamp_start)),
			'year' => intval(date('Y', $timestamp_start)),
		));
		return;
	}
	
	// Utils

	function transformMarkupToHTMLAction() {
		@$format = DevblocksPlatform::importGPC($_REQUEST['format'],'string', '');
		@$data = DevblocksPlatform::importGPC($_REQUEST['data'],'string', '');

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
