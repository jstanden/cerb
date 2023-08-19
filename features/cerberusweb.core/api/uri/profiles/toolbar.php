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

class PageSection_ProfilesToolbar extends Extension_PageSection {
	function render() {
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // toolbar 
		@$context_id = intval(array_shift($stack)); // 123
		
		$context = CerberusContexts::CONTEXT_TOOLBAR;
		
		Page_Profiles::renderProfile($context, $context_id, $stack);
	}
	
	function handleActionForPage(string $action, string $scope=null) {
		if('profileAction' == $scope) {
			switch($action) {
				case 'savePeekJson':
					return $this->_profileAction_savePeekJson();
				case 'tester':
					return $this->_profileAction_tester();
				case 'viewExplore':
					return $this->_profileAction_viewExplore();
			}
		}
		return false;
	}
	
	private function _profileAction_tester() {
		$toolbar = DevblocksPlatform::services()->ui()->toolbar();
		
		$toolbar_kata = DevblocksPlatform::importGPC($_POST['toolbar_kata'] ?? null, 'string');
		$placeholders_kata = DevblocksPlatform::importGPC($_POST['placeholders_kata'] ?? null, 'string');
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			$error = null;
			
			if(false === ($values = DevblocksPlatform::services()->kata()->parse($placeholders_kata, $error)))
				throw new Exception_DevblocksValidationError($error);
			
			if(false === ($values = DevblocksPlatform::services()->kata()->formatTree($values, null, $error)))
				throw new Exception_DevblocksValidationError($error);
			
			$dict = DevblocksDictionaryDelegate::instance($values);
			
			if(false === ($items = $toolbar->parse($toolbar_kata, $dict, $error)))
				throw new Exception_DevblocksValidationError($error);
			
			$tpl = DevblocksPlatform::services()->template();
			$tpl->assign('toolbar', $items);
			$toolbar_html = $tpl->fetch('devblocks:devblocks.core::ui/toolbar/preview.tpl');
			
			echo json_encode([
				'html' => $toolbar_html,
			]);
			
		} catch(Exception_DevblocksValidationError $e) {
			echo json_encode([
				'error' => $e->getMessage(),
			]);
			
		} catch(Exception $e) {
			echo json_encode([
				'error' => 'An unexpected error occurred.',
			]);
		}
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
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
			} else {
				$name = DevblocksPlatform::importGPC($_POST['name'] ?? 'Toolbar', 'string');
				$toolbar_kata = DevblocksPlatform::importGPC($_POST['toolbar_kata'] ?? null, 'string', '');
				
				$error = null;
				
				if(empty($id)) { // New
						throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.create'));
					
				} else { // Edit
					$fields = array(
						DAO_Toolbar::TOOLBAR_KATA => $toolbar_kata,
						DAO_Toolbar::UPDATED_AT => time(),
					);
					
					if(!DAO_Toolbar::validate($fields, $error, $id))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_Toolbar::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					DAO_Toolbar::update($id, $fields);
					DAO_Toolbar::onUpdateByActor($active_worker, $fields, $id);
				}
				
				// Versioning
				try {
					DAO_RecordChangeset::create(
						'toolbar',
						$id,
						[
							'toolbar_kata' => $fields[DAO_Toolbar::TOOLBAR_KATA] ?? '',
						],
						$active_worker->id ?? 0
					);
					
				} catch (Exception $e) {
					DevblocksPlatform::logError('Error saving changeset: ' . $e->getMessage());
				}
				
				// Custom field saves
				$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'] ?? null, 'array', []);
				if(!DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_TOOLBAR, $id, $field_ids, $error))
					throw new Exception_DevblocksAjaxValidationError($error);
				
				echo json_encode(array(
					'status' => true,
					'context' => CerberusContexts::CONTEXT_TOOLBAR,
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
	
	private function _profileAction_viewExplore() {
		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string', '');
		$explore_from = DevblocksPlatform::importGPC($_POST['explore_from'] ?? null, 'int', 0);
		
		$http_response = Cerb_ORMHelper::generateRecordExploreSet($view_id, $explore_from);
		DevblocksPlatform::redirect($http_response);
	}
}
