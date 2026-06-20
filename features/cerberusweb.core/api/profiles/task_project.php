<?php
class PageSection_ProfilesTaskProject extends Extension_PageSection {
	function render() {
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // task_project
		@$context_id = intval(array_shift($stack)); // 123

		$context = Context_TaskProject::ID;

		Page_Profiles::renderProfile($context, $context_id, $stack);
	}

	function handleActionForPage(string $action, ?string $scope=null) {
		if('profileAction' == $scope) {
			switch($action) {
				case 'savePeekJson':
					return $this->_profileAction_savePeekJson();
				case 'viewExplore':
					return $this->_profileAction_viewExplore();
				case 'viewTasksJson':
					return $this->_profileAction_viewTasksJson();
			}
		}
		return false;
	}

	private function _profileAction_savePeekJson() {
		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string', '');

		$id = DevblocksPlatform::importGPC($_POST['id'] ?? null, 'integer', 0);
		$do_delete = DevblocksPlatform::importGPC($_POST['do_delete'] ?? null, 'string', '');

		$active_worker = CerberusApplication::getActiveWorker();

		$context = Context_TaskProject::ID;

		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);

		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');

		try {
			if(!empty($id) && !empty($do_delete)) { // Delete
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", $context)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));

				if(!($model = DAO_TaskProject::get($id)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.record.not_found'));

				if(!Context_TaskProject::isDeletableByActor($model, $active_worker))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));

				CerberusContexts::logActivityRecordDelete($context, $model->id, $model->name);

				DAO_TaskProject::delete($id);

				echo json_encode([
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				]);
				return;

			} else {
				$name = DevblocksPlatform::importGPC($_POST['name'] ?? null, 'string', '');
				$is_closed = DevblocksPlatform::importGPC($_POST['is_closed'] ?? null, 'integer', 0);

				// Owner is "context:id" (worker/group/role/app); blank or invalid → none
				list($owner_context, $owner_context_id) = array_pad(explode(':', DevblocksPlatform::importGPC($_POST['owner'] ?? null, 'string', '')), 2, null);

				switch($owner_context) {
					case CerberusContexts::CONTEXT_APPLICATION:
					case CerberusContexts::CONTEXT_ROLE:
					case CerberusContexts::CONTEXT_GROUP:
					case CerberusContexts::CONTEXT_WORKER:
						break;
					default:
						$owner_context = null;
						$owner_context_id = null;
						break;
				}

				$error = null;

				if(empty($id)) { // New
					// Default to a private (worker-owned) project when no owner chosen
					if(!$owner_context) {
						$owner_context = CerberusContexts::CONTEXT_WORKER;
						$owner_context_id = $active_worker->id;
					}

					$fields = [
						DAO_TaskProject::UPDATED_AT => time(),
						DAO_TaskProject::NAME => $name,
						DAO_TaskProject::IS_CLOSED => $is_closed,
						DAO_TaskProject::OWNER_CONTEXT => $owner_context,
						DAO_TaskProject::OWNER_CONTEXT_ID => $owner_context_id,
					];

					if(!DAO_TaskProject::validate($fields, $error))
						throw new Exception_DevblocksAjaxValidationError($error);

					if(!DAO_TaskProject::onBeforeUpdateByActor($active_worker, $fields, null, $error))
						throw new Exception_DevblocksAjaxValidationError($error);

					$id = DAO_TaskProject::create($fields);
					DAO_TaskProject::onUpdateByActor($active_worker, $fields, $id);

					if(!empty($view_id) && !empty($id))
						C4_AbstractView::setMarqueeContextCreated($view_id, $context, $id);

				} else { // Edit
					$fields = [
						DAO_TaskProject::UPDATED_AT => time(),
						DAO_TaskProject::NAME => $name,
						DAO_TaskProject::IS_CLOSED => $is_closed,
					];

					// Only reassign the owner when one was explicitly chosen
					if($owner_context) {
						$fields[DAO_TaskProject::OWNER_CONTEXT] = $owner_context;
						$fields[DAO_TaskProject::OWNER_CONTEXT_ID] = $owner_context_id;
					}

					if(!DAO_TaskProject::validate($fields, $error, $id))
						throw new Exception_DevblocksAjaxValidationError($error);

					if(!DAO_TaskProject::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
						throw new Exception_DevblocksAjaxValidationError($error);

					DAO_TaskProject::update($id, $fields);
					DAO_TaskProject::onUpdateByActor($active_worker, $fields, $id);
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

		} catch (Exception $e) {
			echo json_encode([
				'status' => false,
				'error' => 'An error occurred.',
			]);
			return;
		}
	}

	// Batched task counts (todo/in-progress/waiting/done) for the worklist "Tasks" distbar column.
	private function _profileAction_viewTasksJson() {
		$active_worker = CerberusApplication::getActiveWorker();

		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');

		$ids = DevblocksPlatform::importGPC($_REQUEST['ids'] ?? [], 'array', []);
		$ids = array_filter(array_map('intval', $ids));

		$out = [];

		if($active_worker && $ids) {
			// Projects can be private; only count those the worker may read
			$models = DAO_TaskProject::getIds($ids);
			$readable = Context_TaskProject::isReadableByActor($models, $active_worker);
			$ids = array_keys(array_filter($readable));

			if($ids)
				$out = DAO_TaskProject::getTaskCountsForProjects($ids);
		}

		// Cast so the response is always a JSON object ({} when empty), keyed by project id
		echo json_encode((object) $out);
	}

	private function _profileAction_viewExplore() {
		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string', '');
		$explore_from = DevblocksPlatform::importGPC($_POST['explore_from'] ?? null, 'int', 0);

		$http_response = Cerb_ORMHelper::generateRecordExploreSet($view_id, $explore_from);
		DevblocksPlatform::redirect($http_response);
	}
};
