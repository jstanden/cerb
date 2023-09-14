<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2019, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerb.ai/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://cerb.ai	    http://webgroup.media
***********************************************************************/

class Controller_Avatars extends DevblocksControllerExtension {
	/*
	 * Request Overload
	 */
	function handleRequest(DevblocksHttpRequest $request) {
		$translate = DevblocksPlatform::getTranslationService();
		
		$stack = $request->path; // URLS like: /avatars/worker/1
		array_shift($stack); // avatars
		
		$alias = array_shift($stack) ?? null; // worker
		$avatar_context_id = array_shift($stack) ?? null; // 1
		
		// Security
		if(null == CerberusApplication::getActiveWorker()) {
			if($request->is_ajax) {
				DevblocksPlatform::dieWithHttpError(null, 401);
			} else {
				DevblocksPlatform::dieWithHttpError($translate->_('common.access_denied'), 403);
			}
		}

		if ($alias == '_fetch') {
			$url = DevblocksPlatform::importGPC($_REQUEST['url'] ?? null, 'string', '');
			$this->_fetchImageFromUrl($url);
			return;
		}
		
		$contexts = Extension_DevblocksContext::getAll(false);
		$avatar_context_mft = null;

		// Allow full context extension IDs
		if(isset($contexts[$alias]))
			$avatar_context_mft = $contexts[$alias];
		
		// Look up the context extension
		if(empty($alias) || (empty($avatar_context_mft) && !($avatar_context_mft = Extension_DevblocksContext::getByAlias($alias, false))))
			$this->_renderDefaultAvatar();
		
		// Is this a resource?
		if(CerberusContexts::isSameContext($avatar_context_mft->id, CerberusContexts::CONTEXT_RESOURCE)) {
			$this->_renderResource($avatar_context_id);
			return;
		}
		
		// Look up the avatar record
		if(($avatar = DAO_ContextAvatar::getByContext($avatar_context_mft->id, intval($avatar_context_id)))) {
			$this->_renderAvatar($avatar);
			return;
		}
		
		$this->_renderDefaultAvatar($avatar_context_mft->id, $avatar_context_id);
	}
	
	private function _renderAvatar(Model_ContextAvatar $avatar, $default_context=null, $default_context_id=null) {
		if(empty($default_context))
			$default_context = $avatar->context;
		if(empty($default_context_id))
			$default_context_id = $avatar->context_id;
		
		if(empty($avatar->content_type) 
				|| empty($avatar->storage_size) 
				|| empty($avatar->storage_key) 
				|| !($contents = Storage_ContextAvatar::get($avatar))) {
			$this->_renderDefaultAvatar($default_context, $default_context_id);
			return;
		}
		
		// Set headers
		header('Pragma: cache');
		header('Cache-control: max-age=86400', true); // 24 hours // , must-revalidate
		header('Expires: ' . gmdate('D, d M Y H:i:s',time()+86400) . ' GMT'); // 2 hours
		header('Accept-Ranges: bytes');

		header('Content-Type: ' . $avatar->content_type);
		header('Content-Length: ' . $avatar->storage_size);
		
		echo $contents;
		exit;
	}
	
