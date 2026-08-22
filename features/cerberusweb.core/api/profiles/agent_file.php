<?php
class PageSection_ProfilesAgentFile extends Extension_PageSection {
	function render() {
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // agent_file
		@$context_id = intval(array_shift($stack)); // 123

		$context = Context_AgentFile::ID;

		Page_Profiles::renderProfile($context, $context_id, $stack);
	}

	function handleActionForPage(string $action, ?string $scope=null) {
		if('profileAction' == $scope) {
			switch($action) {
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
		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string', '');

		$id = DevblocksPlatform::importGPC($_POST['id'] ?? null, 'integer', 0);
		$do_delete = DevblocksPlatform::importGPC($_POST['do_delete'] ?? null, 'string', '');

		$active_worker = CerberusApplication::getActiveWorker();

		$context = Context_AgentFile::ID;

		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);

		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');

		try {
			if(!$active_worker->is_superuser)
				throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.admin'));

			if(!empty($id) && !empty($do_delete)) { // Delete
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", $context)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));

				if(!($model = DAO_AgentFile::get($id)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.record.not_found'));

				if(!Context_AgentFile::isDeletableByActor($model, $active_worker))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));

				CerberusContexts::logActivityRecordDelete($context, $model->id, $model->name);

				DAO_AgentFile::delete($id);

				echo json_encode([
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				]);
				return;

			} else {
				$name = DevblocksPlatform::importGPC($_POST['name'] ?? null, 'string', '');
				$filesystem_id = DevblocksPlatform::importGPC($_POST['filesystem_id'] ?? null, 'integer', 0);
				$content = DevblocksPlatform::importGPC($_POST['content'] ?? null, 'string', '');

				$error = null;

				// `name` holds the virtual filesystem path. sha1/size/file_extension/frontmatter_json are
				// derived by the DAO from the content + that path, the same as an import or a VFS write.
				$fields = [
					DAO_AgentFile::UPDATED_AT => time(),
					DAO_AgentFile::NAME => $name,
					DAO_AgentFile::FILESYSTEM_ID => $filesystem_id,
					DAO_AgentFile::CONTENT => $content,
				];

				if(empty($id)) { // New
					if(!DAO_AgentFile::validate($fields, $error))
						throw new Exception_DevblocksAjaxValidationError($error);

					if(!DAO_AgentFile::onBeforeUpdateByActor($active_worker, $fields, null, $error))
						throw new Exception_DevblocksAjaxValidationError($error);

					$id = DAO_AgentFile::create($fields);
					DAO_AgentFile::onUpdateByActor($active_worker, $fields, $id);

					if(!empty($view_id) && !empty($id))
						C4_AbstractView::setMarqueeContextCreated($view_id, $context, $id);

				} else { // Edit
					if(!DAO_AgentFile::validate($fields, $error, $id))
						throw new Exception_DevblocksAjaxValidationError($error);

					if(!DAO_AgentFile::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
						throw new Exception_DevblocksAjaxValidationError($error);

					DAO_AgentFile::update($id, $fields);
					DAO_AgentFile::onUpdateByActor($active_worker, $fields, $id);
				}

				if($id) {
					DAO_AgentFile::indexRecords([$id]);

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

	private function _profileAction_showBulkPopup() {
		$active_worker = CerberusApplication::getActiveWorker();

		if(!$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);

		$ids = DevblocksPlatform::importGPC($_REQUEST['ids'] ?? null, 'string', '');
		$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'] ?? null, 'string', '');

		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('view_id', $view_id);

		if(!empty($ids)) {
			$id_list = DevblocksPlatform::parseCsvString($ids);
			$tpl->assign('ids', implode(',', $id_list));
		}

		// Custom Fields
		$custom_fields = DAO_CustomField::getByContext(Context_AgentFile::ID, false);
		$tpl->assign('custom_fields', $custom_fields);

		$tpl->assign('bulk_automations', \Cerb\Records\BulkUpdate::getMenuItems(
			Context_AgentFile::ID,
			$view_id,
			'',
			$active_worker
		));

		$tpl->display('devblocks:cerberusweb.core::records/types/agent_file/bulk.tpl');
	}

	private function _profileAction_startBulkUpdateJson() {
		$active_worker = CerberusApplication::getActiveWorker();

		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);

		if(!$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);

		$context = Context_AgentFile::ID;

		// Filter: whole list or checks
		$filter = DevblocksPlatform::importGPC($_POST['filter'] ?? null, 'string', '');
		$ids = [];

		// View
		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string', '');

		if(!($view = C4_AbstractViewLoader::getView($view_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);

		$view->setAutoPersist(false);

		// Actions
		$actions = DevblocksPlatform::importGPC($_POST['actions'] ?? null, 'array', []);
		$params = DevblocksPlatform::importGPC($_POST['params'] ?? null, 'array', []);

		$do = [];

		foreach($actions as $action) {
			switch($action) {
				case 'delete':
					if($active_worker->hasPriv(sprintf('contexts.%s.delete', $context)))
						$do['delete'] = true;
					break;

				case 'filesystem_id':
					if(isset($params[$action]))
						$do[$action] = intval($params[$action]);
					break;

				case 'watchers_add':
				case 'watchers_remove':
					if(!isset($params[$action]))
						break;

					if(!isset($do['watchers']))
						$do['watchers'] = [];

					$do['watchers'][substr($action, 9)] = $params[$action];
					break;
			}
		}

		// Comment
		if($active_worker->hasPriv(sprintf('contexts.%s.comment', $context))) {
			$comment_enabled = DevblocksPlatform::importGPC($_POST['comment_enabled'] ?? null, 'bit', 0);
			$comment_text = DevblocksPlatform::importGPC($_POST['comment'] ?? null, 'string', '');

			if($comment_enabled && '' !== $comment_text) {
				$do['comment'] = [
					'message' => $comment_text,
					'is_markdown' => DevblocksPlatform::importGPC($_POST['comment_is_markdown'] ?? null, 'bit', 0),
					'file_ids' => DevblocksPlatform::sanitizeArray(DevblocksPlatform::importGPC($_POST['comment_file_ids'] ?? null, 'array', []), 'integer', ['nonzero','unique']),
				];
			}
		}

		// Do: Custom fields
		$do = DAO_CustomFieldValue::handleBulkPost($do);

		// Do: Automations
		$error = null;
		if(false === ($do = \Cerb\Records\BulkUpdate::handleBulkPost($do, $context, $view, $active_worker, $error))) {
			DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');
			echo json_encode([
				'status' => false,
				'error' => $error ?: 'Aborted by automation',
			]);
			return;
		}

		switch($filter) {
			// Checked rows
			case 'checks':
				$ids_str = DevblocksPlatform::importGPC($_POST['ids'] ?? null, 'string', '');
				$ids = DevblocksPlatform::parseCsvString($ids_str);
				break;

			case 'sample':
				$sample_size = min(DevblocksPlatform::importGPC($_POST['filter_sample_size'] ?? null, 'integer', 0), 9999);
				$ids = $view->getDataSample($sample_size);
				break;

			default:
				break;
		}

		// If we have specific IDs, add a filter for those too
		if(!empty($ids)) {
			$view->addParams([
				new DevblocksSearchCriteria(SearchFields_AgentFile::ID, 'in', $ids)
			], true);
		}

		// Enqueue a parallel bulk update job
		$queue_job = \Cerb\Records\BulkUpdate::createJob($view, $do, $active_worker->id ?? 0);

		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');

		echo json_encode([
			'job_id' => $queue_job->id ?? 0,
		]);
	}

	private function _profileAction_viewExplore() {
		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string', '');
		$explore_from = DevblocksPlatform::importGPC($_POST['explore_from'] ?? null, 'int', 0);

		$http_response = Cerb_ORMHelper::generateRecordExploreSet($view_id, $explore_from);
		DevblocksPlatform::redirect($http_response);
	}
};
