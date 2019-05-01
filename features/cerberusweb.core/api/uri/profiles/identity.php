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

class PageSection_ProfilesIdentity extends Extension_PageSection {
	function render() {
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // identity 
		@$context_id = intval(array_shift($stack)); // 123
		
		$context = CerberusContexts::CONTEXT_IDENTITY;
		
		Page_Profiles::renderProfile($context, $context_id, $stack);
	}
	
	function handleActionForPage(string $action, string $scope=null) {
		if('profileAction' == $scope) {
			switch ($action) {
				case 'savePeekJson':
					return $this->_profileAction_savePeekJson();
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
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			if(!empty($id) && !empty($do_delete)) { // Delete
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", CerberusContexts::CONTEXT_IDENTITY)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				if(false == ($model = DAO_Identity::get($id)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.record.not_found'));
				
				if(!Context_Identity::isDeletableByActor($model, $active_worker))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				CerberusContexts::logActivityRecordDelete(CerberusContexts::CONTEXT_IDENTITY, $model->id, $model->name);
				
				DAO_Identity::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
				
			} else {
				$birthdate = DevblocksPlatform::importGPC($_POST['birthdate'] ?? null, 'string', '');
				$email_id = DevblocksPlatform::importGPC($_POST['email_id'] ?? null, 'integer', 0);
				$family_name = DevblocksPlatform::importGPC($_POST['family_name'] ?? null, 'string', '');
				$gender = DevblocksPlatform::importGPC($_POST['gender'] ?? null, 'string', '');
				$given_name = DevblocksPlatform::importGPC($_POST['given_name'] ?? null, 'string', '');
				$locale = DevblocksPlatform::importGPC($_POST['locale'] ?? null, 'string', '');
				$middle_name = DevblocksPlatform::importGPC($_POST['middle_name'] ?? null, 'string', '');
				$nickname = DevblocksPlatform::importGPC($_POST['nickname'] ?? null, 'string', '');
				$phone_number = DevblocksPlatform::importGPC($_POST['phone_number'] ?? null, 'string', '');
				$pool_id = DevblocksPlatform::importGPC($_POST['pool_id'] ?? null, 'integer', 0);
				$username = DevblocksPlatform::importGPC($_POST['username'] ?? null, 'string', '');
				$website = DevblocksPlatform::importGPC($_POST['website'] ?? null, 'string', '');
				$zoneinfo = DevblocksPlatform::importGPC($_POST['zoneinfo'] ?? null, 'string', '');
				
				// [TODO] Devblocks string helper that handles empty elements?
				$name = [];
				if($given_name)
					$name[] = $given_name;
				if($nickname)
					$name[] = '"' . $nickname . '"';
				if($middle_name)
					$name[] = $middle_name;
				if($family_name)
					$name[] = $family_name;
				$name = implode(' ', $name);
				
				// If name is blank, default to username
				if(!$name)
					$name = $username;
				
				// Worst case, default to email address
				if(!$name && $email_id && false != ($email = DAO_Address::get($email_id)))
					$name = $email->email;
				
				$error = null;
				
				// [TODO] If the email or phone changes, uncheck verified?
				// [TODO] If changing the pool, make sure the actor has permission
				
				if(empty($id)) { // New
					$fields = array(
						DAO_Identity::POOL_ID => $pool_id,
						DAO_Identity::BIRTHDATE => $birthdate,
						DAO_Identity::EMAIL_ID => $email_id,
						DAO_Identity::FAMILY_NAME => $family_name,
						DAO_Identity::GENDER => $gender,
						DAO_Identity::GIVEN_NAME => $given_name,
						DAO_Identity::LOCALE => $locale,
						DAO_Identity::MIDDLE_NAME => $middle_name,
						DAO_Identity::NAME => $name,
						DAO_Identity::NICKNAME => $nickname,
						DAO_Identity::PHONE_NUMBER => $phone_number,
						DAO_Identity::USERNAME => $username,
						DAO_Identity::UPDATED_AT => time(),
						DAO_Identity::WEBSITE => $website,
						DAO_Identity::ZONEINFO => $zoneinfo,
					);
					
					if(!DAO_Identity::validate($fields, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
						
					if(!DAO_Identity::onBeforeUpdateByActor($active_worker, $fields, null, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					$id = DAO_Identity::create($fields);
					DAO_Identity::onUpdateByActor($active_worker, $fields, $id);
					
					if(!empty($view_id) && !empty($id))
						C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_IDENTITY, $id);
					
				} else { // Edit
					$fields = array(
						DAO_Identity::POOL_ID => $pool_id,
						DAO_Identity::BIRTHDATE => $birthdate,
						DAO_Identity::EMAIL_ID => $email_id,
						DAO_Identity::FAMILY_NAME => $family_name,
						DAO_Identity::GENDER => $gender,
						DAO_Identity::GIVEN_NAME => $given_name,
						DAO_Identity::LOCALE => $locale,
						DAO_Identity::MIDDLE_NAME => $middle_name,
						DAO_Identity::NAME => $name,
						DAO_Identity::NICKNAME => $nickname,
						DAO_Identity::PHONE_NUMBER => $phone_number,
						DAO_Identity::USERNAME => $username,
						DAO_Identity::UPDATED_AT => time(),
						DAO_Identity::WEBSITE => $website,
						DAO_Identity::ZONEINFO => $zoneinfo,
					);
					
					if(!DAO_Identity::validate($fields, $error, $id))
						throw new Exception_DevblocksAjaxValidationError($error);
						
					if(!DAO_Identity::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					DAO_Identity::update($id, $fields);
					DAO_Identity::onUpdateByActor($active_worker, $fields, $id);
					
				}
				
				if($id) {
					// Avatar image
					$avatar_image = DevblocksPlatform::importGPC($_POST['avatar_image'] ?? null, 'string', '');
					DAO_ContextAvatar::upsertWithImage(CerberusContexts::CONTEXT_IDENTITY, $id, $avatar_image);
					
					// Custom field saves
					$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'] ?? null, 'array', []);
					if(!DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_IDENTITY, $id, $field_ids, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
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
	
	private function _profileAction_viewExplore() {
		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string');
		
		$active_worker = CerberusApplication::getActiveWorker();
		$url_writer = DevblocksPlatform::services()->url();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		// Generate hash
		$hash = md5($view_id.$active_worker->id.time());
		
		// Loop through view and get IDs
		$view = C4_AbstractViewLoader::getView($view_id);
		$view->setAutoPersist(false);

		// Page start
		$explore_from = DevblocksPlatform::importGPC($_POST['explore_from'] ?? null, 'integer',0);
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
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=search&type=identity', true),
				);
				$models[] = $model;
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
			foreach($results as $opp_id => $row) {
				if($opp_id==$explore_from)
					$orig_pos = $pos;
				
				$url = $url_writer->writeNoProxy(sprintf("c=profiles&type=identity&id=%d-%s", $row[SearchFields_Identity::ID], DevblocksPlatform::strToPermalink($row[SearchFields_Identity::NAME])), true);
				
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'id' => $row[SearchFields_Identity::ID],
					'url' => $url,
				);
				$models[] = $model;
			}
			
			DAO_ExplorerSet::createFromModels($models);
			
			$view->renderPage++;
			
		} while(!empty($results));
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('explore',$hash,$orig_pos)));
	}
}
