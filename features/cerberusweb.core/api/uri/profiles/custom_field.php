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

class PageSection_ProfilesCustomField extends Extension_PageSection {
	function render() {
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // custom_field 
		$context_id = intval(array_shift($stack)); // 123
		
		$context = CerberusContexts::CONTEXT_CUSTOM_FIELD;
		
		Page_Profiles::renderProfile($context, $context_id, $stack);
	}
	
	function savePeekJsonAction() {
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'], 'string', '');
		
		@$id = DevblocksPlatform::importGPC($_POST['id'], 'integer', 0);
		@$do_delete = DevblocksPlatform::importGPC($_POST['do_delete'], 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(403);
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			if(!empty($id) && !empty($do_delete)) { // Delete
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", CerberusContexts::CONTEXT_CUSTOM_FIELD)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				DAO_CustomField::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
				
			} else {
				@$context = DevblocksPlatform::importGPC($_POST['context'], 'string', '');
				@$custom_fieldset_id = DevblocksPlatform::importGPC($_POST['custom_fieldset_id'], 'integer', 0);
				@$name = DevblocksPlatform::importGPC($_POST['name'], 'string', '');
				@$pos = DevblocksPlatform::importGPC($_POST['pos'], 'integer', 0);
				@$params = DevblocksPlatform::importGPC($_POST['params'], 'array', []);
				@$type = DevblocksPlatform::importGPC($_POST['type'], 'string', '');
				
				// [TODO] Validate param keys by type
				if(isset($params['options']))
					$params['options'] = DevblocksPlatform::parseCrlfString($params['options']);
				
				$error = null;
				
				if(empty($id)) { // New
					$fields = array(
						DAO_CustomField::CONTEXT => $context,
						DAO_CustomField::CUSTOM_FIELDSET_ID => $custom_fieldset_id,
						DAO_CustomField::NAME => $name,
						DAO_CustomField::PARAMS_JSON => json_encode($params),
						DAO_CustomField::POS => $pos,
						DAO_CustomField::TYPE => $type,
						DAO_CustomField::UPDATED_AT => time(),
					);
					
					if(!DAO_CustomField::validate($fields, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_CustomField::onBeforeUpdateByActor($active_worker, $fields, null, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					$id = DAO_CustomField::create($fields);
					DAO_CustomField::onUpdateByActor($active_worker, $fields, $id);
					
					if(!empty($view_id) && !empty($id))
						C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_CUSTOM_FIELD, $id);
					
				} else { // Edit
					$fields = array(
						DAO_CustomField::CUSTOM_FIELDSET_ID => $custom_fieldset_id,
						DAO_CustomField::NAME => $name,
						DAO_CustomField::PARAMS_JSON => json_encode($params),
						DAO_CustomField::POS => $pos,
						DAO_CustomField::UPDATED_AT => time(),
					);
					
					if(!DAO_CustomField::validate($fields, $error, $id))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_CustomField::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					DAO_CustomField::update($id, $fields);
					DAO_CustomField::onUpdateByActor($active_worker, $fields, $id);
					
					// If we're moving the field to a new fieldset, make sure we add it to all those records
					if($custom_fieldset_id)
						DAO_CustomFieldset::addByField($id);
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
	
	function getFieldParamsAction() {
		@$type = DevblocksPlatform::importGPC($_REQUEST['type'],'string',null);
		
		$tpl = DevblocksPlatform::services()->template();
		
		$model = new Model_CustomField();
		$model->type = $type;
		
		if(false != ($custom_field_extension = Extension_CustomField::get($type, true))) {
			/** @var $custom_field_extension Extension_CustomField */
			$custom_field_extension->renderConfig($model);
			
		} else {
			$tpl->assign('model', $model);
			$tpl->display('devblocks:cerberusweb.core::internal/custom_fields/field_params.tpl');
		}
	}
	
	function viewExploreAction() {
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string');
		
		$active_worker = CerberusApplication::getActiveWorker();
		$url_writer = DevblocksPlatform::services()->url();
		
		// Generate hash
		$hash = md5($view_id.$active_worker->id.time());
		
		// Loop through view and get IDs
		$view = C4_AbstractViewLoader::getView($view_id);
		$view->setAutoPersist(false);

		// Page start
		@$explore_from = DevblocksPlatform::importGPC($_POST['explore_from'],'integer',0);
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
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=search&type=custom_field', true),
				);
				$models[] = $model;
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
			foreach($results as $opp_id => $row) {
				if($opp_id==$explore_from)
					$orig_pos = $pos;
				
				$url = $url_writer->writeNoProxy(sprintf("c=profiles&type=custom_field&id=%d-%s", $row[SearchFields_CustomField::ID], DevblocksPlatform::strToPermalink($row[SearchFields_CustomField::NAME])), true);
				
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'id' => $row[SearchFields_CustomField::ID],
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
