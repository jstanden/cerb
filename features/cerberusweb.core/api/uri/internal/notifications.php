<?php /** @noinspection PhpUnused */

/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2019, Webgroup Media LLC
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

class PageSection_InternalNotifications extends Extension_PageSection {
	function render() {}
	
	public function handleActionForPage(string $action, string $scope=null) {
		if('internalAction' == $scope) {
			switch ($action) {
				case 'redirectRead':
					return $this->_internalAction_redirectRead();
				case 'showBulkUpdatePanel':
					return $this->_internalAction_showBulkUpdatePanel();
				case 'startBulkUpdateJson':
					return $this->_internalAction_startBulkUpdateJson();
				case 'viewMarkRead':
					return $this->_internalAction_viewMarkRead();
				case 'viewExplore':
					return $this->_internalAction_viewExplore();
			}
		}
		return false;
	}
	
	/**
	 * Open an event, mark it read, and redirect to its URL.
	 * Used by Home->Notifications view.
	 *
	 */
	private function _internalAction_redirectRead() {
		$active_worker = CerberusApplication::getActiveWorker();
		$request = DevblocksPlatform::getHttpRequest();
		
		$stack = $request->path;
		array_shift($stack); // internal
		array_shift($stack); // redirectRead
		@$id = array_shift($stack); // id
		
		if(null == ($notification = DAO_Notification::get($id))) {
			DevblocksPlatform::redirectURL('');
			DevblocksPlatform::exit();
		}
		
		if(!Context_Notification::isReadableByActor($notification, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		switch($notification->context) {
			case '':
			case CerberusContexts::CONTEXT_APPLICATION:
			case CerberusContexts::CONTEXT_CUSTOM_FIELD:
			case CerberusContexts::CONTEXT_CUSTOM_FIELDSET:
			case CerberusContexts::CONTEXT_MESSAGE:
			case CerberusContexts::CONTEXT_WORKSPACE_PAGE:
			case CerberusContexts::CONTEXT_WORKSPACE_TAB:
			case CerberusContexts::CONTEXT_WORKSPACE_WIDGET:
			case CerberusContexts::CONTEXT_WORKSPACE_WORKLIST:
				// Mark as read before we redirect
				if(empty($notification->is_read)) {
					DAO_Notification::update($id, array(
						DAO_Notification::IS_READ => 1
					));
					
					DAO_Notification::clearCountCache($active_worker->id);
				}
				break;
		}
		
		DevblocksPlatform::redirectURL($notification->getURL());
	}
	
	private function _internalAction_showBulkUpdatePanel() {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker->hasPriv(sprintf('contexts.%s.update.bulk', Context_Notification::ID)))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		@$ids = DevblocksPlatform::importGPC($_REQUEST['ids']);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id']);
		
		$tpl->assign('view_id', $view_id);
		
		if(!empty($ids)) {
			$id_list = DevblocksPlatform::parseCsvString($ids);
			$tpl->assign('ids', implode(',', $id_list));
		}
		
		$tpl->display('devblocks:cerberusweb.core::internal/notifications/bulk.tpl');
	}
	
	private function _internalAction_startBulkUpdateJson() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		if(!$active_worker->hasPriv(sprintf('contexts.%s.update.bulk', Context_Notification::ID)))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		// Filter: whole list or check
		@$filter = DevblocksPlatform::importGPC($_POST['filter'],'string','');
		$ids = array();
		
		// View
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string');
		$view = C4_AbstractViewLoader::getView($view_id);
		$view->setAutoPersist(false);
		
		// Task fields
		$is_read = trim(DevblocksPlatform::importGPC($_POST['is_read'],'string',''));
		
		$do = array();
		
		// Do: Mark Read
		if(0 != strlen($is_read))
			$do['is_read'] = $is_read;
		
		switch($filter) {
			// Checked rows
			case 'checks':
				@$ids_str = DevblocksPlatform::importGPC($_POST['ids'],'string');
				$ids = DevblocksPlatform::parseCsvString($ids_str);
				break;
			
			case 'sample':
				@$sample_size = min(DevblocksPlatform::importGPC($_POST['filter_sample_size'],'integer',0),9999);
				$filter = 'checks';
				$ids = $view->getDataSample($sample_size);
				break;
			default:
				break;
		}
		
		// If we have specific IDs, add a filter for those too
		if(!empty($ids)) {
			$view->addParam(new DevblocksSearchCriteria(SearchFields_Notification::ID, 'in', $ids));
		}
		
		// Create batches
		$batch_key = DAO_ContextBulkUpdate::createFromView($view, $do);
		
		header('Content-Type: application/json; charset=utf-8');
		
		echo json_encode(array(
			'cursor' => $batch_key,
		));
		
		return;
	}
	
