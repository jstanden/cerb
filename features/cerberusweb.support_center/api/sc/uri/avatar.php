<?php
class UmScAvatarController extends Extension_UmScController {
	function __construct($manifest=null) {
		parent::__construct($manifest);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$umsession = ChPortalHelper::getSession();
		
		@$active_contact = $umsession->getProperty('sc_login',null);
		$tpl->assign('active_contact', $active_contact);

		// Usermeet Session
		if(null == ($fingerprint = ChPortalHelper::getFingerprint())) {
			die("A problem occurred.");
		}
		$tpl->assign('fingerprint', $fingerprint);
	}
	
	function handleRequest(DevblocksHttpRequest $request) {
		$translate = DevblocksPlatform::getTranslationService();
		
		$stack = $request->path; // URLS like: /contact/1
		
		@array_shift($stack); // avatar
		@$alias = array_shift($stack); // contact
		@$avatar_context_id = intval(array_shift($stack)); // 1
		
		// Only allow certain contexts
		switch($alias) {
			case 'address':
			case 'contact':
			case 'org':
			case 'worker':
				break;
				
			default:
				$alias = null;
				break;
		}

		// Look up the context extension
		if(empty($alias) || false == ($avatar_context_mft = Extension_DevblocksContext::getByAlias($alias, false)))
			$this->_renderDefaultAvatar();
		
		// Look up the avatar record
		if(false == ($avatar = DAO_ContextAvatar::getByContext($avatar_context_mft->id, $avatar_context_id))) {
			$this->_renderDefaultAvatar($avatar_context_mft->id, $avatar_context_id);
			return;
		}
		
		$this->_renderAvatar($avatar);
		exit;
	}
	
	private function _renderAvatar(Model_ContextAvatar $avatar, $default_context=null, $default_context_id=null) {
		if(empty($default_context))
			$default_context = $avatar->context;
		if(empty($default_context_id))
			$default_context_id = $avatar->context_id;
		
		if(empty($avatar->content_type) 
				|| empty($avatar->storage_size) 
				|| empty($avatar->storage_key) 
				|| false == ($contents = Storage_ContextAvatar::get($avatar))) {
			$this->_renderDefaultAvatar($default_context, $default_context_id);
			return;
		}
		
		// Set headers
		header('Pragma: cache');
		header('Cache-control: max-age=7200', true); // 2 hours // , must-revalidate
		header('Expires: ' . gmdate('D, d M Y H:i:s',time()+7200) . ' GMT'); // 2 hours
		header('Accept-Ranges: bytes');

		header("Content-Type: " . $avatar->content_type);
		header("Content-Length: " . $avatar->storage_size);
		
		echo $contents;
		exit;
	}
	
	private function _renderDefaultAvatar($context=null, $context_id=null) {
		switch($context) {
			case CerberusContexts::CONTEXT_APPLICATION:
				$contents = file_get_contents(APP_PATH . '/features/cerberusweb.core/resources/images/avatars/app.png');
				break;
				
			// Check if the addy's org has an avatar
			case CerberusContexts::CONTEXT_ADDRESS:
				if($context_id && false != ($addy = DAO_Address::get($context_id))) {
					
					// Use contact if an avatar exists
					if($addy->contact_id) {
						if(false != ($avatar = DAO_ContextAvatar::getByContext(CerberusContexts::CONTEXT_CONTACT, $addy->contact_id))) {
							$this->_renderAvatar($avatar, CerberusContexts::CONTEXT_CONTACT);
						} else {
							$this->_renderDefaultAvatar(CerberusContexts::CONTEXT_CONTACT, $addy->contact_id);
						}
					}
					
					// Use org if an avatar exists
					if($addy->contact_org_id && false != ($avatar = DAO_ContextAvatar::getByContext(CerberusContexts::CONTEXT_ORG, $addy->contact_org_id)))
						$this->_renderAvatar($avatar, CerberusContexts::CONTEXT_CONTACT);
					
					$this->_renderDefaultAvatar(CerberusContexts::CONTEXT_CONTACT);
					return;
					break;
				}
				
				$n = mt_rand(1, 4);
				$contents = file_get_contents(APP_PATH . sprintf('/features/cerberusweb.core/resources/images/avatars/person%d.png', $n));
				break;
				
			case CerberusContexts::CONTEXT_CONTACT:
				$gender = '';
				
				if($context_id && false != ($contact = DAO_Contact::get($context_id))) {
					$gender = $contact->gender;
				}
				
				switch($gender) {
					case 'M':
						$male_keys = array(1,3,4);
						$n = $male_keys[array_rand($male_keys)];
						break;
					case 'F':
						$n = 2;
						break;
					default:
						$n = mt_rand(1, 4);
						break;
				}
				
				$contents = file_get_contents(APP_PATH . sprintf('/features/cerberusweb.core/resources/images/avatars/person%d.png', $n));
				break;
				
			case CerberusContexts::CONTEXT_ORG:
				$n = mt_rand(1,3);
				$contents = file_get_contents(APP_PATH . sprintf('/features/cerberusweb.core/resources/images/avatars/building%d.png', $n));
				break;
				
			case CerberusContexts::CONTEXT_WORKER:
				$gender = '';
				
				if($context_id && false != ($worker = DAO_Worker::get($context_id))) {
					$gender = $worker->gender;
				}
				
				switch($gender) {
					case 'M':
						$male_keys = array(1,3,4);
						$n = $male_keys[array_rand($male_keys)];
						break;
					case 'F':
						$n = 2;
						break;
					default:
						$n = mt_rand(1, 4);
						break;
				}
				
				$contents = file_get_contents(APP_PATH . sprintf('/features/cerberusweb.core/resources/images/avatars/person%d.png', $n));
				break;
				
			case CerberusContexts::CONTEXT_VIRTUAL_ATTENDANT:
				$contents = file_get_contents(APP_PATH . '/features/cerberusweb.core/resources/images/avatars/va.png');
				break;
				
			default:
				$contents = file_get_contents(APP_PATH . '/features/cerberusweb.core/resources/images/avatars/convo.png');
				break;
		}
		
		// Set headers
		header('Pragma: cache');
		header('Cache-control: max-age=7200', true); // 2 hours // , must-revalidate
		header('Expires: ' . gmdate('D, d M Y H:i:s',time()+7200) . ' GMT'); // 2 hours
		header('Accept-Ranges: bytes');
		
		header("Content-Type: " . 'image/png');
		header("Content-Length: " . strlen($contents));
		
		echo $contents;
		exit;
	}
	
	private function _getEmailFromContext($context, $context_id) {
		switch($context) {
			case CerberusContexts::CONTEXT_ADDRESS:
				if(false == ($address = DAO_Address::get($context_id)))
					return false;
					
				return $address->email;
				break;
				
			case CerberusContexts::CONTEXT_WORKER:
				if(false == ($worker = DAO_Worker::get($context_id)))
					return false;
					
				return $worker->getEmailString();
				break;
		}
		
		return false;
	}
};