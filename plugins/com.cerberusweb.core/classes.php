<?php
class ChDashboardModule extends CerberusModuleExtension {
	function ChDashboardModule($manifest) {
//		$this->UserMeetMenuExtension($manifest);
		$this->CerberusModuleExtension($manifest,1);
	}
	
	function isVisible() {
		// check login
		$session = UserMeetSessionManager::getInstance();
		$visit = $session->getVisit();
		
		if(empty($visit)) {
			return false;
		} else {
			return true;
		}

		return true;
	}
	
	function render() {
		include_once(UM_PATH . '/libs/adodb/adodb-pager.inc.php');
		$um_db = UserMeetDatabase::getInstance();
		$tpl = UserMeetTemplateManager::getInstance();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		
		// [JAS]: [TODO] This needs to limit by the selected dashboard
		$views = CerberusDashboardDAO::getViews(); // getViews($dashboard_id)
		$views[11]->params = array( // params
//				new CerberusSearchCriteria('t.status','!=','O'),
				new CerberusSearchCriteria('t.priority','=','0'),
//				new CerberusSearchCriteria('t.status','in',array('C','W')),
				new CerberusSearchCriteria('t.mailbox_id','in',array(7,0)),
			);
		$views[12]->params = array( // params
//				new CerberusSearchCriteria('t.status','!=','O'),
//				new CerberusSearchCriteria('t.priority','!=','0'),
				new CerberusSearchCriteria('t.status','in',array('O','C','W')),
//				new CerberusSearchCriteria('t.mailbox_id','in',array(7,0)),
			);

		$tpl->assign('views', $views);
		
		$teams = CerberusApplication::getTeamList();
		$tpl->assign('teams', $teams);
		
		$mailboxes = CerberusApplication::getMailboxList();
		$tpl->assign('mailboxes', $mailboxes);
		
		$translate_tokens = array(
			"whos" => array(1)
		);
		$tpl->assign('translate_tokens', $translate_tokens);
		
		$tpl->cache_lifetime = "0";
		$tpl->display('file:' . dirname(__FILE__) . '/templates/dashboards/index.tpl.php');
	}
	
	//**** Local scope
	
	function viewticket() {
		CerberusApplication::setActiveModule("core.module.display");
	}
	
	function clickteam() {
		CerberusApplication::setActiveModule("core.module.search");
	}
	
	function clickmailbox() {
		CerberusApplication::setActiveModule("core.module.search");
	}
	
	function getLink() {
		return "?c=".$this->id."&a=click";
	}
	
	function viewSortBy() {
		@$id = $_REQUEST['id'];
		@$sortBy = $_REQUEST['sortBy'];
		
		$view = CerberusDashboardDAO::getView($id);
		$iSortAsc = intval($view->renderSortAsc);
		
		// [JAS]: If clicking the same header, toggle asc/desc.
		if(0 == strcasecmp($sortBy,$view->renderSortBy)) {
			$iSortAsc = (0 == $iSortAsc) ? 1 : 0;
		} else { // [JAS]: If a new header, start with asc.
			$iSortAsc = 1;
		}
		
		$um_db = UserMeetDatabase::getInstance();
		
		// [JAS]: [TODO] Move this into DAO
		$sql = sprintf("UPDATE dashboard_view SET sort_by = %s, sort_asc = %d WHERE id = %d",
			$um_db->qstr($sortBy),
			$iSortAsc,
			$id
		);
		$um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		return TRUE;
	}
	
	function viewPage() {
		@$id = $_REQUEST['id'];
		@$page = $_REQUEST['page'];
		
		$tpl = UserMeetTemplateManager::getInstance();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		$tpl->assign('id',$id);
		
		$fields = array(
			'page' => $page
		);
		
		CerberusDashboardDAO::updateView($id,$fields);		
		
		return ' ';
	}
	
	function viewRefresh() {
		@$id = $_REQUEST['id'];

		$tpl = UserMeetTemplateManager::getInstance();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		$tpl->assign('id',$id);
		
		$view = CerberusDashboardDAO::getView($id);
		$tpl->assign('view', $view);

		$tpl->cache_lifetime = "0";
		$tpl->display('file:' . dirname(__FILE__) . '/templates/dashboards/ticket_view.tpl.php');
	}
	
