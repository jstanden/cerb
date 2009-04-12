<?php
class UmScHomeController extends Extension_UmScController {
	const PARAM_HOME_HTML = 'home.html';
	
	function isVisible() {
		// Disable if we have no content to show 
		$sHomeHtml = DAO_CommunityToolProperty::get($this->getPortal(),self::PARAM_HOME_HTML, '');
		return !empty($sHomeHtml);
	}
	
	function writeResponse(DevblocksHttpResponse $response) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = dirname(dirname(dirname(dirname(__FILE__)))) . '/templates/';
		
		$sHomeHtml = DAO_CommunityToolProperty::get($this->getPortal(),self::PARAM_HOME_HTML, '');
		$tpl->assign('home_html', $sHomeHtml);
		
		$tpl->display("file:${tpl_path}portal/sc/internal/home/index.tpl");
	}
	
	function configure() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = dirname(dirname(dirname(dirname(__FILE__)))) . '/templates/';

        $sHomeHtml = DAO_CommunityToolProperty::get($this->getPortal(),self::PARAM_HOME_HTML, '');
        $tpl->assign('home_html', $sHomeHtml);
		
		$tpl->display("file:${tpl_path}portal/sc/config/module/home.tpl");
	}
	
	function saveConfiguration() {
        // Home 
        @$sHomeHtml = DevblocksPlatform::importGPC($_POST['home_html'],'string','');
        
		DAO_CommunityToolProperty::set($this->getPortal(), self::PARAM_HOME_HTML, $sHomeHtml);
	}
	
};