<?php
class PageSection_ProfilesAgentTool extends Extension_PageSection {
	function render() {
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // agent_tool
		@$context_id = intval(array_shift($stack)); // 123

		$context = Context_AgentTool::ID;

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

		$context = Context_AgentTool::ID;

		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);

		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');

		try {
			if(!$active_worker->is_superuser)
				throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.admin'));

			if(!empty($id) && !empty($do_delete)) { // Delete
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", $context)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));

				if(!($model = DAO_AgentTool::get($id)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.record.not_found'));

				if(!Context_AgentTool::isDeletableByActor($model, $active_worker))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));

				CerberusContexts::logActivityRecordDelete($context, $model->id, $model->name);

				DAO_AgentTool::delete($id);

				echo json_encode([
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				]);
				return;

			} else {
				$name = DevblocksPlatform::importGPC($_POST['name'] ?? null, 'string', '');
				$label = DevblocksPlatform::importGPC($_POST['label'] ?? null, 'string', '');
				$description = DevblocksPlatform::importGPC($_POST['description'] ?? null, 'string', '');
				$icon = DevblocksPlatform::importGPC($_POST['icon'] ?? null, 'string', '');
				$label_active = DevblocksPlatform::importGPC($_POST['label_active'] ?? null, 'string', '');
				$label_summary = DevblocksPlatform::importGPC($_POST['label_summary'] ?? null, 'string', '');
				$params_kata = DevblocksPlatform::importGPC($_POST['params_kata'] ?? null, 'string', '');
				$status = DevblocksPlatform::importGPC($_POST['status'] ?? null, 'integer', 0);
				$automation_id = DevblocksPlatform::importGPC($_POST['automation_id'] ?? null, 'integer', 0);

				$error = null;

				// The chooser posts an id, but the record stores a `cerb:automation:<name>` URI -- the same
				// reference an agent's `tools:` block uses, and the form a package or an export can carry.
				$uri = '';

				if($automation_id) {
					if(!($automation = DAO_Automation::get($automation_id)))
						throw new Exception_DevblocksAjaxValidationError("The selected automation no longer exists.");

					$uri = 'cerb:automation:' . $automation->name;
				}

				$editable = [
					DAO_AgentTool::NAME => $name,
					DAO_AgentTool::LABEL => $label,
					DAO_AgentTool::DESCRIPTION => $description,
					DAO_AgentTool::ICON => $icon,
					DAO_AgentTool::LABEL_ACTIVE => $label_active,
					DAO_AgentTool::LABEL_SUMMARY => $label_summary,
					DAO_AgentTool::PARAMS_KATA => $params_kata,
					DAO_AgentTool::STATUS => $status,
					DAO_AgentTool::URI => $uri,
				];

				if(empty($id)) { // New
					$fields = array_merge([
						DAO_AgentTool::UPDATED_AT => time(),
					], $editable);

					if(!DAO_AgentTool::validate($fields, $error))
						throw new Exception_DevblocksAjaxValidationError($error);

					if(!DAO_AgentTool::onBeforeUpdateByActor($active_worker, $fields, null, $error))
						throw new Exception_DevblocksAjaxValidationError($error);

					$id = DAO_AgentTool::create($fields);
					DAO_AgentTool::onUpdateByActor($active_worker, $fields, $id);

					if(!empty($view_id) && !empty($id))
						C4_AbstractView::setMarqueeContextCreated($view_id, $context, $id);

				} else { // Edit
					$fields = array_merge([
						DAO_AgentTool::UPDATED_AT => time(),
					], $editable);

					if(!DAO_AgentTool::validate($fields, $error, $id))
						throw new Exception_DevblocksAjaxValidationError($error);

					if(!DAO_AgentTool::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
						throw new Exception_DevblocksAjaxValidationError($error);

					DAO_AgentTool::update($id, $fields);
					DAO_AgentTool::onUpdateByActor($active_worker, $fields, $id);
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