	function customize() {
		@$id = $_REQUEST['id'];

		$tpl = UserMeetTemplateManager::getInstance();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		$tpl->assign('id',$id);

		$view = CerberusDashboardDAO::getView($id);
		$tpl->assign('view',$view);
		
		$optColumns = CerberusApplication::getDashboardViewColumns();
		$tpl->assign('optColumns',$optColumns);
		
		$tpl->cache_lifetime = "0";
		$tpl->display('file:' . dirname(__FILE__) . '/templates/dashboards/rpc/customize_view.tpl.php');
	}
	
	function saveCustomize() {
		@$id = intval($_REQUEST['id']);
		@$name = $_REQUEST['name'];
		@$num_rows = intval($_REQUEST['num_rows']);
		@$columns = $_REQUEST['columns'];
		
		// [JAS]: Clear any empty columns
		if(is_array($columns))
		foreach($columns as $k => $v) {
			if(empty($v))
				unset($columns[$k]);
		}
		
		$fields = array(
			'name' => $name,
			'columns' => serialize($columns),
			'num_rows' => $num_rows,
			'page' => 0 // reset paging
		);
		
		CerberusDashboardDAO::updateView($id,$fields);
	}
	
	function searchview() {
		@$id = $_REQUEST['id'];
		CerberusApplication::setActiveModule("core.module.search");
	}
	
};

class ChConfigurationModule extends CerberusModuleExtension  {
	function ChConfigurationModule($manifest) {
		$this->CerberusModuleExtension($manifest);
	}
	
	function isVisible() {
		$session = UserMeetSessionManager::getInstance();
		$visit = $session->getVisit();
		
		if(empty($visit)) {
			return false;
		} else {
			return true;
		}
	}
	
	function render() {
		$tpl = UserMeetTemplateManager::getInstance();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');

		$tpl->display('file:' . dirname(__FILE__) . '/templates/configuration/index.tpl.php');
	}
}

class ChDisplayModule extends CerberusModuleExtension {
	function ChDisplayModule($manifest) {
		$this->CerberusModuleExtension($manifest);
	}
	
	function isVisible() {
		// check login
		$session = UserMeetSessionManager::getInstance();
		$visit = $session->getVisit();
		
		if(empty($visit)) {
			return false;
		} else {
			return true;
		}
	}
	
	function render() {
		$tpl = UserMeetTemplateManager::getInstance();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');

		@$id = $_REQUEST['id'];
		
		$ticket = CerberusTicketDAO::getTicket($id);
		$tpl->assign('ticket', $ticket);

		$mailboxes = CerberusApplication::getMailboxList();
		$tpl->assign('mailboxes', $mailboxes);
		
		$display_module_manifests = UserMeetPlatform::getExtensions("com.cerberusweb.display.module");
		$display_modules = array();
		
		if(is_array($display_module_manifests))
		foreach($display_module_manifests as $dmm) { /* @var $dmm UserMeetExtensionManifest */
			$display_modules[] = $dmm->createInstance(1);
		}
		$tpl->assign('display_modules', $display_modules);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/index.tpl.php');
	}

	function reply() {
		@$id = $_REQUEST['id'];

		$tpl = UserMeetTemplateManager::getInstance();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		$tpl->assign('id',$id);

		$message = CerberusTicketDAO::getMessage($id);
		$tpl->assign('message',$message);
		
		$ticket = CerberusTicketDAO::getTicket($message->ticket_id);
		$tpl->assign('ticket',$ticket);
		
		$tpl->cache_lifetime = "0";
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/rpc/reply.tpl.php');
	}
	
