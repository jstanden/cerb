<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2017, Webgroup Media LLC
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
		$tpl = DevblocksPlatform::services()->template();
		$visit = CerberusApplication::getVisit();
		$translate = DevblocksPlatform::getTranslationService();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // bot
		$id = array_shift($stack); // 123

		$context = CerberusContexts::CONTEXT_BOT;
		@$id = intval($id);
		
		if(null == ($model = DAO_Bot::get($id))) {
			DevblocksPlatform::redirect(new DevblocksHttpRequest(array('search','bot')));
			return;
		}
		$tpl->assign('model', $model);
	
		// Dictionary
		$labels = array();
		$values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_BOT, $model, $labels, $values, '', true, false);
		$dict = DevblocksDictionaryDelegate::instance($values);
		$tpl->assign('dict', $dict);
		
		// Tab persistence
		
		$point = 'profiles.bot.tab';
		$tpl->assign('point', $point);
		
		if(null == (@$tab_selected = $stack[0])) {
			$tab_selected = $visit->get($point, '');
		}
		$tpl->assign('tab_selected', $tab_selected);
	
		// Properties
			
		$properties = array();
		
		$properties['owner'] = array(
			'label' => mb_ucfirst($translate->_('common.owner')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->owner_context_id,
			'params' => [
				'context' => $model->owner_context,
			],
		);
		
		$properties['mention_name'] = array(
			'label' => mb_ucfirst($translate->_('worker.at_mention_name')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->at_mention_name,
		);
		
		$properties['is_disabled'] = array(
			'label' => mb_ucfirst($translate->_('common.disabled')),
			'type' => Model_CustomField::TYPE_CHECKBOX,
			'value' => $model->is_disabled,
		);
		
		$properties['created'] = array(
			'label' => mb_ucfirst($translate->_('common.created')),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->created_at,
		);
		
		$properties['updated'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.updated'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->updated_at,
		);
	
		// Custom Fields

		@$values = array_shift(DAO_CustomFieldValue::getValuesByContextIds($context, $model->id)) or array();
		$tpl->assign('custom_field_values', $values);
		
		$properties_cfields = Page_Profiles::getProfilePropertiesCustomFields($context, $values);
		
		if(!empty($properties_cfields))
			$properties = array_merge($properties, $properties_cfields);
		
		// Custom Fieldsets

		$properties_custom_fieldsets = Page_Profiles::getProfilePropertiesCustomFieldsets($context, $model->id, $values);
		$tpl->assign('properties_custom_fieldsets', $properties_custom_fieldsets);
		
		// Counts
		
		$owner_counts = array(
			'behaviors' => DAO_TriggerEvent::countByBot($model->id),
			'calendars' => DAO_Calendar::count($context, $model->id),
			'classifiers' => DAO_Classifier::countByBot($model->id),
			'comments' => DAO_Comment::count($context, $model->id),
			'custom_fieldsets' => DAO_CustomFieldset::count($context, $model->id),
		);
		$tpl->assign('owner_counts', $owner_counts);
		
		// Link counts
		
		$properties_links = array(
			$context => array(
				$model->id => 
					DAO_ContextLink::getContextLinkCounts(
						$context,
						$model->id,
						array(CerberusContexts::CONTEXT_CUSTOM_FIELDSET)
					),
			),
		);
		
		$tpl->assign('properties_links', $properties_links);
		
		// Properties
		
		$tpl->assign('properties', $properties);
			
		// Tabs
		$tab_manifests = Extension_ContextProfileTab::getExtensions(false, CerberusContexts::CONTEXT_BOT);
		$tpl->assign('tab_manifests', $tab_manifests);
		
		// Interactions
		$interactions = Event_GetInteractionsForWorker::getInteractionsByPointAndWorker('record:' . $context, $dict, $active_worker);
		$interactions_menu = Event_GetInteractionsForWorker::getInteractionMenu($interactions);
		$tpl->assign('interactions_menu', $interactions_menu);
	
		// Template
		$tpl->display('devblocks:cerberusweb.core::profiles/bot.tpl');
	}
	
	function savePeekJsonAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'], 'string', '');
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'], 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
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
				@$name = DevblocksPlatform::importGPC($_REQUEST['name'], 'string', '');
				@$at_mention_name = DevblocksPlatform::importGPC($_REQUEST['at_mention_name'], 'string', '');
				@$owner = DevblocksPlatform::importGPC($_REQUEST['owner'], 'string', '');
				@$is_disabled = DevblocksPlatform::importGPC($_REQUEST['is_disabled'], 'integer', 0);
				@$allowed_events = DevblocksPlatform::importGPC($_REQUEST['allowed_events'], 'string', '');
				@$itemized_events = DevblocksPlatform::importGPC($_REQUEST['itemized_events'], 'array', array());
				@$allowed_actions = DevblocksPlatform::importGPC($_REQUEST['allowed_actions'], 'string', '');
				@$itemized_actions = DevblocksPlatform::importGPC($_REQUEST['itemized_actions'], 'array', array());
				@$config_json = DevblocksPlatform::importGPC($_REQUEST['config_json'], 'string', '');
				
				$is_disabled = DevblocksPlatform::intClamp($is_disabled, 0, 1);
				
				if(empty($name))
					throw new Exception_DevblocksAjaxValidationError("The 'Name' field is required.", 'name');
				
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
					
					if(false == ($id = DAO_Bot::create($fields)))
						throw new Exception_DevblocksAjaxValidationError("Failed to create a new record.");
					
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
					DAO_Bot::update($id, $fields);
				}
	
				if($id) {
					// Custom fields
					@$field_ids = DevblocksPlatform::importGPC($_REQUEST['field_ids'], 'array', array());
					DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_BOT, $id, $field_ids);
					
					// Avatar image
					@$avatar_image = DevblocksPlatform::importGPC($_REQUEST['avatar_image'], 'string', '');
					DAO_ContextAvatar::upsertWithImage(CerberusContexts::CONTEXT_BOT, $id, $avatar_image);
				}
				
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
	
	function showScheduledBehaviorsTabAction() {
		@$va_id = DevblocksPlatform::importGPC($_REQUEST['va_id'],'integer',0);
		@$point = DevblocksPlatform::importGPC($_REQUEST['point'],'string','');
		
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
					'toolbar_extension_id' => 'cerberusweb.contexts.bot.explore.toolbar',
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
