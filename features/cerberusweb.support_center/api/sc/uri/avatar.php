<?php /** @noinspection PhpUnused */

class UmScAvatarController extends Extension_UmScController {
	function __construct($manifest=null) {
		parent::__construct($manifest);
	}
	
	public function isVisible() {
		return true;
	}
	
	public function invoke(string $action, DevblocksHttpRequest $request=null) {
		return false;
	}
	
	function handleRequest(DevblocksHttpRequest $request) {
		$stack = $request->path; // URLS like: /contact/1
		
		@array_shift($stack); // avatar
		@$alias = array_shift($stack); // contact
		@$avatar_context_id = intval(array_shift($stack)); // 1
		
		// [TODO] Obscure IDs for contexts since this is displayed publicly (hash w/ uniqueness plus ID)
		
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
			return $this->_renderDefaultAvatar();
		
		// Look up the avatar record
		if(false == ($avatar = DAO_ContextAvatar::getByContext($avatar_context_mft->id, $avatar_context_id))) {
			return $this->_renderDefaultAvatar($avatar_context_mft->id, $avatar_context_id);
		}
		
		return $this->_renderAvatar($avatar);
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
		DevblocksPlatform::exit();
	}
	
	private function _renderDefaultAvatar($context=null, $context_id=null) {
		$avatar_default_style_contact = DevblocksPlatform::getPluginSetting('cerberusweb.core', CerberusSettings::AVATAR_DEFAULT_STYLE_CONTACT, CerberusSettingsDefaults::AVATAR_DEFAULT_STYLE_CONTACT);
		$avatar_default_style_worker = DevblocksPlatform::getPluginSetting('cerberusweb.core', CerberusSettings::AVATAR_DEFAULT_STYLE_WORKER, CerberusSettingsDefaults::AVATAR_DEFAULT_STYLE_WORKER);
		
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
							return;
						} else {
							$this->_renderDefaultAvatar(CerberusContexts::CONTEXT_CONTACT, $addy->contact_id);
							return;
						}
					}
					
					// Use org if an avatar exists (and no contact does)
					if(!$addy->contact_id && $addy->contact_org_id && false != ($avatar = DAO_ContextAvatar::getByContext(CerberusContexts::CONTEXT_ORG, $addy->contact_org_id))) {
						$this->_renderAvatar($avatar, CerberusContexts::CONTEXT_CONTACT);
						return;
					}
					
					$this->_renderMonogram(substr($addy->email,0,1), $context_id);
					return;
					break;
				}
				
				// Unknown ID
				$all_keys = array(1,2,3,4,5,6);
				$n = $all_keys[$context_id % 6];
				$contents = file_get_contents(APP_PATH . sprintf('/features/cerberusweb.core/resources/images/avatars/person%d.png', $n));
				break;
				
			case CerberusContexts::CONTEXT_CONTACT:
				$all_keys = array(1,2,3,4,5,6);
				$n = $all_keys[$context_id % 6];
				
				if($context_id && false != ($contact = DAO_Contact::get($context_id))) {
					if($contact->gender && $avatar_default_style_contact == 'silhouettes') {
						switch($contact->gender) {
							case 'M':
								$male_keys = array(1,3,4);
								$n = $male_keys[$context_id % 3];
								break;
								
							case 'F':
								$female_keys = array(2,5,6);
								$n = $female_keys[$context_id % 3];
								break;
						}
					} else {
						$this->_renderMonogram($contact->getInitials(), $context_id);
						return;
					}
				}
				
				$contents = file_get_contents(APP_PATH . sprintf('/features/cerberusweb.core/resources/images/avatars/person%d.png', $n));
				break;
				
			case CerberusContexts::CONTEXT_ORG:
				$all_keys = array(1,2,3);
				$n = $all_keys[$context_id % 3];
				$contents = file_get_contents(APP_PATH . sprintf('/features/cerberusweb.core/resources/images/avatars/building%d.png', $n));
				break;
				
			case CerberusContexts::CONTEXT_WORKER:
				$all_keys = array(1,2,3,4,5,6);
				$n = $all_keys[$context_id % 6];
				
				if($context_id && false != ($worker = DAO_Worker::get($context_id))) {
					if($worker->gender && $avatar_default_style_worker == 'silhouettes') {
						switch($worker->gender) {
							case 'M':
								$male_keys = array(1,3,4);
								$n = $male_keys[$context_id % 3];
								break;
								
							case 'F':
								$female_keys = array(2,5,6);
								$n = $female_keys[$context_id % 3];
								break;
						}
					} else {
						$this->_renderMonogram($worker->getInitials(), $context_id);
						return;
					}
				}
				
				$contents = file_get_contents(APP_PATH . sprintf('/features/cerberusweb.core/resources/images/avatars/person%d.png', $n));
				break;
				
			case CerberusContexts::CONTEXT_BOT:
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
	
	private function _renderMonogram($text, $hash=null) {
		$text = mb_substr(mb_convert_case($text, MB_CASE_UPPER), 0, 3);
		$font = DEVBLOCKS_PATH . 'resources/font/Oswald-Bold.ttf';
		
		$font_size = 75;

		// Find the optimal font size for the given text
		do {
			$font_size -= 5;
			$box = imagettfbbox($font_size, 0, $font, $text);
			$ascent = abs($box[7]);
			$descent = abs($box[1]);
			$box_width = abs($box[0]) + abs($box[2]);
			$box_height = $ascent + $descent;
		} while($box_width > 80 || $box_height > 70);
		
		$x = floor(50 - ($box_width/2));
		$y = floor(50 - ($box_height/2)) + $ascent;
		
		// Predictably generate random numbers given the same input (consistent hashing)
		mt_srand(crc32($hash ?: $text));
		$r_rand = mt_rand(25,180);
		$g_rand = mt_rand(25,180);
		$b_rand = mt_rand(25,180);
		mt_srand();
		
		header('Pragma: cache');
		header('Cache-control: max-age=86400', true); // 24 hours // , must-revalidate
		header('Expires: ' . gmdate('D, d M Y H:i:s',time()+86400) . ' GMT'); // 2 hours
		header('Accept-Ranges: bytes');
		header('Content-Type: image/png');
		
		if(false == ($im = @imagecreate(100, 100)))
			DevblocksPlatform::dieWithHttpError(null, 500);
		
		imagecolorallocate($im, $r_rand, $g_rand, $b_rand);
		$text_color = imagecolorallocate($im, 255, 255, 255);
		imagettftext($im, $font_size, 0, $x, $y, $text_color, $font, $text);
		imagepng($im, null, 1);
		imagedestroy($im);
		exit;
	}
}