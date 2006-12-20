<?php
class DefaultLoginModule extends CerberusLoginModuleExtension {
	function renderLoginForm() {
		// draws HTML form of controls needed for login information
		$tpl = CgTemplateManager::getInstance();
		$tpl->cache_lifetime = "0";
		$tpl->display('file:' . dirname(__FILE__) . '/templates/login_form_default.tpl.php');
	}
	
	function authenticate() {
		// pull auth info out of $_POST, check it, return user_id or false
		@$email		= $_POST['email'];
		@$password	= $_POST['password'];
			
		$session = CgSessionManager::getInstance();
		$visit = $session->login($email,$password);
		
		if(!is_null($visit)) {
			CerberusApplication::setActiveModule("core.module.dashboard");
			return true;
		} else {
			CerberusApplication::setActiveModule("core.module.signin");
			return false;
		}
	}
};

class LDAPLoginModule extends CerberusLoginModuleExtension {
	function renderLoginForm() {
		// draws HTML form of controls needed for login information
		$tpl = CgTemplateManager::getInstance();
		$tpl->cache_lifetime = "0";
		$tpl->display('file:' . dirname(__FILE__) . '/templates/login_form_ldap.tpl.php');
	}
	
	function authenticate() {
		// pull auth info out of $_POST, check it, return user_id or false
		@$server	= $_POST['server'];
		@$port		= $_POST['port'];
		@$dn		= $_POST['dn'];
		@$password	= $_POST['password'];

		$conn = ldap_connect($server, $port);
		ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);
		
		if ($conn) {
			$auth = ldap_bind($conn, $dn, $password);
			if (1 == $auth) {
				$session = CgSessionManager::getInstance();
				$visit = new CgSession();
					$visit->id = 1;
					$visit->login = 'ldap_user';
					$visit->admin = 1;
				$session->visit = $visit;
				$_SESSION['um_visit'] = $visit;

				return true;
			}
		}
		
		return false;
	}
};

?>