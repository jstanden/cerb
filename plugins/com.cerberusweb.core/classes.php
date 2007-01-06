<?php
class ChDashboardModule extends CerberusModuleExtension {
	function __construct($manifest) {
		parent::__construct($manifest);
	}
	
	function isVisible() {
		// check login
		$session = DevblocksPlatform::getSessionService();
		$visit = $session->getVisit();
		
		if(empty($visit)) {
			return false;
		} else {
			return true;
		}

		return true;
	}
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');

		$session = DevblocksPlatform::getSessionService();
		$visit = $session->getVisit();
		
		$dashboards = CerberusDashboardDAO::getDashboards($visit->id);
		$tpl->assign('dashboards', $dashboards);
		
		// [JAS]: [TODO] This needs to limit by the selected dashboard
		$views = CerberusDashboardDAO::getViews(); // getViews($dashboard_id)
		$tpl->assign('views', $views);
		
		$teams = CerberusWorkflowDAO::getTeams();
		$team_mailbox_counts = array();
		foreach ($teams as $team) { /* @var $team CerberusTeam */
			$team->count = 0;
			$team_mailboxes = $team->getMailboxes(true);
			foreach ($team_mailboxes as $team_mailbox) { /* @var $team_mailbox CerberusMailbox */
				$team_mailbox_counts[$team_mailbox->id] = $team_mailbox->count;
				$team->count += $team_mailbox->count;
			}
		}
		$tpl->assign('teams', $teams);
		
		$team_total_count = 0;
		foreach ($team_mailbox_counts as $idx => $val) {
			$team_total_count += $val;
		}
		$tpl->assign('team_total_count', $team_total_count);
		
		$mailboxes = CerberusMailDAO::getMailboxes(array(), true);
		$tpl->assign('mailboxes', $mailboxes);

		$total_count = 0;
		foreach ($mailboxes as $mailbox) {
			$total_count += $mailbox->count;
		}
		$tpl->assign('total_count', $total_count);
		
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
		@$id = intval($_REQUEST['id']);
		
		$view = CerberusDashboardDAO::getView(0);
		$view->params = array(
			new CerberusSearchCriteria(CerberusSearchFields::MAILBOX_ID,'=', $id),
			new CerberusSearchCriteria(CerberusSearchFields::TICKET_STATUS,'in', array(CerberusTicketStatus::OPEN))
		);
		$_SESSION['search_view'] = $view;
		
		CerberusApplication::setActiveModule("core.module.search");
	}
	
	function getLink() {
		return DEVBLOCKS_WEBPATH . "index.php?c=".$this->id."&a=click";
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
		
		$fields = array(
			'sort_by' => $sortBy,
			'sort_asc' => $iSortAsc
		);
		CerberusDashboardDAO::updateView($id, $fields);
		
		echo ' ';
	}
	
	function viewPage() {
		@$id = $_REQUEST['id'];
		@$page = $_REQUEST['page'];
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		$tpl->assign('id',$id);
		
		$fields = array(
			'page' => $page
		);
		CerberusDashboardDAO::updateView($id,$fields);		
		
		echo ' ';
	}
	
	function viewRefresh() {
		@$id = $_REQUEST['id'];

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		$tpl->assign('id',$id);
		
		$view = CerberusDashboardDAO::getView($id);
		$tpl->assign('view', $view);
		
		if(!empty($view)) {
			$tpl->cache_lifetime = "0";
			$tpl->display('file:' . dirname(__FILE__) . '/templates/dashboards/ticket_view.tpl.php');
		} else {
			echo " ";
		}
	}
	
	function customize() {
		@$id = $_REQUEST['id'];

		$tpl = DevblocksPlatform::getTemplateService();
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
		@$delete = $_REQUEST['delete'];
		
		if(!empty($delete)) {
			CerberusDashboardDAO::deleteView($id);
			
		} else {
			// [JAS]: Clear any empty columns
			if(is_array($columns))
			foreach($columns as $k => $v) {
				if(empty($v))
					unset($columns[$k]);
			}

			$fields = array(
				'name' => $name,
				'view_columns' => serialize($columns),
				'num_rows' => $num_rows,
				'page' => 0 // reset paging
			);
			CerberusDashboardDAO::updateView($id,$fields);
		}

		echo ' ';
	}
	
	function searchview() {
		@$id = $_REQUEST['id'];
		$view = CerberusDashboardDAO::getView($id);

		@$search_id = $_SESSION['search_id'];
		$search_view = CerberusDashboardDAO::getView($search_id);
		$fields = array(
			'params' => serialize($view->params)
		);
		CerberusDashboardDAO::updateView($search_id, $fields);
		
		CerberusApplication::setActiveModule("core.module.search");
	}
	
	function addView() {
		// [JAS]: [TODO] Use a real dashboard ID here.
		$view_id = CerberusDashboardDAO::createView('New View', 1, 10);
		
		$fields = array(
			'view_columns' => serialize(array(
				CerberusSearchFields::TICKET_MASK,
				CerberusSearchFields::TICKET_STATUS,
				CerberusSearchFields::TICKET_PRIORITY,
				CerberusSearchFields::TICKET_LAST_WROTE,
				CerberusSearchFields::TICKET_CREATED_DATE,
			))
		);
		CerberusDashboardDAO::updateView($view_id,$fields);
		
		CerberusApplication::setActiveModule($this->id);
	}
	
	function showHistoryPanel() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		$tpl->cache_lifetime = "0";
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/history_panel.tpl.php');
	}
	
	function showContactPanel() {
		@$sAddress = $_REQUEST['address'];
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		$tpl->cache_lifetime = "0";
		
		$address_id = CerberusContactDAO::lookupAddress($sAddress, false);
		$address = CerberusContactDAO::getAddress($address_id);
		
		$tpl->assign('address', $address);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/contact_panel.tpl.php');
	}
	
};

