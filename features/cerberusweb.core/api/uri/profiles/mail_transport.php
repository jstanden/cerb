<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2026, Webgroup Media LLC
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

class PageSection_ProfilesMailTransport extends Extension_PageSection {
	function render() {
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // mail_transport
		@$context_id = intval(array_shift($stack)); // 123

		$context = CerberusContexts::CONTEXT_MAIL_TRANSPORT;
		
		Page_Profiles::renderProfile($context, $context_id, $stack);
	}
	
	function handleActionForPage(string $action, ?string $scope=null) {
		if('profileAction' == $scope) {
			switch ($action) {
				case 'savePeekJson':
					return $this->_profileAction_savePeekJson();
				case 'getTransportParams':
					return $this->_profileAction_getTransportParams();
				case 'viewExplore':
					return $this->_profileAction_viewExplore();
				case 'viewSparklinesJson':
					return $this->_profileAction_viewSparklinesJson();
			}
		}
		return false;
	}

	// Inline sparkline series (stacked deliveries+failures bars) for the email transports worklist; loaded
	// async so the list paints fast. One metrics.timeseries query for the whole page (no N+1).
	private function _profileAction_viewSparklinesJson() {
		$active_worker = CerberusApplication::getActiveWorker();

		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');

		$ids = DevblocksPlatform::importGPC($_REQUEST['ids'] ?? [], 'array', []);
		$ids = array_filter(array_map('intval', $ids));

		$window = DevblocksPlatform::importGPC($_REQUEST['window'] ?? '1d', 'string', '1d');

		$row_series = [];

		// Each row: the two transport metrics filtered to that transport_id, as stacked bars. Deliveries
		// (green) on the bottom, failures (red) stacked on top; both share a `stack` key so they share
		// one scale and stack cumulatively.
		if($active_worker && $ids) {
			foreach($ids as $id) {
				$row_series[$id] = [
					// Deliveries (green) — successful outbound mail
					['metric' => 'cerb.mail.transport.deliveries', 'function' => 'sum', 'type' => 'bar', 'label' => 'deliveries', 'color' => '#2ca02c', 'stack' => 'mail', 'query' => ['transport_id' => $id], 'missing' => 'zero'],
					// Failures (red) — stacked on top
					['metric' => 'cerb.mail.transport.failures', 'function' => 'sum', 'type' => 'bar', 'label' => 'failures', 'color' => '#d62728', 'stack' => 'mail', 'query' => ['transport_id' => $id], 'missing' => 'zero'],
				];
			}
		}

		$out = $row_series
			? DAO_MetricValue::getSparklines($row_series, $window, $active_worker->timezone ?: null)
			: [];

		// Cast so the response is always a JSON object ({} when empty), keyed by transport id
		echo json_encode((object) $out);
	}
	
	private function _profileAction_savePeekJson() {
		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string', '');
		
		$id = DevblocksPlatform::importGPC($_POST['id'] ?? null, 'integer', 0);
		$do_delete = DevblocksPlatform::importGPC($_POST['do_delete'] ?? null, 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');
		
		try {
			// Only admins can edit mail transports
			if(!$active_worker->is_superuser) {
				throw new Exception_DevblocksAjaxValidationError("Only administrators can modify email transport records.");
			}
			
			if(!empty($id) && !empty($do_delete)) { // Delete
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", CerberusContexts::CONTEXT_MAIL_TRANSPORT)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				if(false == ($model = DAO_MailTransport::get($id)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.record.not_found'));
				
				if(!Context_MailTransport::isDeletableByActor($model, $active_worker))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				CerberusContexts::logActivityRecordDelete(CerberusContexts::CONTEXT_MAIL_TRANSPORT, $model->id, $model->name);
				
				DAO_MailTransport::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
				
			} else {
				$name = DevblocksPlatform::importGPC($_POST['name'] ?? null, 'string', '');
				$extension_id = DevblocksPlatform::importGPC($_POST['extension_id'] ?? null, 'string',null);
				$params = DevblocksPlatform::importGPC($_POST['params'][$extension_id] ?? null,'array',[]);
				
				if(empty($id)) { // New
					$fields = array(
						DAO_MailTransport::EXTENSION_ID => $extension_id,
						DAO_MailTransport::NAME => $name,
						DAO_MailTransport::PARAMS_JSON => json_encode($params),
						DAO_MailTransport::UPDATED_AT => time(),
					);
					
					if(!DAO_MailTransport::validate($fields, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_MailTransport::onBeforeUpdateByActor($active_worker, $fields, null, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!$this->_testTransportParamsAction($extension_id, $params, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					$id = DAO_MailTransport::create($fields);
					DAO_MailTransport::onUpdateByActor($active_worker, $fields, $id);
					
					if(!empty($view_id) && !empty($id))
						C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_MAIL_TRANSPORT, $id);
					
				} else { // Edit
					$fields = array(
						DAO_MailTransport::EXTENSION_ID => $extension_id,
						DAO_MailTransport::NAME => $name,
						DAO_MailTransport::PARAMS_JSON => json_encode($params),
						DAO_MailTransport::UPDATED_AT => time(),
					);
					
					if(!DAO_MailTransport::validate($fields, $error, $id))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_MailTransport::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!$this->_testTransportParamsAction($extension_id, $params, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					DAO_MailTransport::update($id, $fields);
					DAO_MailTransport::onUpdateByActor($active_worker, $fields, $id);
				}
				
				// Custom field saves
				$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'] ?? null, 'array', []);
				if(!DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_MAIL_TRANSPORT, $id, $field_ids, $error))
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
	
	private function _profileAction_getTransportParams() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		$id = DevblocksPlatform::importGPC($_REQUEST['id'] ?? null, 'integer',0);
		$extension_id = DevblocksPlatform::importGPC($_REQUEST['extension_id'] ?? null, 'string',null);
		
		if(!$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		if(false == ($mail_transport_ext = Extension_MailTransport::get($extension_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(empty($id) || false == ($model = DAO_MailTransport::get($id))) {
			$model = new Model_MailTransport();
			$model->extension_id = $mail_transport_ext->id;
		}
		
		$mail_transport_ext->renderConfig($model);
		
		exit;
	}
	
	private function _testTransportParamsAction($extension_id, array $params, &$error=null) {
		try {
			if(empty($extension_id) || false == ($mail_transport_ext = Extension_MailTransport::get($extension_id))) {
				$error = 'The "transport" field is required.';
				return false;
			}
			
			// Test the transport specfic parameters
			if(false == $mail_transport_ext->testConfig($params, $error)) {
				return false;
			}
			
		} catch(Exception $e) {
			$error = 'A problem occurred. Please check your settings and try again.';
			return false;
		}
		
		$error = null;
		return true;
	}
	
	private function _profileAction_viewExplore() {
		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string', '');
		$explore_from = DevblocksPlatform::importGPC($_POST['explore_from'] ?? null, 'int', 0);
		
		$http_response = Cerb_ORMHelper::generateRecordExploreSet($view_id, $explore_from);
		DevblocksPlatform::redirect($http_response);
	}
};
