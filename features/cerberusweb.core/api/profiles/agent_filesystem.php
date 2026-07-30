<?php
class PageSection_ProfilesAgentFilesystem extends Extension_PageSection {
	function render() {
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // agent_filesystem
		@$context_id = intval(array_shift($stack)); // 123

		$context = Context_AgentFilesystem::ID;

		Page_Profiles::renderProfile($context, $context_id, $stack);
	}

	function handleActionForPage(string $action, ?string $scope=null) {
		if('profileAction' == $scope) {
			switch($action) {
				case 'renderImportPopup':
					return $this->_profileAction_renderImportPopup();
				case 'importZipJson':
					return $this->_profileAction_importZipJson();
				case 'startImportJson':
					return $this->_profileAction_startImportJson();
				case 'savePeekJson':
					return $this->_profileAction_savePeekJson();
				case 'viewExplore':
					return $this->_profileAction_viewExplore();
			}
		}
		return false;
	}

	private function _profileAction_renderImportPopup() {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();

		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);

		$filesystem_id = DevblocksPlatform::importGPC($_REQUEST['filesystem_id'] ?? null, 'integer', 0);
		// The card widget's DOM suffix, so a completed upload can hand the job back to it for monitoring
		$widget_uid = DevblocksPlatform::importGPC($_REQUEST['widget_uid'] ?? null, 'string', '');
		$widget_uid = preg_replace('/[^A-Za-z0-9_]/', '', $widget_uid);

		if(!($filesystem = DAO_AgentFilesystem::get($filesystem_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);

		$tpl->assign('filesystem', $filesystem);
		$tpl->assign('widget_uid', $widget_uid);
		$tpl->display('devblocks:cerberusweb.core::internal/agent_filesystem/import_popup.tpl');
		return true;
	}

	/**
	 * Report what's inside an uploaded ZIP so the popup can offer the import options in place. The bytes
	 * are already an ephemeral automation resource -- CerbUI.FileUpload stashed them there on drop.
	 */
	private function _profileAction_importZipJson() {
		$active_worker = CerberusApplication::getActiveWorker();

		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);

		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);

		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');

		$import_token = DevblocksPlatform::importGPC($_POST['import_token'] ?? null, 'string', '');

		if(!($resource = DAO_AutomationResource::getByToken($import_token))) {
			echo json_encode(['status' => false, 'error' => 'The uploaded file could not be found.']);
			return true;
		}

		// chooserOpenFileAjaxUpload gives ephemeral resources 1h; the import job needs the archive longer
		DAO_AutomationResource::update($resource->id, [
			DAO_AutomationResource::EXPIRES_AT => time() + 86400,
		]);

		$error = null;

		if(!($manifest = \Cerb\Agent\FilesystemImporter::inspect($import_token, $error))) {
			echo json_encode(['status' => false, 'error' => $error ?: 'The archive could not be read.']);
			return true;
		}

		echo json_encode([
			'status' => true,
			'import_token' => $import_token,
			'num_files' => $manifest['num_files'],
			'num_skipped' => $manifest['num_skipped'],
			'common_prefix' => $manifest['common_prefix'],
		]);
		return true;
	}

	private function _profileAction_startImportJson() {
		$active_worker = CerberusApplication::getActiveWorker();

		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);

		// Agent filesystems are admin-managed (see Context_AgentFilesystem::isWriteableByActor)
		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);

		$filesystem_id = DevblocksPlatform::importGPC($_POST['filesystem_id'] ?? null, 'integer', 0);
		$import_token = DevblocksPlatform::importGPC($_POST['import_token'] ?? null, 'string', '');
		$strip_prefix = DevblocksPlatform::importGPC($_POST['strip_prefix'] ?? null, 'string', '');
		$prune_missing = DevblocksPlatform::importGPC($_POST['prune_missing'] ?? null, 'integer', 0);

		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');

		$error = null;

		$queue_job = \Cerb\Agent\FilesystemImporter::createJob($filesystem_id, $import_token, [
			'strip_prefix' => $strip_prefix,
			'prune_missing' => $prune_missing ? true : false,
		], $error);

		if(!$queue_job) {
			echo json_encode(['status' => false, 'error' => $error ?: 'Failed to queue the import job.']);
			return true;
		}

		// The client monitors the job, which is also what spins up workers
		echo json_encode(['status' => true, 'job_id' => $queue_job->id]);
		return true;
	}

	private function _profileAction_savePeekJson() {
		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string', '');

		$id = DevblocksPlatform::importGPC($_POST['id'] ?? null, 'integer', 0);
		$do_delete = DevblocksPlatform::importGPC($_POST['do_delete'] ?? null, 'string', '');

		$active_worker = CerberusApplication::getActiveWorker();

		$context = Context_AgentFilesystem::ID;

		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);

		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');

		try {
			if(!$active_worker->is_superuser)
				throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.admin'));

			if(!empty($id) && !empty($do_delete)) { // Delete
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", $context)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));

				if(!($model = DAO_AgentFilesystem::get($id)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.record.not_found'));

				if(!Context_AgentFilesystem::isDeletableByActor($model, $active_worker))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));

				CerberusContexts::logActivityRecordDelete($context, $model->id, $model->name);

				DAO_AgentFilesystem::delete($id);

				echo json_encode([
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				]);
				return;

			} else {
				$name = DevblocksPlatform::importGPC($_POST['name'] ?? null, 'string', '');
				$description = DevblocksPlatform::importGPC($_POST['description'] ?? null, 'string', '');
				$is_disabled = DevblocksPlatform::importGPC($_POST['is_disabled'] ?? null, 'integer', 0);

				$error = null;

				$fields = [
					DAO_AgentFilesystem::UPDATED_AT => time(),
					DAO_AgentFilesystem::NAME => $name,
					DAO_AgentFilesystem::DESCRIPTION => $description,
					DAO_AgentFilesystem::IS_DISABLED => $is_disabled ? 1 : 0,
				];

				if(empty($id)) { // New
					if(!DAO_AgentFilesystem::validate($fields, $error))
						throw new Exception_DevblocksAjaxValidationError($error);

					if(!DAO_AgentFilesystem::onBeforeUpdateByActor($active_worker, $fields, null, $error))
						throw new Exception_DevblocksAjaxValidationError($error);

					$id = DAO_AgentFilesystem::create($fields);
					DAO_AgentFilesystem::onUpdateByActor($active_worker, $fields, $id);

					if(!empty($view_id) && !empty($id))
						C4_AbstractView::setMarqueeContextCreated($view_id, $context, $id);

				} else { // Edit
					if(!DAO_AgentFilesystem::validate($fields, $error, $id))
						throw new Exception_DevblocksAjaxValidationError($error);

					if(!DAO_AgentFilesystem::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
						throw new Exception_DevblocksAjaxValidationError($error);

					DAO_AgentFilesystem::update($id, $fields);
					DAO_AgentFilesystem::onUpdateByActor($active_worker, $fields, $id);
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

	private function _profileAction_viewExplore() {
		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string', '');
		$explore_from = DevblocksPlatform::importGPC($_POST['explore_from'] ?? null, 'int', 0);

		$http_response = Cerb_ORMHelper::generateRecordExploreSet($view_id, $explore_from);
		DevblocksPlatform::redirect($http_response);
	}
};