class ChConfigurationModule extends CerberusModuleExtension  {
	function __construct($manifest) {
		parent::__construct($manifest);
	}
	
	function isVisible() {
		$session = DevblocksPlatform::getSessionService();
		$visit = $session->getVisit();
		
		if(empty($visit)) {
			return false;
		} else {
			return true;
		}
	}
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');

		$routing = CerberusMailDAO::getMailboxRouting();
		$tpl->assign('routing', $routing);

		$address_ids = array_keys($routing);
		$routing_addresses = CerberusContactDAO::getAddresses($address_ids);
		$tpl->assign('routing_addresses', $routing_addresses);
		
		$pop3_accounts = CerberusMailDAO::getPop3Accounts();
		$tpl->assign('pop3_accounts', $pop3_accounts);
		
		$mailboxes = CerberusMailDAO::getMailboxes();
		$tpl->assign('mailboxes', $mailboxes);
		
		$workers = CerberusAgentDAO::getAgents();
		$tpl->assign('workers', $workers);
		
		$teams = CerberusWorkflowDAO::getTeams();
		$tpl->assign('teams', $teams);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/configuration/index.tpl.php');
	}
	
	function getWorker() {
		@$id = $_REQUEST['id'];

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');

		$worker = CerberusAgentDAO::getAgent($id);
		$tpl->assign('worker', $worker);
		
		$teams = CerberusWorkflowDAO::getTeams();
		$tpl->assign('teams', $teams);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/configuration/workflow/edit_worker.tpl.php');
	}
	
	function saveWorker() {
		@$id = $_POST['id'];
		@$login = $_POST['login'];
		@$password = $_POST['password'];
		@$team_id = $_POST['team_id'];
		@$delete = $_POST['delete'];
		
		if(empty($name)) $name = "No Name";
		
		if(!empty($id) && !empty($delete)) {
			CerberusAgentDAO::deleteAgent($id);
			
		} elseif(!empty($id)) {
			$fields = array(
				'login' => $login
			);
			
			// if we're resetting the password
			if(!empty($password)) {
				$fields['password'] = md5($password);
			}
			
			CerberusAgentDAO::updateAgent($id, $fields);
			CerberusAgentDAO::setAgentTeams($id, $team_id);
			
		} else {
			// Don't dupe.
			if(null == CerberusAgentDAO::lookupAgentLogin($login)) {
				$id = CerberusAgentDAO::createAgent($login, $password);
				CerberusAgentDAO::setAgentTeams($id, $team_id);
			}
		}
		
		CerberusApplication::setActiveModule($this->id);
	}
	
	function getTeam() {
		@$id = $_REQUEST['id'];

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');

		$team = CerberusWorkflowDAO::getTeam($id);
		$tpl->assign('team', $team);
		
		$workers = CerberusAgentDAO::getAgents();
		$tpl->assign('workers', $workers);
		
		$mailboxes = CerberusMailDAO::getMailboxes();
		$tpl->assign('mailboxes', $mailboxes);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/configuration/workflow/edit_team.tpl.php');
	}
	
	function saveTeam() {
		@$id = $_POST['id'];
		@$name = $_POST['name'];
		@$mailbox_id = $_POST['mailbox_id'];
		@$agent_id = $_POST['agent_id'];
		@$delete = $_POST['delete'];
		
		if(empty($name)) $name = "No Name";
		
		if(!empty($id) && !empty($delete)) {
			CerberusWorkflowDAO::deleteTeam($id);
			
		} elseif(!empty($id)) {
			$fields = array(
				'name' => $name
			);
			CerberusWorkflowDAO::updateTeam($id, $fields);
			CerberusWorkflowDAO::setTeamMailboxes($id, $mailbox_id);
			CerberusWorkflowDAO::setTeamWorkers($id, $agent_id);
			
		} else {
			$id = CerberusWorkflowDAO::createTeam($name);
			CerberusWorkflowDAO::setTeamMailboxes($id, $mailbox_id);
			CerberusWorkflowDAO::setTeamWorkers($id, $agent_id);
		}
		
		CerberusApplication::setActiveModule($this->id);
	}
	
	function getMailbox() {
		@$id = $_REQUEST['id'];

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');

		$mailbox = CerberusMailDAO::getMailbox($id);
		$tpl->assign('mailbox', $mailbox);
		
		$teams = CerberusWorkflowDAO::getTeams();
		$tpl->assign('teams', $teams);
		
		$reply_address = CerberusContactDAO::getAddress($mailbox->reply_address_id);
		$tpl->assign('reply_address', $reply_address);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/configuration/workflow/edit_mailbox.tpl.php');
	}
	
	function saveMailbox() {
		@$id = $_POST['id'];
		@$name = $_POST['name'];
		@$reply_as = $_POST['reply_as'];
		@$team_id = $_POST['team_id'];
		@$delete = $_POST['delete'];
		
		if(empty($name)) $name = "No Name";
		
		if(!empty($id) && !empty($delete)) {
			CerberusMailDAO::deleteMailbox($id);
			
		} elseif(!empty($id)) {
			$reply_id = CerberusContactDAO::lookupAddress($reply_as, true);

			$fields = array(
				'name' => $name,
				'reply_address_id' => $reply_id
			);
			CerberusMailDAO::updateMailbox($id, $fields);
			CerberusMailDAO::setMailboxTeams($id, $team_id);
			
		} else {
			$reply_id = CerberusContactDAO::lookupAddress($reply_as, true);
			$id = CerberusMailDAO::createMailbox($name,$reply_id);
			CerberusMailDAO::setMailboxTeams($id, $team_id);
		}
		
		CerberusApplication::setActiveModule($this->id);
	}
	
	function getPop3Account() {
		@$id = $_REQUEST['id'];
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');

		$pop3_account = CerberusMailDAO::getPop3Account($id);
		$tpl->assign('pop3_account', $pop3_account);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/configuration/workflow/edit_pop3_account.tpl.php');
	}
	
	function savePop3Account() {
		@$id = $_POST['id'];
		@$nickname = $_POST['nickname'];
		@$host = $_POST['host'];
		@$username = $_POST['username'];
		@$password = $_POST['password'];
		@$delete = $_POST['delete'];
		
		if(empty($nickname)) $nickname = "No Nickname";
		
		if(!empty($id) && !empty($delete)) {
			CerberusMailDAO::deletePop3Account($id);
		} elseif(!empty($id)) {
			$fields = array(
				'nickname' => $nickname,
				'host' => $host,
				'username' => $username,
				'password' => $password
			);
			CerberusMailDAO::updatePop3Account($id, $fields);
		} else {
			$id = CerberusMailDAO::createPop3Account($nickname,$host,$username,$password);
		}
		
		CerberusApplication::setActiveModule($this->id);
	}
	
	function ajaxGetRouting() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');

		$routing = CerberusMailDAO::getMailboxRouting();
		$tpl->assign('routing', $routing);

		$address_ids = array_keys($routing);
		$routing_addresses = CerberusContactDAO::getAddresses($address_ids);
		$tpl->assign('routing_addresses', $routing_addresses);
		
		$mailboxes = CerberusMailDAO::getMailboxes();
		$tpl->assign('mailboxes', $mailboxes);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/configuration/mail_routing.tpl.php');
	}
	
	function ajaxDeleteRouting() {
		@$id = $_REQUEST['id'];
		CerberusMailDAO::deleteMailboxRouting($id);
	}
	
	function getMailboxRoutingDialog() {
		@$id = $_REQUEST['id'];
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');

		$tpl->assign('id', $id);

		$mailboxes = CerberusMailDAO::getMailboxes();
		$tpl->assign('mailboxes', $mailboxes);
		
		$address = CerberusContactDAO::getAddress($id);
		$tpl->assign('address', $address);

		$selected_id = CerberusContactDAO::getMailboxIdByAddress($address->email);
		$tpl->assign('selected_id', $selected_id);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/configuration/mail/edit_mailbox_routing.tpl.php');
	}
	
	function saveMailboxRoutingDialog() {
		@$address_id = intval($_POST['id']);
		@$mailbox_id = intval($_POST['mailbox_id']);
		@$address = $_POST['address'];
		
		if(empty($address_id) && !empty($address)) { // create
			$address_id = CerberusContactDAO::lookupAddress($address, true);
			if(empty($address_id)) return;
		}
		
		CerberusMailDAO::setMailboxRouting($address_id, $mailbox_id);

		// [JAS]: Send the new mailbox name to the server 
		// [TODO] Necessary?
		$mailbox = CerberusMailDAO::getMailbox($mailbox_id);
		echo $mailbox->name;
	}
	
}

