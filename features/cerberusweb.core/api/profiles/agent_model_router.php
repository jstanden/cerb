<?php
class PageSection_ProfilesAgentModelRouter extends Extension_PageSection {
	function render() {
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // agent_model_router
		@$context_id = intval(array_shift($stack)); // 123

		$context = Context_AgentModelRouter::ID;

		Page_Profiles::renderProfile($context, $context_id, $stack);
	}

	function handleActionForPage(string $action, ?string $scope=null) {
		if('profileAction' == $scope) {
			switch($action) {
				case 'previewModelsJson':
					return $this->_profileAction_previewModelsJson();
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

		$context = Context_AgentModelRouter::ID;

		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);

		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');

		try {
			if(!$active_worker->is_superuser)
				throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.admin'));

			if(!empty($id) && !empty($do_delete)) { // Delete
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", $context)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));

				if(!($model = DAO_AgentModelRouter::get($id)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.record.not_found'));

				if(!Context_AgentModelRouter::isDeletableByActor($model, $active_worker))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));

				CerberusContexts::logActivityRecordDelete($context, $model->id, $model->name);

				DAO_AgentModelRouter::delete($id);

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
				$models_kata = DevblocksPlatform::importGPC($_POST['models_kata'] ?? null, 'string', '');
				$models_query = DevblocksPlatform::importGPC($_POST['models_query'] ?? null, 'string', '');

				$error = null;

				$fields = [
					DAO_AgentModelRouter::UPDATED_AT => time(),
					DAO_AgentModelRouter::NAME => $name,
					DAO_AgentModelRouter::LABEL => $label,
					DAO_AgentModelRouter::DESCRIPTION => $description,
					DAO_AgentModelRouter::MODELS_KATA => $models_kata,
					DAO_AgentModelRouter::MODELS_QUERY => $models_query,
				];

				if(empty($id)) { // New
					if(!DAO_AgentModelRouter::validate($fields, $error))
						throw new Exception_DevblocksAjaxValidationError($error);

					if(!DAO_AgentModelRouter::onBeforeUpdateByActor($active_worker, $fields, null, $error))
						throw new Exception_DevblocksAjaxValidationError($error);

					// create() auto-flags the FIRST router as default, so an environment is never router-less
					$id = DAO_AgentModelRouter::create($fields);
					DAO_AgentModelRouter::onUpdateByActor($active_worker, $fields, $id);

					if(!empty($view_id) && !empty($id))
						C4_AbstractView::setMarqueeContextCreated($view_id, $context, $id);

				} else { // Edit
					if(!DAO_AgentModelRouter::validate($fields, $error, $id))
						throw new Exception_DevblocksAjaxValidationError($error);

					if(!DAO_AgentModelRouter::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
						throw new Exception_DevblocksAjaxValidationError($error);

					DAO_AgentModelRouter::update($id, $fields);
					DAO_AgentModelRouter::onUpdateByActor($active_worker, $fields, $id);
				}

				// Promote AFTER the save so the row exists. Only on the way ON: unchecking doesn't demote, because
				// that would leave the system with no default at all -- you change the default by promoting another.
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
	/**
	 * The models a query matches RIGHT NOW, in resolved order -- what the editor's preview shows.
	 *
	 * Runs the query on screen, not the saved one, so it answers "is this what I meant?" before a save.
	 * Uncached for the same reason.
	 */
	private function _profileAction_previewModelsJson() {
		$active_worker = CerberusApplication::getActiveWorker();

		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);

		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');

		if(!$active_worker->is_superuser) {
			echo json_encode(['status' => false, 'error' => DevblocksPlatform::translate('error.core.no_acl.admin')]);
			return;
		}

		$query = DevblocksPlatform::importGPC($_POST['models_query'] ?? null, 'string', '');

		$error = null;
		$names = DAO_AgentModelRouter::resolveQueryModelNames($query, $error);

		if($error) {
			echo json_encode(['status' => false, 'error' => $error]);
			return;
		}

		// The vendor names a reader knows ("AWS Bedrock", not `aws_bedrock`).
		$provider_labels = array_column(DevblocksPlatform::services()->llm()->getAgentProviders(), 'label', 'id');

		$scales = [];

		foreach(Model_AgentModel::getRatings() as $rating)
			$scales[$rating] = Model_AgentModel::getRatingScale($rating);

		$models = [];

		foreach($names as $name) {
			if(!($record = DAO_AgentModel::getByName($name)))
				continue;

			$ratings = [];

			foreach(Model_AgentModel::getRatings() as $rating) {
				$value = intval($record->{'rating_' . $rating});

				if($value)
					$ratings[$rating] = $scales[$rating][$value] ?? '';
			}

			$models[] = [
				'id' => $record->id,
				'name' => $record->name,
				'label' => $record->getDisplayName(),
				'provider' => $provider_labels[$record->provider] ?? $record->provider,
				'model' => $record->model,
				'icon' => $record->getDisplayIcon(),
				'icon_color' => $record->getDisplayIconColor(),
				'has_vision' => $record->has_vision ? 1 : 0,
				'has_thinking' => $record->has_thinking ? 1 : 0,
				'context_window' => intval($record->context_window),
				'ratings' => $ratings,
			];
		}

		echo json_encode([
			'status' => true,
			'count' => count($models),
			'models' => $models,
		]);
	}

};
