<?php
class ChResearchPage extends CerberusPageExtension {
	private $_TPL_PATH = '';
	
	function __construct($manifest) {
		$this->_TPL_PATH = dirname(dirname(dirname(__FILE__))) . '/templates/';
		parent::__construct($manifest);
	}
		
	function isVisible() {
		// check login
		$visit = CerberusApplication::getVisit();
		
		if(empty($visit)) {
			return false;
		} else {
			return true;
		}
	}
	
//	function getActivity() {
		//return new Model_Activity('activity.kb');
//	}
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);
		
		$visit = CerberusApplication::getVisit();
		$translate = DevblocksPlatform::getTranslationService();
		
		$response = DevblocksPlatform::getHttpResponse();
		$tpl->assign('request_path', implode('/',$response->path));

		$stack = $response->path;
		array_shift($stack); // research
		
		$tab_manifests = DevblocksPlatform::getExtensions('cerberusweb.research.tab', false);
		uasort($tab_manifests, create_function('$a, $b', "return strcasecmp(\$a->name,\$b->name);\n"));
		$tpl->assign('tab_manifests', $tab_manifests);
		
		@$tab_selected = array_shift($stack);
		if(empty($tab_selected)) $tab_selected = '';
		$tpl->assign('tab_selected', $tab_selected);
		
		$tpl->display('file:' . $this->_TPL_PATH . 'research/index.tpl');
	}
	
	// Ajax
	function showTabAction() {
		@$ext_id = DevblocksPlatform::importGPC($_REQUEST['ext_id'],'string','');
		
		if(null != ($tab_mft = DevblocksPlatform::getExtension($ext_id)) 
			&& null != ($inst = $tab_mft->createInstance()) 
			&& $inst instanceof Extension_ResearchTab) {
			$inst->showTab();
		}
	}

};