	private function _internalAction_viewMarkRead() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string');
		@$row_ids = DevblocksPlatform::importGPC($_POST['row_id'],'array',array());
		
		$models = DAO_Notification::getIds($row_ids);
		
		$models = array_intersect_key(
			$models,
			array_flip(
				array_keys(
					Context_Notification::isReadableByActor($models, $active_worker),
					true
				)
			)
		);
		
		if($models) {
			DAO_Notification::update(array_keys($models), [
				DAO_Notification::IS_READ => 1,
			]);
			
			DAO_Notification::clearCountCache($active_worker->id);
		}
		
		$view = C4_AbstractViewLoader::getView($view_id);
		$view->render();
		
		DevblocksPlatform::exit();
	}
	
	private function _internalAction_viewExplore() {
		$active_worker = CerberusApplication::getActiveWorker();
		$url_writer = DevblocksPlatform::services()->url();
		
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string');
		
		// Generate hash
		$hash = md5($view_id.$active_worker->id.time());
		
		// Loop through view and get IDs
		$view = C4_AbstractViewLoader::getView($view_id);
		$view->setAutoPersist(false);
		
		// Page start
		@$explore_from = DevblocksPlatform::importGPC($_POST['explore_from'],'integer',0);
		if(empty($explore_from)) {
			$orig_pos = 1+($view->renderPage * $view->renderLimit);
		} else {
			$orig_pos = 1;
		}
		
		$view->renderPage = 0;
		$view->renderLimit = 250;
		$pos = 0;
		$keys = [];
		
		$view->renderTotal = false;
		
		do {
			$models = array();
			list($results,) = $view->getData();
			
			if(is_array($results))
				foreach($results as $event_id => $row) {
					if($event_id==$explore_from)
						$orig_pos = $pos;
					
					$entry = json_decode($row[SearchFields_Notification::ENTRY_JSON], true);
					
					$content = CerberusContexts::formatActivityLogEntry($entry, 'text');
					$context = $row[SearchFields_Notification::CONTEXT];
					$context_id = $row[SearchFields_Notification::CONTEXT_ID];
					
					// Composite key
					$key = $row[SearchFields_Notification::WORKER_ID]
						. '_' . $context
						. '_' . $context_id
					;
					
					$url = $url_writer->write(sprintf("c=internal&a=redirectRead&id=%d", $row[SearchFields_Notification::ID]));
					
					if(empty($url))
						continue;
					
					if(!empty($context) && !empty($context_id)) {
						// Is this a dupe?
						if(isset($keys[$key])) {
							continue;
						} else {
							$keys[$key] = ++$pos;
						}
					} else {
						++$pos;
					}
					
					$model = new Model_ExplorerSet();
					$model->hash = $hash;
					$model->pos = $pos;
					$model->params = array(
						'id' => $row[SearchFields_Notification::ID],
						'content' => $content,
						'url' => $url,
					);
					$models[] = $model;
				}
			
			if(!empty($models))
				DAO_ExplorerSet::createFromModels($models);
			
			$view->renderPage++;
			
		} while(!empty($results));
		
		// Add the manifest row
		
		DAO_ExplorerSet::set(
			$hash,
			array(
				'title' => $view->name,
				'created' => time(),
				'worker_id' => $active_worker->id,
				'total' => $pos,
				'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=profiles&k=worker&id=me&tab=notifications', true),
			),
			0
		);
		
		// Clamp the starting position based on dupe key folding
		$orig_pos = DevblocksPlatform::intClamp($orig_pos, 1, count($keys));
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('explore',$hash,$orig_pos)));
	}
}