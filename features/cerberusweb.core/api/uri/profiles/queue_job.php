<?php
class PageSection_ProfilesQueueJob extends Extension_PageSection {
	function render() {
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // queue_job
		@$context_id = intval(array_shift($stack)); // 123

		$context = Context_QueueJob::ID;

		Page_Profiles::renderProfile($context, $context_id, $stack);
	}

	function handleActionForPage(string $action, ?string $scope=null) {
		if('profileAction' == $scope) {
			switch($action) {
				case 'savePeekJson':
					return $this->_profileAction_savePeekJson();
				case 'viewExplore':
					return $this->_profileAction_viewExplore();
			}
		}
		return false;
	}

	private function _profileAction_savePeekJson() {
		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string', '');

		$id = DevblocksPlatform::importGPC($_POST['id'] ?? null, 'integer', 0);
		$do_delete = DevblocksPlatform::importGPC($_POST['do_delete'] ?? null, 'string', '');

		$active_worker = CerberusApplication::getActiveWorker();

		$context = Context_QueueJob::ID;

		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);

		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');

		try {
			if(!($model = DAO_QueueJob::get($id)))
				throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.record.not_found'));

			if(!$active_worker->is_superuser)
				throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.admin'));
			
			if(!empty($do_delete)) { // Delete
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", $context)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));

				if(!Context_QueueJob::isDeletableByActor($model, $active_worker))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));

				CerberusContexts::logActivityRecordDelete($context, $model->id, $model->name);

				DAO_QueueJob::delete($id);

				echo json_encode([
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				]);
				return;

			} else {
				// Status change (pause/resume) — admin only
				$set_status = DevblocksPlatform::importGPC($_POST['set_status'] ?? null, 'integer', -1);

				if($set_status >= 0) {
					$new_status = match($set_status) {
						QueueJobStatus::RUNNING->value => QueueJobStatus::RUNNING,
						QueueJobStatus::PAUSED->value  => QueueJobStatus::PAUSED,
						default => null,
					};

					if(is_null($new_status))
						throw new Exception_DevblocksAjaxValidationError('Invalid status');

					DAO_QueueJob::setStatus([$id], $new_status);
				}

				if(!($model = DAO_QueueJob::get($id)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.record.not_found'));

				echo json_encode([
					'status' => true,
					'context' => $context,
					'id' => $id,
					'label' => $model->name,
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

	private function _profileAction_viewExplore() {
		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string', '');
		$explore_from = DevblocksPlatform::importGPC($_POST['explore_from'] ?? null, 'int', 0);

		$http_response = Cerb_ORMHelper::generateRecordExploreSet($view_id, $explore_from);
		DevblocksPlatform::redirect($http_response);
	}
}
