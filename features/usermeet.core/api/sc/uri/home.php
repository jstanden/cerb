<?php
class UmScHomeController extends Extension_UmScController {
	const PARAM_HOME_HTML = 'home.html';
	
	function isVisible() {
		return true;
	}
	
	function writeResponse(DevblocksHttpResponse $response) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->display("devblocks:usermeet.core:support_center/home/index.tpl:portal_" . UmPortalHelper::getCode());
	}
};