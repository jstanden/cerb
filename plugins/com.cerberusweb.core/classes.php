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
		$um_db = UserMeetDatabase::getInstance();
		$tpl = UserMeetTemplateManager::getInstance();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');

		$session = UserMeetSessionManager::getInstance();
		$visit = $session->getVisit();
		
		$dashboards = CerberusDashboardDAO::getDashboards($visit->id);
		$tpl->assign('dashboards', $dashboards);
		
		// [JAS]: [TODO] This needs to limit by the selected dashboard
		$views = CerberusDashboardDAO::getViews(); // getViews($dashboard_id)
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
		
		$tpl = UserMeetTemplateManager::getInstance();
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

		$tpl = UserMeetTemplateManager::getInstance();
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
				't.mask',
				't.status',
				't.priority',
				't.last_wrote',
				't.created_date'
			))
		);
		CerberusDashboardDAO::updateView($view_id,$fields);
		
		CerberusApplication::setActiveModule($this->id);
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

		$tpl = UserMeetTemplateManager::getInstance();
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
	function sendMessage($type) {
		// mailer setup
		require_once(UM_PATH . '/libs/pear/Mail.php');
		require_once(UM_PATH . '/libs/pear/mime.php');
		$mail_params = array();
		$mail_params['host'] = 'mail.webgroupmedia.com'; //[TODO]: make this pull from a config instance rather than hard-coded
		$mailer =& Mail::factory("smtp", $mail_params);
		
		// variable loading
		@$id		= $_REQUEST['id']; // message id
		@$to		= $_REQUEST['to'];
		@$cc		= $_REQUEST['cc'];
		@$bcc		= $_REQUEST['bcc'];
		@$content	= $_REQUEST['content'];
		@$priority	= $_REQUEST['priority'];
		@$status	= $_REQUEST['status'];
		@$agent_id	= $_REQUEST['agent_id'];
		
		// object loading
		$message	= CerberusTicketDAO::getMessage($id);
		$ticket_id	= $message->ticket_id;
		$ticket		= CerberusTicketDAO::getTicket($ticket_id);
		$mailboxes	= CerberusApplication::getMailboxList();
		$mailbox	= $mailboxes[$ticket->mailbox_id];
		$requesters	= CerberusTicketDAO::getRequestersByTicket($ticket_id);
		
		// requester address parsing - needs to vary based on type
		$sTo = '';
		$sRCPT = '';
		if ($type == CerberusMessageType::EMAIL) {
			foreach ($requesters as $requester) {
				if (!empty($sTo)) $sTo .= ', ';
				if (!empty($sRCPT)) $sRCPT .= ', ';
				if (!empty($requester->personal)) $sTo .= $requester->personal . ' ';
				$sTo .= '<' . $requester->email . '>';
				$sRCPT .= $requester->email;
			}
		} else {
			$sTo = $to;
			$sRCPT = $to;
		}
		
		// header setup: varies based on type of response - BREAK statements intentionally left out!
		$headers = array();
		switch ($type) {
			case CerberusMessageType::FORWARD :
			case CerberusMessageType::EMAIL :
				$headers['to']			= $sTo;
				$headers['cc']			= $cc;
				$headers['bcc']			= $bcc;
			case CerberusMessageType::COMMENT :
				// TODO: pull info from mailbox instead of hard-coding it.  (display name cannot be just a personal on a mailbox address...)
				// TODO: differentiate between mailbox from as part of email/forward and agent from as part of comment (may not be necessary, depends on ticket display)
				//$headers['From']		= !empty($mailbox->display_name)?'"'.$mailbox->display_name.'" <'.needafunction::getAddress($mailbox->reply_address_id)->email.'>':needafunction::getAddress($mailbox->reply_address_id)->email;
				$headers['from']		= 'pop1@cerberus6.webgroupmedia.com';
				$headers['date']		= gmdate(r);
				$headers['message-id']	= CerberusApplication::generateMessageId();
				$headers['subject']		= $ticket->subject;
				$headers['references']	= $message->headers['message-id'];
				$headers['in-reply-to']	= $message->headers['message-id'];
		}
		
		$files = $_FILES['attachment'];
		// send email (if necessary)
		if ($type != CerberusMessageType::COMMENT) {
			// build MIME message if message has attachments
			if (is_array($files) && !empty($files)) {
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
			
			$mail_result =& $mailer->send($sRCPT, $email_headers, $email_body);
			if ($mail_result !== true) die("Error message was: " . $mail_result->getMessage());
		}
		
		// TODO: create DAO object for Agent, be able to pull address by having agent id.
//		$headers['From'] = $agent_address->personal . ' <' . $agent_address->email . '>';
//		CerberusTicketDAO::createMessage($ticket_id,CerberusMessageType::EMAIL,gmmktime(),$agent_id,$headers,$content);
		$message_id = CerberusTicketDAO::createMessage($ticket_id,$type,gmmktime(),1,$headers,$content);
		
		// if this message was submitted with attachments, store them in the filestore and link them in the db.
		if (is_array($files) && !empty($files)) {
			foreach ($files['tmp_name'] as $idx => $file) {
				$timestamp = gmdate('Y.m.d.H.i.s.', gmmktime());
				list($usec, $sec) = explode(' ', microtime());
				$timestamp .= substr($usec,2,3) . '.';
				copy($files['tmp_name'][$idx],UM_ATTACHMENT_SAVE_PATH . $timestamp . $files['name'][$idx]);
				CerberusTicketDAO::createAttachment($message_id, $files['name'][$idx], $timestamp . $files['name'][$idx]);
			}
		}
		
		$_REQUEST['id'] = $ticket_id;
		CerberusApplication::setActiveModule($this->id);
	}
	
	function refreshRequesters() {
		$tpl = UserMeetTemplateManager::getInstance();
		@$id = $_REQUEST['id']; // ticket id
		
		$ticket = CerberusTicketDAO::getTicket($id);

		$tpl->assign('ticket',$ticket);
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/requesters.tpl.php');
	}

	function saveRequester() {
		@$id = $_REQUEST['id']; // ticket id
		@$add_requester = $_POST['add_requester'];
		
		// I'd really like to know why the *$#! this doesn't work.  The if statement works fine atomically...
//		require_once(UM_PATH . '/libs/pear/Mail/RFC822.php');
//		if (false === Mail_RFC822::isValidInetAddress($add_requester)) {
//			return $add_requester . UserMeetTranslationManager::say('ticket.requester.invalid');
//		}
		
		$address_id = CerberusContactDAO::lookupAddress($add_requester, true);
		CerberusTicketDAO::createRequester($address_id, $id);
		
		echo ' ';
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
		$manifest = UserMeetPlatform::getExtension('login.default');
		$inst = $manifest->createInstance(1); /* @var $inst CerberusLoginModuleExtension */
		$inst->renderLoginForm();
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
		return "?c=".$this->id."&a=click";
	}
	
	function getCriteria() {
		@$field = $_REQUEST['field'];
		
		$tpl = UserMeetTemplateManager::getInstance();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		$tpl->cache_lifetime = "0";
		
		switch($field) {
			case "t.mask":
				$tpl->display('file:' . dirname(__FILE__) . '/templates/search/criteria/ticket_mask.tpl.php');
				break;
				
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
	
	function getCriteriaDialog() {
		@$divName = $_REQUEST['divName'];
		
		$tpl = UserMeetTemplateManager::getInstance();
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
			case "t.mask":
				@$mask = $_REQUEST['mask'];
				$params[$field] = new CerberusSearchCriteria($field,'like',$mask);
				break;
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
		
		$tpl = UserMeetTemplateManager::getInstance();
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
		
		$tpl = UserMeetTemplateManager::getInstance();
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
			
			$view_id = CerberusDashboardDAO::createView($name, 0, 50, 't.created_date', 0, 'S');
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
		$tpl->assign('callback','renderBody');
		$tpl->assign('moduleLabel', $this->manifest->id);
		$tpl->display('display/expandable_module_template.tpl.php');
	}
	
	function renderBody() {
		$tpl = UserMeetTemplateManager::getInstance();
		$tpl->cache_lifetime = "0";
		
		$favoriteTags = CerberusWorkflowDAO::getTags();
		$tpl->assign('favoriteTags', $favoriteTags);
		
		$suggestedTags = CerberusWorkflowDAO::getTags();
		$tpl->assign('suggestedTags', $suggestedTags);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/modules/ticket_workflow.tpl.php');
	}
	
	function refresh() {
		$tpl = UserMeetTemplateManager::getInstance();
		$tpl->caching = 0;
		$tpl->cache_lifetime = 0;

		$tpl->assign('moduleLabel', $this->manifest->id);

		@$id = $_REQUEST['id'];
		
		$ticket = CerberusTicketDAO::getTicket($id);
		$tpl->assign('ticket', $ticket);
		
		$favoriteTags = CerberusWorkflowDAO::getTags();
		$tpl->assign('favoriteTags', $favoriteTags);
		
		$suggestedTags = CerberusWorkflowDAO::getTags();
		$tpl->assign('suggestedTags', $suggestedTags);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/modules/ticket_workflow.tpl.php');
	}
	
	function getTagDialog() {
		@$tag_id = intval($_REQUEST['id']);
		@$ticket_id = intval($_REQUEST['ticket_id']);
		@$parent_div = $_REQUEST['parent_div'];
		
		$tpl = UserMeetTemplateManager::getInstance();
		$tpl->caching = 0;
		$tpl->cache_lifetime = 0;
		
		$tag = CerberusWorkflowDAO::getTag($tag_id);
		$tpl->assign('tag', $tag);
		
		$tpl->assign('ticket_id', $ticket_id);
		$tpl->assign('parent_div', $parent_div);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/modules/workflow/tag_dialog.tpl.php');
	}
	
	function saveTagDialog() {
		@$id = intval($_REQUEST['id']);
		@$ticket_id = intval($_REQUEST['ticket_id']);
		@$untag = intval($_REQUEST['untag']);
		@$terms = $_REQUEST['terms'];
		
		if(!empty($untag) && !empty($ticket_id)) {
			CerberusTicketDAO::untagTicket($ticket_id, $id);
		} else {
			// save changes
		}
		
		echo ' ';
	}
	
	function applyTags() {
		@$id = intval($_POST['id']);
		@$tagEntry = $_POST['tagEntry'];
		
		CerberusTicketDAO::tagTicket($id, $tagEntry);
		
		echo ' ';
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