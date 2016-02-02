<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2015, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerberusweb.com/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerbweb.com	    http://www.webgroupmedia.com/
***********************************************************************/

class Controller_Portal extends DevblocksControllerExtension {
	const ID = 'core.controller.portal';
	
	/**
	 * @param DevblocksHttpRequest $request
	 * @return DevblocksHttpResponse $response
	 */
	function handleRequest(DevblocksHttpRequest $request) {
		$stack = $request->path;

		$tpl = DevblocksPlatform::getTemplateService();
		$umsession = ChPortalHelper::getSession();

		// Globals for Community Tool template scope
		$translate = DevblocksPlatform::getTranslationService();
		$tpl->assign('translate', $translate);
		
		array_shift($stack); // portal
		$code = array_shift($stack); // xxxxxxxx

		ChPortalHelper::setCode($code);
		
		// Routing

		if(null != (@$tool = DAO_CommunityTool::getByCode($code))) {
			$manifest = DevblocksPlatform::getExtension($tool->extension_id,false,true);
			
			if(DEVELOPMENT_MODE) {
				$tool = $manifest->createInstance();
			} else {
				@$tool = $manifest->createInstance();
			}
			
			if(!is_null($tool)) { /* @var $app Extension_UsermeetTool */
				$delegate_request = new DevblocksHttpRequest($stack);
				$delegate_request->csrf_token = $request->csrf_token;
				return $tool->handleRequest($delegate_request);
			}
		} else {
			DevblocksPlatform::dieWithHttpError("Tool not found.", 404);
		}
	}
	
	/**
	 * @param DevblocksHttpResponse $response
	 */
	function writeResponse(DevblocksHttpResponse $response) {
		$stack = $response->path;
		
		$tpl = DevblocksPlatform::getTemplateService();

		// Globals for Community Tool template scope
		$translate = DevblocksPlatform::getTranslationService();
		$tpl->assign('translate', $translate);
		
		array_shift($stack); // portal
		$code = array_shift($stack); // xxxxxxxx

		if(null != ($tool = DAO_CommunityTool::getByCode($code))) {
			// [TODO] Don't double instance any apps (add instance registry to ::getExtension?)
			$manifest = DevblocksPlatform::getExtension($tool->extension_id,false,true);
			if(null != ($tool = $manifest->createInstance())) { /* @var $app Extension_UsermeetTool */
				$tool->writeResponse(new DevblocksHttpResponse($stack));
			}
		} else {
			DevblocksPlatform::dieWithHttpError("Tool not found.", 404);
		}
	}
};

class ChPortalHelper {
	static private $_code = null;
	static private $_fingerprint = null;
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
		$fingerprint = self::getFingerprint();
		$session_id = md5($fingerprint['ip'] . self::getCode() . $fingerprint['local_sessid']);
		
		// Did we cache the lookup?
		if(!isset(self::$_sessions_cache[$session_id])) {
			$session = DAO_CommunitySession::get($session_id);
		
			// Cache it
			self::$_sessions_cache[$session_id] = $session;
		}
			
		return self::$_sessions_cache[$session_id];
	}
	
	public static function getFingerprint() {
		if(empty(self::$_fingerprint)) {
			@$sFingerPrint = DevblocksPlatform::importGPC($_COOKIE['GroupLoginPassport'],'string','');
			
			if(!empty($sFingerPrint))
				self::$_fingerprint = unserialize($sFingerPrint);
			
			if(empty(self::$_fingerprint)) {
				
				// [TODO] We don't need to be storing this in the cookie
				self::$_fingerprint = array(
					'browser' => @$_SERVER['HTTP_USER_AGENT'],
					'ip' => @$_SERVER['REMOTE_ADDR'],
					'local_sessid' => session_id(),
					'started' => time(),
				);
				
				setcookie(
					'GroupLoginPassport',
					serialize(self::$_fingerprint),
					0,
					'/',
					null,
					null,
					true
				);
			}
		}

		return self::$_fingerprint;
	}
};