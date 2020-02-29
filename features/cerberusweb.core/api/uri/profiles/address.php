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

class PageSection_ProfilesAddress extends Extension_PageSection {
	function render() {
		$response = DevblocksPlatform::getHttpResponse();
		$context = CerberusContexts::CONTEXT_ADDRESS;
		
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // address
		@$context_id = intval(array_shift($stack));
		
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
		
		header('Content-Type: application/json; charset=utf-8');
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		try {
			@$id = DevblocksPlatform::importGPC($_POST['id'],'integer', 0);
			@$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string', '');
			@$email = mb_convert_case(trim(DevblocksPlatform::importGPC($_POST['email'],'string','')), MB_CASE_LOWER);
			@$contact_id = DevblocksPlatform::importGPC($_POST['contact_id'],'integer',0);
			@$org_id = DevblocksPlatform::importGPC($_POST['org_id'],'integer',0);
			@$is_banned = DevblocksPlatform::importGPC($_POST['is_banned'],'bit',0);
			@$is_defunct = DevblocksPlatform::importGPC($_POST['is_defunct'],'bit',0);
			
			// Common fields
			$fields = [
				DAO_Address::CONTACT_ORG_ID => $org_id,
				DAO_Address::CONTACT_ID => $contact_id,
				DAO_Address::IS_BANNED => $is_banned,
				DAO_Address::IS_DEFUNCT => $is_defunct,
			];
			
			if($active_worker->is_superuser) {
				@$type = DevblocksPlatform::importGPC($_POST['type'],'string', '');
				
				$fields[DAO_Address::MAIL_TRANSPORT_ID] = 0;
				$fields[DAO_Address::WORKER_ID] = 0;
				
				switch($type) {
					case 'transport':
						@$mail_transport_id = DevblocksPlatform::importGPC($_POST['mail_transport_id'],'integer',0);
						$fields[DAO_Address::MAIL_TRANSPORT_ID] = $mail_transport_id;
						break;
						
					case 'worker':
						@$worker_id = DevblocksPlatform::importGPC($_POST['worker_id'],'integer',0);
						$fields[DAO_Address::WORKER_ID] = $worker_id;
						break;
						
					default:
						break;
				}
				
				DAO_Address::clearCache();
			}
			
			$error = null;
			
			if(empty($id)) {
				$fields[DAO_Address::EMAIL] = $email;
				
				if(!DAO_Address::validate($fields, $error))
					throw new Exception_DevblocksAjaxValidationError($error);
				
				if(!DAO_Address::onBeforeUpdateByActor($active_worker, $fields, null, $error))
					throw new Exception_DevblocksAjaxValidationError($error);

				if(false == ($id = DAO_Address::create($fields)))
					throw new Exception_DevblocksAjaxValidationError('An unexpected error occurred while trying to save the record.');
				
				DAO_Address::onUpdateByActor($active_worker, $fields, $id);
				
				// View marquee
				if(!empty($id) && !empty($view_id)) {
					C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_ADDRESS, $id);
				}
				
			} else {
				if(!DAO_Address::validate($fields, $error, $id))
					throw new Exception_DevblocksAjaxValidationError($error);
				
				if(!DAO_Address::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
					throw new Exception_DevblocksAjaxValidationError($error);
				
				DAO_Address::update($id, $fields);
				DAO_Address::onUpdateByActor($active_worker, $fields, $id);
			}
	
			if($id) {
				// Custom field saves
				@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', []);
				if(!DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_ADDRESS, $id, $field_ids, $error))
					throw new Exception_DevblocksAjaxValidationError($error);
				
				// Index immediately
				$search = Extension_DevblocksSearchSchema::get(Search_Address::ID);
				$search->indexIds(array($id));
			}
			
			/*
			 * Notify anything that wants to know when Address Peek saves.
			 */
			$eventMgr = DevblocksPlatform::services()->event();
			$eventMgr->trigger(
				new Model_DevblocksEvent(
					'address.peek.saved',
					array(
						'address_id' => $id,
						'changed_fields' => $fields,
					)
				)
			);
			
			echo json_encode(array(
				'status' => true,
				'id' => $id,
				'label' => $email,
				'view_id' => $view_id,
			));
			
		} catch (Exception_DevblocksAjaxValidationError $e) {
				echo json_encode(array(
					'status' => false,
					'id' => $id,
					'error' => $e->getMessage(),
					'field' => $e->getFieldName(),
				));
			
		} catch (Exception $e) {
				echo json_encode(array(
					'status' => false,
					'id' => $id,
					'error' => 'An unexpected error occurred.',
				));
		}
	}
	
	private function _profileAction_showBulkPopup() {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker->hasPriv(sprintf('contexts.%s.update.bulk', Context_Address::ID)))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		@$ids = DevblocksPlatform::importGPC($_REQUEST['ids']);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id']);

		$tpl->assign('view_id', $view_id);

		if(!empty($ids)) {
			$tpl->assign('ids', $ids);
		}
		
		// Custom fields
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_ADDRESS, false);
		$tpl->assign('custom_fields', $custom_fields);
		
		// Groups
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		$buckets = DAO_Bucket::getAll();
		$tpl->assign('buckets', $buckets);
		
		// HTML templates
		$html_templates = DAO_MailHtmlTemplate::getAll();
		$tpl->assign('html_templates', $html_templates);
		
		// Broadcast
		if(false == ($context_ext = Extension_DevblocksContext::get(CerberusContexts::CONTEXT_ADDRESS)))
			return [];
		
		/* @var $context_ext IDevblocksContextBroadcast */
			
		// Recipient fields
		$recipient_fields = $context_ext->broadcastRecipientFieldsGet();
		$tpl->assign('broadcast_recipient_fields', $recipient_fields);
		
		// Placeholders
		$token_values = $context_ext->broadcastPlaceholdersGet();
		@$token_labels = $token_values['_labels'] ?: [];
		
		$placeholders = Extension_DevblocksContext::getPlaceholderTree($token_labels);
		$tpl->assign('placeholders', $placeholders);
		
		$tpl->display('devblocks:cerberusweb.core::contacts/addresses/bulk.tpl');
	}
	
	private function _profileAction_startBulkUpdateJson() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		if(!$active_worker->hasPriv(sprintf('contexts.%s.update.bulk', Context_Address::ID)))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		@$filter = DevblocksPlatform::importGPC($_POST['filter'],'string','');
		$ids = [];
		
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string');
		$view = C4_AbstractViewLoader::getView($view_id);
		$view->setAutoPersist(false);

		@$org_id = DevblocksPlatform::importGPC($_POST['org_id'],'string',null);
		@$sla = DevblocksPlatform::importGPC($_POST['sla'],'string','');
		@$is_banned = DevblocksPlatform::importGPC($_POST['is_banned'],'integer',0);
		@$is_defunct = DevblocksPlatform::importGPC($_POST['is_defunct'],'integer',0);
		@$is_trusted = DevblocksPlatform::importGPC($_POST['is_trusted'],'integer',0);
		@$mail_transport_id = DevblocksPlatform::importGPC($_POST['mail_transport_id'],'string',null);

		// Scheduled behavior
		@$behavior_id = DevblocksPlatform::importGPC($_POST['behavior_id'],'string','');
		@$behavior_when = DevblocksPlatform::importGPC($_POST['behavior_when'],'string','');
		@$behavior_params = DevblocksPlatform::importGPC($_POST['behavior_params'],'array',[]);
		
		$do = [];
		
		// Do: Organization
		if(0 != strlen($org_id)) {
			$do['org_id'] = $org_id;
		}
		
		// Do: SLA
		if('' != $sla)
			$do['sla'] = $sla;
		
		// Do: Banned
		if(0 != strlen($is_banned))
			$do['banned'] = $is_banned;
		
		// Do: Defunct
		if(0 != strlen($is_defunct))
			$do['defunct'] = $is_defunct;
		
		// Do: Trusted
		if(0 != strlen($is_trusted))
			$do['trusted'] = $is_trusted;
		
		// Do: Mail Transport
		if($active_worker->is_superuser 
			&& 0 != strlen($mail_transport_id) 
				&& false !== DAO_MailTransport::get($mail_transport_id))
					$do['mail_transport_id'] = $mail_transport_id;
		
		// Do: Scheduled Behavior
		if(0 != strlen($behavior_id)) {
			$do['behavior'] = array(
				'id' => $behavior_id,
				'when' => $behavior_when,
				'params' => $behavior_params,
			);
		}
		
		// Broadcast: Compose
		if($active_worker->hasPriv('contexts.cerberusweb.contexts.address.broadcast')) {
			@$do_broadcast = DevblocksPlatform::importGPC($_POST['do_broadcast'],'string',null);
			@$broadcast_group_id = DevblocksPlatform::importGPC($_POST['broadcast_group_id'],'integer',0);
			@$broadcast_bucket_id = DevblocksPlatform::importGPC($_POST['broadcast_bucket_id'],'integer',0);
			@$broadcast_to = DevblocksPlatform::importGPC($_POST['broadcast_to'],'array',[]);
			@$broadcast_subject = DevblocksPlatform::importGPC($_POST['broadcast_subject'],'string',null);
			@$broadcast_message = DevblocksPlatform::importGPC($_POST['broadcast_message'],'string',null);
			@$broadcast_format = DevblocksPlatform::importGPC($_POST['broadcast_format'],'string',null);
			@$broadcast_html_template_id = DevblocksPlatform::importGPC($_POST['broadcast_html_template_id'],'integer',0);
			@$broadcast_is_queued = DevblocksPlatform::importGPC($_POST['broadcast_is_queued'],'integer',0);
			@$broadcast_status_id = DevblocksPlatform::importGPC($_POST['broadcast_status_id'],'integer',0);
			@$broadcast_file_ids = DevblocksPlatform::sanitizeArray(DevblocksPlatform::importGPC($_POST['broadcast_file_ids'],'array',array()), 'integer', array('nonzero','unique'));
			
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
				@$address_id_str = DevblocksPlatform::importGPC($_POST['ids'],'string');
				$ids = DevblocksPlatform::parseCsvString($address_id_str);
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
				new DevblocksSearchCriteria(SearchFields_Address::ID, 'in', $ids)
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
		
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string');
		
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
					'worker_id' => $active_worker->id,
					'total' => $total,
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=search&tab=address', true),
				);
				$models[] = $model;
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
				foreach($results as $org_id => $row) {
					if($org_id==$explore_from)
						$orig_pos = $pos;
					
					$model = new Model_ExplorerSet();
					$model->hash = $hash;
					$model->pos = $pos++;
					$model->params = array(
						'id' => $row[SearchFields_Address::ID],
						'url' => $url_writer->writeNoProxy(sprintf("c=profiles&type=address&id=%d-%s", $row[SearchFields_Address::ID], $row[SearchFields_Address::EMAIL]), true),
					);
					$models[] = $model;
				}
			
			DAO_ExplorerSet::createFromModels($models);
			
			$view->renderPage++;
			
		} while(!empty($results));
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('explore',$hash,$orig_pos)));
	}
}