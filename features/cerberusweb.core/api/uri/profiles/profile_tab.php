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

class PageSection_ProfilesProfileTab extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::services()->template();
		$visit = CerberusApplication::getVisit();
		$translate = DevblocksPlatform::getTranslationService();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // profile_tab 
		@$id = intval(array_shift($stack)); // 123
		
		if(null == ($profile_tab = DAO_ProfileTab::get($id))) {
			return;
		}
		$tpl->assign('profile_tab', $profile_tab);

		// Context

		$context = 'cerberusweb.contexts.profile.tab';

		if(false == ($context_ext = Extension_DevblocksContext::get($context, true)))
			return;

		// Dictionary

		$labels = $values = [];
		CerberusContexts::getContext($context, $profile_tab, $labels, $values, '', true, false);
		$dict = DevblocksDictionaryDelegate::instance($values);
		$tpl->assign('dict', $dict);
		
		// Tab persistence
		
		$point = 'profiles.profile_tab.tab';
		$tpl->assign('point', $point);
		
		if(null == (@$tab_selected = $stack[0])) {
			$tab_selected = $visit->get($point, '');
		}
		$tpl->assign('tab_selected', $tab_selected);
		
		// Properties
		
		$properties = [];
		
		$properties['updated'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.updated'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $profile_tab->updated_at,
		);
		
		// Custom Fields
		
		@$values = array_shift(DAO_CustomFieldValue::getValuesByContextIds($context, $profile_tab->id)) or [];
		$tpl->assign('custom_field_values', $values);
		
		$properties_cfields = Page_Profiles::getProfilePropertiesCustomFields($context, $values);
		
		if(!empty($properties_cfields))
			$properties = array_merge($properties, $properties_cfields);
		
		// Custom Fieldsets
		
		$properties_custom_fieldsets = Page_Profiles::getProfilePropertiesCustomFieldsets($context, $profile_tab->id, $values);
		$tpl->assign('properties_custom_fieldsets', $properties_custom_fieldsets);
		
		// Link counts
		
		$properties_links = array(
			$context => array(
				$profile_tab->id => 
					DAO_ContextLink::getContextLinkCounts(
						$context,
						$profile_tab->id,
						array(CerberusContexts::CONTEXT_CUSTOM_FIELDSET)
					),
			),
		);
		
		$tpl->assign('properties_links', $properties_links);
		
		// Properties
		
		$tpl->assign('properties', $properties);
		
		// Tabs
		$tab_manifests = Extension_ContextProfileTab::getExtensions(false, $context);
		$tpl->assign('tab_manifests', $tab_manifests);

		// Card search buttons
		$search_buttons = $context_ext->getCardSearchButtons($dict, []);
		$tpl->assign('search_buttons', $search_buttons);
		
		// Template
		$tpl->display('devblocks:cerberusweb.core::internal/profiles/tabs/profile.tpl');
	}
	
	function savePeekJsonAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'], 'string', '');
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'], 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			if(!empty($id) && !empty($do_delete)) { // Delete
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", 'cerberusweb.contexts.profile.tab')))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				DAO_ProfileTab::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
				
			} else {
				@$name = DevblocksPlatform::importGPC($_REQUEST['name'], 'string', '');
				@$context = DevblocksPlatform::importGPC($_REQUEST['context'], 'string', '');
				@$extension_id = DevblocksPlatform::importGPC($_REQUEST['extension_id'], 'string', '');
				@$extension_params = DevblocksPlatform::importGPC($_REQUEST['params'], 'array', []);
				
				if(empty($id)) { // New
					$fields = array(
						DAO_ProfileTab::CONTEXT => $context,
						DAO_ProfileTab::EXTENSION_ID => $extension_id,
						DAO_ProfileTab::EXTENSION_PARAMS_JSON => json_encode($extension_params),
						DAO_ProfileTab::NAME => $name,
						DAO_ProfileTab::UPDATED_AT => time(),
					);
					
					if(!DAO_ProfileTab::validate($fields, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
						
					if(!DAO_ProfileTab::onBeforeUpdateByActor($active_worker, $fields, null, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					$id = DAO_ProfileTab::create($fields);
					DAO_ProfileTab::onUpdateByActor($active_worker, $id, $fields);
					
					if(!empty($view_id) && !empty($id))
						C4_AbstractView::setMarqueeContextCreated($view_id, 'cerberusweb.contexts.profile.tab', $id);
					
				} else { // Edit
					$fields = array(
						DAO_ProfileTab::NAME => $name,
						DAO_ProfileTab::EXTENSION_PARAMS_JSON => json_encode($extension_params),
						DAO_ProfileTab::UPDATED_AT => time(),
					);
					
					if(!DAO_ProfileTab::validate($fields, $error, $id))
						throw new Exception_DevblocksAjaxValidationError($error);
						
					if(!DAO_ProfileTab::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					DAO_ProfileTab::update($id, $fields);
					DAO_ProfileTab::onUpdateByActor($active_worker, $id, $fields);
					
				}
	
				// Custom field saves
				@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', []);
				if(!DAO_CustomFieldValue::handleFormPost('cerberusweb.contexts.profile.tab', $id, $field_ids, $error))
					throw new Exception_DevblocksAjaxValidationError($error);
				
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
	
	function getContextColumnsJsonAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'], 'string', null);
		
		header('Content-Type: application/json');
		
		if(null == ($context_ext = Extension_DevblocksContext::get($context))) {
			echo json_encode(false);
			return;
		}

		$view_class = $context_ext->getViewClass();
		
		if(null == ($view = new $view_class())) { /* @var $view C4_AbstractView */
			echo json_encode(false);
			return;
		}
		
		$view->setAutoPersist(false);
		
		$results = [];
		$columns_selected = $view->view_columns;
		$columns_avail = $view->getColumnsAvailable();
		
		if(is_array($columns_avail))
		foreach($columns_avail as $column) {
			if(empty($column->db_label))
				continue;
			
			$results[] = array(
				'key' => $column->token,
				'label' => mb_convert_case($column->db_label, MB_CASE_TITLE),
				'type' => $column->type,
				'is_selected' => in_array($column->token, $columns_selected),
			);
		}
		
		usort($results, function($a, $b) use ($columns_selected) {
			if($a['is_selected'] == $b['is_selected']) {
				if($a['is_selected']) {
					$a_idx = array_search($a['key'], $columns_selected);
					$b_idx = array_search($b['key'], $columns_selected);
					return $a_idx < $b_idx ? -1 : 1;
					
				} else {
					return $a['label'] < $b['label'] ? -1 : 1;
				}
				
			} else {
				return $a['is_selected'] ? -1 : 1;
			}
		});
		
		echo json_encode($results);
	}
	
	function getExtensionConfigAction() {
		@$extension_id = DevblocksPlatform::importGPC($_REQUEST['extension_id'],'string','');
		
		if(false == ($extension = Extension_ProfileTab::get($extension_id)))
			return;
		
		$model = new Model_ProfileTab();
		$model->extension_id = $extension_id;
		
		$extension->renderConfig($model);
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
			$models = [];
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
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=search&type=profile_tab', true),
					'toolbar_extension_id' => 'cerberusweb.contexts.profile.tab.explore.toolbar',
				);
				$models[] = $model;
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
			foreach($results as $opp_id => $row) {
				if($opp_id==$explore_from)
					$orig_pos = $pos;
				
				$url = $url_writer->writeNoProxy(sprintf("c=profiles&type=profile_tab&id=%d-%s", $row[SearchFields_ProfileTab::ID], DevblocksPlatform::strToPermalink($row[SearchFields_ProfileTab::NAME])), true);
				
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'id' => $row[SearchFields_ProfileTab::ID],
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
