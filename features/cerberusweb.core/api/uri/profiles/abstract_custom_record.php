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

class PageSection_ProfilesAbstractCustomRecord extends Extension_PageSection {
	static private function _getContextName() {
		return 'contexts.custom_record.' . static::_ID;
	}
	
	function render() {
		// This shouldn't be called directly
		if(get_called_class() == 'PageSection_ProfilesAbstractCustomRecord')
			return;
		
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // uri 
		$context_id = intval(array_shift($stack)); // 123
		
		$context = self::_getContextName();
		
		Page_Profiles::renderProfile($context, $context_id, $stack);
	}
	
	function handleActionForPage(string $action, string $scope=null) {
		if('profileAction' == $scope) {
			switch ($action) {
				case 'savePeekJson':
					return $this->_profileAction_savePeekJson();
				case 'showBulkPopup':
					return $this->_profileAction_showBulkPopup();
				case 'startBulkUpdateJson':
					return $this->_profileAction_startBulkUpdateJson();
				case 'viewExplore':
					return $this->_profileAction_viewExplore();
			}
		}
		return false;
	}
	
	private function _profileAction_savePeekJson() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string', '');
		
		$id = DevblocksPlatform::importGPC($_POST['id'] ?? null, 'integer', 0);
		$record_id = DevblocksPlatform::importGPC($_POST['_record_id'] ?? null, 'integer', 0);
		$do_delete = DevblocksPlatform::importGPC($_POST['do_delete'] ?? null, 'string', '');
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			if(!$record_id || false == ($custom_record = DAO_CustomRecord::get($record_id)))
				throw new Exception_DevblocksAjaxValidationError("Invalid record type.", '_record_id');
			
			$dao_class = $custom_record->getDaoClass();
			$context = $custom_record->getContext();
			
