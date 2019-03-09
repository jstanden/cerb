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

class PageSection_ProfilesPackageLibrary extends Extension_PageSection {
	function render() {
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // package_library 
		@$context_id = intval(array_shift($stack)); // 123
		
		$context = CerberusContexts::CONTEXT_PACKAGE;
		
		Page_Profiles::renderProfile($context, $context_id, $stack);
	}
	
	function savePeekJsonAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'], 'string', '');
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'], 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		header('Content-Type: application/json; charset=utf-8');
		
		if(!$active_worker->is_superuser)
			throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.admin'));
		
		try {
			if(!empty($id) && !empty($do_delete)) { // Delete
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", CerberusContexts::CONTEXT_PACKAGE)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				DAO_PackageLibrary::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
				
			} else {
				@$package_json = DevblocksPlatform::importGPC($_REQUEST['package_json'], 'string', '');
				
				$error = null;
				
				if(false == ($package = json_decode($package_json, true)) || !array_key_exists('package', $package))
					throw new Exception_DevblocksAjaxValidationError("Invalid package JSON.");
				
				if(false == (@$package_library_meta = $package['package']['library']))
					throw new Exception_DevblocksAjaxValidationError("Missing package library JSON.");
				
				$name = '';
				$description = '';
				$instructions = '';
				$point = '';
				$uri = '';
				$avatar_image = '';
				
				if(!$name && array_key_exists('name', $package_library_meta))
					@$name = $package_library_meta['name'];
				
				if(!$description && array_key_exists('description', $package_library_meta))
					@$description = $package_library_meta['description'];
				
				if(!$instructions && array_key_exists('instructions', $package_library_meta))
					@$instructions = $package_library_meta['instructions'];
				
				if(!$point && array_key_exists('point', $package_library_meta))
					@$point = $package_library_meta['point'];
				
				if(!$uri && array_key_exists('uri', $package_library_meta))
					@$uri = $package_library_meta['uri'];
				
				if(!$avatar_image && array_key_exists('image', $package_library_meta))
					@$avatar_image = $package_library_meta['image'];
				
				if(empty($id)) { // New
					$fields = array(
						DAO_PackageLibrary::DESCRIPTION => $description,
						DAO_PackageLibrary::INSTRUCTIONS => $instructions,
						DAO_PackageLibrary::NAME => $name,
						DAO_PackageLibrary::PACKAGE_JSON => $package_json,
						DAO_PackageLibrary::POINT => $point,
						DAO_PackageLibrary::UPDATED_AT => time(),
						DAO_PackageLibrary::URI => $uri,
					);
					
					if(!DAO_PackageLibrary::validate($fields, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
						
					if(!DAO_PackageLibrary::onBeforeUpdateByActor($active_worker, $fields, null, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					$id = DAO_PackageLibrary::create($fields);
					DAO_PackageLibrary::onUpdateByActor($active_worker, $id, $fields);
					
					if(!empty($view_id) && !empty($id))
						C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_PACKAGE, $id);
					
				} else { // Edit
					$fields = array(
						DAO_PackageLibrary::DESCRIPTION => $description,
						DAO_PackageLibrary::INSTRUCTIONS => $instructions,
						DAO_PackageLibrary::NAME => $name,
						DAO_PackageLibrary::PACKAGE_JSON => $package_json,
						DAO_PackageLibrary::POINT => $point,
						DAO_PackageLibrary::UPDATED_AT => time(),
						DAO_PackageLibrary::URI => $uri,
					);
					
					if(!DAO_PackageLibrary::validate($fields, $error, $id))
						throw new Exception_DevblocksAjaxValidationError($error);
						
					if(!DAO_PackageLibrary::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					DAO_PackageLibrary::update($id, $fields);
					DAO_PackageLibrary::onUpdateByActor($active_worker, $id, $fields);
					
				}
				
				// Custom field saves
				@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', []);
				if(!DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_PACKAGE, $id, $field_ids, $error))
					throw new Exception_DevblocksAjaxValidationError($error);
				
				// Avatar image
				DAO_ContextAvatar::upsertWithImage(CerberusContexts::CONTEXT_PACKAGE, $id, $avatar_image);
				
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
	
	function showPackagePromptsAction() {
		@$package_uri = DevblocksPlatform::importGPC($_REQUEST['package'],'string',null);
		
		$tpl = DevblocksPlatform::services()->template();
		
		if(false == ($package = DAO_PackageLibrary::getByUri($package_uri))) {
			return;
		}
		
		$tpl->assign('package', $package);
		
		try {
			$config_prompts = $package->getPrompts();
			$tpl->assign('prompts', $config_prompts);
			
		} catch (Exception $e) {
			return;
		}
		
		$tpl->display('devblocks:cerberusweb.core::internal/package_library/editor_select.tpl');
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
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=search&type=package', true),
				);
				$models[] = $model;
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
			foreach($results as $opp_id => $row) {
				if($opp_id==$explore_from)
					$orig_pos = $pos;
				
				$url = $url_writer->writeNoProxy(sprintf("c=profiles&type=package&id=%d-%s", $row[SearchFields_PackageLibrary::ID], DevblocksPlatform::strToPermalink($row[SearchFields_PackageLibrary::NAME])), true);
				
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'id' => $row[SearchFields_PackageLibrary::ID],
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
