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

class PageSection_ProfilesSnippet extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();
		$translate = DevblocksPlatform::getTranslationService();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // snippet 
		$id = array_shift($stack); // 123

		@$id = intval($id);
		
		if(null == ($snippet = DAO_Snippet::get($id)))
			return;
		
		$tpl->assign('snippet', $snippet);
	
		// Tab persistence
		
		$point = 'profiles.snippet.tab';
		$tpl->assign('point', $point);
		
		if(null == (@$tab_selected = $stack[0])) {
			$tab_selected = $visit->get($point, '');
		}
		$tpl->assign('tab_selected', $tab_selected);
	
		// Properties
			
		$properties = array();
			
		$properties['owner'] = array(
			'label' => mb_ucfirst($translate->_('common.owner')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $snippet->owner_context_id,
			'params' => [
				'context' => $snippet->owner_context,
			]
		);
		
		$properties['context'] = array(
			'label' => mb_ucfirst($translate->_('common.context')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $snippet->context,
		);
		
		$properties['total_uses'] = array(
			'label' => mb_ucfirst($translate->_('dao.snippet.total_uses')),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $snippet->total_uses,
		);
		
		$properties['updated'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.updated'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $snippet->updated_at,
		);
	
		// Custom Fields

		@$values = array_shift(DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_SNIPPET, $snippet->id)) or array();
		$tpl->assign('custom_field_values', $values);
		
		$properties_cfields = Page_Profiles::getProfilePropertiesCustomFields(CerberusContexts::CONTEXT_SNIPPET, $values);
		
		if(!empty($properties_cfields))
			$properties = array_merge($properties, $properties_cfields);
		
		// Custom Fieldsets

		$properties_custom_fieldsets = Page_Profiles::getProfilePropertiesCustomFieldsets(CerberusContexts::CONTEXT_SNIPPET, $snippet->id, $values);
		$tpl->assign('properties_custom_fieldsets', $properties_custom_fieldsets);
		
		// Link counts
		
		$properties_links = array(
			CerberusContexts::CONTEXT_SNIPPET => array(
				$snippet->id => 
					DAO_ContextLink::getContextLinkCounts(
						CerberusContexts::CONTEXT_SNIPPET,
						$snippet->id,
						array(CerberusContexts::CONTEXT_CUSTOM_FIELDSET)
					),
			),
		);
		
		$tpl->assign('properties_links', $properties_links);
		
		// Properties
		
		$tpl->assign('properties', $properties);
			
		// Macros
		
		/*
		$macros = DAO_TriggerEvent::getReadableByActor(
			$active_worker,
			'event.macro.snippet'
		);
		$tpl->assign('macros', $macros);
		*/

		// Tabs
		$tab_manifests = Extension_ContextProfileTab::getExtensions(false, CerberusContexts::CONTEXT_SNIPPET);
		$tpl->assign('tab_manifests', $tab_manifests);
		
		// Template
		$tpl->display('devblocks:cerberusweb.core::internal/snippets/profile.tpl');
	}
	
	function viewExploreAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		
		$active_worker = CerberusApplication::getActiveWorker();
		$url_writer = DevblocksPlatform::getUrlService();
		
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
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=search&type=snippet', true),
					'toolbar_extension_id' => 'cerberusweb.contexts.snippet.explore.toolbar',
				);
				$models[] = $model;
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
			foreach($results as $opp_id => $row) {
				if($opp_id==$explore_from)
					$orig_pos = $pos;
				
				$url = $url_writer->writeNoProxy(sprintf("c=profiles&type=snippet&id=%d-%s", $row[SearchFields_Snippet::ID], DevblocksPlatform::strToPermalink($row[SearchFields_Snippet::NAME])), true);
				
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
	
	function showSnippetsPeekToolbarAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$form_id = DevblocksPlatform::importGPC($_REQUEST['form_id'],'string','');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('context', $context);
		$tpl->assign('form_id', $form_id);
		
		if(false == ($context_ext = Extension_DevblocksContext::get($context)))
			return;
		
		$labels = array();
		$null = array();
		
		CerberusContexts::getContext($context, null, $labels, $null, '', true, false);
		
		$placeholders = Extension_DevblocksContext::getPlaceholderTree($labels);
		$tpl->assign('placeholders', $placeholders);
		
		$tpl->display('devblocks:cerberusweb.core::internal/snippets/peek_toolbar.tpl');
	}
	
	function savePeekJsonAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$title = DevblocksPlatform::importGPC($_REQUEST['title'],'string','');
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$content = DevblocksPlatform::importGPC($_REQUEST['content'],'string','');
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'],'integer',0);

		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		
		$active_worker = CerberusApplication::getActiveWorker();

		header('Content-Type: application/json; charset=utf-8');
		
		try {
			if($do_delete) {
				if(null == ($snippet = DAO_Snippet::get($id))) /* @var $snippet Model_Snippet */
					throw new Exception_DevblocksAjaxValidationError('Failed to delete the record.');
					
				if(!Context_Snippet::isWriteableByActor($snippet, $active_worker))
					throw new Exception_DevblocksAjaxValidationError("You do not have permission to delete this record.");
					
				DAO_Snippet::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				
			} else { // Create || Update
				if(empty($title))
					throw new Exception_DevblocksAjaxValidationError("The 'Title' field is required.", 'title');
				
				@list($owner_context, $owner_context_id) = explode(':', DevblocksPlatform::importGPC($_REQUEST['owner'],'string',''));
			
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

				if(empty($owner_context))
					throw new Exception_DevblocksAjaxValidationError("The 'Owner' field is required.", 'owner_id');
				
				if(empty($content))
					throw new Exception_DevblocksAjaxValidationError("The 'Content' field is required.", 'content');
				
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
				@$placeholder_keys = DevblocksPlatform::importGPC($_REQUEST['placeholder_keys'],'array',array());
				
				if(is_array($placeholder_keys) && !empty($placeholder_keys)) {
					@$placeholder_types = DevblocksPlatform::importGPC($_REQUEST['placeholder_types'],'array',array());
					@$placeholder_labels = DevblocksPlatform::importGPC($_REQUEST['placeholder_labels'],'array',array());
					@$placeholder_defaults = DevblocksPlatform::importGPC($_REQUEST['placeholder_defaults'],'array',array());
					@$placeholder_deletes = DevblocksPlatform::importGPC($_REQUEST['placeholder_deletes'],'array',array());
					
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
				
				if(empty($id)) {
					if($active_worker->hasPriv('core.snippets.actions.create')) {
						if(false == ($id = DAO_Snippet::create($fields)))
							throw new Exception_DevblocksAjaxValidationError('Failed to create the record.');
						
						// View marquee
						if(!empty($id) && !empty($view_id)) {
							C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_SNIPPET, $id);
						}
					}
					
				} else {
					if(null == ($snippet = DAO_Snippet::get($id)))
						throw new Exception_DevblocksAjaxValidationError('This record no longer exists.');
					
					if(!Context_Snippet::isWriteableByActor($snippet, $active_worker))
						throw new Exception_DevblocksAjaxValidationError('You do not have permission to modify this record.');
					
					DAO_Snippet::update($id, $fields);
				}
				
				// Custom field saves
				@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', array());
				DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_SNIPPET, $id, $field_ids);
			
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
};
