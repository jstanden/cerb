<?php
class ChFaqTour extends DevblocksHttpResponseListenerExtension implements IDevblocksTourListener {
    function __construct($manifest) {
        parent::__construct($manifest);
    }
    
    /**
     * @return DevblocksTourCallout[]
     */
    function registerCallouts() {
        return array(
            '' => new DevblocksTourCallout('',''),
        );
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