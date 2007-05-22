<?php
// [TODO] Local scope includes can go here too (rather than classload if never reused)

class UmForumsApp extends Extension_UsermeetTool {
    function __construct($manifest) {
        parent::__construct($manifest);
    }
    
	public function handleRequest(DevblocksHttpRequest $request) {
//	    echo "FORUM REQUEST";
	}
	
	public function writeResponse(DevblocksHttpResponse $response) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$realpath = realpath(dirname(__FILE__) . '/../templates/');
		$tpl->assign('path', $realpath);
		
		$stack = $response->path;
		
		// Usermeet Session
		@$fingerprint = unserialize($_COOKIE['GroupLoginPassport']);
		if(empty($fingerprint)) die("..."); // [TODO] Fix
        $tpl->assign('fingerprint', $fingerprint);
        
        // Routing        
	    array_shift($stack); // CERB

//        echo "FORUM RESPONSE";
	    
//		switch(array_shift($stack)) {
//		    default:
		        @$q = DevblocksPlatform::importGPC($_POST['q']);
//		        
		        if(!empty($q)) {
//		            list($faqs, $faqs_count) = DAO_Faq::search(
//		                array(
//		                    new DevblocksSearchCriteria(SearchFields_Faq::QUESTION,DevblocksSearchCriteria::OPER_LIKE,'%'.$q.'%')
//		                ),
//		                10,
//		                0
//		            );
		            $tpl->assign('q', $q);
//		            $tpl->assign('results', $faqs);
//		            $tpl->assign('results_count', $faqs_count);
//		            
//		        } else {
//		            $faqs = DAO_Faq::getList();
//		            $tpl->assign('faqs', $faqs);
		        }
//		        
		        $tpl->display('file:' . $realpath . '/forums/index.tpl.php');
//		        break;
//		}
	}

	/**
	 * @param $instance Model_CommunityTool 
	 */
    public function configure($instance) {
        $tpl = DevblocksPlatform::getTemplateService();
        $tpl_path = realpath(dirname(__FILE__) . '/..') . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR;
        
        
        
        $tpl->display("file:${tpl_path}forums/config.tpl.php");
    }
    
    public function saveConfigurationAction() {
        
    }
};
?>