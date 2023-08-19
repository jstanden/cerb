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
	
	function handleActionForPage(string $action, string $scope=null) {
		if('profileAction' == $scope) {
			switch ($action) {
				case 'savePeekJson':
					return $this->_profileAction_savePeekJson();
				case 'getFieldParams':
					return $this->_profileAction_getFieldParams();
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
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			if(!empty($id) && !empty($do_delete)) { // Delete
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", CerberusContexts::CONTEXT_CUSTOM_FIELD)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				if(false == ($model = DAO_CustomField::get($id)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.record.not_found'));
				
				if(!Context_CustomField::isDeletableByActor($model, $active_worker))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				CerberusContexts::logActivityRecordDelete(CerberusContexts::CONTEXT_CUSTOM_FIELD, $model->id, $model->name);
				
				DAO_CustomField::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
				
			} else {
				$context = DevblocksPlatform::importGPC($_POST['context'] ?? null, 'string', '');
				$custom_fieldset_id = DevblocksPlatform::importGPC($_POST['custom_fieldset_id'] ?? null, 'integer', 0);
				$name = DevblocksPlatform::importGPC($_POST['name'] ?? null, 'string', '');
				$pos = DevblocksPlatform::importGPC($_POST['pos'] ?? null, 'integer', 0);
				$params = DevblocksPlatform::importGPC($_POST['params'] ?? null, 'array', []);
				$type = DevblocksPlatform::importGPC($_POST['type'] ?? null, 'string', '');
				$uri = DevblocksPlatform::importGPC($_POST['uri'] ?? null, 'string', '');
				
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
						DAO_CustomField::URI => $uri,
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
					$before_model = DAO_CustomField::get($id);
					
					$fields = array(
						DAO_CustomField::CUSTOM_FIELDSET_ID => $custom_fieldset_id,
						DAO_CustomField::NAME => $name,
						DAO_CustomField::PARAMS_JSON => json_encode($params),
						DAO_CustomField::POS => $pos,
						DAO_CustomField::UPDATED_AT => time(),
						DAO_CustomField::URI => $uri,
					);
					
					if(!DAO_CustomField::validate($fields, $error, $id))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_CustomField::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					DAO_CustomField::update($id, $fields);
					DAO_CustomField::onUpdateByActor($active_worker, $fields, $id);
					
					// If we're moving the field to a new fieldset, make sure we add it to all those records
					if($before_model && $before_model->custom_fieldset_id != $custom_fieldset_id)
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
	
	private function _profileAction_getFieldParams() {
		$tpl = DevblocksPlatform::services()->template();
		
		$type = DevblocksPlatform::importGPC($_REQUEST['type'] ?? null, 'string',null);
		
		$model = new Model_CustomField();
		$model->type = $type;
		
		if(($custom_field_extension = Extension_CustomField::get($type, true))) {
			/** @var $custom_field_extension Extension_CustomField */
			$custom_field_extension->renderConfig($model);
			
		} else {
			$tpl->assign('model', $model);
			$tpl->display('devblocks:cerberusweb.core::internal/custom_fields/field_params.tpl');
		}
	}
	
	private function _profileAction_viewExplore() {
		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string', '');
		$explore_from = DevblocksPlatform::importGPC($_POST['explore_from'] ?? null, 'int', 0);
		
		$http_response = Cerb_ORMHelper::generateRecordExploreSet($view_id, $explore_from);
		DevblocksPlatform::redirect($http_response);
	}
};
