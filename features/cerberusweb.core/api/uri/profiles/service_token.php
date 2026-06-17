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

class PageSection_ProfilesServiceToken extends Extension_PageSection {
	function render() {
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // service_token
		@$context_id = intval(array_shift($stack)); // 123

		$context = Context_ServiceToken::ID;

		Page_Profiles::renderProfile($context, $context_id, $stack);
	}

	function handleActionForPage(string $action, ?string $scope=null) {
		if('profileAction' == $scope) {
			switch($action) {
				case 'savePeekJson':
					return $this->_profileAction_savePeekJson();
				case 'viewExplore':
					return $this->_profileAction_viewExplore();
				case 'viewSparklinesJson':
					return $this->_profileAction_viewSparklinesJson();
			}
		}
		return false;
	}

	// Inline sparkline series (uses line) for the service tokens worklist; loaded async so the list paints
	// fast. One metrics.timeseries query for the whole page (no N+1).
	private function _profileAction_viewSparklinesJson() {
		$active_worker = CerberusApplication::getActiveWorker();

		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');

		$ids = DevblocksPlatform::importGPC($_REQUEST['ids'] ?? [], 'array', []);
		$ids = array_filter(array_map('intval', $ids));

		$window = DevblocksPlatform::importGPC($_REQUEST['window'] ?? '1d', 'string', '1d');

		$row_series = [];

		// Each row: the token's authentication count (uses) as a blue line, filtered to that token_id.
		// Service tokens are admin-only, so this requires a logged-in worker.
		if($active_worker && $ids) {
			foreach($ids as $id) {
				$row_series[$id] = [
					['metric' => 'cerb.service.token.uses', 'function' => 'count', 'type' => 'line', 'label' => 'uses', 'color' => '#0088e6', 'query' => ['token_id' => $id], 'missing' => 'zero'],
				];
			}
		}

		$out = $row_series
			? DAO_MetricValue::getSparklines($row_series, $window, $active_worker->timezone ?: null)
			: [];

		// Cast so the response is always a JSON object ({} when empty), keyed by token id
		echo json_encode((object) $out);
	}

	private function _profileAction_savePeekJson() {
		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string', '');

		$id = DevblocksPlatform::importGPC($_POST['id'] ?? null, 'integer', 0);
		$do_delete = DevblocksPlatform::importGPC($_POST['do_delete'] ?? null, 'string', '');

		$active_worker = CerberusApplication::getActiveWorker();

		$context = Context_ServiceToken::ID;

		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');

		try {
			if(!$active_worker->is_superuser)
				throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.admin'));
	
			if(!empty($id) && !empty($do_delete)) { // Delete
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", $context)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));

				if(!($model = DAO_ServiceToken::get($id)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.record.not_found'));

				if(!Context_ServiceToken::isDeletableByActor($model, $active_worker))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));

				CerberusContexts::logActivityRecordDelete($context, $model->id, $model->name);

				DAO_ServiceToken::delete($id);

				echo json_encode([
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				]);
				return;

			} else {
				$name = DevblocksPlatform::importGPC($_POST['name'] ?? null, 'string', '');
				$token = DevblocksPlatform::importGPC($_POST['token'] ?? null, 'string', '');
				$scope = DevblocksPlatform::importGPC($_POST['scope'] ?? null, 'array', []);

				$error = null;

				// [TODO] Expiration

				$fields = [
					DAO_ServiceToken::NAME => $name,
					DAO_ServiceToken::SCOPES => implode(' ', DevblocksPlatform::sanitizeArray($scope, 'string')),
					DAO_ServiceToken::UPDATED_AT => time(),
				];

				if(empty($id)) { // New
					$fields[DAO_ServiceToken::CREATED_AT] = time();
					
					// Generate a random token
					if(!$token) {
						if(!($token = DAO_ServiceToken::generateToken()))
							throw new Exception_DevblocksAjaxValidationError("Failed to generate a random token.");
					}
						
					$fields[DAO_ServiceToken::TOKEN_HINT] = sprintf("%s...%s", substr($token,0,6), substr($token,-1));
					$fields[DAO_ServiceToken::TOKEN_HASH] = hash('sha256', $token);
					
					if(!DAO_ServiceToken::validate($fields, $error))
						throw new Exception_DevblocksAjaxValidationError($error);

					if(!DAO_ServiceToken::onBeforeUpdateByActor($active_worker, $fields, null, $error))
						throw new Exception_DevblocksAjaxValidationError($error);

					$id = DAO_ServiceToken::create($fields);
					DAO_ServiceToken::onUpdateByActor($active_worker, $fields, $id);

					if(!empty($view_id) && !empty($id))
						C4_AbstractView::setMarqueeContextCreated($view_id, $context, $id);

				} else { // Edit
					if(!DAO_ServiceToken::validate($fields, $error, $id))
						throw new Exception_DevblocksAjaxValidationError($error);

					if(!DAO_ServiceToken::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
						throw new Exception_DevblocksAjaxValidationError($error);

					DAO_ServiceToken::update($id, $fields);
					DAO_ServiceToken::onUpdateByActor($active_worker, $fields, $id);
				}

				if($id) {
					// Custom field saves
					$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'] ?? null, 'array', []);
					if(!DAO_CustomFieldValue::handleFormPost($context, $id, $field_ids, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
				}

				echo json_encode([
					'status' => true,
					'context' => $context,
					'id' => $id,
					'label' => $name,
					'view_id' => $view_id,
				]);
				return;
			}

		} catch (Exception_DevblocksAjaxValidationError $e) {
			echo json_encode([
				'status' => false,
				'error' => $e->getMessage(),
				'field' => $e->getFieldName(),
			]);
			return;

		} catch (Throwable) {
			echo json_encode([
				'status' => false,
				'error' => 'An error occurred.',
			]);
			return;
		}
	}

	private function _profileAction_viewExplore() {
		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string', '');
		$explore_from = DevblocksPlatform::importGPC($_POST['explore_from'] ?? null, 'int', 0);

		$http_response = Cerb_ORMHelper::generateRecordExploreSet($view_id, $explore_from);
		DevblocksPlatform::redirect($http_response);
	}
};
