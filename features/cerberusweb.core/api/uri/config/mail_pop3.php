<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2014, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerberusweb.com/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerbweb.com	    http://www.webgroupmedia.com/
***********************************************************************/

class PageSection_SetupMailPop3 extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();
		
		$visit->set(ChConfigurationPage::ID, 'mail_pop3');
		
		// POP3
		$pop3_accounts = DAO_Pop3Account::getPop3Accounts();
		$tpl->assign('pop3_accounts', $pop3_accounts);
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/mail_pop3/index.tpl');
	}
	
	function getMailboxAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		
		$tpl = DevblocksPlatform::getTemplateService();
				
		if(!empty($id)) {
			@$pop3 = DAO_Pop3Account::getPop3Account($id);
			$tpl->assign('pop3_account', $pop3);
		}
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/mail_pop3/edit_pop3_account.tpl');
		
		return;
	}
	
	function saveMailboxJsonAction() {
		try {
			$worker = CerberusApplication::getActiveWorker();
			if(!$worker || !$worker->is_superuser)
				throw new Exception("You are not an administrator.");
			
			@$id = DevblocksPlatform::importGPC($_POST['account_id'],'integer');
			@$enabled = DevblocksPlatform::importGPC($_POST['pop3_enabled'],'integer',0);
			@$nickname = DevblocksPlatform::importGPC($_POST['nickname'],'string');
			@$protocol = DevblocksPlatform::importGPC($_POST['protocol'],'string');
			@$host = DevblocksPlatform::importGPC($_POST['host'],'string');
			@$username = DevblocksPlatform::importGPC($_POST['username'],'string');
			@$password = DevblocksPlatform::importGPC($_POST['password'],'string');
			@$port = DevblocksPlatform::importGPC($_POST['port'],'integer');
			@$timeout_secs = DevblocksPlatform::importGPC($_POST['timeout_secs'],'integer');
			@$max_msg_size_kb = DevblocksPlatform::importGPC($_POST['max_msg_size_kb'],'integer');
			@$ssl_ignore_validation = DevblocksPlatform::importGPC($_REQUEST['ssl_ignore_validation'],'integer',0);
			@$delete = DevblocksPlatform::importGPC($_POST['do_delete'],'integer',0);
	
			if(empty($nickname))
				$nickname = "POP3";
			
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
				DAO_Pop3Account::ENABLED => $enabled,
				DAO_Pop3Account::NICKNAME => $nickname,
				DAO_Pop3Account::PROTOCOL => $protocol,
				DAO_Pop3Account::HOST => $host,
				DAO_Pop3Account::USERNAME => $username,
				DAO_Pop3Account::PASSWORD => $password,
				DAO_Pop3Account::PORT => $port,
				DAO_Pop3Account::NUM_FAILS => 0,
				DAO_Pop3Account::DELAY_UNTIL => 0,
				DAO_Pop3Account::TIMEOUT_SECS => $timeout_secs,
				DAO_Pop3Account::MAX_MSG_SIZE_KB => $max_msg_size_kb,
				DAO_Pop3Account::SSL_IGNORE_VALIDATION => $ssl_ignore_validation,
			);
			
			if(!empty($id) && !empty($delete)) {
				DAO_Pop3Account::deletePop3Account($id);
				
			} elseif(!empty($id)) {
				DAO_Pop3Account::updatePop3Account($id, $fields);
				
			} else {
				if(!empty($host) && !empty($username)) {
					$id = DAO_Pop3Account::createPop3Account($fields);
				}
			}
			
			echo json_encode(array('status'=>true));
			return;
			
		} catch(Exception $e) {
			echo json_encode(array('status'=>false,'error'=>$e->getMessage()));
			return;
			
		}
	}

	function testMailboxJsonAction() {
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
				$mail_service = DevblocksPlatform::getMailService();
				
				if(false == $mail_service->testMailbox($host, $port, $protocol, $user, $pass, $ssl_ignore_validation, $timeout_secs, $max_msg_size_kb))
					throw new Exception($translate->_('config.mail.pop3.failed'));
				
			} else {
				throw new Exception($translate->_('config.mail.pop3.error_hostname'));
				
			}
			
			echo json_encode(array('status'=>true));
			return;
			
		} catch(Exception $e) {
			echo json_encode(array('status'=>false,'error'=>$e->getMessage()));
			return;
			
		}
	}
}