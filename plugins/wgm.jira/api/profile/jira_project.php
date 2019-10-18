<?php
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

class PageSection_ProfilesJiraProject extends Extension_PageSection {
	function render() {
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // jira_project
		$context_id = intval(array_shift($stack)); // 123

		$context = Context_JiraProject::ID;
		
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
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", 'cerberusweb.contexts.jira.project')))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				DAO_JiraProject::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
				
			} else {
				@$name = DevblocksPlatform::importGPC($_REQUEST['name'], 'string', '');
				@$jira_key = DevblocksPlatform::importGPC($_REQUEST['jira_key'], 'string', '');
				@$connected_account_id = DevblocksPlatform::importGPC($_REQUEST['connected_account_id'], 'integer', 0);
				@$url = DevblocksPlatform::importGPC($_REQUEST['url'], 'string', '');
				
				$error = null;
				
				if(empty($id)) { // New
					$fields = array(
						DAO_JiraProject::CONNECTED_ACCOUNT_ID => $connected_account_id,
						DAO_JiraProject::JIRA_KEY => $jira_key,
						DAO_JiraProject::NAME => $name,
						DAO_JiraProject::UPDATED_AT => time(),
						DAO_JiraProject::URL => $url,
					);
					
					if(!DAO_JiraProject::validate($fields, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
						
					if(!DAO_JiraProject::onBeforeUpdateByActor($active_worker, $fields, null, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					$id = DAO_JiraProject::create($fields);
					DAO_JiraProject::onUpdateByActor($active_worker, $id, $fields);
					
					if(!empty($view_id) && !empty($id))
						C4_AbstractView::setMarqueeContextCreated($view_id, 'cerberusweb.contexts.jira.project', $id);
					
				} else { // Edit
					$fields = array(
						DAO_JiraProject::CONNECTED_ACCOUNT_ID => $connected_account_id,
						DAO_JiraProject::JIRA_KEY => $jira_key,
						DAO_JiraProject::NAME => $name,
						DAO_JiraProject::UPDATED_AT => time(),
						DAO_JiraProject::URL => $url,
					);
					
					if(!DAO_JiraProject::validate($fields, $error, $id))
						throw new Exception_DevblocksAjaxValidationError($error);
						
					if(!DAO_JiraProject::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					DAO_JiraProject::update($id, $fields);
					DAO_JiraProject::onUpdateByActor($active_worker, $id, $fields);
					
				}
				
				// Custom field saves
				@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', []);
				if(!DAO_CustomFieldValue::handleFormPost('cerberusweb.contexts.jira.project', $id, $field_ids, $error))
					throw new Exception_DevblocksAjaxValidationError($error);
				
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
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=search&type=jira_project', true),
				);
				$models[] = $model;
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
			foreach($results as $opp_id => $row) {
				if($opp_id==$explore_from)
					$orig_pos = $pos;
				
				$url = $url_writer->writeNoProxy(sprintf("c=profiles&type=jira_project&id=%d-%s", $row[SearchFields_JiraProject::ID], DevblocksPlatform::strToPermalink($row[SearchFields_JiraProject::NAME])), true);
				
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'id' => $row[SearchFields_JiraProject::ID],
					'url' => $url,
				);
				$models[] = $model;
			}
			
			DAO_ExplorerSet::createFromModels($models);
			
			$view->renderPage++;
			
		} while(!empty($results));
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('explore',$hash,$orig_pos)));
	}
	
	function showIssuesTabAction() {
		//@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer',0);
		
		$tpl = DevblocksPlatform::services()->template();
		$translate = DevblocksPlatform::getTranslationService();
		
		if(empty($context_id))
			return;
		
		if(false == ($project = DAO_JiraProject::get($context_id)))
			return;

		// Issue worklist with project filter
		
		$view_id = sprintf("jira_project_profile_issues_%d", $project->id);
		
		if(null == ($view = C4_AbstractViewLoader::getView($view_id))) {
			$context_ext = Extension_DevblocksContext::get(Context_JiraIssue::ID);
			$view = $context_ext->getSearchView($view_id);
		}

		if(empty($view))
			return;
		
		$view->name = mb_convert_case($translate->_('wgm.jira.common.issues'), MB_CASE_TITLE);
		$view->is_ephemeral = true;
		
		$view->addParamsRequired(
			array(
				SearchFields_JiraIssue::PROJECT_ID => new DevblocksSearchCriteria(SearchFields_JiraIssue::PROJECT_ID, '=', $project->jira_id),
			),
			true
		);
		
		$tpl->assign('view', $view);
		
		$tpl->display('devblocks:cerberusweb.core::internal/views/search_and_view.tpl');
	}
};
