<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2014, Webgroup Media LLC
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

class PageSection_ProfilesWorkerRole extends Extension_PageSection {
	function render() {
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // role 
		@$context_id = intval(array_shift($stack)); // 123
		
		$context = CerberusContexts::CONTEXT_ROLE;
		
		Page_Profiles::renderProfile($context, $context_id, $stack);
	}
	
	function savePeekJsonAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'], 'string', '');
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'], 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			if(!empty($id) && !empty($do_delete)) { // Delete
				if(!$active_worker->is_superuser || !$active_worker->hasPriv(sprintf("contexts.%s.delete", CerberusContexts::CONTEXT_ROLE)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				DAO_WorkerRole::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
				
			} else {
				@$name = DevblocksPlatform::importGPC($_REQUEST['name'], 'string', '');
				@$member_query_worker = DevblocksPlatform::importGPC($_REQUEST['member_query_worker'], 'string', '');
				@$editor_query_worker = DevblocksPlatform::importGPC($_REQUEST['editor_query_worker'], 'string', '');
				@$reader_query_worker = DevblocksPlatform::importGPC($_REQUEST['reader_query_worker'], 'string', '');
				
				@$privs_mode = DevblocksPlatform::importGPC($_REQUEST['privs_mode'],'string','');
				@$acl_privs = DevblocksPlatform::importGPC($_REQUEST['acl_privs'],'array', []);
				
				if(in_array($privs_mode, ['all','']))
					$acl_privs = [];
				
				$error = null;
				
				if(empty($id)) { // New
					$fields = array(
						DAO_WorkerRole::NAME => $name,
						DAO_WorkerRole::MEMBER_QUERY_WORKER => $member_query_worker,
						DAO_WorkerRole::EDITOR_QUERY_WORKER => $editor_query_worker,
						DAO_WorkerRole::READER_QUERY_WORKER => $reader_query_worker,
						DAO_WorkerRole::PRIVS_MODE => $privs_mode,
						DAO_WorkerRole::PRIVS_JSON => json_encode($acl_privs),
						DAO_WorkerRole::UPDATED_AT => time(),
					);
					
					if(!DAO_WorkerRole::validate($fields, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_WorkerRole::onBeforeUpdateByActor($active_worker, $fields, null, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					$id = DAO_WorkerRole::create($fields);
					DAO_WorkerRole::onUpdateByActor($active_worker, $fields, $id);
					
					if(!empty($view_id) && !empty($id))
						C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_ROLE, $id);
					
				} else { // Edit
					$fields = array(
						DAO_WorkerRole::NAME => $name,
						DAO_WorkerRole::MEMBER_QUERY_WORKER => $member_query_worker,
						DAO_WorkerRole::EDITOR_QUERY_WORKER => $editor_query_worker,
						DAO_WorkerRole::READER_QUERY_WORKER => $reader_query_worker,
						DAO_WorkerRole::PRIVS_MODE => $privs_mode,
						DAO_WorkerRole::PRIVS_JSON => json_encode($acl_privs),
						DAO_WorkerRole::UPDATED_AT => time(),
					);
					
					if(!DAO_WorkerRole::validate($fields, $error, $id))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_WorkerRole::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					DAO_WorkerRole::update($id, $fields);
					DAO_WorkerRole::onUpdateByActor($active_worker, $fields, $id);
				}
				
				// Custom field saves
				@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', []);
				if(!DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_ROLE, $id, $field_ids, $error))
					throw new Exception_DevblocksAjaxValidationError($error);
				
				// Avatar image
				@$avatar_image = DevblocksPlatform::importGPC($_REQUEST['avatar_image'], 'string', '');
				DAO_ContextAvatar::upsertWithImage(CerberusContexts::CONTEXT_ROLE, $id, $avatar_image);
				
				// Clear cache
				DAO_WorkerRole::updateRosters($id);
				DAO_WorkerRole::clearCache();
				
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
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=search&type=role', true),
				);
				$models[] = $model;
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
			foreach($results as $opp_id => $row) {
				if($opp_id==$explore_from)
					$orig_pos = $pos;
				
				$url = $url_writer->writeNoProxy(sprintf("c=profiles&type=role&id=%d-%s", $row[SearchFields_WorkerRole::ID], DevblocksPlatform::strToPermalink($row[SearchFields_WorkerRole::NAME])), true);
				
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'id' => $row[SearchFields_WorkerRole::ID],
					'url' => $url,
				);
				$models[] = $model;
			}
			
			DAO_ExplorerSet::createFromModels($models);
			
			$view->renderPage++;
			
		} while(!empty($results));
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('explore',$hash,$orig_pos)));
	}
};