class ChDisplayModule extends CerberusModuleExtension {
	function __construct($manifest) {
		parent::__construct($manifest);
	}
	
	function isVisible() {
		// check login
		$session = DevblocksPlatform::getSessionService();
		$visit = $session->getVisit();
		
		if(empty($visit)) {
			return false;
		} else {
			return true;
		}
	}
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');

		@$id = $_REQUEST['id'];
		
		$ticket = CerberusTicketDAO::getTicket($id);
		$tpl->assign('ticket', $ticket);

		$mailboxes = CerberusMailDAO::getMailboxes();
		$tpl->assign('mailboxes', $mailboxes);
		
		$display_module_manifests = DevblocksPlatform::getExtensions("com.cerberusweb.display.module");
		$display_modules = array();
		
		if(is_array($display_module_manifests))
		foreach($display_module_manifests as $dmm) { /* @var $dmm DevblocksExtensionManifest */
			$display_modules[] = $dmm->createInstance(1);
		}
		$tpl->assign('display_modules', $display_modules);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/index.tpl.php');
	}

	function updateProperties() {
		@$id = $_REQUEST['id']; // ticket id
		@$status = $_REQUEST['status'];
		@$priority = $_REQUEST['priority'];
		@$mailbox_id = $_REQUEST['mailbox_id'];
		@$subject = $_REQUEST['subject'];
		
		$properties = array(
			'status' => $status,
			'priority' => intval($priority),
			'mailbox_id' => $mailbox_id,
			'subject' => $subject,
			'updated_date' => gmmktime()
		);
		CerberusTicketDAO::updateTicket($id, $properties);
		
		$_REQUEST['id'] = $id;
		CerberusApplication::setActiveModule($this->id);
	}
	
	function reply()	{ ChDisplayModule::loadMessageTemplate(CerberusMessageType::EMAIL); }
	function forward()	{ ChDisplayModule::loadMessageTemplate(CerberusMessageType::FORWARD); }
	function comment()	{ ChDisplayModule::loadMessageTemplate(CerberusMessageType::COMMENT); }
	
	function loadMessageTemplate($type) {
		@$id = $_REQUEST['id'];

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		$tpl->assign('id',$id);
		
		$message = CerberusTicketDAO::getMessage($id);
		$tpl->assign('message',$message);
		
		$ticket = CerberusTicketDAO::getTicket($message->ticket_id);
		$tpl->assign('ticket',$ticket);
		
		$tpl->cache_lifetime = "0";
		
		switch ($type) {
			case CerberusMessageType::FORWARD :
				$tpl->display('file:' . dirname(__FILE__) . '/templates/display/rpc/forward.tpl.php');
				break;
			case CerberusMessageType::EMAIL :
				$tpl->display('file:' . dirname(__FILE__) . '/templates/display/rpc/reply.tpl.php');
				break;
			case CerberusMessageType::COMMENT :
				$tpl->display('file:' . dirname(__FILE__) . '/templates/display/rpc/comment.tpl.php');
				break;
		}
	}
	
	function sendReply()	{ ChDisplayModule::sendMessage(CerberusMessageType::EMAIL); }
	function sendForward()	{ ChDisplayModule::sendMessage(CerberusMessageType::FORWARD); }
	function sendComment()	{ ChDisplayModule::sendMessage(CerberusMessageType::COMMENT); }
	
	// TODO: may need to also have an agent_id passed to it in the request, to identify the agent making the reply
	/*
	 * [JAS]: [TODO] This really belongs in the API, it will be reused plenty and there's way too much 
	 * reusable code here stuck in the plugin action.
	 */
	function sendMessage($type) {
		// variable loading
		@$id		= $_REQUEST['id']; // message id
		@$content	= $_REQUEST['content'];
		@$priority	= $_REQUEST['priority'];	// DDH: TODO: if priority and/or status change, we need to update the
		@$status	= $_REQUEST['status'];		// ticket object.  not sure if we want to do that here or not.
		@$agent_id	= $_REQUEST['agent_id'];
		
		// object loading
		$message	= CerberusTicketDAO::getMessage($id);
		$ticket_id	= $message->ticket_id;
		$ticket		= CerberusTicketDAO::getTicket($ticket_id);
		$mailbox	= CerberusMailDAO::getMailbox($ticket->mailbox_id);
		$requesters	= CerberusTicketDAO::getRequestersByTicket($ticket_id);
		$mailMgr	= DevblocksPlatform::getMailService();
		$headers	= CerberusMailDAO::getHeaders($type, $ticket_id);
		
		$files = $_FILES['attachment'];
		// send email (if necessary)
		if ($type != CerberusMessageType::COMMENT) {
			// build MIME message if message has attachments
			if (is_array($files) && !empty($files)) {
				
				/*
				 * [JAS]: [TODO] If we're going to call platform libs directly we should just have
				 * the platform provide the functionality.
				 */
				require_once(DEVBLOCKS_PATH . '/libs/devblocks/pear/mime.php');
				$mime_mail = new Mail_mime();
				$mime_mail->setTXTBody($content);
				foreach ($files['tmp_name'] as $idx => $file) {
					$mime_mail->addAttachment($files['tmp_name'][$idx], $files['type'][$idx], $files['name'][$idx]);
				}
				
				$email_body = $mime_mail->get();
				$email_headers = $mime_mail->headers($headers);
			} else {
				$email_body = $content;
				$email_headers = $headers;
			}
			
			$mail_result =& $mailMgr->send('mail.webgroupmedia.com', $headers['x-rcpt'], $email_headers, $email_body); // DDH: TODO: this needs to pull the servername from a config, not hardcoded.
			if ($mail_result !== true) die("Error message was: " . $mail_result->getMessage());
		}
		
		// TODO: create DAO object for Agent, be able to pull address by having agent id.
//		$headers['From'] = $agent_address->personal . ' <' . $agent_address->email . '>';
//		$message_id = CerberusTicketDAO::createMessage($ticket_id,$type,gmmktime(),$agent_id,$headers,$content);
		$message_id = CerberusTicketDAO::createMessage($ticket_id,$type,gmmktime(),1,$headers,$content);
		
		// if this message was submitted with attachments, store them in the filestore and link them in the db.
		if (is_array($files) && !empty($files)) {
			foreach ($files['tmp_name'] as $idx => $file) {
				$timestamp = gmdate('Y.m.d.H.i.s.', gmmktime());
				list($usec, $sec) = explode(' ', microtime());
				$timestamp .= substr($usec,2,3) . '.';
				copy($files['tmp_name'][$idx],DEVBLOCKS_ATTACHMENT_SAVE_PATH . $timestamp . $files['name'][$idx]);
				CerberusTicketDAO::createAttachment($message_id, $files['name'][$idx], $timestamp . $files['name'][$idx]);
			}
		}
		
		$_REQUEST['id'] = $ticket_id;
		CerberusApplication::setActiveModule($this->id);
	}
	
	function refreshRequesters() {
		$tpl = DevblocksPlatform::getTemplateService();
		@$id = $_REQUEST['id']; // ticket id
		
		$ticket = CerberusTicketDAO::getTicket($id);

		$tpl->assign('ticket',$ticket);
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/requesters.tpl.php');
	}

	function saveRequester() {
		@$id = $_REQUEST['id']; // ticket id
		@$add_requester = $_POST['add_requester'];
		
		// I'd really like to know why the *$#! this doesn't work.  The if statement works fine atomically...
//		require_once(DEVBLOCKS_PATH . '/libs/pear/Mail/RFC822.php');
//		if (false === Mail_RFC822::isValidInetAddress($add_requester)) {
//			return $add_requester . DevblocksTranslationManager::say('ticket.requester.invalid');
//		}
		
		$address_id = CerberusContactDAO::lookupAddress($add_requester, true);
		CerberusTicketDAO::createRequester($address_id, $id);
		
		echo ' ';
	}
	
};

