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
	
	function savePeekJsonAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'], 'string', '');
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'], 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker || !$active_worker->is_superuser)
			throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.admin'));
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			if(!empty($id) && !empty($do_delete)) { // Delete
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", 'cerberusweb.contexts.mailbox')))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				DAO_Mailbox::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
				
			} else {
				@$auth_disable_plain = DevblocksPlatform::importGPC($_REQUEST['auth_disable_plain'],'integer',0);
				@$enabled = DevblocksPlatform::importGPC($_POST['enabled'],'integer',0);
				@$host = DevblocksPlatform::importGPC($_POST['host'],'string');
				@$max_msg_size_kb = DevblocksPlatform::importGPC($_POST['max_msg_size_kb'],'integer');
				@$name = DevblocksPlatform::importGPC($_POST['name'],'string');
				@$password = DevblocksPlatform::importGPC($_POST['password'],'string');
				@$port = DevblocksPlatform::importGPC($_POST['port'],'integer');
				@$protocol = DevblocksPlatform::importGPC($_POST['protocol'],'string');
				@$ssl_ignore_validation = DevblocksPlatform::importGPC($_REQUEST['ssl_ignore_validation'],'integer',0);
				@$timeout_secs = DevblocksPlatform::importGPC($_POST['timeout_secs'],'integer');
				@$username = DevblocksPlatform::importGPC($_POST['username'],'string');
				
				// Defaults
				if(empty($port)) {
					switch($protocol) {
						case 'pop3':
							$port = 110;
							break;
						case 'pop3-ssl':
							$port = 995;
							break;
						case 'imap':
							$port = 143;
							break;
						case 'imap-ssl':
							$port = 993;
							break;
					}
				}
				
				if(empty($id)) { // New
					$fields = array(
						DAO_Mailbox::AUTH_DISABLE_PLAIN => $auth_disable_plain,
						DAO_Mailbox::DELAY_UNTIL => 0,
						DAO_Mailbox::ENABLED => $enabled,
						DAO_Mailbox::HOST => $host,
						DAO_Mailbox::MAX_MSG_SIZE_KB => $max_msg_size_kb,
						DAO_Mailbox::NAME => $name,
						DAO_Mailbox::NUM_FAILS => 0,
						DAO_Mailbox::PASSWORD => $password,
						DAO_Mailbox::PORT => $port,
						DAO_Mailbox::PROTOCOL => $protocol,
						DAO_Mailbox::SSL_IGNORE_VALIDATION => $ssl_ignore_validation,
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
						DAO_Mailbox::AUTH_DISABLE_PLAIN => $auth_disable_plain,
						DAO_Mailbox::DELAY_UNTIL => 0,
						DAO_Mailbox::ENABLED => $enabled,
						DAO_Mailbox::HOST => $host,
						DAO_Mailbox::MAX_MSG_SIZE_KB => $max_msg_size_kb,
						DAO_Mailbox::NAME => $name,
						DAO_Mailbox::NUM_FAILS => 0,
						DAO_Mailbox::PASSWORD => $password,
						DAO_Mailbox::PORT => $port,
						DAO_Mailbox::PROTOCOL => $protocol,
						DAO_Mailbox::SSL_IGNORE_VALIDATION => $ssl_ignore_validation,
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
				@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', []);
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
	
	function testMailboxJsonAction() {
		header('Content-Type: application/json');
		
		try {
			$error_reporting = error_reporting(E_ERROR & ~E_NOTICE);
			
			$translate = DevblocksPlatform::getTranslationService();
			
			@$protocol = DevblocksPlatform::importGPC($_REQUEST['protocol'],'string','');
			@$host = DevblocksPlatform::importGPC($_REQUEST['host'],'string','');
			@$port = DevblocksPlatform::importGPC($_REQUEST['port'],'integer',110);
			@$user = DevblocksPlatform::importGPC($_REQUEST['username'],'string','');
			@$pass = DevblocksPlatform::importGPC($_REQUEST['password'],'string','');
			@$timeout_secs = DevblocksPlatform::importGPC($_REQUEST['timeout_secs'],'integer',0);
			@$max_msg_size_kb = DevblocksPlatform::importGPC($_REQUEST['max_msg_size_kb'],'integer',25600);
			@$ssl_ignore_validation = DevblocksPlatform::importGPC($_REQUEST['ssl_ignore_validation'],'integer',0);
			@$auth_disable_plain = DevblocksPlatform::importGPC($_REQUEST['auth_disable_plain'],'integer',0);
			
			// Defaults
			if(empty($port)) {
				switch($protocol) {
					case 'pop3':
						$port = 110;
						break;
					case 'pop3-ssl':
						$port = 995;
						break;
					case 'imap':
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
				
				if(false == $mail_service->testMailbox($host, $port, $protocol, $user, $pass, $ssl_ignore_validation, $auth_disable_plain, $timeout_secs, $max_msg_size_kb))
					throw new Exception($translate->_('config.mailboxes.failed'));
				
			} else {
				throw new Exception($translate->_('config.mailboxes.error_hostname'));
				
			}
			
			echo json_encode(array('status'=>true));
			return;
			
		} catch(Exception $e) {
			echo json_encode(array('status'=>false,'error'=>$e->getMessage()));
			return;
			
		}
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
			$models = array();
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
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=search&type=mailbox', true),
				);
				$models[] = $model;
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
			foreach($results as $opp_id => $row) {
				if($opp_id==$explore_from)
					$orig_pos = $pos;
				
				$url = $url_writer->writeNoProxy(sprintf("c=profiles&type=mailbox&id=%d-%s", $row[SearchFields_Mailbox::ID], DevblocksPlatform::strToPermalink($row[SearchFields_Mailbox::NAME])), true);
				
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'id' => $row[SearchFields_Mailbox::ID],
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
