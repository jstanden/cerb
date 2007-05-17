<?php
class ChFaqApp extends Extension_UsermeetTool {
    function __construct($manifest) {
        parent::__construct($manifest);
    }
    
	public function handleRequest(DevblocksHttpRequest $request) {
//	    echo "FAQ REQUEST";
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
        
        // Routing        
//	    array_shift($stack);
		
		switch(array_shift($stack)) {
		    default:
		        @$q = DevblocksPlatform::importGPC($_POST['q']);
		        
		        if(!empty($q)) {
		            list($faqs, $faqs_count) = DAO_Faq::search(
		                array(
		                    new DevblocksSearchCriteria(SearchFields_Faq::QUESTION,DevblocksSearchCriteria::OPER_LIKE,'%'.$q.'%')
		                ),
		                10,
		                0
		            );
		            $tpl->assign('q', $q);
		            $tpl->assign('results', $faqs);
		            $tpl->assign('results_count', $faqs_count);
		            
		        } else {
		            $faqs = DAO_Faq::getList();
		            $tpl->assign('faqs', $faqs);
		        }
		        
		        $tpl->display('file:' . dirname(__FILE__) . '/templates/usermeet/faq/index.tpl.php');
		        break;
		}
	}
};
?>