class ChSignInModule extends CerberusModuleExtension {
	function __construct($manifest) {
		parent::__construct($manifest);
	}
	
	function isVisible() {
		// check login
		$session = DevblocksPlatform::getSessionService();
		$visit = $session->getVisit();
		
//		if(empty($visit)) {
//			return true;
//		} else {
//			return false;
//		}

		return true;
	}
	
//	function getLink() {
//		return DEVBLOCKS_WEBPATH . "index.php?c=".$this->id."&a=show";
//	}

	function show() {
//		echo "You clicked: " . __CLASS__ . "->" . __FUNCTION__ . "!<br>";
		CerberusApplication::setActiveModule("core.module.signin");
	}
	
	function render() {
		$manifest = DevblocksPlatform::getExtension('login.default');
		$inst = $manifest->createInstance(1); /* @var $inst CerberusLoginModuleExtension */
		$inst->renderLoginForm();
	}
	
	function signout() {
//		echo "Sign out: " . __CLASS__ . "->" . __FUNCTION__ . "!<br>";
		$session = DevblocksPlatform::getSessionService();
		$session->logout();
		CerberusApplication::setActiveModule("core.module.signin");
	}
};

class ChTeamworkModule extends CerberusModuleExtension {
	function __construct($manifest) {
		parent::__construct($manifest);
	}
	
