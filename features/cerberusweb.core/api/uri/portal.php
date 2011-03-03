<?php
class Controller_Portal extends DevblocksControllerExtension {
    const ID = 'core.controller.portal';
    
	/**
	 * @param DevblocksHttpRequest $request 
	 * @return DevblocksHttpResponse $response 
	 */
	function handleRequest(DevblocksHttpRequest $request) {
		$stack = $request->path;

		$tpl = DevblocksPlatform::getTemplateService();

		// Globals for Community Tool template scope
		$translate = DevblocksPlatform::getTranslationService();
		$tpl->assign('translate', $translate);
		
		array_shift($stack); // portal
		$code = array_shift($stack); // xxxxxxxx

		ChPortalHelper::setCode($code);

        if(null != (@$tool = DAO_CommunityTool::getByCode($code))) {
	        // [TODO] Don't double instance any apps (add instance registry to ::getExtension?)
	        $manifest = DevblocksPlatform::getExtension($tool->extension_id,false,true);
            if(null != (@$tool = $manifest->createInstance())) { /* @var $app Extension_UsermeetTool */
	        	return $tool->handleRequest(new DevblocksHttpRequest($stack));
            }
        } else {
            die("Tool not found.");
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
            die("Tool not found.");
        }
	}
};

class ChPortalHelper {
	static private $_code = null; 
	
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
		return DAO_CommunitySession::get($session_id);
	}
	
	public static function getFingerprint() {
		$sFingerPrint = DevblocksPlatform::importGPC($_COOKIE['GroupLoginPassport'],'string','');
		$fingerprint = null;
		if(!empty($sFingerPrint)) {
			$fingerprint = unserialize($sFingerPrint);
		}
		return $fingerprint;
	}
};