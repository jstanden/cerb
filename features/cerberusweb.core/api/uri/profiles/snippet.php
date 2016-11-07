<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2014, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerb.io/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://cerb.io	    http://webgroup.media
***********************************************************************/

class PageSection_ProfilesSnippet extends Extension_PageSection {
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
					
				if(!$snippet->isWriteableByWorker($active_worker))
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
					case CerberusContexts::CONTEXT_VIRTUAL_ATTENDANT:
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
					
					if(!$snippet->isWriteableByWorker($active_worker))
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
