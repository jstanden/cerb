<?php
class PageSection_SetupMailPop3 extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();
		
		$visit->set(Extension_ConfigTab::POINT, 'mail_pop3');
		
		// POP3
		$pop3_accounts = DAO_Mail::getPop3Accounts();
		$tpl->assign('pop3_accounts', $pop3_accounts);
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/mail_pop3/index.tpl');		
	}
	
	function getMailboxAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		
		$tpl = DevblocksPlatform::getTemplateService();
				
		if(!empty($id)) {
			@$pop3 = DAO_Mail::getPop3Account($id);
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
			@$delete = DevblocksPlatform::importGPC($_POST['delete'],'integer');
	
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
	
		    // [JAS]: [TODO] convert to field constants
			$fields = array(
			    'enabled' => $enabled,
				'nickname' => $nickname,
				'protocol' => $protocol,
				'host' => $host,
				'username' => $username,
				'password' => $password,
				'port' => $port
			);
			
			if(!empty($id) && !empty($delete)) {
				DAO_Mail::deletePop3Account($id);
				
			} elseif(!empty($id)) {
				DAO_Mail::updatePop3Account($id, $fields);
				
			} else {
	            if(!empty($host) && !empty($username)) {
				    $id = DAO_Mail::createPop3Account($fields);
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
				
				if(false == $mail_service->testImap($host, $port, $protocol, $user, $pass))
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