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

class PageSection_ProfilesMailbox extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();
		$translate = DevblocksPlatform::getTranslationService();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // mailbox
		$id = array_shift($stack); // 123

		@$id = intval($id);
		
		if(!$active_worker->is_superuser)
			return;
		
		if(!$id || (false == ($mailbox = DAO_Mailbox::get($id))))
			return;

		$tpl->assign('mailbox', $mailbox);
	
		// Tab persistence
		
		$point = 'profiles.mailbox.tab';
		$tpl->assign('point', $point);
		
		if(null == (@$tab_selected = $stack[0])) {
			$tab_selected = $visit->get($point, '');
		}
		$tpl->assign('tab_selected', $tab_selected);
	
		// Properties
			
		$properties = array();
			
		$properties['enabled'] = array(
			'label' => mb_ucfirst($translate->_('common.enabled')),
			'type' => Model_CustomField::TYPE_CHECKBOX,
			'value' => $mailbox->enabled,
		);
		
		$properties['protocol'] = array(
			'label' => mb_ucfirst($translate->_('dao.mailbox.protocol')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $mailbox->protocol,
		);
			
		$properties['host'] = array(
			'label' => mb_ucfirst($translate->_('common.host')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $mailbox->host,
		);
			
		$properties['username'] = array(
			'label' => mb_ucfirst($translate->_('common.user')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $mailbox->username,
		);
		
		$properties['port'] = array(
			'label' => mb_ucfirst($translate->_('dao.mailbox.port')),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $mailbox->port,
		);
			
		$properties['num_fails'] = array(
			'label' => mb_ucfirst($translate->_('dao.mailbox.num_fails')),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $mailbox->num_fails,
		);
		
		if($mailbox->delay_until) {
			$properties['delay_until'] = array(
				'label' => mb_ucfirst($translate->_('dao.mailbox.delay_until')),
				'type' => Model_CustomField::TYPE_DATE,
				'value' => $mailbox->delay_until,
			);
		}
		
		$properties['delay_until'] = array(
			'label' => 'Timeout (secs)',
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $mailbox->timeout_secs,
		);
		
		$properties['max_msg_size_kb'] = array(
			'label' => 'Max. Msg. Size',
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => DevblocksPlatform::strPrettyBytes($mailbox->max_msg_size_kb * 1000),
		);
		
		$properties['ssl_ignore_validation'] = array(
			'label' => mb_ucfirst($translate->_('dao.mailbox.ssl_ignore_validation')),
			'type' => Model_CustomField::TYPE_CHECKBOX,
			'value' => $mailbox->ssl_ignore_validation,
		);
		
		$properties['auth_disable_plain'] = array(
			'label' => mb_ucfirst($translate->_('dao.mailbox.auth_disable_plain')),
			'type' => Model_CustomField::TYPE_CHECKBOX,
			'value' => $mailbox->auth_disable_plain,
		);
		
		$properties['updated'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.updated'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $mailbox->updated_at,
		);
			
	
		// Custom Fields

		@$values = array_shift(DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_MAILBOX, $mailbox->id)) or array();
		$tpl->assign('custom_field_values', $values);
		
		$properties_cfields = Page_Profiles::getProfilePropertiesCustomFields(CerberusContexts::CONTEXT_MAILBOX, $values);
		
		if(!empty($properties_cfields))
			$properties = array_merge($properties, $properties_cfields);
		
		// Custom Fieldsets

		$properties_custom_fieldsets = Page_Profiles::getProfilePropertiesCustomFieldsets(CerberusContexts::CONTEXT_MAILBOX, $mailbox->id, $values);
		$tpl->assign('properties_custom_fieldsets', $properties_custom_fieldsets);
		
		// Link counts
		
		$properties_links = array(
			CerberusContexts::CONTEXT_MAILBOX => array(
				$mailbox->id => 
					DAO_ContextLink::getContextLinkCounts(
						CerberusContexts::CONTEXT_MAILBOX,
						$mailbox->id,
						array(CerberusContexts::CONTEXT_CUSTOM_FIELDSET)
					),
			),
		);
		
		$tpl->assign('properties_links', $properties_links);
		
		// Properties
		
		$tpl->assign('properties', $properties);
			
		// Tabs
		$tab_manifests = Extension_ContextProfileTab::getExtensions(false, CerberusContexts::CONTEXT_MAILBOX);
		$tpl->assign('tab_manifests', $tab_manifests);
		
		// Template
		$tpl->display('devblocks:cerberusweb.core::profiles/mailbox.tpl');
	}
	
	function savePeekJsonAction() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		header('Content-Type: application/json');
		
		try {
			if(!$active_worker || !$active_worker->is_superuser)
				throw new Exception("You are not an administrator.");
			
			@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'], 'string', '');
			@$id = DevblocksPlatform::importGPC($_POST['id'],'integer');
			@$do_delete = DevblocksPlatform::importGPC($_POST['do_delete'],'integer',0);
	
			if(!empty($id) && !empty($do_delete)) { // Delete
				DAO_Mailbox::delete($id);
				
			} else {
				@$enabled = DevblocksPlatform::importGPC($_POST['enabled'],'integer',0);
				@$name = DevblocksPlatform::importGPC($_POST['name'],'string');
				@$protocol = DevblocksPlatform::importGPC($_POST['protocol'],'string');
				@$host = DevblocksPlatform::importGPC($_POST['host'],'string');
				@$username = DevblocksPlatform::importGPC($_POST['username'],'string');
				@$password = DevblocksPlatform::importGPC($_POST['password'],'string');
				@$port = DevblocksPlatform::importGPC($_POST['port'],'integer');
				@$timeout_secs = DevblocksPlatform::importGPC($_POST['timeout_secs'],'integer');
				@$max_msg_size_kb = DevblocksPlatform::importGPC($_POST['max_msg_size_kb'],'integer');
				@$ssl_ignore_validation = DevblocksPlatform::importGPC($_REQUEST['ssl_ignore_validation'],'integer',0);
				@$auth_disable_plain = DevblocksPlatform::importGPC($_REQUEST['auth_disable_plain'],'integer',0);
				
				if(empty($name))
					$name = "Mailbox";
			
				if(empty($host))
					throw new Exception("Host is blank.");
				if(empty($username))
					throw new Exception("Username is blank.");
				if(empty($password))
					throw new Exception("Password is blank.");
				
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
				
				$fields = array(
					DAO_Mailbox::ENABLED => $enabled,
					DAO_Mailbox::NAME => $name,
					DAO_Mailbox::PROTOCOL => $protocol,
					DAO_Mailbox::HOST => $host,
					DAO_Mailbox::USERNAME => $username,
					DAO_Mailbox::PASSWORD => $password,
					DAO_Mailbox::PORT => $port,
					DAO_Mailbox::NUM_FAILS => 0,
					DAO_Mailbox::DELAY_UNTIL => 0,
					DAO_Mailbox::TIMEOUT_SECS => $timeout_secs,
					DAO_Mailbox::MAX_MSG_SIZE_KB => $max_msg_size_kb,
					DAO_Mailbox::SSL_IGNORE_VALIDATION => $ssl_ignore_validation,
					DAO_Mailbox::AUTH_DISABLE_PLAIN => $auth_disable_plain,
					DAO_Mailbox::UPDATED_AT => time(),
				);
				
				if(empty($id)) { // New
					$id = DAO_Mailbox::create($fields);
					
					if(!empty($view_id) && !empty($id))
						C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_MAILBOX, $id);
					
				} else { // Edit
					DAO_Mailbox::update($id, $fields);
					
				}
	
				// If we're adding a comment
				if(!empty($comment)) {
					$also_notify_worker_ids = array_keys(CerberusApplication::getWorkersByAtMentionsText($comment));
					
					$fields = array(
						DAO_Comment::CREATED => time(),
						DAO_Comment::CONTEXT => CerberusContexts::CONTEXT_MAILBOX,
						DAO_Comment::CONTEXT_ID => $id,
						DAO_Comment::COMMENT => $comment,
						DAO_Comment::OWNER_CONTEXT => CerberusContexts::CONTEXT_WORKER,
						DAO_Comment::OWNER_CONTEXT_ID => $active_worker->id,
					);
					$comment_id = DAO_Comment::create($fields, $also_notify_worker_ids);
				}
				
				// Custom fields
				@$field_ids = DevblocksPlatform::importGPC($_REQUEST['field_ids'], 'array', array());
				DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_MAILBOX, $id, $field_ids);
			}
			
			echo json_encode(array('status'=>true));
			return;
			
		} catch(Exception $e) {
			echo json_encode(array('status'=>false,'error'=>$e->getMessage()));
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
		$url_writer = DevblocksPlatform::getUrlService();
		
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
					'toolbar_extension_id' => 'cerberusweb.contexts.mailbox.explore.toolbar',
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
