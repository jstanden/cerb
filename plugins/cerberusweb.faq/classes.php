<?php

class ChFaqPage extends CerberusPageExtension {
	function __construct($manifest) {
		parent::__construct($manifest);

		$path = realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR;
		
		CerberusClassLoader::registerClasses($path. 'api/DAO.php', array(
		    'DAO_Faq'
		));
		
		CerberusClassLoader::registerClasses($path. 'api/Model.php', array(
		    'Model_Faq'
		));
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
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;

		list($faqs, $faqs_count) = DAO_Faq::search(
		    array(
		        new DevblocksSearchCriteria(SearchFields_Faq::IS_ANSWERED,DevblocksSearchCriteria::OPER_EQ,0)
		    ),
		    10,
		    0
		);
		
		$tpl->assign('faqs', $faqs);

		list($popular_faqs, $popular_faqs_count) = DAO_Faq::search(
		    array(
		        new DevblocksSearchCriteria(SearchFields_Faq::IS_ANSWERED,DevblocksSearchCriteria::OPER_EQ,1)
		    ),
		    10,
		    0
		);

		$tpl->assign('popular_faqs', $popular_faqs);
				
		$tpl->display('file:' . dirname(__FILE__) . '/templates/faq/index.tpl.php');
	}
	
	// Ajax
	function answer() {
	    @$id = intval(DevblocksPlatform::importGPC($_REQUEST['id'],'integer'));
	    @$question = DevblocksPlatform::importGPC($_REQUEST['question'],'string');
	    @$answer = DevblocksPlatform::importGPC($_REQUEST['answer'],'string');
	    @$delete = DevblocksPlatform::importGPC($_REQUEST['delete'],'integer');
	    
	    $worker = CerberusApplication::getActiveWorker();
	    
	    if(empty($question) || empty($worker)) {
	        echo ' ';
	        return;
	    }

	    if($delete) {
	        DAO_Faq::delete(array($id));
	        
	    } else {
		    $fields = array();
		    
		    if(!empty($question))
		        $fields[DAO_Faq::QUESTION] = $question;
		    
		    if(!empty($answer)) {
		        $fields[DAO_Faq::ANSWER] = $answer;
	            $fields[DAO_Faq::IS_ANSWERED] = 1;
	            $fields[DAO_Faq::ANSWERED_BY] = $worker->id;
		    }
	
		    if(empty($id)) {
		        $id = DAO_Faq::create($fields);
		    } else {
	            DAO_Faq::update($id, $fields);
		    }
	    }

//	    DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('faq')));
        echo ' ';
	}
	
	// Ajax
	function showFaqPanel() {
	    @$id = intval(DevblocksPlatform::importGPC($_REQUEST['id']));

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
	    
	    include_once(DEVBLOCKS_PATH . 'libs/markdown/markdown.php');
		$tpl->register_modifier('markdown','smarty_modifier_markdown');
		
		$faq = DAO_Faq::get($id);
		$tpl->assign('faq', $faq);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/faq/faq_panel.tpl.php');
	}
	
};


class ChDisplayFaq extends CerberusDisplayPageExtension {
	function __construct($manifest) {
		parent::__construct($manifest);
	}
	
	function render($ticket) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/ticket_fnr.tpl.php');
	}
	
	function renderBody($ticket) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		
		list($fnr_faqs, $fnr_faqs_count) = DAO_Faq::search(
		    array(
		        new DevblocksSearchCriteria(SearchFields_Faq::IS_ANSWERED,DevblocksSearchCriteria::OPER_EQ,1)
		    ),
		    5,
		    0
		);
		$tpl->assign('fnr_faqs', $fnr_faqs);
				
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/faq/index.tpl.php');
	}
}

?>