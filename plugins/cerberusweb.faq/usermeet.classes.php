<?php
class ChFaqApp extends Extension_UsermeetTool {
    function __construct($manifest) {
        parent::__construct($manifest);
        
        $filepath = realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR;
        
        CerberusClassLoader::registerClasses($filepath.'api/DAO.php',array(
            'DAO_Faq',
            'SearchFields_Faq'
        ));
        CerberusClassLoader::registerClasses($filepath.'api/Model.php',array(
            'Model_Faq'
        ));
    }
    
	public function writeResponse(DevblocksHttpResponse $response) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		
		$stack = $response->path;
		
		// Usermeet Session
		@$fingerprint = unserialize($_COOKIE['GroupLoginPassport']);
//		if(empty($fingerprint)) die("..."); // [TODO] Fix
        $tpl->assign('fingerprint', $fingerprint);

		switch(array_shift($stack)) {
		    case 'search':
		        @$q = DevblocksPlatform::importGPC($_POST['q']);
		        
		        $criteria = array();
		        
		        if(!empty($q))
                    $criteria[] = new DevblocksSearchCriteria(SearchFields_Faq::QUESTION,DevblocksSearchCriteria::OPER_LIKE,'%'.$q.'%');
		        
		        list($faqs, $faqs_count) = DAO_Faq::search(
		            $criteria,
				    10,
				    0
				);
				$tpl->assign('q', $q);
				$tpl->assign('results', $faqs);
				$tpl->assign('results_count', $faqs_count);
				
				$tpl->display('file:' . dirname(__FILE__) . '/templates/usermeet/faq/index.tpl.php');
		        break;
		        
		    default:
	            $faqs = DAO_Faq::getList();
	            $tpl->assign('faqs', $faqs);
		        $tpl->display('file:' . dirname(__FILE__) . '/templates/usermeet/faq/index.tpl.php');
		        break;
		}
	}
	
	function doAskAction() {
	    @$q = DevblocksPlatform::importGPC($_POST['q']);
	    
	    $fields = array(
	        DAO_Faq::QUESTION => $q,
	    );
	    $id = DAO_Faq::create($fields);

	    // Fire off a FAQ question event
	    $faq = DAO_Faq::get($id);
	    $eventMgr = DevblocksPlatform::getEventService();
	    $eventMgr->trigger(
	        new Model_DevblocksEvent(
	            'faq.question_asked',
                array(
                    'id' => $faq->id,
                    'question' => $faq->question,
                )
            )
	    );
	    
//	    DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse());
	}

	/**
	 * @param $instance Model_CommunityTool 
	 */
    public function configure($instance) {
        $tpl = DevblocksPlatform::getTemplateService();
        $tpl_path = realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR;
        
        
        $tpl->display("file:${tpl_path}usermeet/faq/config.tpl.php");
    }
    
    public function saveConfigurationAction() {
        
    }
	
};
?>