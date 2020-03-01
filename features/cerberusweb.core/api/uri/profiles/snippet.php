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

class PageSection_ProfilesSnippet extends Extension_PageSection {
	function render() {
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // snippet 
		@$context_id = intval(array_shift($stack)); // 123

		$context = CerberusContexts::CONTEXT_SNIPPET;
		
		Page_Profiles::renderProfile($context, $context_id, $stack);
	}
	
	function handleActionForPage(string $action, string $scope=null) {
		if('profileAction' == $scope) {
			switch ($action) {
				case 'getPlaceholders':
					return $this->_profileAction_getPlaceholders();
				case 'getSnippetPlaceholders':
					return $this->_profileAction_getSnippetPlaceholders();
				case 'helpPopup':
					return $this->_profileAction_helpPopup();
				case 'paste':
					return $this->_profileAction_paste();
				case 'previewPlaceholders':
					return $this->_profileAction_previewPlaceholders();
				case 'renderToolbar':
					return $this->_profileAction_renderToolbar();
				case 'showBulkPanel':
					return $this->_profileAction_showBulkPanel();
				case 'startBulkUpdateJson':
					return $this->_profileAction_startBulkUpdateJson();
				case 'savePeekJson':
					return $this->_profileAction_savePeekJson();
				case 'test':
					return $this->_profileAction_test();
				case 'viewExplore':
					return $this->_profileAction_viewExplore();
			}
		}
		return false;
	}
	