	function isVisible() {
		// check login
		$session = DevblocksPlatform::getSessionService();
		$visit = $session->getVisit();
		
		if(empty($visit)) {
			return false;
		} else {
			return true;
		}

		return true;
	}
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		$tpl->cache_lifetime = "0";
		
		$session = DevblocksPlatform::getSessionService();
		$visit = $session->getVisit();
		
		$teams = CerberusWorkflowDAO::getTeams();
		$tpl->assign('teams', $teams);		
		
		$mytickets = CerberusSearchDAO::searchTickets(
			array(
				new CerberusSearchCriteria(CerberusSearchFields::ASSIGNED_WORKER,'in',array($visit->id))
			),
			25
		);
		$tpl->assign('count',$mytickets[1]);
		$tpl->assign('mytickets',$mytickets[0]);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/teamwork/index.tpl.php');
	}
	
	function getLink() {
		return DEVBLOCKS_WEBPATH . "index.php?c=".$this->id."&a=click";
	}
	
};

class ChSearchModule extends CerberusModuleExtension {
	function __construct($manifest) {
		parent::__construct($manifest);
	}
	
	function isVisible() {
		// check login
		$session = DevblocksPlatform::getSessionService();
		$visit = $session->getVisit();
		
		if(empty($visit)) {
			return false;
		} else {
			return true;
		}
	}
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		$tpl->cache_lifetime = "0";

		$search_id = $_SESSION['search_id'];
		$view = CerberusDashboardDAO::getView($search_id);
		
		// [JAS]: Recover from a bad cached ID.
		if(null == $view) {
			$search_id = 0;
			$_SESSION['search_id'] = $search_id;
			unset($_SESSION['search_view']);
			$view = CerberusDashboardDAO::getView($search_id);
		}
		