	private function _fetchImageFromUrl($url) {
		$validation = DevblocksPlatform::services()->validation();
		$url_writer = DevblocksPlatform::services()->url();
		$response = array('status'=>true, 'imageData'=>null);
		
		try {
			if(empty($url))
				throw new DevblocksException("No URL provided");
		
			$error = null;
			$values = ['url' => $url];
			
			$validation
				->addField('url', 'URL')
				->url()
				->setRequired(true)
			;
			
			if(!$validation->validateAll($values, $error))
				throw new DevblocksException($error);
			
			$cerbUiImageUrl = $url_writer->write('c=ui&a=image', true);
			
			// Allow fetching images from the local ui/image endpoint
			if(DevblocksPlatform::strStartsWith($url, [$cerbUiImageUrl])) {
				$token = substr($url, strlen($cerbUiImageUrl) + 1);
				
				if (!($resource = DAO_AutomationResource::getByToken($token)))
					throw new DevblocksException("Invalid URL.");
				
				$info['content_type'] = $resource->mime_type;
				$output = $resource->getFileContents();
				
				// Verify the "magic bytes": 89 50 4E 47 0D 0A 1A 0A
				if ('89504e470d0a1a0a' != bin2hex(substr($output, 0, 8)))
					throw new DevblocksException("Invalid image.");
				
			} else {
				$ch = DevblocksPlatform::curlInit($url);
				$output = DevblocksPlatform::curlExec($ch);
				$info = curl_getinfo($ch);
				curl_close($ch);
			}
			
			// Make sure this is only image content
			if(substr(DevblocksPlatform::strLower($info['content_type']),0,6) != 'image/')
				throw new DevblocksException("Only image types may be fetched.");
			
			$response['status'] = true;
			$response['imageData'] = sprintf("data:%s;base64,", $info['content_type']) . base64_encode($output);
			unset($output);
			
		} catch(DevblocksException $e) {
			$response['status'] = false;
			$response['error'] = $e->getMessage();
			
		} catch(Exception $e) {
			$response['status'] = false;
			$response['error'] = 'An error occurred.';
		}
		
		header("Pragma: no-cache");
		header("Content-Type: application/json");
		
		echo json_encode($response);
		exit;
	}
	
	private function _renderResource($resource_name) : void {
		if(
			($resource = DAO_Resource::getByNameAndType($resource_name, ResourceType_Image::ID))
			&& ($content_data = $resource->getExtension()->getContentData($resource))
		) {
			$content_data->writeHeaders();
			$content_data->writeBody();
			return;
		}
		
		$this->_renderDefaultAvatar();
	}
	
