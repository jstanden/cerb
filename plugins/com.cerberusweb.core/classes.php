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
		
//		if(empty($visit)) {
//			return true;
//		} else {
//			return false;
//		}

		return true;
	}
	
	function render() {
		$tpl = UserMeetTemplateManager::getInstance();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		
		$tickets = CerberusApplication::getTicketList();
		$tpl->assign('tickets', $tickets);
		
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
		echo "<H1>Customize $id</H1>";
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
		$tpl->assign('id', $id);
		
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
		echo "You clicked: " . __CLASS__ . "->" . __FUNCTION__ . "!<br>";
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
		
		echo "Sign in: " . __CLASS__ . "->" . __FUNCTION__ . "!<br>";
		$session = UserMeetSessionManager::getInstance();
		$session->login($email,$password);
	}
	
	function signout() {
		echo "Sign out: " . __CLASS__ . "->" . __FUNCTION__ . "!<br>";
		$session = UserMeetSessionManager::getInstance();
		$session->logout();
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
		
//		if(empty($visit)) {
//			return true;
//		} else {
//			return false;
//		}

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
		
//		if(empty($visit)) {
//			return true;
//		} else {
//			return false;
//		}

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