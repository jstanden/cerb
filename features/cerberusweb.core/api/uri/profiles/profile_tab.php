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
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // profile_tab 
		@$context_id = intval(array_shift($stack)); // 123
		
		$context = CerberusContexts::CONTEXT_PROFILE_TAB;
		
		Page_Profiles::renderProfile($context, $context_id, $stack);
	}
	
	function savePeekJsonAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'], 'string', '');
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'], 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			if(!empty($id) && !empty($do_delete)) { // Delete
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", CerberusContexts::CONTEXT_PROFILE_TAB)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				DAO_ProfileTab::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
				
			} else {
				@$package_uri = DevblocksPlatform::importGPC($_REQUEST['package'], 'string', '');
				
				$mode = 'build';
				
				if(!$id && $package_uri)
					$mode = 'library';
				
				switch($mode) {
					case 'library':
						@$package_context = DevblocksPlatform::importGPC($_REQUEST['package_context'], 'string', '');
						@$prompts = DevblocksPlatform::importGPC($_REQUEST['prompts'], 'array', []);
						
						if(empty($package_uri))
							throw new Exception_DevblocksAjaxValidationError("You must select a package from the library.");
						
						// Verify worker can edit this profile (is admin)
						if(!$active_worker->is_superuser)
							throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.admin'));
						
						if(false == ($package = DAO_PackageLibrary::getByUri($package_uri)))
							throw new Exception_DevblocksAjaxValidationError("You selected an invalid package.");
						
						if(false == ($package_context_mft = Extension_DevblocksContext::get($package_context, false))) {
							/* @var $package_context_mft DevblocksExtensionManifest */
							throw new Exception_DevblocksAjaxValidationError("Invalid profile type.");
						}
						
						if($package->point != 'profile_tab' && !DevblocksPlatform::strStartsWith($package->point, 'profile_tab:'))
							throw new Exception_DevblocksAjaxValidationError("The selected package is not for this extension point.");
						
						$package_json = $package->getPackageJson();
						$records_created = [];
						
						$prompts['profile_context'] = @$package_context_mft->params['alias'];
						
						try {
							CerberusApplication::packages()->import($package_json, $prompts, $records_created);
							
						} catch(Exception_DevblocksValidationError $e) {
							throw new Exception_DevblocksAjaxValidationError($e->getMessage());
							
						} catch (Exception $e) {
							throw new Exception_DevblocksAjaxValidationError("An unexpected error occurred.");
						}
						
						if(!array_key_exists(Context_ProfileWidget::ID, $records_created))
							throw new Exception_DevblocksAjaxValidationError("There was an issue creating the record.");
						
						$new_tab = reset($records_created[Context_ProfileTab::ID]);
						
						if($view_id)
							C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_PROFILE_TAB, $new_tab['id']);
						
						echo json_encode([
							'status' => true,
							'id' => $new_tab['id'],
							'label' => $new_tab['label'],
							'view_id' => $view_id,
						]);
						return;
						break;
						
					case 'build':
						@$name = DevblocksPlatform::importGPC($_REQUEST['name'], 'string', '');
						@$context = DevblocksPlatform::importGPC($_REQUEST['context'], 'string', '');
						@$extension_id = DevblocksPlatform::importGPC($_REQUEST['extension_id'], 'string', '');
						@$extension_params = DevblocksPlatform::importGPC($_REQUEST['params'], 'array', []);
						
						$error = null;
						
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
								C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_PROFILE_TAB, $id);
							
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
						if(!DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_PROFILE_TAB, $id, $field_ids, $error))
							throw new Exception_DevblocksAjaxValidationError($error);
						
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
			
			throw new Exception_DevblocksAjaxValidationError("An unexpected error occurred.");
			
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
		
		if(null == ($context_ext = Extension_DevblocksContext::getByAlias($context, true))) {
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
	
	function getExtensionsByContextJsonAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		
		header('Content-Type: application/json; charset=utf-8');
		
		if(empty($context)) {
			echo json_encode([]);
			return;
		}
		
		$tab_manifests = Extension_ProfileTab::getByContext($context, false);
		
		echo json_encode(array_column(DevblocksPlatform::objectsToArrays($tab_manifests), 'name', 'id'));
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