	private function _renderDefaultAvatar($context=null, $context_id=null) {
		$avatar_default_style_contact = DevblocksPlatform::getPluginSetting('cerberusweb.core', CerberusSettings::AVATAR_DEFAULT_STYLE_CONTACT, CerberusSettingsDefaults::AVATAR_DEFAULT_STYLE_CONTACT);
		$avatar_default_style_worker = DevblocksPlatform::getPluginSetting('cerberusweb.core', CerberusSettings::AVATAR_DEFAULT_STYLE_WORKER, CerberusSettingsDefaults::AVATAR_DEFAULT_STYLE_WORKER);
		
		switch($context) {
			case CerberusContexts::CONTEXT_APPLICATION:
				$this->_renderFilePng(APP_PATH . '/features/cerberusweb.core/resources/images/avatars/app.png');
				break;
				
			// Check if the addy's org has an avatar
			case CerberusContexts::CONTEXT_ADDRESS:
				if($context_id && false != ($addy = DAO_Address::get($context_id))) {
					
					// Use contact if an avatar exists
					if($addy->contact_id && false != ($avatar = DAO_ContextAvatar::getByContext(CerberusContexts::CONTEXT_CONTACT, $addy->contact_id))) {
						$this->_renderAvatar($avatar, CerberusContexts::CONTEXT_CONTACT);
						return;
					}
					
					// Use org if an avatar exists (and no contact does)
					if(!$addy->contact_id && $addy->contact_org_id && false != ($avatar = DAO_ContextAvatar::getByContext(CerberusContexts::CONTEXT_ORG, $addy->contact_org_id))) {
						$this->_renderAvatar($avatar, CerberusContexts::CONTEXT_ORG);
						return;
					}
					
					// Use a default org picture (if no contact)
					if(!$addy->contact_id && $addy->contact_org_id) {
						$this->_renderDefaultAvatar(CerberusContexts::CONTEXT_ORG, $addy->contact_org_id);
						return;
					}
					
					// Use a default contact picture
					if($addy->contact_id) {
						$this->_renderDefaultAvatar(CerberusContexts::CONTEXT_CONTACT, $addy->contact_id);
						return;
					}

					// Display monograms by default
					$this->_renderMonogram(substr($addy->email,0,1), $context_id);
					return;
				}
				
				// Unknown ID
				$all_keys = array(1,2,3,4,5,6);
				$n = $all_keys[$context_id % 6];
				$this->_renderFilePng(APP_PATH . sprintf('/features/cerberusweb.core/resources/images/avatars/person%d.png', $n));
				break;
				
			case CerberusContexts::CONTEXT_CONTACT:
				$all_keys = [1,2,3,4,5,6];
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
				
				$this->_renderFilePng(APP_PATH . sprintf('/features/cerberusweb.core/resources/images/avatars/person%d.png', $n));
				break;
				
			case CerberusContexts::CONTEXT_ORG:
				$all_keys = array(1,2,3);
				$n = $all_keys[$context_id % 3];
				$this->_renderFilePng(APP_PATH . sprintf('/features/cerberusweb.core/resources/images/avatars/building%d.png', $n));
				break;
				
			case CerberusContexts::CONTEXT_WORKER:
				$all_keys = [1,2,3,4,5,6];
				$n = $all_keys[$context_id % 6];
				
				if($context_id && ($worker = DAO_Worker::get($context_id))) {
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
				
				$this->_renderFilePng(APP_PATH . sprintf('/features/cerberusweb.core/resources/images/avatars/person%d.png', $n));
				break;
			
			case CerberusContexts::CONTEXT_BUCKET:
				// Look up the avatar record
				if(
					($bucket = DAO_Bucket::get($context_id))
					&& ($avatar = DAO_ContextAvatar::getByContext(CerberusContexts::CONTEXT_GROUP, $bucket->group_id))
				) {
					$this->_renderAvatar($avatar);
					return;
				}
				
				$this->_renderFilePng(APP_PATH . '/features/cerberusweb.core/resources/images/avatars/convo.png');
				return;

			case CerberusContexts::CONTEXT_BOT:
				$this->_renderFilePng(APP_PATH . '/features/cerberusweb.core/resources/images/avatars/va.png');
				break;
				
			case CerberusContexts::CONTEXT_PACKAGE:
				$this->_renderFilePng(APP_PATH . '/features/cerberusweb.core/resources/images/avatars/package.png');
				break;
				
			case CerberusContexts::CONTEXT_GROUP:
				$this->_renderFilePng(APP_PATH . '/features/cerberusweb.core/resources/images/avatars/convo.png');
				break;
				
			case CerberusContexts::CONTEXT_TICKET:
				// Look up the avatar record
				if(
					false != ($ticket = DAO_Ticket::get($context_id))
					&& false != ($avatar = DAO_ContextAvatar::getByContext(CerberusContexts::CONTEXT_GROUP, $ticket->group_id))
				) {
					$this->_renderAvatar($avatar);
					return;
				}
				
				$this->_renderFilePng(APP_PATH . '/features/cerberusweb.core/resources/images/avatars/convo.png');
				return;
				
			default:
				$this->_renderFilePng(APP_PATH . '/features/cerberusweb.core/resources/images/avatars/va.png');
				break;
		}
	}
	
	private function _renderFilePng($file) {
		$base_path = realpath(APP_PATH . '/features/cerberusweb.core/resources/images/avatars/') . DIRECTORY_SEPARATOR;
		$file_path = realpath($file);
		
		if(!DevblocksPlatform::strStartsWith($file_path, $base_path))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$contents = file_get_contents($file_path);
		
		// Set headers
		header('Pragma: cache');
		header('Cache-control: max-age=86400', true); // 24 hours // , must-revalidate
		header('Expires: ' . gmdate('D, d M Y H:i:s',time()+86400) . ' GMT'); // 2 hours
		header('Accept-Ranges: bytes');
		
		header('Content-Type: image/png');
		header('Content-Length: ' . strlen($contents));
		
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
	}
};