	// TODO: this needs to also have an agent_id passed to it, to identify the agent making the reply
	/**
	 * Enter description here...
	 * @todo Reuse sendReply
	 */
	function sendReply() {
		require_once(UM_PATH . '/libs/pear/Mail.php');
		// TODO: make this pull from a config instance rather than hard-coded
		$mail_params = array();
		$mail_params['host'] = 'mail.webgroupmedia.com';
		$mailer =& Mail::factory("smtp", $mail_params);
		
		@$id = $_REQUEST['id']; // message id
		@$cc = $_REQUEST['cc'];
		@$bcc = $_REQUEST['bcc'];
		@$content = $_REQUEST['content'];
		@$priority = $_REQUEST['priority'];
		@$status = $_REQUEST['status'];
		@$agent_id = $_REQUEST['agent_id'];
		
		$message = CerberusTicketDAO::getMessage($id);
		$ticket_id = $message->ticket_id;
		$ticket = CerberusTicketDAO::getTicket($ticket_id);
		$mailboxes = CerberusApplication::getMailboxList();
		$mailbox = $mailboxes[$ticket->mailbox_id];
		$requesters = CerberusTicketDAO::getRequestersByTicket($ticket_id);
		$sTo = '';
		$sRCPT = '';
		foreach ($requesters as $requester) {
			if (!empty($sTo)) $sTo .= ', ';
			if (!empty($sRCPT)) $sRCPT .= ', ';
			if (!empty($requester->personal)) $sTo .= $requester->personal . ' ';
			$sTo .= '<' . $requester->email . '>';
			$sRCPT .= $requester->email;
		}
		
		$headers = array();
		// TODO: pull info from mailbox instead of hard-coding it.  (may want to do this such that the display_name is just a personal on a mailbox address...)
//		$headers['From']		= !empty($mailbox->display_name)?'"'.$mailbox->display_name.'" <'.needafunction::getAddress($mailbox->reply_address_id)->email.'>':needafunction::getAddress($mailbox->reply_address_id)->email;
		$headers['from']		= 'pop1@cerberus6.webgroupmedia.com';
		$headers['to']			= $sTo;
		$headers['cc']			= $cc;
		$headers['bcc']			= $bcc;
		$headers['date']		= gmdate(r);
		$headers['message-id'] = CerberusApplication::generateMessageId();
		$headers['subject']		= $ticket->subject;
		$headers['references']	= $message->headers['message-id'];
		$headers['in-reply-to']	= $message->headers['message-id'];
		
		$mail_result =& $mailer->send($sRCPT, $headers, $content);
		if ($mail_result !== true) die("Error message was: " . $mail_result->getMessage());
		
		// TODO: create DAO object for Agent, be able to pull address by having agent id.
//		$headers['From'] = $agent_address->personal . ' <' . $agent_address->email . '>';
//		CerberusTicketDAO::createMessage($ticket_id,CerberusMessageType::EMAIL,gmmktime(),$agent_id,$headers,$content);
		CerberusTicketDAO::createMessage($ticket_id,CerberusMessageType::EMAIL,gmmktime(),1,$headers,$content);
		
		$_REQUEST['id'] = $ticket_id;
		CerberusApplication::setActiveModule($this->id);
	}
	
	function forward() {
		@$id = $_REQUEST['id'];

		$tpl = UserMeetTemplateManager::getInstance();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		$tpl->assign('id',$id);
		
		$message = CerberusTicketDAO::getMessage($id);
		$tpl->assign('message',$message);
		
		$ticket = CerberusTicketDAO::getTicket($message->ticket_id);
		$tpl->assign('ticket',$ticket);
		
		$tpl->cache_lifetime = "0";
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/rpc/forward.tpl.php');
	}
	
	function sendForward() {
		@$id = $_REQUEST['id']; // message id
		
		$message = CerberusTicketDAO::getMessage($id);
		$ticket_id = $message->ticket_id;
		$ticket = CerberusTicketDAO::getTicket($ticket_id);
		
		$_REQUEST['id'] = $ticket_id;
		CerberusApplication::setActiveModule($this->id);
	}
	
	function comment() {
		@$id = $_REQUEST['id'];

		$tpl = UserMeetTemplateManager::getInstance();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		$tpl->assign('id',$id);

		$message = CerberusTicketDAO::getMessage($id);
		$tpl->assign('message',$message);
		
		$ticket = CerberusTicketDAO::getTicket($message->ticket_id);
		$tpl->assign('ticket',$ticket);
		
		$tpl->cache_lifetime = "0";
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/rpc/comment.tpl.php');
	}
	
