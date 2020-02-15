<?php
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

class PageSection_ProfilesBot extends Extension_PageSection {
	function render() {
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // bot
		@$context_id = intval(array_shift($stack)); // 123

		$context = CerberusContexts::CONTEXT_BOT;
		
		Page_Profiles::renderProfile($context, $context_id, $stack);
	}
	
	function savePeekJsonAction() {
		@$id = DevblocksPlatform::importGPC($_POST['id'], 'integer', 0);
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'], 'string', '');
		@$do_delete = DevblocksPlatform::importGPC($_POST['do_delete'], 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(403);
		
		if(!$active_worker)
			return false;
		
		// Model
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			if(!empty($id) && !empty($do_delete)) { // Delete
				DAO_Bot::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
				
			} else {
				@$package_uri = DevblocksPlatform::importGPC($_POST['package'], 'string', '');
				@$import_json = DevblocksPlatform::importGPC($_POST['import_json'],'string', '');
				
				$mode = 'build';
				
				if(!$id && $package_uri) {
					$mode = 'library';
					
				} elseif(!$id && $import_json) {
					$mode = 'import';
				}
				
				switch($mode) {
					case 'library':
						@$prompts = DevblocksPlatform::importGPC($_POST['prompts'], 'array', []);
						@$owner = DevblocksPlatform::importGPC($_POST['owner'], 'string', '');
						
						if(empty($package_uri))
							throw new Exception_DevblocksAjaxValidationError("You must select a package from the library.");
						
						if(false == ($package = DAO_PackageLibrary::getByUri($package_uri)))
							throw new Exception_DevblocksAjaxValidationError("You selected an invalid package.");
						
						if($package->point != 'bot')
							throw new Exception_DevblocksAjaxValidationError("The selected package is not for this extension point.");
						
						// Owner
					
						$owner_ctx = '';
						@list($owner_ctx, $owner_ctx_id) = explode(':', $owner, 2);
						
						// Make sure we're given a valid ctx
						
						switch($owner_ctx) {
							case CerberusContexts::CONTEXT_APPLICATION:
							case CerberusContexts::CONTEXT_ROLE:
							case CerberusContexts::CONTEXT_GROUP:
							case CerberusContexts::CONTEXT_WORKER:
								break;
								
							default:
								$owner_ctx = null;
						}
						
						if(empty($owner_ctx))
							throw new Exception_DevblocksAjaxValidationError("A valid 'Owner' is required.");
						
						// Does the worker have access to this bot?
						if(
							!$active_worker->hasPriv(sprintf("contexts.%s.create", CerberusContexts::CONTEXT_BOT)) 
							|| !CerberusContexts::isOwnableBy($owner_ctx, $owner_ctx_id, $active_worker)
						) {
							throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.edit'));
						}
						
						$package_json = $package->getPackageJson();
						$records_created = [];
						$prompts['prompt_owner_context'] = $owner_ctx;
						$prompts['prompt_owner_context_id'] = $owner_ctx_id;
						
						try {
							CerberusApplication::packages()->import($package_json, $prompts, $records_created);
							
						} catch(Exception_DevblocksValidationError $e) {
							throw new Exception_DevblocksAjaxValidationError($e->getMessage());
							
						} catch (Exception $e) {
							throw new Exception_DevblocksAjaxValidationError("An unexpected error occurred.");
						}
						
						if(!array_key_exists(Context_Bot::ID, $records_created))
							throw new Exception_DevblocksAjaxValidationError("There was an issue creating the record.");
						
						$new_bot = reset($records_created[Context_Bot::ID]);
						
						if($view_id)
							C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_BOT, $new_bot['id']);
						
						echo json_encode([
							'status' => true,
							'id' => $new_bot['id'],
							'label' => $new_bot['label'],
							'view_id' => $view_id,
						]);
						return;
						break;
						
					case 'build':
						@$name = DevblocksPlatform::importGPC($_POST['name'], 'string', '');
						@$at_mention_name = DevblocksPlatform::importGPC($_POST['at_mention_name'], 'string', '');
						@$owner = DevblocksPlatform::importGPC($_POST['owner'], 'string', '');
						@$is_disabled = DevblocksPlatform::importGPC($_POST['is_disabled'], 'integer', 0);
						@$allowed_events = DevblocksPlatform::importGPC($_POST['allowed_events'], 'string', '');
						@$itemized_events = DevblocksPlatform::importGPC($_POST['itemized_events'], 'array', array());
						@$allowed_actions = DevblocksPlatform::importGPC($_POST['allowed_actions'], 'string', '');
						@$itemized_actions = DevblocksPlatform::importGPC($_POST['itemized_actions'], 'array', array());
						@$config_json = DevblocksPlatform::importGPC($_POST['config_json'], 'string', '');
						
						$is_disabled = DevblocksPlatform::intClamp($is_disabled, 0, 1);
						
						// Owner
					
						$owner_ctx = '';
						@list($owner_ctx, $owner_ctx_id) = explode(':', $owner, 2);
						
						// Make sure we're given a valid ctx
						
						switch($owner_ctx) {
							case CerberusContexts::CONTEXT_APPLICATION:
							case CerberusContexts::CONTEXT_ROLE:
							case CerberusContexts::CONTEXT_GROUP:
							case CerberusContexts::CONTEXT_WORKER:
								break;
								
							default:
								$owner_ctx = null;
						}
						
						if(empty($owner_ctx))
							throw new Exception_DevblocksAjaxValidationError("A valid 'Owner' is required.");
						
						// Permissions
						
						$params = array(
							'config' => json_decode($config_json, true),
							'events' => array(
								'mode' => $allowed_events,
								'items' => $itemized_events,
							),
							'actions' => array(
								'mode' => $allowed_actions,
								'items' => $itemized_actions,
							),
						);
						
						// Create or update
						
						if(empty($id)) { // New
							if(!$active_worker->is_superuser)
								throw new Exception_DevblocksAjaxValidationError("Only admins can create new bots.");
							
							$fields = array(
								DAO_Bot::CREATED_AT => time(),
								DAO_Bot::UPDATED_AT => time(),
								DAO_Bot::NAME => $name,
								DAO_Bot::AT_MENTION_NAME => $at_mention_name,
								DAO_Bot::IS_DISABLED => $is_disabled,
								DAO_Bot::OWNER_CONTEXT => $owner_ctx,
								DAO_Bot::OWNER_CONTEXT_ID => $owner_ctx_id,
								DAO_Bot::PARAMS_JSON => json_encode($params),
							);
							
							$error = null;
							
							if(!DAO_Bot::validate($fields, $error))
								throw new Exception_DevblocksAjaxValidationError($error);
							
							if(!DAO_Bot::onBeforeUpdateByActor($active_worker, $fields, null, $error))
								throw new Exception_DevblocksAjaxValidationError($error);
							
							if(false == ($id = DAO_Bot::create($fields)))
								throw new Exception_DevblocksAjaxValidationError("Failed to create a new record.");
							
							DAO_Bot::onUpdateByActor($active_worker, $fields, $id);
							
							if(!empty($view_id) && !empty($id))
								C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_BOT, $id);
							
						} else { // Edit
							if(!$active_worker->is_superuser)
								throw new Exception_DevblocksAjaxValidationError("You do not have permission to modify this record.");
							
							$fields = array(
								DAO_Bot::UPDATED_AT => time(),
								DAO_Bot::NAME => $name,
								DAO_Bot::AT_MENTION_NAME => $at_mention_name,
								DAO_Bot::IS_DISABLED => $is_disabled,
								DAO_Bot::OWNER_CONTEXT => $owner_ctx,
								DAO_Bot::OWNER_CONTEXT_ID => $owner_ctx_id,
								DAO_Bot::PARAMS_JSON => json_encode($params),
							);
							
							if(!DAO_Bot::validate($fields, $error, $id))
								throw new Exception_DevblocksAjaxValidationError($error);
							
							if(!DAO_Bot::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
								throw new Exception_DevblocksAjaxValidationError($error);
							
							DAO_Bot::update($id, $fields);
							DAO_Bot::onUpdateByActor($active_worker, $fields, $id);
						}
			
						if($id) {
							// Custom field saves
							@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', []);
							if(!DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_BOT, $id, $field_ids, $error))
								throw new Exception_DevblocksAjaxValidationError($error);
							
							// Avatar image
							@$avatar_image = DevblocksPlatform::importGPC($_POST['avatar_image'], 'string', '');
							DAO_ContextAvatar::upsertWithImage(CerberusContexts::CONTEXT_BOT, $id, $avatar_image);
						}
						
						echo json_encode(array(
							'status' => true,
							'id' => $id,
							'label' => $name,
							'view_id' => $view_id,
						));
						return;
						break;
				}
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
	
	function showScheduledBehaviorsTabAction() {
		@$va_id = DevblocksPlatform::importGPC($_REQUEST['va_id'],'integer',0);
		
		$active_worker = CerberusApplication::getActiveWorker();
		$tpl = DevblocksPlatform::services()->template();

		// Admins can see all owners at once
		if(empty($va_id) && !$active_worker->is_superuser)
			return;

		// [TODO] ACL

		$defaults = C4_AbstractViewModel::loadFromClass('View_ContextScheduledBehavior');
		$defaults->id = 'va_schedbeh_' . $va_id;
		$defaults->is_ephemeral = true;
		
		$view = C4_AbstractViewLoader::getView($defaults->id, $defaults);

		if(empty($va_id) && $active_worker->is_superuser) {
			$view->addParamsRequired(array(), true);
			
		} else {
			$view->addParamsRequired(array(
				'_privs' => array(
					DevblocksSearchCriteria::GROUP_AND,
					new DevblocksSearchCriteria(SearchFields_ContextScheduledBehavior::BEHAVIOR_BOT_ID, '=', $va_id),
				)
			), true);
		}
		
		$tpl->assign('view', $view);
		
		// Template
		
		$tpl->display('devblocks:cerberusweb.core::internal/views/search_and_view.tpl');
	}
	
	function showExportBotPopupAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(false == ($bot = DAO_Bot::get($id)))
			return;
		
		if(!Context_Bot::isWriteableByActor($bot, $active_worker))
			return;
		
		$bot_json = $bot->exportToJson();
		
		$package_json = [
			'package' => [
				'name' => $bot->name,
				'revision' => 1,
				'requires' => [
					'cerb_version' => APP_VERSION,
					'plugins' => [],
				],
				'configure' => [
					'placeholders' => [],
					'prompts' => [],
				]
			],
			'bots' => [
				json_decode($bot_json, true)
			]
		];
		
		$tpl->assign('package_json', DevblocksPlatform::strFormatJson(json_encode($package_json)));
		
		$tpl->display('devblocks:cerberusweb.core::internal/bot/export.tpl');
	}
	
