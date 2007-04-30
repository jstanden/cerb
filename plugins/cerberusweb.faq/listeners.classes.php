<?php
class ChFaqTour extends DevblocksHttpResponseListenerExtension {
    function __construct($manifest) {
        parent::__construct($manifest);
    }
    
    function run(DevblocksHttpResponse $response, Smarty $tpl) {
        $path = $response->path;
        
        switch(array_shift($path)) {
            case NULL:
            case "faq":
		        $tour = array(
		            'title' => 'FAQ'
		        );
                break;
        }
        
        if(!empty($tour))
            $tpl->assign('tour', $tour);
    }
}
?>