			if(!empty($id) && !empty($do_delete)) { // Delete
				if(false == ($model = $dao_class::get($id)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.record.not_found'));
				
				if(
					!CerberusContexts::isWriteableByActor($context, $id, $active_worker)
					|| !$active_worker->hasPriv(sprintf("contexts.%s.delete", $context))
					)
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				CerberusContexts::logActivityRecordDelete($context, $model->id, $model->name);
				
				$dao_class::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
				
			} else {
				$name = DevblocksPlatform::importGPC($_POST['name'] ?? null, 'string', '');
				$owner = DevblocksPlatform::importGPC($_POST['owner'] ?? null, 'string', '');
				$file_ids = DevblocksPlatform::sanitizeArray(DevblocksPlatform::importGPC($_POST['file_ids'] ?? null,'array', []), 'int');
				
				// Owner
			
				list($owner_ctx, $owner_ctx_id) = array_pad(explode(':', $owner, 2), 2, null);
				
				// Make sure we're given a valid ctx
				
				switch($owner_ctx) {
					case CerberusContexts::CONTEXT_APPLICATION:
					case CerberusContexts::CONTEXT_ROLE:
					case CerberusContexts::CONTEXT_GROUP:
					case CerberusContexts::CONTEXT_WORKER:
						break;
						
					default:
						$owner_ctx = null;
				}
				
				$fields = [
					$dao_class::UPDATED_AT => time(),
					$dao_class::NAME => $name,
					$dao_class::OWNER_CONTEXT => $owner_ctx,
					$dao_class::OWNER_CONTEXT_ID => intval($owner_ctx_id),
				];
				
				// DAO
				
				$error = null;
				
				if(empty($id)) { // New
					if(!$dao_class::validate($fields, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!$dao_class::onBeforeUpdateByActor($active_worker, $fields, null, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					$id = $dao_class::create($fields);
					$dao_class::onUpdateByActor($active_worker, $fields, $id);
					
					if(!empty($view_id) && !empty($id))
						C4_AbstractView::setMarqueeContextCreated($view_id, $context, $id);
					
				} else { // Edit
					if(!$dao_class::validate($fields, $error, $id))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!$dao_class::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					$dao_class::update($id, $fields);
					$dao_class::onUpdateByActor($active_worker, $fields, $id);
				}
				
				if($id) {
					// Add attachments
					DAO_Attachment::setLinks($context, $id, $file_ids);
					
					// Custom field saves
					$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'] ?? null, 'array', []);
					if (!DAO_CustomFieldValue::handleFormPost($context, $id, $field_ids, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					// Avatar image
					$avatar_image = DevblocksPlatform::importGPC($_POST['avatar_image'] ?? null, 'string', '');
					DAO_ContextAvatar::upsertWithImage($context, $id, $avatar_image);
				}
				
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
	
	private function _profileAction_showBulkPopup() {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker->hasPriv(sprintf('contexts.%s.update.bulk', static::_ID)))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$ids = DevblocksPlatform::importGPC($_REQUEST['ids'] ?? null);
		$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'] ?? null);
		
		$context = $this->_getContextName();
		
		$tpl->assign('view_id', $view_id);
		$tpl->assign('context', $context);
		
		if(!empty($ids)) {
			$tpl->assign('ids', $ids);
		}
		
		// Custom record
		if(false == ($custom_record = DAO_CustomRecord::get(static::_ID)))
			return;
		
		$tpl->assign('custom_record', $custom_record);
		
		// Custom fields
		$custom_fields = DAO_CustomField::getByContext($context, false);
		$tpl->assign('custom_fields', $custom_fields);
		
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		$buckets = DAO_Bucket::getAll();
		$tpl->assign('buckets', $buckets);
		
		// HTML templates
		$html_templates = DAO_MailHtmlTemplate::getAll();
		$tpl->assign('html_templates', $html_templates);
		
		// Broadcast
		if(false == ($context_ext = Extension_DevblocksContext::get($context)))
			return;
		
		/* @var $context_ext IDevblocksContextBroadcast */
			
		// Recipient fields
		$recipient_fields = $context_ext->broadcastRecipientFieldsGet();
		$tpl->assign('broadcast_recipient_fields', $recipient_fields);
		
		// Placeholders
		$token_values = $context_ext->broadcastPlaceholdersGet();
		@$token_labels = $token_values['_labels'] ?: [];
		
		$placeholders = Extension_DevblocksContext::getPlaceholderTree($token_labels);
		$tpl->assign('placeholders', $placeholders);
		
		$tpl->display('devblocks:cerberusweb.core::internal/abstract_custom_record/bulk.tpl');
	}
	
	private function _profileAction_startBulkUpdateJson() {
		$active_worker = CerberusApplication::getActiveWorker();
		$context = $this->_getContextName();
		$search_class = sprintf("SearchFields_AbstractCustomRecord_%d", static::_ID);
		
		if(!$active_worker->hasPriv(sprintf('contexts.%s.update.bulk', static::_ID)))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$filter = DevblocksPlatform::importGPC($_POST['filter'] ?? null, 'string','');
		$ids = [];
		
		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string');
		$view = C4_AbstractViewLoader::getView($view_id);
		$view->setAutoPersist(false);
		
		$actions = DevblocksPlatform::importGPC($_POST['actions'] ?? [],'array');
		
		// Scheduled behavior
		$behavior_id = DevblocksPlatform::importGPC($_POST['behavior_id'] ?? null, 'string','');
		$behavior_when = DevblocksPlatform::importGPC($_POST['behavior_when'] ?? null, 'string','');
		$behavior_params = DevblocksPlatform::importGPC($_POST['behavior_params'] ?? null, 'array', []);
		
		$do = [];
		
		// Do: Scheduled Behavior
		if(0 != strlen($behavior_id)) {
			$do['behavior'] = array(
				'id' => $behavior_id,
				'when' => $behavior_when,
				'params' => $behavior_params,
			);
		}
		
		// Delete
		if($active_worker->hasPriv(sprintf('contexts.%s.delete', $context))) {
			if(in_array('delete', $actions))
				$do['delete'] = true;
		}
		
		// Broadcast: Compose
		if($active_worker->hasPriv(sprintf('contexts.%s.broadcast', $context))) {
			$do_broadcast = DevblocksPlatform::importGPC($_POST['do_broadcast'] ?? null, 'string',null);
			$broadcast_group_id = DevblocksPlatform::importGPC($_POST['broadcast_group_id'] ?? null, 'integer',0);
			$broadcast_bucket_id = DevblocksPlatform::importGPC($_POST['broadcast_bucket_id'] ?? null, 'integer',0);
			$broadcast_to = DevblocksPlatform::importGPC($_POST['broadcast_to'] ?? null, 'array',[]);
			$broadcast_subject = DevblocksPlatform::importGPC($_POST['broadcast_subject'] ?? null, 'string',null);
			$broadcast_message = DevblocksPlatform::importGPC($_POST['broadcast_message'] ?? null, 'string',null);
			$broadcast_format = DevblocksPlatform::importGPC($_POST['broadcast_format'] ?? null, 'string',null);
			$broadcast_html_template_id = DevblocksPlatform::importGPC($_POST['broadcast_html_template_id'] ?? null, 'integer',0);
			$broadcast_is_queued = DevblocksPlatform::importGPC($_POST['broadcast_is_queued'] ?? null, 'integer',0);
			$broadcast_status_id = DevblocksPlatform::importGPC($_POST['broadcast_status_id'] ?? null, 'integer',0);
			$broadcast_file_ids = DevblocksPlatform::sanitizeArray(DevblocksPlatform::importGPC($_POST['broadcast_file_ids'] ?? null,'array',[]), 'integer', array('nonzero','unique'));
			
			if(0 != strlen($do_broadcast) && !empty($broadcast_subject) && !empty($broadcast_message)) {
				$do['broadcast'] = [
					'to' => $broadcast_to,
					'subject' => $broadcast_subject,
					'message' => $broadcast_message,
					'format' => $broadcast_format,
					'html_template_id' => $broadcast_html_template_id,
					'is_queued' => $broadcast_is_queued,
					'status_id' => $broadcast_status_id,
					'group_id' => $broadcast_group_id,
					'bucket_id' => $broadcast_bucket_id,
					'worker_id' => $active_worker->id,
					'file_ids' => $broadcast_file_ids,
				];
			}
		}
			
		// Do: Custom fields
		$do = DAO_CustomFieldValue::handleBulkPost($do);
		
		switch($filter) {
			// Checked rows
			case 'checks':
				$ids_str = DevblocksPlatform::importGPC($_POST['ids'] ?? null, 'string');
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
			$view->addParams([
				new DevblocksSearchCriteria($search_class::ID, 'in', $ids)
			], true);
		}
		
		// Create batches
		$batch_key = DAO_ContextBulkUpdate::createFromView($view, $do);
		
		header('Content-Type: application/json; charset=utf-8');
		
		echo json_encode(array(
			'cursor' => $batch_key,
		));
		return;
	}
	
	private function _profileAction_viewExplore() {
		$active_worker = CerberusApplication::getActiveWorker();
		$url_writer = DevblocksPlatform::services()->url();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string');
		
		// Abstraction
		
		if(false == ($custom_record = DAO_CustomRecord::get(static::_ID)))
			return;
		
		// Generate hash
		$hash = md5($view_id.$active_worker->id.time());
		
		// Loop through view and get IDs
		$view = C4_AbstractViewLoader::getView($view_id);
		$view->setAutoPersist(false);

		// Page start
		$explore_from = DevblocksPlatform::importGPC($_POST['explore_from'] ?? null, 'integer',0);
		if(empty($explore_from)) {
			$orig_pos = 1+($view->renderPage * $view->renderLimit);
		} else {
			$orig_pos = 1;
		}

		$view->renderPage = 0;
		$view->renderLimit = 250;
		$pos = 0;
		
		do {
			$models = [];
			list($results, $total) = $view->getData();
			
			// Summary row
			if(0==$view->renderPage) {
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = [
					'title' => $view->name,
					'created' => time(),
					'total' => $total,
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy(sprintf('c=search&type=%s', $custom_record->uri), true),
				];
				$models[] = $model;
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
			foreach($results as $opp_id => $row) {
				if($opp_id==$explore_from)
					$orig_pos = $pos;
				
				$url = $url_writer->writeNoProxy(sprintf("c=profiles&type=%s&id=%d-%s", $custom_record->uri, $row[SearchFields_AbstractCustomRecord::ID], DevblocksPlatform::strToPermalink($row[SearchFields_AbstractCustomRecord::NAME])), true);
				
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'id' => $row[SearchFields_AbstractCustomRecord::ID],
					'url' => $url,
				);
				$models[] = $model;
			}
			
			DAO_ExplorerSet::createFromModels($models);
			
			$view->renderPage++;
			
		} while(!empty($results));
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(['explore', $hash, $orig_pos]));
	}
};
