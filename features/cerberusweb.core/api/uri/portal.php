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

class Controller_Portal extends DevblocksControllerExtension {
	const ID = 'core.controller.portal';
	
	/**
	 * @param DevblocksHttpRequest $request
	 * @return DevblocksHttpResponse $response
	 */
	function handleRequest(DevblocksHttpRequest $request) {
		$stack = $request->path;
		
		$tpl = DevblocksPlatform::services()->template();
		$url_writer = DevblocksPlatform::services()->url();
		
		// Globals for Community Tool template scope
		$translate = DevblocksPlatform::getTranslationService();
		$tpl->assign('translate', $translate);
		
		array_shift($stack); // portal
		$uri = array_shift($stack); // xxxxxxxx
		
		// Allow portal aliases
		
		if(false == ($tool = DAO_CommunityTool::getByCode($uri)))
			DevblocksPlatform::dieWithHttpError("Portal not found.", 404);
		
		$code = $tool->code;
		$request->path[1] = $code;
		
		ChPortalHelper::setCode($code);
		
		// Resource proxy
		if(current($stack) == 'resource') {
			$resource_request = new DevblocksHttpRequest($stack);
			$controller = new Controller_Resource();
			$controller->handleRequest($resource_request);
		}
		
		// Allow direct use of /portal URLs
		
		@$proxy_type = $_SERVER['HTTP_DEVBLOCKSPROXYTYPE'] ?: ($_SERVER['HTTP_DEVBLOCKSPROXYHOST'] ? 'portal' : 'app');
		
		switch($proxy_type) {
			case 'app':
				if(!isset($_SERVER['HTTP_DEVBLOCKSPROXYHOST'])) {
					$_SERVER['HTTP_DEVBLOCKSPROXYHOST'] = DevblocksPlatform::getHostname();
				}
				
				if(!isset($_SERVER['HTTP_DEVBLOCKSPROXYSSL'])) {
					$_SERVER['HTTP_DEVBLOCKSPROXYSSL'] = $url_writer->isSSL(false) ? 1 : 0;
				}
				
				if(!isset($_SERVER['HTTP_DEVBLOCKSPROXYBASE'])) {
					$_SERVER['HTTP_DEVBLOCKSPROXYBASE'] = rtrim($url_writer->writeNoProxy('c=portal&code=' . $tool->uri), '/');
				}
				break;
		}
		
		// Routing

		if(false == ($manifest = DevblocksPlatform::getExtension($tool->extension_id,false,true)))
			DevblocksPlatform::dieWithHttpError("Portal extension not found.", 404);
		
		if(DEVELOPMENT_MODE) {
			$tool = $manifest->createInstance();
		} else {
			@$tool = $manifest->createInstance();
		}
		
		if(!is_null($tool)) { /* @var $app Extension_CommunityPortal */
			$delegate_request = new DevblocksHttpRequest($stack);
			$delegate_request->csrf_token = $request->csrf_token;
			return $tool->handleRequest($delegate_request);
		}
	}
	
	/**
	 * @param DevblocksHttpResponse $response
	 */
	function writeResponse(DevblocksHttpResponse $response) {
		$stack = $response->path;
		
		$tpl = DevblocksPlatform::services()->template();

		// Globals for Community Tool template scope
		$translate = DevblocksPlatform::getTranslationService();
		$tpl->assign('translate', $translate);
		
		array_shift($stack); // portal
		$code = array_shift($stack); // xxxxxxxx

		if(null != ($tool = DAO_CommunityTool::getByCode($code))) {
			// [TODO] Don't double instance any apps (add instance registry to ::getExtension?)
			$manifest = DevblocksPlatform::getExtension($tool->extension_id,false,true);
			if(null != ($tool = $manifest->createInstance())) { /* @var $app Extension_CommunityPortal */
				$tool->writeResponse(new DevblocksHttpResponse($stack));
			}
		} else {
			DevblocksPlatform::dieWithHttpError("Tool not found.", 404);
		}
	}
};

class ChPortalHelper {
	static private $_code = null;
	static private $_session_id = null;
	static private $_sessions_cache = array();
	
	public static function getCode() {
		return self::$_code;
	}
	
	public static function setCode($code) {
		self::$_code = $code;
	}
	
	/**
	 * @return Model_CommunitySession
	 */
	public static function getSession() {
		$session_id = self::$_session_id;
		$url_writer = DevblocksPlatform::services()->url();
		
		if(empty(self::$_code))
			return false;
		
		if(empty($session_id)) {
			$cookie_name = 'CerbPortal' . self::$_code;
			@$session_id = DevblocksPlatform::importGPC($_COOKIE[$cookie_name],'string','');
			
			if(empty($session_id)) {
				$session_id = sha1(DevblocksPlatform::getClientIp() . self::getCode() . uniqid());
				setcookie($cookie_name, $session_id, 0, '/', null, $url_writer->isSSL(), true);
			}
			
			if(empty($session_id))
				return false;
			
			self::$_session_id = $session_id;
		}
		
		// Did we cache the lookup?
		if(!isset(self::$_sessions_cache[$session_id])) {
			$session = DAO_CommunitySession::get($session_id);
		
			// Cache it
			self::$_sessions_cache[$session_id] = $session;
		}
			
		return self::$_sessions_cache[$session_id];
	}
};