		$tpl->assign('view', $view);
		$tpl->assign('params', $view->params);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/search/index.tpl.php');
	}
	
	function getLink() {
		return DEVBLOCKS_WEBPATH . "index.php?c=".$this->id."&a=click";
	}
	
	function getCriteria() {
		@$field = $_REQUEST['field'];
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		$tpl->cache_lifetime = "0";
		
		switch($field) {
			case CerberusSearchFields::TICKET_MASK:
				$tpl->display('file:' . dirname(__FILE__) . '/templates/search/criteria/ticket_mask.tpl.php');
				break;
				
			case CerberusSearchFields::TICKET_STATUS:
				$tpl->display('file:' . dirname(__FILE__) . '/templates/search/criteria/ticket_status.tpl.php');
				break;
				
			case CerberusSearchFields::TICKET_PRIORITY:
				$tpl->display('file:' . dirname(__FILE__) . '/templates/search/criteria/ticket_priority.tpl.php');
				break;
				
			case CerberusSearchFields::TICKET_SUBJECT:
				$tpl->display('file:' . dirname(__FILE__) . '/templates/search/criteria/ticket_subject.tpl.php');
				break;
				
			case CerberusSearchFields::REQUESTER_ADDRESS:
				$tpl->display('file:' . dirname(__FILE__) . '/templates/search/criteria/requester_email.tpl.php');
				break;
				
			case CerberusSearchFields::MESSAGE_CONTENT:
				$tpl->display('file:' . dirname(__FILE__) . '/templates/search/criteria/message_content.tpl.php');
				break;
				
			case CerberusSearchFields::ASSIGNED_WORKER:
				$workers = CerberusAgentDAO::getAgents();
				$tpl->assign('workers', $workers);
				
				$tpl->display('file:' . dirname(__FILE__) . '/templates/search/criteria/assigned_worker.tpl.php');
				break;
				
			case CerberusSearchFields::SUGGESTED_WORKER:
				$workers = CerberusAgentDAO::getAgents();
				$tpl->assign('workers', $workers);
				
				$tpl->display('file:' . dirname(__FILE__) . '/templates/search/criteria/suggested_worker.tpl.php');
				break;
		}
	}
	
	function getCriteriaDialog() {
		@$divName = $_REQUEST['divName'];
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		$tpl->cache_lifetime = "0";
		
		$tpl->assign('divName',$divName);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/search/rpc/add_criteria.tpl.php');
	}
	
	function addCriteria() {
		@$search_id = $_SESSION['search_id'];
		$view = CerberusDashboardDAO::getView($search_id);

		$params = $view->params;
		@$field = $_REQUEST['field'];

		switch($field) {
			case CerberusSearchFields::TICKET_MASK:
				@$mask = $_REQUEST['mask'];
				$params[$field] = new CerberusSearchCriteria($field,'like',$mask);
				break;
			case CerberusSearchFields::TICKET_STATUS:
				@$status = $_REQUEST['status'];
				$params[$field] = new CerberusSearchCriteria($field,'in',$status);
				break;
			case CerberusSearchFields::TICKET_PRIORITY:
				@$priority = $_REQUEST['priority'];
				$params[$field] = new CerberusSearchCriteria($field,'in',$priority);
				break;
			case CerberusSearchFields::TICKET_SUBJECT:
				@$subject = $_REQUEST['subject'];
				$params[$field] = new CerberusSearchCriteria($field,'like',$subject);
				break;
			case CerberusSearchFields::REQUESTER_ADDRESS:
				@$requester = $_REQUEST['requester'];
				@$oper = $_REQUEST['oper'];
				$params[$field] = new CerberusSearchCriteria($field,$oper,$requester);
				break;
			case CerberusSearchFields::MESSAGE_CONTENT:
				@$requester = $_REQUEST['content'];
				$params[$field] = new CerberusSearchCriteria($field,'like',$requester);
				break;
			case CerberusSearchFields::ASSIGNED_WORKER:
				@$worker_ids = $_REQUEST['worker_id'];
				$params[$field] = new CerberusSearchCriteria($field,'in',$worker_ids);
				break;
			case CerberusSearchFields::SUGGESTED_WORKER:
				@$worker_ids = $_REQUEST['worker_id'];
				$params[$field] = new CerberusSearchCriteria($field,'in',$worker_ids);
				break;
		}
		
		$fields = array(
			'params' => serialize($params)
		);
		CerberusDashboardDAO::updateView($search_id, $fields);
		
		CerberusApplication::setActiveModule($this->id);
	}
	
	function removeCriteria() {
		@$search_id = $_SESSION['search_id'];
		$view = CerberusDashboardDAO::getView($search_id);

		@$params = $view->params;
		@$field = $_REQUEST['field'];
		
		if(isset($params[$field]))
			unset($params[$field]);
			
		$fields = array(
			'params' => serialize($params)
		);
		CerberusDashboardDAO::updateView($search_id, $fields);
		
		CerberusApplication::setActiveModule($this->id);
	}
	
	function resetCriteria() {
		$_SESSION['search_id'] = 0;
		unset($_SESSION['search_view']);
		CerberusApplication::setActiveModule($this->id);
	}
	
	function getLoadSearch() {
		@$divName = $_REQUEST['divName'];
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		$tpl->cache_lifetime = "0";
		
		$tpl->assign('divName',$divName);
		
		$searches = CerberusSearchDAO::getSavedSearches(1); /* @var $searches CerberusDashboardView[] */
		$tpl->assign('searches', $searches);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/search/rpc/load_search.tpl.php');
	}
	
	function loadSearch() {
		@$search_id = $_REQUEST['search_id'];
		
		$view = CerberusDashboardDAO::getView($search_id);

		$_SESSION['search_view'] = $view;
		$_SESSION['search_id'] = $view->id;
		
		CerberusApplication::setActiveModule($this->id);
	}
	
	function getSaveSearch() {
		@$divName = $_REQUEST['divName'];
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		$tpl->cache_lifetime = "0";

		$tpl->assign('divName',$divName);
		
		$views = CerberusDashboardDAO::getViews(0);
		$tpl->assign('views', $views);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/search/rpc/save_search.tpl.php');
	}
	
	function saveSearch() {
		@$search_id = $_SESSION['search_id'];
		$view = CerberusDashboardDAO::getView($search_id);

		@$params = $view->params;
		@$columns = $view->view_columns;
		@$save_as = $_REQUEST['save_as'];

		if($save_as=='view') {
			@$view_id = $_REQUEST['view_id'];
			
			$fields = array(
				'params' => serialize($params)
			);
			CerberusDashboardDAO::updateView($view_id,$fields);
			echo "Saved as view!";
			
		} else { // named search
			@$name = $_REQUEST['name'];
			
			$view_id = CerberusDashboardDAO::createView($name, 0, 50, 't_created_date', 0, 'S');
			$fields = array(
				'view_columns' => serialize($columns),
				'params' => serialize($params),
				'sort_by' => $view->renderSortBy,
				'sort_asc' => $view->renderSortAsc,
				'num_rows' => $view->renderLimit
			);
			CerberusDashboardDAO::updateView($view_id, $fields);
			$_SESSION['search_view'] = CerberusDashboardDAO::getView($view_id);
			$_SESSION['search_id'] = $view_id;
			
			echo "Saved search!";
		}
	}
	
	function deleteSearch() {
		@$search_id = $_SESSION['search_id'];
		
		if($_SESSION['search_id']==$search_id) {
			$_SESSION['search_id'] = 0;
			unset($_SESSION['search_view']);
		}
		
		CerberusDashboardDAO::deleteView($search_id);
		
		CerberusApplication::setActiveModule($this->id);
	}
}

class ChPreferencesModule extends CerberusModuleExtension {
	function __construct($manifest) {
		parent::__construct($manifest);
	}
	
	function isVisible() {
		// check login
		$session = DevblocksPlatform::getSessionService();
		$visit = $session->getVisit();
		
		if(empty($visit)) {
			return false;
		} else {
			return true;
		}
	}
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		$tpl->cache_lifetime = "0";

		$tpl->display('file:' . dirname(__FILE__) . '/templates/preferences/index.tpl.php');
	}
}

class ChDisplayTicketHistory extends CerberusDisplayModuleExtension {
	function __construct($manifest) {
		parent::__construct($manifest);
	}
	
	function render($ticket) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/modules/ticket_history.tpl.php');
	}
	
	function renderBody($ticket) {
		/* @var $ticket CerberusTicket */
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		
		$requesters = $ticket->getRequesters();
		
		$history_tickets = CerberusSearchDAO::searchTickets(
			array(
				new CerberusSearchCriteria(CerberusSearchFields::REQUESTER_ID,'in',array_keys($requesters))
			),
			10,
			0,
			CerberusSearchFields::TICKET_CREATED_DATE,
			0
		);
		$tpl->assign('history_tickets', $history_tickets[0]);
		$tpl->assign('history_count', $history_tickets[1]);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/modules/history/index.tpl.php');
	}
}

