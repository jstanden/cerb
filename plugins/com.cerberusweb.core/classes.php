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
		
		$tickets = CerberusTicketDAO::getTicketList();
		$tpl->assign('tickets', $tickets[0]);
		$tpl->assign('total', $tickets[1]);
		
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
	
	function customize() {
		@$id = $_REQUEST['id'];

		$tpl = UserMeetTemplateManager::getInstance();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		$tpl->assign('id',$id);

		$tpl->cache_lifetime = "0";
		$tpl->display('file:' . dirname(__FILE__) . '/templates/dashboards/rpc/customize_view.tpl.php');
	}
	
	function searchview() {
		@$id = $_REQUEST['id'];
		CerberusApplication::setActiveModule("core.module.search");
	}
	
};

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

		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/index.tpl.php');
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
		$tpl->display('file:' . dirname(__FILE__) . '/templates/teamwork.tpl.php');
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

		return true;
	}
	
	function render() {
		$tpl = UserMeetTemplateManager::getInstance();
		$tpl->cache_lifetime = "0";
		$tpl->display('file:' . dirname(__FILE__) . '/templates/search.tpl.php');
	}
	
	function getLink() {
		return "?c=".$this->id."&a=click";
	}
	
}

?>