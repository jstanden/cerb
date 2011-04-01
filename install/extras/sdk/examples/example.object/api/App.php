<?php
class Page_ExampleObjects extends CerberusPageExtension {
	const VIEW_ID = 'example_objects';
	
	function isVisible() {
		// The current session must be a logged-in worker to use this page.
		if(null == ($worker = CerberusApplication::getActiveWorker()))
			return false;
		return true;
	}
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$translate = DevblocksPlatform::getTranslationService();
		
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		
		@array_shift($stack); // example_objects
		@$arg = array_shift($stack); // profiles
		
		switch($arg) {
			case 'profile':
				@$id = array_shift($stack);

				if(null == ($model = DAO_ExampleObject::get($id)))
					return;
					
				$tpl->assign('model', $model);
				
				$tpl->display('devblocks:example.object::page/profile/index.tpl');
				break;
			
			default:
				$defaults = new C4_AbstractViewModel();
				$defaults->class_name = 'View_ExampleObject';
				$defaults->id = self::VIEW_ID;
				$defaults->renderSortBy = SearchFields_ExampleObject::CREATED;
				$defaults->renderSortAsc = 0;
				$defaults->paramsDefault = array(
				);
				
				if(null == ($view = C4_AbstractViewLoader::getView(self::VIEW_ID, $defaults))) {
					$view->name = $translate->_('example.object.common.objects');
					C4_AbstractViewLoader::setView($view->id, $view);
				}
		
				$tpl->assign('view', $view);
				
				$tpl->display('devblocks:example.object::page/index.tpl');
				break;
		}
	}
	
	function showEntryPopupAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('view_id', $view_id);
		
		if(null != ($model = DAO_ExampleObject::get($id)))
			$tpl->assign('model', $model);
		
		$custom_fields = DAO_CustomField::getByContext(Context_ExampleObject::ID);
		$tpl->assign('custom_fields', $custom_fields);
		
		if(!empty($id)) {
			$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds(Context_ExampleObject::ID, $id);
			if(isset($custom_field_values[$id]))
				$tpl->assign('custom_field_values', $custom_field_values[$id]);
		}

		// Comments
		$comments = DAO_Comment::getByContext(Context_ExampleObject::ID, $id);
		$last_comment = array_shift($comments);
		unset($comments);
		$tpl->assign('last_comment', $last_comment);
		
		$tpl->display('devblocks:example.object::example_object/peek.tpl');
	}
	
	function saveEntryPopupAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$name = DevblocksPlatform::importGPC($_REQUEST['name'],'string','');
		@$comment = DevblocksPlatform::importGPC($_REQUEST['comment'],'string','');
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'],'integer',0);
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		$fields = array(
			DAO_ExampleObject::NAME => $name,
		);
		
		// Delete
		if(!empty($id) && !empty($do_delete)) {
			DAO_ExampleObject::delete($id);
			return;
		}
		
		// Update
		if(!empty($id)) {
			DAO_ExampleObject::update($id, $fields);
			
		// Create
		} else {
			$id = DAO_ExampleObject::create($fields);
			
			@$is_watcher = DevblocksPlatform::importGPC($_REQUEST['is_watcher'],'integer',0);
			if($is_watcher)
				CerberusContexts::addWatchers(Context_ExampleObject::ID, $id, $active_worker->id);
		}
		
		// If we're adding a comment
		if(!empty($comment)) {
			$fields = array(
				DAO_Comment::CREATED => time(),
				DAO_Comment::CONTEXT => Context_ExampleObject::ID,
				DAO_Comment::CONTEXT_ID => $id,
				DAO_Comment::COMMENT => $comment,
				DAO_Comment::ADDRESS_ID => $active_worker->getAddress()->id,
			);
			$comment_id = DAO_Comment::create($fields);

			// Notifications
			@$notify_worker_ids = DevblocksPlatform::importGPC($_REQUEST['notify_worker_ids'],'array',array());
			
			$notify_worker_ids = array_merge(
				$notify_worker_ids,
				array_keys(CerberusContexts::getWatchers(Context_ExampleObject::ID, $id))
			);
			$notify_worker_ids = array_diff( // Remove ourselves
				$notify_worker_ids,
				array($active_worker->id)
			);

			if(!empty($notify_worker_ids)) {
				DAO_Comment::triggerCommentNotifications(
					Context_ExampleObject::ID,
					$id,
					$active_worker,
					$notify_worker_ids
				);
			}
		}		
		
		// Custom fields
		@$field_ids = DevblocksPlatform::importGPC($_REQUEST['field_ids'], 'array', array());
		DAO_CustomFieldValue::handleFormPost(Context_ExampleObject::ID, $id, $field_ids);
	}
	
	function showBulkUpdatePopupAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		@$ids = DevblocksPlatform::importGPC($_REQUEST['ids'],'string','');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('view_id', $view_id);
		
	    if(!empty($ids)) {
	        $id_list = DevblocksPlatform::parseCsvString($ids);
	        $tpl->assign('ids', implode(',', $id_list));
	    }

		// Custom Fields
		$custom_fields = DAO_CustomField::getByContext(Context_ExampleObject::ID);
		$tpl->assign('custom_fields', $custom_fields);
	    
		$tpl->display('devblocks:example.object::example_object/bulk.tpl');
	}
	
	function saveBulkUpdatePopupAction() {
		// Filter: whole list or check
	    @$filter = DevblocksPlatform::importGPC($_REQUEST['filter'],'string','');
		$ids = array();
	    
	    // View
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		$view = C4_AbstractViewLoader::getView($view_id);
		
		// Fields
		//$is_closed = trim(DevblocksPlatform::importGPC($_POST['is_closed'],'string',''));

		$do = array();
		
		// Do: ...
		//if(0 != strlen($is_closed))
		//	$do['is_closed'] = !empty($is_closed) ? 1 : 0;
			
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
	
	function viewExploreAction() {
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
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->write('c=example.objects', true),
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
					'url' => $url_writer->write(sprintf("c=example.objects&p=profile&id=%d", $row[SearchFields_ExampleObject::ID]), true),
				);
				$models[] = $model; 
			}
			
			DAO_ExplorerSet::createFromModels($models);
			
			$view->renderPage++;
			
		} while(!empty($results));
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('explore',$hash,$orig_pos)));		
	}
};