	function sendComment() {
		@$id = $_REQUEST['id']; // message id
		
		$message = CerberusTicketDAO::getMessage($id);
		$ticket_id = $message->ticket_id;
		$ticket = CerberusTicketDAO::getTicket($ticket_id);
		
		$_REQUEST['id'] = $ticket_id;
		CerberusApplication::setActiveModule($this->id);
	}
	
};

class ChSignInModule extends CerberusModuleExtension {
	function ChSignInModule($manifest) {
//		$this->UserMeetMenuExtension($manifest);
		$this->CerberusModuleExtension($manifest);
	}
	
	function isVisible() {
		// check login
		$session = UserMeetSessionManager::getInstance();
		$visit = $session->getVisit();
		
//		if(empty($visit)) {
//			return true;
//		} else {
//			return false;
//		}

		return true;
	}
	
//	function getLink() {
//		return "?c=".$this->id."&a=show";
//	}

	function show() {
//		echo "You clicked: " . __CLASS__ . "->" . __FUNCTION__ . "!<br>";
		CerberusApplication::setActiveModule("core.module.signin");
	}
	
	function render() {
		$tpl = UserMeetTemplateManager::getInstance();
		$tpl->cache_lifetime = "0";
		$tpl->display('file:' . dirname(__FILE__) . '/templates/signin.tpl.php');
	}
	
	function signin() {
		$email = $_REQUEST['email'];
		$password = $_REQUEST['password'];
		
//		echo "Sign in: " . __CLASS__ . "->" . __FUNCTION__ . "!<br>";
		$session = UserMeetSessionManager::getInstance();
		$visit = $session->login($email,$password);
		
		if(!is_null($visit)) {
			CerberusApplication::setActiveModule("core.module.dashboard");
		} else {
			CerberusApplication::setActiveModule("core.module.signin");
		}
	}
	
	function signout() {
//		echo "Sign out: " . __CLASS__ . "->" . __FUNCTION__ . "!<br>";
		$session = UserMeetSessionManager::getInstance();
		$session->logout();
		CerberusApplication::setActiveModule("core.module.signin");
	}
};

class ChTeamworkModule extends CerberusModuleExtension {
	function ChTeamworkModule($manifest) {
//		$this->UserMeetMenuExtension($manifest);
		$this->CerberusModuleExtension($manifest);
	}
	
	function isVisible() {
		// check login
		$session = UserMeetSessionManager::getInstance();
		$visit = $session->getVisit();
		
		if(empty($visit)) {
			return false;
		} else {
			return true;
		}

		return true;
	}
	
	function render() {
		$tpl = UserMeetTemplateManager::getInstance();
		$tpl->cache_lifetime = "0";
		
		$teams = CerberusApplication::getTeamList();
		$tpl->assign('teams', $teams);		
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/teamwork/index.tpl.php');
	}
	
	function getLink() {
		return "?c=".$this->id."&a=click";
	}
	
};

class ChSearchModule extends CerberusModuleExtension {
	function ChSearchModule($manifest) {
//		$this->UserMeetMenuExtension($manifest);
		$this->CerberusModuleExtension($manifest);
	}
	
	function isVisible() {
		// check login
		$session = UserMeetSessionManager::getInstance();
		$visit = $session->getVisit();
		
		if(empty($visit)) {
			return false;
		} else {
			return true;
		}
	}
	
	function render() {
		$tpl = UserMeetTemplateManager::getInstance();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		$tpl->cache_lifetime = "0";

//		global $_SESSION;
		$params = $_SESSION['params'];
//		print_r($params);
		
//		$params = array(
//			new CerberusSearchCriteria('t.status','in',array('O')),
//			new CerberusSearchCriteria('t.priority','!=',0),
//		);
		$tpl->assign('params', $params);
		
		$search = CerberusTicketDAO::searchTickets(
			$params,
			50,
			0,
			't.updated_date',
			false
		);
		$tpl->assign('search', $search[0]);
		$tpl->assign('total', $search[1]);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/search/index.tpl.php');
	}
	
	function getLink() {
		return "?c=".$this->id."&a=click";
	}
	
