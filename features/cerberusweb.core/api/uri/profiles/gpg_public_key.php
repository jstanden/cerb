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

class PageSection_ProfilesGpgPublicKey extends Extension_PageSection {
	function render() {
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // gpg_public_key 
		@$context_id = intval(array_shift($stack)); // 123
		
		$context = CerberusContexts::CONTEXT_GPG_PUBLIC_KEY;
		
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
		
		$gpg = DevblocksPlatform::services()->gpg();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			if(!empty($id) && !empty($do_delete)) { // Delete
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", CerberusContexts::CONTEXT_GPG_PUBLIC_KEY)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				if(false == ($model = DAO_GpgPublicKey::get($id)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.record.not_found'));
				
				if(!Context_GpgPublicKey::isDeletableByActor($model, $active_worker))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				CerberusContexts::logActivityRecordDelete(CerberusContexts::CONTEXT_GPG_PUBLIC_KEY, $model->id, $model->name);
				
				DAO_GpgPublicKey::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
				
			} else {
				$name = DevblocksPlatform::importGPC($_POST['name'] ?? null, 'string', '');
				$key_text = DevblocksPlatform::importGPC($_POST['key_text'] ?? null, 'string', '');
				
				$keyinfo = [];
				$expires_at = 0;
				
				if($key_text) {
					if (false == ($keyinfo = $gpg->importPublicKey($key_text)))
						throw new Exception_DevblocksAjaxValidationError("Failed to decrypt the given public key.", 'key_text');
					
					if (!is_array($keyinfo))
						throw new Exception_DevblocksAjaxValidationError("Failed to retrieve public key info.", 'key_text');
					
					if (!isset($keyinfo['uids']) || !is_array($keyinfo['uids']) || empty($keyinfo['uids']))
						throw new Exception_DevblocksAjaxValidationError("Failed to retrieve public key UID info.", 'key_text');
					
					if ($keyinfo['is_secret'])
						throw new Exception_DevblocksAjaxValidationError("This is a private key.", "key_text");
					
					if (!$keyinfo['can_encrypt'])
						throw new Exception_DevblocksAjaxValidationError("This public key doesn't support encryption.", "key_text");
					
					if (($keyinfo['expired'] ?? false) || ($keyinfo['disabled'] ?? false) || ($keyinfo['revoked'] ?? false))
						throw new Exception_DevblocksAjaxValidationError("This public key is expired, revoked, or disabled.", "key_text");

					@$key = $keyinfo['subkeys'][0];
					
					if (empty($key))
						throw new Exception_DevblocksAjaxValidationError("Failed to retrieve public key subkey info.", "key_text");
					
					if (empty($name))
						$name = $keyinfo['uids'][0]['uid'] ?? null;
					
					$expires_at = $key['expires'];
				}
				
				// If this fingerprint already exists, return the existing key info
				if(!$id && false != ($record = DAO_GpgPublicKey::getByFingerprint($keyinfo['subkeys'][0]['fingerprint']))) {
					$id = $record->id;
					C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_GPG_PUBLIC_KEY, $id);
				}
				
				if(empty($id)) { // New
					if(!$active_worker->hasPriv(sprintf("contexts.%s.create", CerberusContexts::CONTEXT_GPG_PUBLIC_KEY)))
						throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.create'));
					
					if(!$key_text || !$keyinfo)
						throw new Exception_DevblocksAjaxValidationError("The 'Key' field is required.", 'key_text');
					
					$fields = [
						DAO_GpgPublicKey::NAME => $name,
						DAO_GpgPublicKey::FINGERPRINT => $keyinfo['subkeys'][0]['fingerprint'],
						DAO_GpgPublicKey::EXPIRES_AT => $expires_at,
						DAO_GpgPublicKey::KEY_TEXT => $key_text,
						DAO_GpgPublicKey::UPDATED_AT => time(),
					];
					
					if(!DAO_GpgPublicKey::validate($fields, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_GpgPublicKey::onBeforeUpdateByActor($active_worker, $fields, null, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(false != ($id = DAO_GpgPublicKey::create($fields))) {
						if(!empty($view_id))
							C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_GPG_PUBLIC_KEY, $id);
					}
					
					DAO_GpgKeyPart::upsert(Context_GpgPublicKey::ID, $id, $keyinfo);
					
					DAO_GpgPublicKey::onUpdateByActor($active_worker, $fields, $id);
					
				} else { // Edit
					if(!$active_worker->hasPriv(sprintf("contexts.%s.update", CerberusContexts::CONTEXT_GPG_PUBLIC_KEY)))
						throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.edit'));
					
					$fields = [
						DAO_GpgPublicKey::NAME => $name,
						DAO_GpgPublicKey::UPDATED_AT => time(),
					];
					
					if($keyinfo) {
						$fields[DAO_GpgPublicKey::FINGERPRINT] = $keyinfo['subkeys'][0]['fingerprint'];
						$fields[DAO_GpgPublicKey::KEY_TEXT] = $key_text;
						$fields[DAO_GpgPublicKey::EXPIRES_AT] = $expires_at;
						
						DAO_GpgKeyPart::upsert(Context_GpgPublicKey::ID, $id, $keyinfo);
					}
					
					if(!DAO_GpgPublicKey::validate($fields, $error, $id))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_GpgPublicKey::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					DAO_GpgPublicKey::update($id, $fields);
					DAO_GpgPublicKey::onUpdateByActor($active_worker, $fields, $id);
				}
				
				if($id) {
					// Custom field saves
					$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'] ?? null, 'array', []);
					if(!DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_GPG_PUBLIC_KEY, $id, $field_ids, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					// Key links
					if($keyinfo) {
						// Links
						$uid_emails = [];
						
						foreach($keyinfo['uids'] as $idx => $uid) {
							if (!isset($uid['email']))
								continue;
							
							$uid_emails[DevblocksPlatform::strLower($uid['email'])] = true;
						}
						
						$email_addys = DAO_Address::lookupAddresses(array_keys($uid_emails), true);
						
						if (is_array($email_addys)) {
							foreach ($email_addys as $email_addy) {
								DAO_ContextLink::setLink(CerberusContexts::CONTEXT_GPG_PUBLIC_KEY, $id, CerberusContexts::CONTEXT_ADDRESS, $email_addy->id);
								
								// Has contact
								if ($email_addy->contact_id)
									DAO_ContextLink::setLink(CerberusContexts::CONTEXT_GPG_PUBLIC_KEY, $id, CerberusContexts::CONTEXT_CONTACT, $email_addy->contact_id);
								
								// Has bare org
								if ($email_addy->contact_org_id && !$email_addy->contact_id)
									DAO_ContextLink::setLink(CerberusContexts::CONTEXT_GPG_PUBLIC_KEY, $id, CerberusContexts::CONTEXT_ORG, $email_addy->contact_org_id);
							}
						}
					}
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
		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string', '');
		$explore_from = DevblocksPlatform::importGPC($_POST['explore_from'] ?? null, 'int', 0);
		
		$http_response = Cerb_ORMHelper::generateRecordExploreSet($view_id, $explore_from);
		DevblocksPlatform::redirect($http_response);
	}
}
