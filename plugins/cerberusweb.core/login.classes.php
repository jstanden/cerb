<?php
class DefaultLoginModule extends CerberusLoginModuleExtension {
	function renderLoginForm() {
		// draws HTML form of controls needed for login information
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->display('file:' . dirname(__FILE__) . '/templates/login/login_form_default.tpl.php');
	}
	
	function authenticate() {
		// pull auth info out of $_POST, check it, return user_id or false
		@$email		= DevblocksPlatform::importGPC($_POST['email']);
		@$password	= DevblocksPlatform::importGPC($_POST['password']);
			
		$worker = DAO_Worker::login($email, $password);
		
		if(!is_null($worker)) {
			$session = DevblocksPlatform::getSessionService();
			$visit = new CerberusVisit();
				$visit->worker = $worker;
			$session->setVisit($visit);
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('dashboard')));
			return true;
			
		} else {
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('login')));
			return false;
		}
	}
};

class LDAPLoginModule extends CerberusLoginModuleExtension {
	function renderLoginForm() {
		// draws HTML form of controls needed for login information
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->display('file:' . dirname(__FILE__) . '/templates/login/login_form_ldap.tpl.php');
	}
	
	function authenticate() {
		// pull auth info out of $_POST, check it, return user_id or false
		@$server	= DevblocksPlatform::importGPC($_POST['server']);
		@$port		= DevblocksPlatform::importGPC($_POST['port']);
		@$dn		= DevblocksPlatform::importGPC($_POST['dn']);
		@$password	= DevblocksPlatform::importGPC($_POST['password']);

		$conn = ldap_connect($server, $port);
		ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);
		
		if ($conn) {
			$auth = ldap_bind($conn, $dn, $password);
			if (1 == $auth) {
				$session = DevblocksPlatform::getSessionService();
				$visit = new DevblocksSession();
					// [TODO]: Need to set real properties here
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