class ChDisplayTicketLog extends CerberusDisplayModuleExtension {
	function __construct($manifest) {
		parent::__construct($manifest);
	}
	
	function render($ticket) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/modules/ticket_log.tpl.php');
	}
	
	function renderBody($ticket) {
		echo "Ticket log content goes here!";
	}
}

class ChDisplayTicketWorkflow extends CerberusDisplayModuleExtension {
	function __construct($manifest) {
		parent::__construct($manifest);
	}
	
	function render($ticket) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('callback','renderBody');
		$tpl->assign('moduleLabel', $this->manifest->id);
		$tpl->display('display/expandable_module_template.tpl.php');
	}
	
	function renderBody($ticket) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$session = DevblocksPlatform::getSessionService();
		$visit = $session->getVisit();
		
		$favoriteTags = CerberusAgentDAO::getFavoriteTags($visit->id);
		$tpl->assign('favoriteTags', $favoriteTags);
		
//		$suggestedTags = CerberusWorkflowDAO::getSuggestedTags($ticket->id,10);
//		$tpl->assign('suggestedTags', $suggestedTags);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/modules/ticket_workflow.tpl.php');
	}
	
	function refresh() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->caching = 0;
		$tpl->cache_lifetime = 0;

		$tpl->assign('moduleLabel', $this->manifest->id);

		@$id = $_REQUEST['id'];
		
		$ticket = CerberusTicketDAO::getTicket($id);
		$tpl->assign('ticket', $ticket);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/modules/ticket_workflow.tpl.php');
	}
	
	function showApplyTagDialog() {
		@$ticket_id = intval($_REQUEST['ticket_id']);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->caching = 0;
		$tpl->cache_lifetime = 0;
		
		$session = DevblocksPlatform::getSessionService();
		$visit = $session->getVisit();
		
		$ticket = CerberusTicketDAO::getTicket($ticket_id);
		$tpl->assign('ticket', $ticket);
		
		$favoriteTags = CerberusAgentDAO::getFavoriteTags($visit->id);
		$tpl->assign('favoriteTags', $favoriteTags);
		
		$suggestedTags = CerberusWorkflowDAO::getSuggestedTags($ticket->id,10);
		$tpl->assign('suggestedTags', $suggestedTags);
				
		$tpl->assign('ticket_id', $ticket_id);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/modules/workflow/apply_tag.tpl.php');
	}
	
	function getTagDialog() {
		@$tag_id = intval($_REQUEST['id']);
		@$ticket_id = intval($_REQUEST['ticket_id']);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->caching = 0;
		$tpl->cache_lifetime = 0;
		
		$tag = CerberusWorkflowDAO::getTag($tag_id);
		$tpl->assign('tag', $tag);
		
		$tpl->assign('ticket_id', $ticket_id);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/modules/workflow/tag_dialog.tpl.php');
	}
	
	function saveTagDialog() {
		@$id = intval($_REQUEST['id']);
		@$ticket_id = intval($_REQUEST['ticket_id']);
		@$str_terms = $_REQUEST['terms'];
		@$untag = intval($_REQUEST['untag']);
		
		if(!empty($untag) && !empty($ticket_id)) {
			CerberusTicketDAO::untagTicket($ticket_id, $id);
		} else {
			// Save Changes
			
			// Terms
			$terms = preg_split("/[\r\n]/", $str_terms);
			
			if(!empty($terms)) {
				CerberusWorkflowDAO::setTagTerms($id, $terms);
			}
		}
		
		echo ' ';
	}
	
	function autoTag() {
		@$q = $_REQUEST['q'];
		header("Content-Type: text/plain");
		
		$tags = CerberusWorkflowDAO::searchTags($q, 10);
		
		if(is_array($tags))
		foreach($tags as $tag) {
			echo $tag->name,"\t",$tag->id,"\n";
		}
	}
	
	function autoWorker() {
		@$q = $_REQUEST['q'];
		header("Content-Type: text/plain");
		
		$workers = CerberusAgentDAO::searchAgents($q, 10);
		
		if(is_array($workers))
		foreach($workers as $worker) {
			echo $worker->login,"\t",$worker->id,"\n";
		}
	}
	
	function autoAddress() {
		@$q = $_REQUEST['q'];
		header("Content-Type: text/plain");
		
		$addresses = CerberusMailDAO::searchAddresses($q, 10);
		
		if(is_array($addresses))
		foreach($addresses as $address) {
			echo $address->email,"\t",$address->id,"\n";
		}
	}
	
	
	function getAgentDialog() {
		@$id = intval($_REQUEST['id']);
		@$ticket_id = intval($_REQUEST['ticket_id']);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->caching = 0;
		$tpl->cache_lifetime = 0;
		
		$agent = CerberusAgentDAO::getAgent($id);
		$tpl->assign('agent', $agent);
		
		$tpl->assign('ticket_id', $ticket_id);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/modules/workflow/agent_dialog.tpl.php');
	}
	
	function saveAgentDialog() {
		@$id = intval($_REQUEST['id']);
		@$ticket_id = intval($_REQUEST['ticket_id']);
		@$unassign = intval($_REQUEST['unassign']);
		
		if(!empty($unassign) && !empty($ticket_id)) {
			CerberusTicketDAO::unflagTicket($ticket_id, $id);
			CerberusTicketDAO::unsuggestTicket($ticket_id, $id);
		} else {
			// save changes
		}
		
		echo ' ';
	}
	
