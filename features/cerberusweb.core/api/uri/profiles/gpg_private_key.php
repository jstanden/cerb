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

class PageSection_ProfilesGpgPrivateKey extends Extension_PageSection {
	function render() {
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // gpg_private_key
		@$context_id = intval(array_shift($stack)); // 123
		
		$context = Context_GpgPrivateKey::ID;
		
		Page_Profiles::renderProfile($context, $context_id, $stack);
	}
	
	function handleActionForPage(string $action, string $scope=null) {
		if('profileAction' == $scope) {
			switch ($action) {
				case 'generateJson':
					return $this->_profileAction_generateJson();
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
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			// Must be an admin
			if(!$active_worker->is_superuser)
				throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.admin'));
			
			if(!empty($id) && !empty($do_delete)) { // Delete
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", Context_GpgPrivateKey::ID)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				if(false == ($model = DAO_GpgPrivateKey::get($id)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.record.not_found'));
				
				if(!Context_GpgPrivateKey::isDeletableByActor($model, $active_worker))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				CerberusContexts::logActivityRecordDelete(Context_GpgPrivateKey::ID, $model->id, $model->name);
				
				DAO_GpgPrivateKey::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
				
			} else {
				$name = DevblocksPlatform::importGPC($_POST['name'] ?? null, 'string', '');
				$key_text = DevblocksPlatform::importGPC($_POST['key_text'] ?? null, 'string', '');
				$passphrase = DevblocksPlatform::importGPC($_POST['passphrase'] ?? null, 'string', '');
				
				$keyinfo = [];
				$expires_at = 0;
				
				$error = null;
				
				if($key_text) {
					if(false == ($keyinfo = $gpg->importPrivateKey($key_text)))
						throw new Exception_DevblocksAjaxValidationError("Failed to decrypt the given private key.", 'key_text');
					
					if(!isset($keyinfo['uids']) || !is_array($keyinfo['uids']) || empty($keyinfo['uids']))
						throw new Exception_DevblocksAjaxValidationError("Failed to retrieve private key UID info.", 'key_text');
					
					if(!$keyinfo['can_sign'] || !$keyinfo['is_secret'])
						throw new Exception_DevblocksAjaxValidationError("This is not a valid private key.", "key_text");
					
					@$key = $keyinfo['subkeys'][0];
					
					if(empty($key))
						throw new Exception_DevblocksAjaxValidationError("Failed to retrieve private key subkey info.", "key_text");
					
					if($key['expired'] || $key['disabled'] || $key['revoked'])
						throw new Exception_DevblocksAjaxValidationError("This private key is expired, revoked, or disabled.", "key_text");
					
					if(!$name)
						$name = $keyinfo['uids'][0]['uid'] ?? $keyinfo['uids'][0]['email'] ?? null;
					
					$expires_at = $key['expires'];
					
					// If this fingerprint already exists, return the existing key info
					if(!$id && false != ($record = DAO_GpgPrivateKey::getByFingerprint($key['fingerprint']))) {
						$id = $record->id;
						C4_AbstractView::setMarqueeContextCreated($view_id, Context_GpgPrivateKey::ID, $id);
					}
					
				} else {
					if(!$id)
						throw new Exception_DevblocksAjaxValidationError('The key text is required.');
				}
				
				if(empty($id)) { // New
					// [TODO] Derive a public key from private
					
					if(empty($key_text))
						throw new Exception_DevblocksAjaxValidationError("The 'Key' field is required.", 'key_text');
					
					$fields = [
						DAO_GpgPrivateKey::NAME => $name,
						DAO_GpgPrivateKey::FINGERPRINT => $keyinfo['subkeys'][0]['fingerprint'],
						DAO_GpgPrivateKey::EXPIRES_AT => $expires_at,
						DAO_GpgPrivateKey::KEY_TEXT => $key_text,
						DAO_GpgPrivateKey::UPDATED_AT => time(),
					];
					
					if($passphrase)
						$fields[DAO_GpgPrivateKey::PASSPHRASE_ENCRYPTED] = DevblocksPlatform::services()->encryption()->encrypt($passphrase);
					
					if(!DAO_GpgPrivateKey::validate($fields, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_GpgPrivateKey::onBeforeUpdateByActor($active_worker, $fields, null, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					$id = DAO_GpgPrivateKey::create($fields);
					DAO_GpgPrivateKey::onUpdateByActor($active_worker, $fields, $id);
					
					DAO_GpgKeyPart::upsert(Context_GpgPrivateKey::ID, $id, $keyinfo);
					
					if(!empty($view_id) && !empty($id))
						C4_AbstractView::setMarqueeContextCreated($view_id, Context_GpgPrivateKey::ID, $id);
					
				} else { // Edit
					$fields = array(
						DAO_GpgPrivateKey::NAME => $name,
						DAO_GpgPrivateKey::UPDATED_AT => time(),
					);
					
					if($passphrase)
						$fields[DAO_GpgPrivateKey::PASSPHRASE_ENCRYPTED] = DevblocksPlatform::services()->encryption()->encrypt($passphrase);
					
					if($keyinfo) {
						$fields[DAO_GpgPrivateKey::FINGERPRINT] = $keyinfo['subkeys'][0]['fingerprint'];
						$fields[DAO_GpgPrivateKey::KEY_TEXT] = $key_text;
						$fields[DAO_GpgPrivateKey::EXPIRES_AT] = $expires_at;
						
						DAO_GpgKeyPart::upsert(Context_GpgPrivateKey::ID, $id, $keyinfo);
					}
					
					if(!DAO_GpgPrivateKey::validate($fields, $error, $id))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_GpgPrivateKey::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					DAO_GpgPrivateKey::update($id, $fields);
					DAO_GpgPrivateKey::onUpdateByActor($active_worker, $fields, $id);
				}
				
				if($id) {
					// Custom field saves
					$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'] ?? null, 'array', []);
					if(!DAO_CustomFieldValue::handleFormPost(Context_GpgPrivateKey::ID, $id, $field_ids, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
				}
				
				echo json_encode(array(
					'status' => true,
					'context' => Context_GpgPrivateKey::ID,
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
	
	private function _profileAction_generateJson() {
		$gpg = DevblocksPlatform::services()->gpg();
		$active_worker = CerberusApplication::getActiveWorker();
		
		try {
			if('POST' != DevblocksPlatform::getHttpMethod())
				throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('common.access_denied'));
			
			if(!$active_worker->is_superuser)
				throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('common.access_denied'));
			
			header('Content-Type: application/json; charset=utf-8');
			
			$key_length = DevblocksPlatform::importGPC($_POST['key_length'] ?? null, 'int', 2048);
			$uid_names = DevblocksPlatform::importGPC($_POST['uid_names'] ?? null, 'array', []);
			$uid_emails = DevblocksPlatform::importGPC($_POST['uid_emails'] ?? null, 'array', []);
			$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string', '');
			
			// [TODO] Validate key length
			
			$uids = [];
			
			foreach(array_keys($uid_emails) as $idx) {
				$uid_name = $uid_names[$idx];
				$uid_email = $uid_emails[$idx];
				
				if(!$uid_name || !$uid_email)
					continue;
				
				$uids[] = [
					'name' => $uid_name,
					'email' => $uid_email,
					'comment' => '',
				];
			}
			
			// [TODO] Validate UIDs
			// [TODO] Exceptions
			
			list('public_key'=>$public_key, 'private_key'=>$private_key) = @$gpg->keygen($uids, $key_length, null);
			
			// Import private key
			
			$private_keyinfo = $gpg->importPrivateKey($private_key);
			
			$fields = [
				DAO_GpgPrivateKey::NAME => $private_keyinfo['uids'][0]['uid'],
				DAO_GpgPrivateKey::FINGERPRINT => $private_keyinfo['subkeys'][0]['fingerprint'],
				DAO_GpgPrivateKey::EXPIRES_AT => intval(@$private_keyinfo['subkeys'][0]['expires']),
				DAO_GpgPrivateKey::KEY_TEXT => $private_key,
				DAO_GpgPrivateKey::UPDATED_AT => time(),
			];
			
//			if($passphrase)
//				$fields[DAO_GpgPrivateKey::PASSPHRASE_ENCRYPTED] = DevblocksPlatform::services()->encryption()->encrypt($passphrase);
			
			if(!DAO_GpgPrivateKey::validate($fields, $error))
				throw new Exception_DevblocksAjaxValidationError($error);
			
			if(!DAO_GpgPrivateKey::onBeforeUpdateByActor($active_worker, $fields, null, $error))
				throw new Exception_DevblocksAjaxValidationError($error);
			
			$private_key_id = DAO_GpgPrivateKey::create($fields);
			DAO_GpgPrivateKey::onUpdateByActor($active_worker, $fields, $private_key_id);
			
			DAO_GpgKeyPart::upsert(Context_GpgPrivateKey::ID, $private_key_id, $private_keyinfo);
			
			// Import public key
			
			$public_keyinfo = $gpg->importPublicKey($public_key);
			
			$fields = [
				DAO_GpgPublicKey::NAME => $public_keyinfo['uids'][0]['uid'],
				DAO_GpgPublicKey::FINGERPRINT => $public_keyinfo['subkeys'][0]['fingerprint'],
				DAO_GpgPublicKey::EXPIRES_AT => intval(@$public_keyinfo['subkeys'][0]['expires']),
				DAO_GpgPublicKey::KEY_TEXT => $public_key,
				DAO_GpgPublicKey::UPDATED_AT => time(),
			];
			
			if(!DAO_GpgPublicKey::validate($fields, $error))
				throw new Exception_DevblocksAjaxValidationError($error);
			
			if(!DAO_GpgPublicKey::onBeforeUpdateByActor($active_worker, $fields, null, $error))
				throw new Exception_DevblocksAjaxValidationError($error);
			
			$public_key_id = DAO_GpgPublicKey::create($fields);
			
			DAO_GpgKeyPart::upsert(Context_GpgPublicKey::ID, $public_key_id, $public_keyinfo);
			
			DAO_GpgPublicKey::onUpdateByActor($active_worker, $fields, $public_key_id);
			
			if(!empty($view_id) && !empty($id))
				C4_AbstractView::setMarqueeContextCreated($view_id, Context_GpgPrivateKey::ID, $private_key_id);
			
			echo json_encode(array(
				'status' => true,
				'context' => Context_GpgPrivateKey::ID,
				'id' => $private_key_id,
				'label' => $private_keyinfo['uids'][0]['uid'],
				'view_id' => $view_id,
			));
			return;
			
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
