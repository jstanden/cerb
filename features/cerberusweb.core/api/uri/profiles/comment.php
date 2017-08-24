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

class PageSection_ProfilesComment extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::services()->template();
		$visit = CerberusApplication::getVisit();
		$translate = DevblocksPlatform::getTranslationService();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // comment 
		$id = array_shift($stack); // 123

		@$id = intval($id);
		
		if(null == ($comment = DAO_Comment::get($id))) {
			return;
		}
		$tpl->assign('comment', $comment);
	
		// Tab persistence
		
		$point = 'profiles.comment.tab';
		$tpl->assign('point', $point);
		
		if(null == (@$tab_selected = $stack[0])) {
			$tab_selected = $visit->get($point, '');
		}
		$tpl->assign('tab_selected', $tab_selected);
	
		// Properties
			
		$properties = array();
			
		$properties['author'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.author'),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $comment->owner_context_id,
			'params' => [
				'context' => $comment->owner_context,
			],
		);
		
		$properties['created'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.created'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $comment->created,
		);
		
		$properties['target'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.target'),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $comment->context_id,
			'params' => [
				'context' => $comment->context,
			],
		);
	
		// Custom Fields

		@$values = array_shift(DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_COMMENT, $comment->id)) or array();
		$tpl->assign('custom_field_values', $values);
		
		$properties_cfields = Page_Profiles::getProfilePropertiesCustomFields(CerberusContexts::CONTEXT_COMMENT, $values);
		
		if(!empty($properties_cfields))
			$properties = array_merge($properties, $properties_cfields);
		
		// Custom Fieldsets

		$properties_custom_fieldsets = Page_Profiles::getProfilePropertiesCustomFieldsets(CerberusContexts::CONTEXT_COMMENT, $comment->id, $values);
		$tpl->assign('properties_custom_fieldsets', $properties_custom_fieldsets);
		
		// Link counts
		
		$properties_links = array(
			CerberusContexts::CONTEXT_COMMENT => array(
				$comment->id => 
					DAO_ContextLink::getContextLinkCounts(
						CerberusContexts::CONTEXT_COMMENT,
						$comment->id,
						array(CerberusContexts::CONTEXT_CUSTOM_FIELDSET)
					),
			),
		);
		
		$tpl->assign('properties_links', $properties_links);
		
		// Properties
		
		$tpl->assign('properties', $properties);
			
		// Tabs
		$tab_manifests = Extension_ContextProfileTab::getExtensions(false, CerberusContexts::CONTEXT_COMMENT);
		$tpl->assign('tab_manifests', $tab_manifests);
		
		// Template
		$tpl->display('devblocks:cerberusweb.core::profiles/comment.tpl');
	}
	
	function savePeekJsonAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'], 'string', '');
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'], 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		$tpl = DevblocksPlatform::services()->template();
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			if($id && !Context_Comment::isWriteableByActor($id, $active_worker))
				throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translateCapitalized('common.access_denied'));
			
			if(!empty($id) && !empty($do_delete)) { // Delete
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", CerberusContexts::CONTEXT_COMMENT)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				DAO_Comment::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
			}
				
			@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string');
			@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer',0);
			@$comment = DevblocksPlatform::importGPC($_REQUEST['comment'],'string','');
			@$file_ids = DevblocksPlatform::sanitizeArray(DevblocksPlatform::importGPC($_REQUEST['file_ids'],'array',array()), 'int');
			@$options = DevblocksPlatform::importGPC($_REQUEST['options'],'array',[]);
			
			if(empty($id)) { // New
				$also_notify_worker_ids = array_keys(CerberusApplication::getWorkersByAtMentionsText($comment));
				
				// Validate the context
				if(false == ($context_ext = Extension_DevblocksContext::get($context)) || false == ($context_ext->getMeta($context_id)))
					throw new Exception_DevblocksAjaxValidationError("The 'Target' is invalid.", 'context');
				
				$fields = array(
					DAO_Comment::CONTEXT => $context,
					DAO_Comment::CONTEXT_ID => $context_id,
					DAO_Comment::OWNER_CONTEXT => CerberusContexts::CONTEXT_WORKER,
					DAO_Comment::OWNER_CONTEXT_ID => $active_worker->id,
					DAO_Comment::COMMENT => $comment,
					DAO_Comment::CREATED => time(),
				);
				
				if(!DAO_Comment::validate($fields, $error))
					throw new Exception_DevblocksAjaxValidationError($error);
				
				$id = DAO_Comment::create($fields, $also_notify_worker_ids, $file_ids);
				
				if(!empty($view_id) && !empty($id))
					C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_COMMENT, $id);
				
			} else { // Edit
				if(!$active_worker->hasPriv(sprintf("contexts.%s.update", CerberusContexts::CONTEXT_COMMENT)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.edit'));
				
				$fields = array(
					DAO_Comment::COMMENT => $comment,
				);
				
				if(isset($options['update_timestamp']) && $options['update_timestamp'])
					$fields[DAO_Comment::CREATED] = time();
				
				if(!DAO_Comment::validate($fields, $error, $id))
					throw new Exception_DevblocksAjaxValidationError($error);
				
				DAO_Comment::update($id, $fields);
			}
			
			$html = null;
			
			if($id) {
				// Add attachments
				if(is_array($file_ids) && !empty($file_ids))
					DAO_Attachment::setLinks(CerberusContexts::CONTEXT_COMMENT, $id, $file_ids);
	
				// Custom fields
				@$field_ids = DevblocksPlatform::importGPC($_REQUEST['field_ids'], 'array', array());
				DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_COMMENT, $id, $field_ids);
				
				// Refresh HTML
				$model = DAO_Comment::get($id);
				if($model->context == CerberusContexts::CONTEXT_MESSAGE) {
					$tpl->assign('note', $model);
					$ticket = DAO_Ticket::getTicketByMessageId($model->context_id);
					$tpl->assign('ticket', $ticket);
					$html = $tpl->fetch('devblocks:cerberusweb.core::internal/comments/note.tpl');
					
				} else {
					$tpl->assign('comment', $model);
					
					if($model->context == CerberusContexts::CONTEXT_TICKET) {
						$ticket = DAO_Ticket::get($model->context_id);
						$tpl->assign('ticket', $ticket);
					}
					
					$html = $tpl->fetch('devblocks:cerberusweb.core::internal/comments/comment.tpl');
				}
			}
			
			echo json_encode(array(
				'status' => true,
				'id' => $id,
				'label' => $comment,
				'context' => $context,
				'comment_html' => $html,
				'view_id' => $view_id,
			));
			return;
			
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
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=search&type=comment', true),
					'toolbar_extension_id' => 'cerberusweb.contexts.comment.explore.toolbar',
				);
				$models[] = $model;
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
			foreach($results as $opp_id => $row) {
				if($opp_id==$explore_from)
					$orig_pos = $pos;
				
				$url = $url_writer->writeNoProxy(sprintf("c=profiles&type=comment&id=%d", $row[SearchFields_Comment::ID]), true);
				
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'id' => $row[SearchFields_Comment::ID],
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