	private function _profileAction_viewExplore() {
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string');
		
		$active_worker = CerberusApplication::getActiveWorker();
		$url_writer = DevblocksPlatform::services()->url();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
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
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=search&type=snippet', true),
				);
				$models[] = $model;
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
			foreach($results as $opp_id => $row) {
				if($opp_id==$explore_from)
					$orig_pos = $pos;
				
				$url = $url_writer->writeNoProxy(sprintf("c=profiles&type=snippet&id=%d-%s", $row[SearchFields_Snippet::ID], DevblocksPlatform::strToPermalink($row[SearchFields_Snippet::TITLE])), true);
				
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'id' => $row[SearchFields_Snippet::ID],
					'url' => $url,
				);
				$models[] = $model;
			}
			
			DAO_ExplorerSet::createFromModels($models);
			
			$view->renderPage++;
			
		} while(!empty($results));
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('explore',$hash,$orig_pos)));
	}
	
	private function _profileAction_renderToolbar() {
		$tpl = DevblocksPlatform::services()->template();
		
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$form_id = DevblocksPlatform::importGPC($_REQUEST['form_id'],'string','');
		
		$tpl->assign('context', $context);
		$tpl->assign('form_id', $form_id);
		
		if(false == (Extension_DevblocksContext::get($context)))
			return;
		
		$labels = [];
		$null = [];
		
		CerberusContexts::getContext($context, null, $labels, $null, '', true, false);
		
		$placeholders = Extension_DevblocksContext::getPlaceholderTree($labels);
		$tpl->assign('placeholders', $placeholders);
		
		$tpl->display('devblocks:cerberusweb.core::internal/snippets/peek_toolbar.tpl');
	}
	
	private function _profileAction_helpPopup() {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->display('devblocks:cerberusweb.core::internal/snippets/help_popup.tpl');
	}
	
	private function _profileAction_paste() {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$active_worker = CerberusApplication::getActiveWorker();
		
		@$id = DevblocksPlatform::importGPC($_POST['id'],'integer',0);
		@$context_ids = DevblocksPlatform::importGPC($_POST['context_ids'],'array',[]);
		$context_id = 0;
		
		$token_labels = $token_values = [];
		
		if(!$id)
			DevblocksPlatform::dieWithHttpError(null, 404);
			
		if(false == ($snippet = DAO_Snippet::get($id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(isset($context_ids[$snippet->context]))
			$context_id = intval($context_ids[$snippet->context]);
		
		// Make sure the worker is allowed to view this context+ID
		if($snippet->context && $context_id) {
			if(!CerberusContexts::isReadableByActor($snippet->context, $context_id, $active_worker))
				DevblocksPlatform::dieWithHttpError(null, 403);
			
			CerberusContexts::getContext($snippet->context, $context_id, $token_labels, $token_values);
		}
		
		// Build template
		if($snippet->context && $context_id) {
			@$output = $tpl_builder->build($snippet->content, $token_values);
			
		} else {
			$output = $snippet->content;
		}
		
		// Increment the usage counter
		$snippet->incrementUse($active_worker->id);
		
		header('Content-Type: application/json');
		
		echo json_encode(array(
			'id' => $id,
			'context_id' => $context_id,
			'has_custom_placeholders' => !empty($snippet->custom_placeholders),
			'text' => rtrim(str_replace("\r","",$output),"\r\n") . "\n",
		));
	}
	
	private function _profileAction_getPlaceholders() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer',0);
		
		if(!$id)
			DevblocksPlatform::dieWithHttpError(null, 404);
			
		if(false == ($snippet = DAO_Snippet:: get($id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(!Context_Snippet::isReadableByActor($snippet, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$tpl = DevblocksPlatform::services()->template();
		
		$tpl->assign('snippet', $snippet);
		$tpl->assign('context_id', $context_id);
		$tpl->display('devblocks:cerberusweb.core::internal/snippets/paste_placeholders.tpl');
	}
	
	private function _profileAction_previewPlaceholders() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		@$id = DevblocksPlatform::importGPC($_POST['id'],'integer',0);
		@$context_id = DevblocksPlatform::importGPC($_POST['context_id'],'integer',0);
		@$placeholders = DevblocksPlatform::importGPC($_POST['placeholders'],'array',array());
		
		if(!$id)
			DevblocksPlatform::dieWithHttpError(null, 404);
			
		if(false == ($snippet = DAO_Snippet:: get($id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(!Context_Snippet::isReadableByActor($snippet, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$custom_placeholders = $snippet->custom_placeholders;
		
		$text = $snippet->content;
		
		$labels = array();
		$values = array();
		
		if($context_id) {
			CerberusContexts::getContext($snippet->context, $context_id, $labels, $values);
		}
		
		if(is_array($custom_placeholders))
			foreach($custom_placeholders as $placeholder_key => $placeholder) {
				$value = null;
				
				if(!isset($placeholders[$placeholder_key]))
					$placeholders[$placeholder_key] = '{{' . $placeholder_key . '}}';
				
				switch($placeholder['type']) {
					case Model_CustomField::TYPE_CHECKBOX:
						@$value = $placeholders[$placeholder_key] ? true : false;
						break;
					
					case Model_CustomField::TYPE_SINGLE_LINE:
					case Model_CustomField::TYPE_MULTI_LINE:
						@$value = $placeholders[$placeholder_key];
						break;
				}
				
				$values[$placeholder_key] = $value;
			}
		
		@$text = $tpl_builder->build($text, $values);
		
		$tpl = DevblocksPlatform::services()->template();
		
		$tpl->assign('text', $text);
		
		$tpl->display('devblocks:cerberusweb.core::internal/snippets/paste_placeholders_preview.tpl');
	}
	
	private function _profileAction_test() {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		
		@$snippet_context = DevblocksPlatform::importGPC($_REQUEST['snippet_context'],'string','');
		@$snippet_context_id = DevblocksPlatform::importGPC($_REQUEST['snippet_context_id'],'integer',0);
		@$snippet_key_prefix = DevblocksPlatform::importGPC($_REQUEST['snippet_key_prefix'],'string','');
		@$snippet_field = DevblocksPlatform::importGPC($_REQUEST['snippet_field'],'string','');
		
		$content = '';
		if(isset($_REQUEST[$snippet_field]))
			$content = DevblocksPlatform::importGPC($_REQUEST[$snippet_field]);
		
		$tpl = DevblocksPlatform::services()->template();
		
		$token_labels = $token_values = $merge_labels = $merge_values = [];
		
		$ctx = Extension_DevblocksContext::get($snippet_context);
		
		// If no ID is given, randomize one
		if(empty($snippet_context_id) && method_exists($ctx, 'getRandom'))
			$snippet_context_id = $ctx->getRandom();
		
		CerberusContexts::getContext($snippet_context, $snippet_context_id, $merge_labels, $merge_values);
		CerberusContexts::merge($snippet_key_prefix, '', $merge_labels, $merge_values, $token_labels, $token_values);
		
		// Add prompted placeholders to the valid tokens
		
		@$placeholder_keys = DevblocksPlatform::importGPC($_REQUEST['placeholder_keys'], 'array', array());
		@$placeholder_defaults = DevblocksPlatform::importGPC($_REQUEST['placeholder_defaults'], 'array', array());
		
		foreach($placeholder_keys as $idx => $v) {
			@$placeholder_default = $placeholder_defaults[$idx];
			$token_values[$v] =  (!empty($placeholder_default) ? $placeholder_default : ('{{' . $v . '}}'));
			$token_labels[$v] =  $token_values[$v];
		}
		
		// Tester
		
		$success = false;
		$output = '';
		
		if(!empty($token_values)) {
			// Tokenize
			//$tokens = $tpl_builder->tokenize($content);
			$unknown_tokens = array();
			
			//$valid_tokens = $tpl_builder->stripModifiers(array_keys($token_labels));
			
			// Test legal values
			//$unknown_tokens = array_diff($tokens, $valid_tokens);
			//$matching_tokens = array_intersect($tokens, $valid_tokens);
			
			if(!empty($unknown_tokens)) {
				$success = false;
				$output = "The following placeholders are unknown: ".
					implode(', ', $unknown_tokens);
				
			} else {
				// Try to build the template
				if(false === (@$out = $tpl_builder->build($content, $token_values))) {
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
	
	private function _profileAction_showBulkPanel() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		@$ids = DevblocksPlatform::importGPC($_REQUEST['ids']);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id']);
		
		if(!$active_worker->hasPriv(sprintf("contexts.%s.update.bulk", CerberusContexts::CONTEXT_SNIPPET)))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$tpl = DevblocksPlatform::services()->template();
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
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_SNIPPET, false);
		$tpl->assign('custom_fields', $custom_fields);
		
		$tpl->display('devblocks:cerberusweb.core::internal/snippets/bulk.tpl');
	}
	
	private function _profileAction_startBulkUpdateJson() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		// Filter: whole list or check
		@$filter = DevblocksPlatform::importGPC($_POST['filter'],'string','');
		$ids = [];
		
		// View
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string');
		$view = C4_AbstractViewLoader::getView($view_id);
		$view->setAutoPersist(false);
		
		// Snippet fields
		@$owner = trim(DevblocksPlatform::importGPC($_POST['owner'],'string',''));
		
		$do = [];
		
		if(!$active_worker->hasPriv(sprintf("contexts.%s.update.bulk", CerberusContexts::CONTEXT_SNIPPET)))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		// Do: Due
		if(0 != strlen($owner))
			$do['owner'] = $owner;
		
		// Do: Custom fields
		$do = DAO_CustomFieldValue::handleBulkPost($do);
		
		switch($filter) {
			// Checked rows
			case 'checks':
				@$ids_str = DevblocksPlatform::importGPC($_POST['ids'],'string');
				$ids = DevblocksPlatform::parseCsvString($ids_str);
				break;
			
			case 'sample':
				@$sample_size = min(DevblocksPlatform::importGPC($_POST['filter_sample_size'],'integer',0),9999);
				$ids = $view->getDataSample($sample_size);
				break;
			
			default:
				break;
		}
		
		// If we have specific IDs, add a filter for those too
		if(!empty($ids)) {
			$view->addParam(new DevblocksSearchCriteria(SearchFields_Snippet::ID, 'in', $ids));
		}
		
		// Create batches
		$batch_key = DAO_ContextBulkUpdate::createFromView($view, $do);
		
		header('Content-Type: application/json; charset=utf-8');
		
		echo json_encode(array(
			'cursor' => $batch_key,
		));
		
		return;
	}
	
	private function _profileAction_savePeekJson() {
		@$id = DevblocksPlatform::importGPC($_POST['id'],'integer',0);
		@$title = DevblocksPlatform::importGPC($_POST['title'],'string','');
		@$context = DevblocksPlatform::importGPC($_POST['context'],'string','');
		@$content = DevblocksPlatform::importGPC($_POST['content'],'string','');
		@$do_delete = DevblocksPlatform::importGPC($_POST['do_delete'],'integer',0);

		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string','');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);

		header('Content-Type: application/json; charset=utf-8');
		
		try {
			if($do_delete) {
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", CerberusContexts::CONTEXT_SNIPPET)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				if(false == ($model = DAO_Snippet::get($id)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.record.not_found'));
				
				if(!Context_Snippet::isDeletableByActor($model, $active_worker))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				CerberusContexts::logActivityRecordDelete(CerberusContexts::CONTEXT_SNIPPET, $model->id, $model->title);
				
				DAO_Snippet::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				
			} else { // Create || Update
				@list($owner_context, $owner_context_id) = explode(':', DevblocksPlatform::importGPC($_POST['owner'],'string',''));
			
				switch($owner_context) {
					case CerberusContexts::CONTEXT_APPLICATION:
					case CerberusContexts::CONTEXT_ROLE:
					case CerberusContexts::CONTEXT_GROUP:
					case CerberusContexts::CONTEXT_BOT:
					case CerberusContexts::CONTEXT_WORKER:
						break;
						
					default:
						$owner_context = null;
						$owner_context_id = null;
						break;
				}

				$fields = array(
					DAO_Snippet::TITLE => $title,
					DAO_Snippet::CONTEXT => $context,
					DAO_Snippet::CONTENT => $content,
					DAO_Snippet::UPDATED_AT => time(),
					DAO_Snippet::OWNER_CONTEXT => $owner_context,
					DAO_Snippet::OWNER_CONTEXT_ID => $owner_context_id,
				);
				
				// Custom placeholders
				
				$placeholders = array();
				@$placeholder_keys = DevblocksPlatform::importGPC($_POST['placeholder_keys'],'array',array());
				
				if(is_array($placeholder_keys) && !empty($placeholder_keys)) {
					@$placeholder_types = DevblocksPlatform::importGPC($_POST['placeholder_types'],'array',array());
					@$placeholder_labels = DevblocksPlatform::importGPC($_POST['placeholder_labels'],'array',array());
					@$placeholder_defaults = DevblocksPlatform::importGPC($_POST['placeholder_defaults'],'array',array());
					@$placeholder_deletes = DevblocksPlatform::importGPC($_POST['placeholder_deletes'],'array',array());
					
					foreach($placeholder_keys as $placeholder_idx => $placeholder_key) {
						@$placeholder_type = $placeholder_types[$placeholder_idx];
						@$placeholder_label = $placeholder_labels[$placeholder_idx];
						@$placeholder_default = $placeholder_defaults[$placeholder_idx];
						@$placeholder_delete = $placeholder_deletes[$placeholder_idx];
						
						if(empty($placeholder_key) || !empty($placeholder_delete))
							continue;
						
						$placeholders[$placeholder_key] = array(
							'type' => $placeholder_type,
							'key' => $placeholder_key,
							'label' => $placeholder_label,
							'default' => $placeholder_default,
						);
					}
					
					$fields[DAO_Snippet::CUSTOM_PLACEHOLDERS_JSON] = json_encode($placeholders);
				}
				
				// Create / Update
				
				$error = null;
				
				if(empty($id)) {
					// Validate fields from DAO
					if(!DAO_Snippet::validate($fields, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_Snippet::onBeforeUpdateByActor($active_worker, $fields, null, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(false == ($id = DAO_Snippet::create($fields)))
						throw new Exception_DevblocksAjaxValidationError('Failed to create the record.');
					
					DAO_Snippet::onUpdateByActor($active_worker, $fields, $id);
					
					// View marquee
					if(!empty($id) && !empty($view_id)) {
						C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_SNIPPET, $id);
					}
					
				} else {
					// Validate fields from DAO
					if(!DAO_Snippet::validate($fields, $error, $id))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_Snippet::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(null == ($snippet = DAO_Snippet::get($id)))
						throw new Exception_DevblocksAjaxValidationError('This record no longer exists.');
					
					DAO_Snippet::update($id, $fields);
					DAO_Snippet::onUpdateByActor($active_worker, $fields, $id);
				}
				
				// Custom field saves
				@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', []);
				if(!DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_SNIPPET, $id, $field_ids, $error))
					throw new Exception_DevblocksAjaxValidationError($error);
			
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'label' => $title,
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
	
	private function _profileAction_getSnippetPlaceholders() {
		@$name_prefix = DevblocksPlatform::importGPC($_REQUEST['name_prefix'],'string', '');
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer', 0);
		
		$tpl = DevblocksPlatform::services()->template();
		
		$tpl->assign('namePrefix', $name_prefix);
		
		if(null != ($snippet = DAO_Snippet::get($id)))
			$tpl->assign('snippet', $snippet);
		
		$tpl->display('devblocks:cerberusweb.core::events/action_set_placeholder_using_snippet_params.tpl');
	}
}