	function viewExploreAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		
		$active_worker = CerberusApplication::getActiveWorker();
		$url_writer = DevblocksPlatform::services()->url();
		
		// Generate hash
		$hash = md5($view_id.$active_worker->id.time());
		
		// Loop through view and get IDs
		$view = C4_AbstractViewLoader::getView($view_id);
		$view->setAutoPersist(false);

		// Page start
		@$explore_from = DevblocksPlatform::importGPC($_REQUEST['explore_from'],'integer',0);
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
//					'worker_id' => $active_worker->id,
					'total' => $total,
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=search&type=bot', true),
				);
				$models[] = $model;
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
			foreach($results as $opp_id => $row) {
				if($opp_id==$explore_from)
					$orig_pos = $pos;
				
				$url = $url_writer->writeNoProxy(sprintf("c=profiles&type=bot&id=%d-%s", $row[SearchFields_Bot::ID], DevblocksPlatform::strToPermalink($row[SearchFields_Bot::NAME])), true);
				
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'id' => $row[SearchFields_Bot::ID],
					'url' => $url,
				);
				$models[] = $model;
			}
			
			DAO_ExplorerSet::createFromModels($models);
			
			$view->renderPage++;
			
		} while(!empty($results));
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('explore',$hash,$orig_pos)));
	}
};