	function getCriteria() {
		@$field = $_REQUEST['field'];
		
		$tpl = UserMeetTemplateManager::getInstance();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		$tpl->cache_lifetime = "0";
		
		switch($field) {
			case "t.status":
				$tpl->display('file:' . dirname(__FILE__) . '/templates/search/criteria/ticket_status.tpl.php');
				break;
				
			case "t.priority":
				$tpl->display('file:' . dirname(__FILE__) . '/templates/search/criteria/ticket_priority.tpl.php');
				break;
				
			case "t.subject":
				$tpl->display('file:' . dirname(__FILE__) . '/templates/search/criteria/ticket_subject.tpl.php');
				break;
		}
	}
	
	function addCriteria() {
		@$params = $_SESSION['params'];
		@$field = $_REQUEST['field'];
		
		switch($field) {
			case "t.status":
				@$status = $_REQUEST['status'];
				$params[$field] = new CerberusSearchCriteria($field,'in',$status);
				break;
			case "t.priority":
				@$priority = $_REQUEST['priority'];
				$params[$field] = new CerberusSearchCriteria($field,'in',$priority);
				break;
			case "t.subject":
				@$subject = $_REQUEST['subject'];
				$params[$field] = new CerberusSearchCriteria($field,'like',$subject);
				break;
		}
		
		
		$_SESSION['params'] = $params;
		
		CerberusApplication::setActiveModule($this->id);
	}
}

class ChPreferencesModule extends CerberusModuleExtension {
	function ChPreferencesModule($manifest) {
		$this->CerberusModuleExtension($manifest);
	}
	
	function isVisible() {
		// check login
		$session = UserMeetSessionManager::getInstance();
		$visit = $session->getVisit();
		
		if(empty($visit)) {
			return false;
		} else {
			return true;
		}
	}
	
	function render() {
		$tpl = UserMeetTemplateManager::getInstance();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		$tpl->cache_lifetime = "0";

		$tpl->display('file:' . dirname(__FILE__) . '/templates/preferences/index.tpl.php');
	}
}

class ChDisplayTicketHistory extends CerberusDisplayModuleExtension {
	function ChDisplayTicketHistory($manifest) {
		$this->CerberusDisplayModuleExtension($manifest);
	}

	function render($ticket) {
		$tpl = UserMeetTemplateManager::getInstance();
		$tpl->cache_lifetime = "0";
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/modules/ticket_history.tpl.php');
	}
	
	function renderBody() {
		echo "History content goes here!";
	}
}

class ChDisplayTicketLog extends CerberusDisplayModuleExtension {
	function ChDisplayTicketLog($manifest) {
		$this->CerberusDisplayModuleExtension($manifest);
	}

	function render($ticket) {
		$tpl = UserMeetTemplateManager::getInstance();
		$tpl->cache_lifetime = "0";
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/modules/ticket_log.tpl.php');
	}
	
	function renderBody() {
		echo "Ticket log content goes here!";
	}
}

class ChDisplayTicketWorkflow extends CerberusDisplayModuleExtension {
	function ChDisplayTicketWorkflow($manifest) {
		$this->CerberusDisplayModuleExtension($manifest);
	}

	function render($ticket) {
		$tpl = UserMeetTemplateManager::getInstance();
		$tpl->cache_lifetime = "0";
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/modules/ticket_workflow.tpl.php');
	}
	
	function renderBody() {
		echo "Ticket workflow content goes here!";
	}
}

class ChDisplayTicketFields extends CerberusDisplayModuleExtension {
	function ChDisplayTicketFields($manifest) {
		$this->CerberusDisplayModuleExtension($manifest);
	}

	function render($ticket) {
		$tpl = UserMeetTemplateManager::getInstance();
		$tpl->cache_lifetime = "0";
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/modules/ticket_fields.tpl.php');
	}
	
	function renderBody() {
		echo "Ticket custom fields content goes here!";
	}
}

class ChDisplayTicketConversation extends CerberusDisplayModuleExtension {
	function ChDisplayTicketConversation($manifest) {
		$this->CerberusDisplayModuleExtension($manifest);
	}

	function render($ticket) {
		$tpl = UserMeetTemplateManager::getInstance();
		$tpl->cache_lifetime = "0";
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/modules/ticket_conversation.tpl.php');
	}
}



?>