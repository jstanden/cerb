<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2014, Webgroup Media LLC
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

class PageSection_ProfilesProjectBoard extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::services()->template();
		$visit = CerberusApplication::getVisit();
		$translate = DevblocksPlatform::getTranslationService();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // project_board 
		$id = array_shift($stack); // 123
		
		@$id = intval($id);
		
		if(null == ($project_board = DAO_ProjectBoard::get($id))) {
			return;
		}
		$tpl->assign('project_board', $project_board);
		
		// Tab persistence
		
		$point = 'profiles.project_board.tab';
		$tpl->assign('point', $point);
		
		if(null == (@$tab_selected = $stack[0])) {
			$tab_selected = $visit->get($point, '');
		}
		$tpl->assign('tab_selected', $tab_selected);
		
		// Properties
		
		$properties = array();
		
		$properties['updated'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.updated'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $project_board->updated_at,
		);
		
		// Custom Fields
		
		@$values = array_shift(DAO_CustomFieldValue::getValuesByContextIds(Context_ProjectBoard::ID, $project_board->id)) or array();
		$tpl->assign('custom_field_values', $values);
		
		$properties_cfields = Page_Profiles::getProfilePropertiesCustomFields(Context_ProjectBoard::ID, $values);
		
		if(!empty($properties_cfields))
			$properties = array_merge($properties, $properties_cfields);
		
		// Custom Fieldsets

		$properties_custom_fieldsets = Page_Profiles::getProfilePropertiesCustomFieldsets(Context_ProjectBoard::ID, $project_board->id, $values);
		$tpl->assign('properties_custom_fieldsets', $properties_custom_fieldsets);
		
		// Link counts
		
		$properties_links = array(
			Context_ProjectBoard::ID => array(
				$project_board->id => 
					DAO_ContextLink::getContextLinkCounts(
						Context_ProjectBoard::ID,
						$project_board->id,
						array(CerberusContexts::CONTEXT_CUSTOM_FIELDSET)
					),
			),
		);
		
		$tpl->assign('properties_links', $properties_links);
		
		// Properties
		
		$tpl->assign('properties', $properties);
		
		// Tabs
		$tab_manifests = Extension_ContextProfileTab::getExtensions(false, Context_ProjectBoard::ID);
		$tpl->assign('tab_manifests', $tab_manifests);
		
		// Template
		$tpl->display('devblocks:cerb.project_boards::boards/profile.tpl');
	}
	
	function savePeekJsonAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'], 'string', '');
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'], 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			if(!empty($id) && !empty($do_delete)) { // Delete
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", Context_ProjectBoard::ID)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				DAO_ProjectBoard::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
				
			} else {
				@$name = DevblocksPlatform::importGPC($_REQUEST['name'], 'string', '');
				@$params = DevblocksPlatform::importGPC($_REQUEST['params'], 'array', []);
				
				// Sanitize $add_contexts
				if(isset($params['add_contexts'])) {
					$contexts = Extension_DevblocksContext::getAll(false, 'links');
					$params['add_contexts'] = array_intersect($params['add_contexts'], array_keys($contexts));
				}
				
				$params['card_queries'] = array_filter($params['card_queries'], function($value) {
					return !empty($value);
				});
				
				$params['card_templates'] = array_filter($params['card_templates'], function($value) {
					return !empty($value);
				});
				
				if(empty($id)) { // New
					if(!$active_worker->hasPriv(sprintf("contexts.%s.create", Context_ProjectBoard::ID)))
						throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.create'));
					
					$fields = array(
						DAO_ProjectBoard::UPDATED_AT => time(),
						DAO_ProjectBoard::NAME => $name,
						DAO_ProjectBoard::PARAMS_JSON => json_encode($params),
					);
					
					if(!DAO_ProjectBoard::validate($fields, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					$id = DAO_ProjectBoard::create($fields);
					
					if(!empty($view_id) && !empty($id))
						C4_AbstractView::setMarqueeContextCreated($view_id, Context_ProjectBoard::ID, $id);
					
				} else { // Edit
					if(!$active_worker->hasPriv(sprintf("contexts.%s.update", Context_ProjectBoard::ID)))
						throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.edit'));
					
					$fields = array(
						DAO_ProjectBoard::UPDATED_AT => time(),
						DAO_ProjectBoard::NAME => $name,
						DAO_ProjectBoard::PARAMS_JSON => json_encode($params),
					);
					
					if(!DAO_ProjectBoard::validate($fields, $error, $id))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					DAO_ProjectBoard::update($id, $fields);
				}
	
				// Custom fields
				@$field_ids = DevblocksPlatform::importGPC($_REQUEST['field_ids'], 'array', array());
				DAO_CustomFieldValue::handleFormPost(Context_ProjectBoard::ID, $id, $field_ids);
				
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
	
	function showBoardTabAction() {
		@$board_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer',0);
		
		$tpl = DevblocksPlatform::services()->template();
		
		if(empty($board_id) || false == ($board = DAO_ProjectBoard::get($board_id)))
			return;
		
		$tpl->assign('board', $board);
		
		$contexts = Extension_DevblocksContext::getAll(false, 'links');
		$tpl->assign('contexts', $contexts);
		
		$tpl->display('devblocks:cerb.project_boards::boards/board/board.tpl');
	}
	
	// [TODO] This should run on newly added cards too
	function moveCardAction() {
		@$card_context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$card_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$from_column_id = DevblocksPlatform::importGPC($_REQUEST['from'],'integer',0);
		@$to_column_id = DevblocksPlatform::importGPC($_REQUEST['to'],'integer',0);
		
		// [TODO] Validate everything (context/id/privs)
		
		if(false == ($to_column = DAO_ProjectBoardColumn::get($to_column_id)))
			return;
		
		DAO_ContextLink::deleteLink(Context_ProjectBoardColumn::ID, $from_column_id, $card_context, $card_id);
		DAO_ContextLink::setLink(Context_ProjectBoardColumn::ID, $to_column_id, $card_context, $card_id);
		
		// Setting links should trigger configured bot behaviors
		if(isset($to_column->params['behaviors'])) {
			@$behavior_params = $to_column->params['behaviors'];
			$behaviors = DAO_TriggerEvent::getIds(array_keys($behavior_params));
			
			if(is_array($behaviors))
			foreach($behaviors as $behavior) {
				$event_ext = $behavior->getEvent();
				
				// Only run events for this context
				if(@$event_ext->manifest->params['macro_context'] != $card_context)
					continue;
				
				$runners = call_user_func(array($event_ext->manifest->class, 'trigger'), $behavior->id, $card_id, @$behavior_params[$behavior->id] ?: []);
			}
		}
	}
	
	function refreshColumnAction() {
		@$column_id = DevblocksPlatform::importGPC($_REQUEST['column_id'],'integer',0);
		
		// [TODO] Validate everything (context/id/privs)
		
		if(false == ($column = DAO_ProjectBoardColumn::get($column_id)))
			return;
		
		if(false == ($board = $column->getProjectBoard()))
			return;
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('board', $board);
		$tpl->assign('column', $column);
		
		$tpl->display('devblocks:cerb.project_boards::boards/board/column.tpl');
	}
	
	function refreshCardAction() {
		@$board_id = DevblocksPlatform::importGPC($_REQUEST['board_id'],'integer',0);
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string',null);
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		
		$tpl = DevblocksPlatform::services()->template();
		
		// [TODO] Validate everything (context/id/privs)
		
		if(false == ($board = DAO_ProjectBoard::get($board_id)))
			return;
		
		$tpl->assign('board', $board);
		
		$dict = ['_context' => $context, 'id' => $id];
		
		// Figure out which column this card is in
		$columns = $board->getColumns();
		$links = DAO_ContextLink::getContextLinks($context, [$id], Context_ProjectBoardColumn::ID);
		@$column = array_shift(array_intersect_key($columns, $links[$id]));
		
		if(isset($column)) {
			$dict['column__context'] = Context_ProjectBoardColumn::ID;
			$dict['column_id'] = $column->id;
		}
		
		$card = new DevblocksDictionaryDelegate($dict);
		$tpl->assign('card', $card);
		
		$tpl->display('devblocks:cerb.project_boards::boards/board/card.tpl');
	}
	
	function reorderBoardAction() {
		@$board_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$columns = DevblocksPlatform::importGPC($_REQUEST['columns'],'string','');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(false == ($board = DAO_ProjectBoard::get($board_id)))
			return;
		
		// Check permissions
		if(!Context_ProjectBoard::isWriteableByActor($board, $active_worker))
			return;
		
		DAO_ProjectBoard::update($board_id, [
			DAO_ProjectBoard::COLUMNS_JSON => json_encode(DevblocksPlatform::sanitizeArray(DevblocksPlatform::parseCsvString($columns), 'int')),
		]);
	}
	
	function reorderColumnAction() {
		@$column_id = DevblocksPlatform::importGPC($_REQUEST['column_id'],'integer',0);
		@$cards = DevblocksPlatform::importGPC($_REQUEST['cards'],'array',[]);
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(false == ($column = DAO_ProjectBoardColumn::get($column_id)))
			return;
		
		if(false == ($board = $column->getProjectBoard()))
			return;
		
		// Check permissions
		if(!Context_ProjectBoard::isWriteableByActor($board, $active_worker))
			return;
		
		// [TODO] Validate $cards
		
		DAO_ProjectBoardColumn::update($column_id, [
			DAO_ProjectBoardColumn::CARDS_JSON => json_encode($cards),
		]);
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
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=search&type=project_board', true),
					'toolbar_extension_id' => 'cerberusweb.contexts.project.board.explore.toolbar',
				);
				$models[] = $model;
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
			foreach($results as $opp_id => $row) {
				if($opp_id==$explore_from)
					$orig_pos = $pos;
				
				$url = $url_writer->writeNoProxy(sprintf("c=profiles&type=project_board&id=%d-%s", $row[SearchFields_ProjectBoard::ID], DevblocksPlatform::strToPermalink($row[SearchFields_ProjectBoard::NAME])), true);
				
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'id' => $row[SearchFields_ProjectBoard::ID],
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
