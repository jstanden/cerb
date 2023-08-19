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

class PageSection_ProfilesMailbox extends Extension_PageSection {
	function render() {
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // mailbox
		@$context_id = intval(array_shift($stack)); // 123

		$context = CerberusContexts::CONTEXT_MAILBOX;
		
		Page_Profiles::renderProfile($context, $context_id, $stack);
	}
	
	function handleActionForPage(string $action, string $scope=null) {
		if('profileAction' == $scope) {
			switch ($action) {
				case 'savePeekJson':
					return $this->_profileAction_savePeekJson();
				case 'testMailboxJson':
					return $this->_profileAction_testMailboxJson();
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
			if(!$active_worker || !$active_worker->is_superuser)
				throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.admin'));
			
			if(!empty($id) && !empty($do_delete)) { // Delete
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", 'cerberusweb.contexts.mailbox')))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				if(false == ($model = DAO_Mailbox::get($id)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.record.not_found'));
				
				if(!Context_Mailbox::isDeletableByActor($model, $active_worker))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				CerberusContexts::logActivityRecordDelete(CerberusContexts::CONTEXT_MAILBOX, $model->id, $model->name);
				
				DAO_Mailbox::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
				
			} else {
				$connected_account_id = DevblocksPlatform::importGPC($_POST['connected_account_id'] ?? null, 'integer', 0);
				$enabled = DevblocksPlatform::importGPC($_POST['enabled'] ?? null, 'integer',0);
				$host = DevblocksPlatform::importGPC($_POST['host'] ?? null, 'string');
				$max_msg_size_kb = DevblocksPlatform::importGPC($_POST['max_msg_size_kb'] ?? null, 'integer');
				$name = DevblocksPlatform::importGPC($_POST['name'] ?? null, 'string');
				$password = DevblocksPlatform::importGPC($_POST['password'] ?? null, 'string');
				$port = DevblocksPlatform::importGPC($_POST['port'] ?? null, 'integer');
				$protocol = DevblocksPlatform::importGPC($_POST['protocol'] ?? null, 'string');
				$timeout_secs = DevblocksPlatform::importGPC($_POST['timeout_secs'] ?? null, 'integer');
				$username = DevblocksPlatform::importGPC($_POST['username'] ?? null, 'string');
				
				// Defaults
				if(empty($port)) {
					switch($protocol) {
						case 'pop3':
						case 'pop3-starttls':
							$port = 110;
							break;
						case 'pop3-ssl':
							$port = 995;
							break;
						case 'imap':
						case 'imap-starttls':
							$port = 143;
							break;
						case 'imap-ssl':
							$port = 993;
							break;
					}
				}
				
				if(empty($id)) { // New
					$fields = array(
						DAO_Mailbox::CONNECTED_ACCOUNT_ID => $connected_account_id,
						DAO_Mailbox::DELAY_UNTIL => 0,
						DAO_Mailbox::ENABLED => $enabled,
						DAO_Mailbox::HOST => $host,
						DAO_Mailbox::MAX_MSG_SIZE_KB => $max_msg_size_kb,
						DAO_Mailbox::NAME => $name,
						DAO_Mailbox::NUM_FAILS => 0,
						DAO_Mailbox::PASSWORD => $password,
						DAO_Mailbox::PORT => $port,
						DAO_Mailbox::PROTOCOL => $protocol,
						DAO_Mailbox::TIMEOUT_SECS => $timeout_secs,
						DAO_Mailbox::UPDATED_AT => time(),
						DAO_Mailbox::USERNAME => $username,
					);
					
					if(!DAO_Mailbox::validate($fields, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_Mailbox::onBeforeUpdateByActor($active_worker, $fields, null, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					$id = DAO_Mailbox::create($fields);
					DAO_Mailbox::onUpdateByActor($active_worker, $fields, $id);
					
					if(!empty($view_id) && !empty($id))
						C4_AbstractView::setMarqueeContextCreated($view_id, 'cerberusweb.contexts.mailbox', $id);
					
				} else { // Edit
					$fields = array(
						DAO_Mailbox::CONNECTED_ACCOUNT_ID => $connected_account_id,
						DAO_Mailbox::DELAY_UNTIL => 0,
						DAO_Mailbox::ENABLED => $enabled,
						DAO_Mailbox::HOST => $host,
						DAO_Mailbox::MAX_MSG_SIZE_KB => $max_msg_size_kb,
						DAO_Mailbox::NAME => $name,
						DAO_Mailbox::NUM_FAILS => 0,
						DAO_Mailbox::PASSWORD => $password,
						DAO_Mailbox::PORT => $port,
						DAO_Mailbox::PROTOCOL => $protocol,
						DAO_Mailbox::TIMEOUT_SECS => $timeout_secs,
						DAO_Mailbox::UPDATED_AT => time(),
						DAO_Mailbox::USERNAME => $username,
					);
					
					if(!DAO_Mailbox::validate($fields, $error, $id))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_Mailbox::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					DAO_Mailbox::update($id, $fields);
					DAO_Mailbox::onUpdateByActor($active_worker, $fields, $id);
				}
				
				// Custom field saves
				$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'] ?? null, 'array', []);
				if(!DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_MAILBOX, $id, $field_ids, $error))
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
	
	private function _profileAction_testMailboxJson() {
		$translate = DevblocksPlatform::getTranslationService();
		$active_worker = CerberusApplication::getActiveWorker();
		
		header('Content-Type: application/json');
		
		@$error_reporting = error_reporting(E_ERROR & ~E_NOTICE);
		
		try {
			if('POST' != DevblocksPlatform::getHttpMethod())
				throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('common.access_denied'));
			
			if(!$active_worker->is_superuser)
				throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('common.access_denied'));
			
			$protocol = DevblocksPlatform::importGPC($_POST['protocol'] ?? null, 'string','');
			$host = DevblocksPlatform::importGPC($_POST['host'] ?? null, 'string','');
			$port = DevblocksPlatform::importGPC($_POST['port'] ?? null, 'integer',110);
			$user = DevblocksPlatform::importGPC($_POST['username'] ?? null, 'string','');
			$pass = DevblocksPlatform::importGPC($_POST['password'] ?? null, 'string','');
			$timeout_secs = DevblocksPlatform::importGPC($_POST['timeout_secs'] ?? null, 'integer',0);
			$max_msg_size_kb = DevblocksPlatform::importGPC($_POST['max_msg_size_kb'] ?? null, 'integer',25600);
			$connected_account_id = DevblocksPlatform::importGPC($_POST['connected_account_id'] ?? null, 'integer',0);
			
			// Defaults
			if(empty($port)) {
				switch($protocol) {
					case 'pop3':
					case 'pop3-starttls':
						$port = 110;
						break;
					case 'pop3-ssl':
						$port = 995;
						break;
					case 'imap':
					case 'imap-starttls':
						$port = 143;
						break;
					case 'imap-ssl':
						$port = 993;
						break;
				}
			}
			
			// Test the provided POP settings and give form feedback
			if(!empty($host)) {
				$mail_service = DevblocksPlatform::services()->mail();
				
				if(false == $mail_service->testMailbox($host, $port, $protocol, $user, $pass, $timeout_secs, $connected_account_id))
					throw new Exception($translate->_('config.mailboxes.failed'));
				
			} else {
				throw new Exception($translate->_('config.mailboxes.error_hostname'));
				
			}
			
			echo json_encode(array('status'=>true));
			return;
			
		} catch(Exception $e) {
			echo json_encode(array('status'=>false,'error'=>$e->getMessage()));
			return;
			
		} finally {
			error_reporting($error_reporting);
		}
	}
	
	private function _profileAction_viewExplore() {
		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string', '');
		$explore_from = DevblocksPlatform::importGPC($_POST['explore_from'] ?? null, 'int', 0);
		
		$http_response = Cerb_ORMHelper::generateRecordExploreSet($view_id, $explore_from);
		DevblocksPlatform::redirect($http_response);
	}
};