//	function showApplyTags() {
//		@$id = intval($_REQUEST['id']);
//		
//		$session = DevblocksPlatform::getSessionService();
//		$visit = $session->getVisit();
//		
//		$tpl = DevblocksPlatform::getTemplateService();
//		$tpl->caching = 0;
//		$tpl->cache_lifetime = 0;
//		
//		$tpl->assign('moduleLabel', $this->manifest->id);
//		
//		$ticket = CerberusTicketDAO::getTicket($id);
//		$tpl->assign('ticket', $ticket);
//		
//		$favoriteTags = CerberusAgentDAO::getFavoriteTags($visit->id);
//		$tpl->assign('favoriteTags', $favoriteTags);
//		
//		$suggestedTags = CerberusWorkflowDAO::getTags();
//		$tpl->assign('suggestedTags', $suggestedTags);
//		
//		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/modules/workflow/add_tags.tpl.php');
//	}
	
	function applyTags() {
		@$id = intval($_POST['id']);
		@$tagEntry = $_POST['tagEntry'];
		
		CerberusTicketDAO::tagTicket($id, $tagEntry);
		
		echo ' ';
	}

	function showFavTags() {
		$session = DevblocksPlatform::getSessionService();
		$visit = $session->getVisit();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->caching = 0;
		$tpl->cache_lifetime = 0;

		$favoriteTags = CerberusAgentDAO::getFavoriteTags($visit->id);
		$tpl->assign('favoriteTags', $favoriteTags);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/modules/workflow/add_favtags.tpl.php');
	}

	function saveFavoriteTags() {
		@$favTagEntry = $_POST['favTagEntry'];
		
		$session = DevblocksPlatform::getSessionService();
		$visit = $session->getVisit();
		
		CerberusAgentDAO::setFavoriteTags($visit->id, $favTagEntry);
		
		echo ' ';
	}
	
	function showFavWorkers() {
		$session = DevblocksPlatform::getSessionService();
		$visit = $session->getVisit();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->caching = 0;
		$tpl->cache_lifetime = 0;

		$tpl->assign('moduleLabel', $this->manifest->id);

		$agents = CerberusAgentDAO::getAgents();
		$tpl->assign('agents', $agents);
		
		$favoriteWorkers = CerberusAgentDAO::getFavoriteWorkers($visit->id);
		$tpl->assign('favoriteWorkers', $favoriteWorkers);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/modules/workflow/add_favworkers.tpl.php');
	}

	function saveFavoriteWorkers() {
		@$favWorkerEntry = $_POST['favWorkerEntry'];
		
		$session = DevblocksPlatform::getSessionService();
		$visit = $session->getVisit();
		
		CerberusAgentDAO::setFavoriteWorkers($visit->id, $favWorkerEntry);
		
		echo ' ';
	}
	
	function showFlagAgents() {
		@$id = intval($_REQUEST['id']);

		$session = DevblocksPlatform::getSessionService();
		$visit = $session->getVisit();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->caching = 0;
		$tpl->cache_lifetime = 0;

		$tpl->assign('moduleLabel', $this->manifest->id);

		$ticket = CerberusTicketDAO::getTicket($id);
		$tpl->assign('ticket', $ticket);
		
		$favoriteWorkers = CerberusAgentDAO::getFavoriteWorkers($visit->id);
		$tpl->assign('favoriteWorkers', $favoriteWorkers);
		
		$agents = CerberusAgentDAO::getAgents();
		$tpl->assign('agents', $agents);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/modules/workflow/add_flags.tpl.php');
	}
	
	function flagAgents() {
		@$id = intval($_POST['id']);
		@$agentEntry = $_POST['workerEntry'];
		
		$tokens = CerberusApplication::parseCsvString($agentEntry);
		
		foreach($tokens as $token) {
			$agent_id = CerberusAgentDAO::lookupAgentLogin($token);
			if(empty($agent_id)) continue;
			CerberusTicketDAO::flagTicket($id, $agent_id);
		}
		
		echo ' ';
	}
	
	function showSuggestAgents() {
		@$id = intval($_REQUEST['id']);
		
		$session = DevblocksPlatform::getSessionService();
		$visit = $session->getVisit();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->caching = 0;
		$tpl->cache_lifetime = 0;

		$tpl->assign('moduleLabel', $this->manifest->id);

		$ticket = CerberusTicketDAO::getTicket($id);
		$tpl->assign('ticket', $ticket);
		
		$favoriteWorkers = CerberusAgentDAO::getFavoriteWorkers($visit->id);
		$tpl->assign('favoriteWorkers', $favoriteWorkers);
		
		$agents = CerberusAgentDAO::getAgents();
		$tpl->assign('agents', $agents);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/modules/workflow/add_suggestions.tpl.php');
	}

	function suggestAgents() {
		@$id = intval($_POST['id']);
		@$agentEntry = $_POST['workerEntry'];
		
		$tokens = CerberusApplication::parseCsvString($agentEntry);
		
		foreach($tokens as $token) {
			$agent_id = CerberusAgentDAO::lookupAgentLogin($token);
			if(empty($agent_id)) continue;
			CerberusTicketDAO::suggestTicket($id, $agent_id);
		}
		
		echo ' ';
	}
	
}

class ChDisplayTicketFields extends CerberusDisplayModuleExtension {
	function __construct($manifest) {
		parent::__construct($manifest);
	}
	
	function render($ticket) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/modules/ticket_fields.tpl.php');
	}
	
	function renderBody($ticket) {
		echo "Ticket custom fields content goes here!";
	}
}

class ChDisplayTicketConversation extends CerberusDisplayModuleExtension {
	function __construct($manifest) {
		parent::__construct($manifest);
	}
	
	function render($ticket) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/modules/ticket_conversation.tpl.php');
	}
}



?>