<?php
if (class_exists('Extension_ResearchTab')):
class WgmGoogleCSEResearchTab extends Extension_ResearchTab {
	
	function __construct($manifest) {
		parent::__construct($manifest);
	}
	
	function showTab() {
		$translate = DevblocksPlatform::getTranslationService();

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl_path = dirname(dirname(__FILE__)) . '/templates/';
		$tpl->assign('path', $tpl_path);
		
//		$tpl->assign('response_uri', 'research/fnr');
		
		$tpl->display($tpl_path . 'research_tab/index.tpl');		
	}
}
endif;

if (class_exists('Extension_ReplyToolbarItem',true)):
	class WgmGoogleCSEReplyToolbarButton extends Extension_ReplyToolbarItem {
		function render(CerberusMessage $message) { 
			$tpl = DevblocksPlatform::getTemplateService();
			$tpl_path = dirname(dirname(__FILE__)).'/templates/';
			$tpl->assign('path', $tpl_path);
			$tpl->cache_lifetime = "0";
			
			$tpl->assign('message', $message); /* @var $message CerberusMessage */
			
			$tpl->display('file:' . $tpl_path . 'renderers/reply_button.tpl');
		}
	};
endif;

class WgmGoogleCSEAjaxController extends DevblocksControllerExtension {
	private $_CORE_TPL_PATH = '';
	private $_TPL_PATH = '';

	function __construct($manifest) {
		$this->_CORE_TPL_PATH = DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/';
		$this->_TPL_PATH = dirname(dirname(__FILE__)) . '/templates/';
		parent::__construct($manifest);
	}
	
	function isVisible() {
		// check login
		$session = DevblocksPlatform::getSessionService();
		$visit = $session->getVisit();
		
		if(empty($visit)) {
			return false;
		} else {
			return true;
		}
	}
	
	/*
	 * Request Overload
	 */
	function handleRequest(DevblocksHttpRequest $request) {
		if(!$this->isVisible())
			return;
		
	    $path = $request->path;
		$controller = array_shift($path); // timetracking

	    @$action = DevblocksPlatform::strAlphaNumDash(array_shift($path)) . 'Action';

	    switch($action) {
	        case NULL:
	            // [TODO] Index/page render
	            break;
	            
	        default:
			    // Default action, call arg as a method suffixed with Action
				if(method_exists($this,$action)) {
					call_user_func(array(&$this, $action));
				}
	            break;
	    }
	}
	
	function showReplyPanelAction() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', $this->_TPL_PATH);

		$tpl->display('file:' . $this->_TPL_PATH . 'ajax/reply_panel.tpl');
	}
	
};