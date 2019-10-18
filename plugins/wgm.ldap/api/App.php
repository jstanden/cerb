<?php
if(class_exists('Extension_ScLoginAuthenticator',true)):
class ScLdapLoginAuthenticator extends Extension_ScLoginAuthenticator {
	function writeResponse(DevblocksHttpResponse $response) {
		$tpl = DevblocksPlatform::services()->templateSandbox();
		
		$stack = $response->path;
		@$module = array_shift($stack);
		
		switch($module) {
			default:
				$tpl->display("devblocks:wgm.ldap:portal_".ChPortalHelper::getCode().":support_center/login/ldap.tpl");
				break;
		}
	}
	
	function renderConfigForm(Model_CommunityTool $instance) {
		$tpl = DevblocksPlatform::services()->template();

		$tpl->assign('instance', $instance);
		$tpl->assign('extension', $this);
		
		$ldap_service_id = DAO_CommunityToolProperty::get($instance->code, 'sso.ldap.service_id', 0);
		$tpl->assign('ldap_service_id', $ldap_service_id);
		
		$tpl->display('devblocks:wgm.ldap::support_center/login/config.tpl');
	}
	
	function saveConfiguration(Model_CommunityTool $instance) {
		@$params = DevblocksPlatform::importGPC($_REQUEST['params'], 'array', []);
		
		@$ldap_service_id = DevblocksPlatform::importGPC($params['ldap_service_id'], 'int', 0);
		
		// [TODO] Validation
		// [TODO] Must be an LDAP service extension
		
		DAO_CommunityToolProperty::set($instance->code, 'sso.ldap.service_id', $ldap_service_id);
		return true;
	}
	
	function authenticateAction() {
		$umsession = ChPortalHelper::getSession();
		$tpl = DevblocksPlatform::services()->template();

		// Clear the past session
		$umsession->logout();
		
		try {
			@$email = DevblocksPlatform::importGPC($_REQUEST['email'],'string','');
			@$password = DevblocksPlatform::importGPC($_REQUEST['password'],'string','');
			
			// Check for extension
			if(!extension_loaded('ldap'))
				throw new Exception("The authentication server is offline. Please try again later.");
			
			if(empty($email))
				throw new Exception("An email address is required.");
			
			if(empty($password))
				throw new Exception("A password is required.");
			
			// Validate email address
			
			$valid_email = imap_rfc822_parse_adrlist($email, 'host');
			
			if(empty($valid_email) || !is_array($valid_email) || empty($valid_email[0]->host) || $valid_email[0]->host=='host')
				throw new Exception("Please provide a valid email address.");
			
			$email = $valid_email[0]->mailbox . '@' . $valid_email[0]->host;
			
			$ldap_service_id = DAO_CommunityToolProperty::get(ChPortalHelper::getCode(), 'sso.ldap.service_id', 0);
			
			if(!$ldap_service_id || false == ($service = DAO_ConnectedService::get($ldap_service_id))) {
				throw new Exception("The authentication server is offline. Please try again later.");
			}
			
			$service_params = $service->decryptParams();
			
			$ldap_settings = [
				'host' => @$service_params['host'],
				'port' => @$service_params['port'] ?: 389,
				'username' => @$service_params['bind_dn'],
				'password' => @$service_params['bind_password'],
				
				'context_search' => @$service_params['context_search'],
				'field_email' => @$service_params['field_email'],
				'field_firstname' => @$service_params['field_firstname'],
				'field_lastname' => @$service_params['field_lastname'],
			];

			@$ldap = ldap_connect($ldap_settings['host'], $ldap_settings['port']);
			
			if(!$ldap)
				throw new Exception("The authentication server is offline. Please try again later.");
			
			ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
			ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);
			
			@$login = ldap_bind($ldap, $ldap_settings['username'], $ldap_settings['password']);
			
			if(!$login)
				throw new Exception("The authentication server is offline. Please try again later.");
			
			$query = sprintf("(%s=%s)", $ldap_settings['field_email'], $email);
			@$results = ldap_search($ldap, $ldap_settings['context_search'], $query);
			@$entries = ldap_get_entries($ldap, $results);
			@$count = intval($entries['count']);
			
			if(empty($count))
				throw new Exception("auth.failed");
			
			// Rebind as the customer DN
			
			$dn = $entries[0]['dn'];
			
			if(@ldap_bind($ldap, $dn, $password)) {
				
				// Look up address by email
				if(null == ($address = DAO_Address::lookupAddress($email))) {
					$address_id = DAO_Address::create(array(
						DAO_Address::EMAIL => $email,
					));
					
					if(null == ($address = DAO_Address::get($address_id)))
						throw new Exception("Your account could not be created. Please try again later.");
					
					if($address->is_banned)
						throw new Exception("email.unavailable");
				}
				
				// See if the contact person exists or not
				if(!empty($address->contact_id)) {
					if(null != ($contact = DAO_Contact::get($address->contact_id))) {
						$umsession->login($contact);
						
						$original_path = $umsession->getProperty('login.original_path', '');
						$path = !empty($original_path) ? explode('/', $original_path) : array();
						
						DevblocksPlatform::redirect(new DevblocksHttpResponse($path));
						exit;
					}
					
				} else { // create
					$given_name = @$entries[0][DevblocksPlatform::strLower($ldap_settings['field_firstname'])][0];
					$surname = @$entries[0][DevblocksPlatform::strLower($ldap_settings['field_lastname'])][0];
					
					$fields = array(
						DAO_Contact::CREATED_AT => time(),
						DAO_Contact::PRIMARY_EMAIL_ID => $address->id,
						DAO_Contact::FIRST_NAME => $given_name ?: '',
						DAO_Contact::LAST_NAME => $surname ?: '',
					);
					$contact_id = DAO_Contact::create($fields);
					
					if(null != ($contact = DAO_Contact::get($contact_id))) {
						DAO_Address::update($address->id, array(
							DAO_Address::CONTACT_ID => $contact->id,
						));
						
						$umsession->login($contact);
						
						@ldap_unbind($ldap);
						
						$original_path = $umsession->getProperty('login.original_path', 'account');
						$path = !empty($original_path) ? explode('/', $original_path) : array();
						
						DevblocksPlatform::redirect(new DevblocksHttpResponse($path));
						exit;
					}
				}
				
			} else {
				throw new Exception("auth.failed");
			}
			
		} catch (Exception $e) {
			$error = $e->getMessage();
			
			if($error) {
				$error_msg = Page_Login::getErrorMessage($error);
				$tpl->assign('error', $error_msg);
			}
		}
		
		@ldap_unbind($ldap);
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',ChPortalHelper::getCode(),'login')));
	}
};
endif;