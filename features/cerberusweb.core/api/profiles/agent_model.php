<?php
class PageSection_ProfilesAgentModel extends Extension_PageSection {
	function render() {
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // agent_model
		@$context_id = intval(array_shift($stack)); // 123

		$context = Context_AgentModel::ID;

		Page_Profiles::renderProfile($context, $context_id, $stack);
	}

	function handleActionForPage(string $action, ?string $scope=null) {
		if('profileAction' == $scope) {
			switch($action) {
				case 'savePeekJson':
					return $this->_profileAction_savePeekJson();
				case 'modelsJson':
					return $this->_profileAction_modelsJson();
				case 'showBulkPopup':
					return $this->_profileAction_showBulkPopup();
				case 'startBulkUpdateJson':
					return $this->_profileAction_startBulkUpdateJson();
				case 'testJson':
					return $this->_profileAction_testJson();
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

		$context = Context_AgentModel::ID;

		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);

		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');

		try {
			if(!$active_worker->is_superuser)
				throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.admin'));

			if(!empty($id) && !empty($do_delete)) { // Delete
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", $context)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));

				if(!($model = DAO_AgentModel::get($id)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.record.not_found'));

				if(!Context_AgentModel::isDeletableByActor($model, $active_worker))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));

				CerberusContexts::logActivityRecordDelete($context, $model->id, $model->name);

				DAO_AgentModel::delete($id);

				echo json_encode([
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				]);
				return;

			} else {
				$name = DevblocksPlatform::importGPC($_POST['name'] ?? null, 'string', '');
				$label = DevblocksPlatform::importGPC($_POST['label'] ?? null, 'string', '');
				$icon = DevblocksPlatform::importGPC($_POST['icon'] ?? null, 'string', '');
				$icon_color = DevblocksPlatform::importGPC($_POST['icon_color'] ?? null, 'string', '');
				$provider = DevblocksPlatform::importGPC($_POST['provider'] ?? null, 'string', '');
				$model_str = DevblocksPlatform::importGPC($_POST['model'] ?? null, 'string', '');
				$api_endpoint_url = DevblocksPlatform::importGPC($_POST['api_endpoint_url'] ?? null, 'string', '');
				$connected_account_id = DevblocksPlatform::importGPC($_POST['connected_account_id'] ?? null, 'integer', 0);
				$has_vision = DevblocksPlatform::importGPC($_POST['has_vision'] ?? null, 'bit', 0);
				$context_window = DevblocksPlatform::importGPC($_POST['context_window'] ?? null, 'integer', 0);
				$params_kata = DevblocksPlatform::importGPC($_POST['params_kata'] ?? null, 'string', '');

				$error = null;

				// Parse-check the open params block here rather than letting a typo surface as a provider
				// error on the first turn, hours later.
				if('' !== trim($params_kata)) {
					$kata = DevblocksPlatform::services()->kata();
					
					if(false === ($kata->parse($params_kata, $error)))
						throw new Exception_DevblocksAjaxValidationError(sprintf("Parameters: %s", $error));
				}

				$fields = [
					DAO_AgentModel::NAME => $name,
					DAO_AgentModel::LABEL => $label,
					DAO_AgentModel::ICON => $icon,
					DAO_AgentModel::ICON_COLOR => $icon_color,
					DAO_AgentModel::PROVIDER => $provider,
					DAO_AgentModel::MODEL => $model_str,
					DAO_AgentModel::API_ENDPOINT_URL => $api_endpoint_url,
					DAO_AgentModel::CONNECTED_ACCOUNT_ID => $connected_account_id,
					DAO_AgentModel::HAS_VISION => $has_vision,
					DAO_AgentModel::CONTEXT_WINDOW => $context_window,
					DAO_AgentModel::PARAMS_KATA => $params_kata,
					DAO_AgentModel::STATUS => DevblocksPlatform::importGPC($_POST['status'] ?? null, 'integer', 0),
					DAO_AgentModel::HAS_THINKING => DevblocksPlatform::importGPC($_POST['has_thinking'] ?? null, 'bit', 0),
					DAO_AgentModel::RATING_COST => DevblocksPlatform::importGPC($_POST['rating_cost'] ?? null, 'integer', 0),
					DAO_AgentModel::RATING_INTELLIGENCE => DevblocksPlatform::importGPC($_POST['rating_intelligence'] ?? null, 'integer', 0),
					DAO_AgentModel::RATING_PRIVACY => DevblocksPlatform::importGPC($_POST['rating_privacy'] ?? null, 'integer', 0),
					DAO_AgentModel::RATING_SPEED => DevblocksPlatform::importGPC($_POST['rating_speed'] ?? null, 'integer', 0),
					DAO_AgentModel::UPDATED_AT => time(),
				];

				if(empty($id)) { // New
					$fields[DAO_AgentModel::CREATED_AT] = time();

					if(!DAO_AgentModel::validate($fields, $error))
						throw new Exception_DevblocksAjaxValidationError($error);

					if(!DAO_AgentModel::onBeforeUpdateByActor($active_worker, $fields, null, $error))
						throw new Exception_DevblocksAjaxValidationError($error);

					$id = DAO_AgentModel::create($fields);
					DAO_AgentModel::onUpdateByActor($active_worker, $fields, $id);

					if(!empty($view_id) && !empty($id))
						C4_AbstractView::setMarqueeContextCreated($view_id, $context, $id);

				} else { // Edit
					if(!DAO_AgentModel::validate($fields, $error, $id))
						throw new Exception_DevblocksAjaxValidationError($error);

					if(!DAO_AgentModel::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
						throw new Exception_DevblocksAjaxValidationError($error);

					DAO_AgentModel::update($id, $fields);
					DAO_AgentModel::onUpdateByActor($active_worker, $fields, $id);
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

	/**
	 * Ask the configured provider which models this key can actually use. A hardcoded list can't know what a
	 * self-hosted OpenAI-compatible endpoint (llama.cpp, LM Studio, vLLM) or a local Ollama has loaded — and
	 * even for a hosted provider, the live list is the one that's current.
	 */
	private function _profileAction_modelsJson() {
		$active_worker = CerberusApplication::getActiveWorker();

		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);

		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');

		try {
			if(!$active_worker->is_superuser)
				throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.admin'));

			$model = new Model_AgentModel();
			$model->provider = DevblocksPlatform::importGPC($_POST['provider'] ?? null, 'string', '');
			$model->api_endpoint_url = DevblocksPlatform::importGPC($_POST['api_endpoint_url'] ?? null, 'string', '');
			$model->connected_account_id = DevblocksPlatform::importGPC($_POST['connected_account_id'] ?? null, 'integer', 0);
			$model->params_kata = DevblocksPlatform::importGPC($_POST['params_kata'] ?? null, 'string', '');

			list($provider_id, $params) = $model->getProviderParams();

			if(!$provider_id)
				throw new Exception_DevblocksAjaxValidationError("Choose a provider first.");

			$llm = DevblocksPlatform::services()->llm();

			// validate:false -- we're DISCOVERING the model, so requiring `model:` would be circular
			try {
				$provider = $llm->getProvider($provider_id, $params, false);

			} catch(Throwable $e) {
				throw new Exception_DevblocksAjaxValidationError($e->getMessage());
			}

			if(!$provider)
				throw new Exception_DevblocksAjaxValidationError(sprintf("`%s` is not a known LLM provider.", $provider_id));

			$error = null;

			if(null === ($models = $provider->fetchChatModels($error)))
				throw new Exception_DevblocksAjaxValidationError($error ?: "The provider didn't return a model list.");

			// Enrich each id with whatever per-model defaults the provider knows (vision, context window),
			// so selecting a model in the editor can pre-fill those fields. `fetchChatModels()` keeps its
			// flat string[] contract; enrichment lives only here.
			$models = array_map(function($model_id) use ($provider) {
				$row = ['id' => $model_id];
				$defaults = $provider->getModelDefaults($model_id);

				if(array_key_exists('vision', $defaults))
					$row['has_vision'] = (bool) $defaults['vision'];

				if(array_key_exists('context_window', $defaults))
					$row['context_window'] = intval($defaults['context_window']);

				if(!empty($defaults['description']))
					$row['description'] = strval($defaults['description']);

				return $row;
			}, $models);

			echo json_encode([
				'status' => true,
				'provider' => $provider_id,
				'models' => $models,
			]);

		} catch(Exception_DevblocksAjaxValidationError $e) {
			echo json_encode([
				'status' => false,
				'error' => $e->getMessage(),
			]);

		} catch(Throwable) {
			echo json_encode([
				'status' => false,
				'error' => "An unexpected error occurred.",
			]);
		}
	}

	/**
	 * Verify a model's credentials + model string with one real (billed) chat turn, from the editor, BEFORE
	 * saving -- so a bad key surfaces here instead of hours later inside an automation. The config under test
	 * is the POSTED form, not the stored record: that's the whole point of testing before you commit.
	 */
	private function _profileAction_testJson() {
		$active_worker = CerberusApplication::getActiveWorker();

		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);

		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');

		try {
			if(!$active_worker->is_superuser)
				throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.admin'));

			// A transient model: never created, never saved -- just the merge from form fields to an
			// `llm:<provider>:` block, using the same code path a real turn uses.
			$model = new Model_AgentModel();
			$model->provider = DevblocksPlatform::importGPC($_POST['provider'] ?? null, 'string', '');
			$model->model = DevblocksPlatform::importGPC($_POST['model'] ?? null, 'string', '');
			$model->api_endpoint_url = DevblocksPlatform::importGPC($_POST['api_endpoint_url'] ?? null, 'string', '');
			$model->connected_account_id = DevblocksPlatform::importGPC($_POST['connected_account_id'] ?? null, 'integer', 0);
			$model->has_vision = DevblocksPlatform::importGPC($_POST['has_vision'] ?? null, 'bit', 0);
			$model->context_window = DevblocksPlatform::importGPC($_POST['context_window'] ?? null, 'integer', 0);
			$model->params_kata = DevblocksPlatform::importGPC($_POST['params_kata'] ?? null, 'string', '');

			list($provider_id, $params) = $model->getProviderParams();

			if(!$provider_id)
				throw new Exception_DevblocksAjaxValidationError("Choose a provider first.");

			$llm = DevblocksPlatform::services()->llm();

			// validate:true is what makes a missing authentication:/model: fail loudly right here
			try {
				$provider = $llm->getProvider($provider_id, $params);

			} catch(Throwable $e) {
				throw new Exception_DevblocksAjaxValidationError($e->getMessage());
			}

			if(!$provider)
				throw new Exception_DevblocksAjaxValidationError(sprintf("`%s` is not a known LLM provider.", $provider_id));

			$error = null;

			if(!($result = $provider->testConnection($error)))
				throw new Exception_DevblocksAjaxValidationError($error ?: "The provider didn't respond.");

			echo json_encode([
				'status' => true,
				'provider' => $provider_id,
				'model' => $model->model,
				'reply' => $result['reply'],
				'usage' => $result['usage'],
				'elapsed_ms' => $result['elapsed_ms'],
			]);

		} catch(Exception_DevblocksAjaxValidationError $e) {
			echo json_encode([
				'status' => false,
				'error' => $e->getMessage(),
			]);

		} catch(Throwable) {
			echo json_encode([
				'status' => false,
				'error' => "An unexpected error occurred.",
			]);
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

		$custom_fields = DAO_CustomField::getByContext(Context_AgentModel::ID, false);
		$tpl->assign('custom_fields', $custom_fields);

		$tpl->assign('statuses', Model_AgentModel::getStatuses());

		$rating_scales = [];

		foreach(Model_AgentModel::getRatings() as $rating)
			$rating_scales[$rating] = Model_AgentModel::getRatingScale($rating);

		$tpl->assign('rating_scales', $rating_scales);

		$tpl->assign('bulk_automations', \Cerb\Records\BulkUpdate::getMenuItems(
			Context_AgentModel::ID,
			$view_id,
			'',
			$active_worker
		));

		$tpl->display('devblocks:cerberusweb.core::records/types/agent_model/bulk.tpl');
	}

	private function _profileAction_startBulkUpdateJson() {
		$active_worker = CerberusApplication::getActiveWorker();

		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);

		if(!$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);

		$context = Context_AgentModel::ID;

		$filter = DevblocksPlatform::importGPC($_POST['filter'] ?? null, 'string', '');
		$ids = [];

		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string', '');

		if(!($view = C4_AbstractViewLoader::getView($view_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);

		$view->setAutoPersist(false);

		$actions = DevblocksPlatform::importGPC($_POST['actions'] ?? null, 'array', []);
		$params = DevblocksPlatform::importGPC($_POST['params'] ?? null, 'array', []);

		$do = [];

		foreach($actions as $action) {
			switch($action) {
				case 'delete':
					if($active_worker->hasPriv(sprintf('contexts.%s.delete', $context)))
						$do['delete'] = true;
					break;

				case 'connected_account_id':
				case 'has_thinking':
				case 'has_vision':
				case 'rating_cost':
				case 'rating_intelligence':
				case 'rating_privacy':
				case 'rating_speed':
				case 'status':
					// An unset chooser posts nothing; that's a deliberate "clear it", not a skip.
					$do[$action] = intval($params[$action] ?? 0);
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

		$do = DAO_CustomFieldValue::handleBulkPost($do);

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

		if(!empty($ids)) {
			$view->addParams([
				new DevblocksSearchCriteria(SearchFields_AgentModel::ID, 'in', $ids)
			], true);
		}

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
