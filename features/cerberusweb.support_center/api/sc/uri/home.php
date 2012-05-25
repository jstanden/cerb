<?php
class UmScHomeController extends Extension_UmScController {
	const PARAM_HOME_HTML = 'home.html';
	
	function isVisible() {
		return true;
	}
	
	function writeResponse(DevblocksHttpResponse $response) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->display("devblocks:cerberusweb.support_center:portal_" . ChPortalHelper::getCode() .":support_center/home/index.tpl");
	}
	
	function configure(Model_CommunityTool $instance) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->display("devblocks:cerberusweb.support_center::portal/sc/config/module/home.tpl");
	}	
};