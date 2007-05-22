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
        $visit = CerberusApplication::getVisit();
        
        // [TODO] This should be more shared in the listener/parent
        if(!$visit || !$visit->get('TOUR_ENABLED',0))
            return;
        
        switch(array_shift($path)) {
            case NULL:
            case "faq":
		        $tour = array(
		            'title' => 'FAQ',
		            'body' => "The FAQ is your repository of Frequently-Asked-Questions.  This functionality ensures your team isn't answering the same questions multiple times.  It also provides a time-saving self-help resource for your customers.",
		        );
                break;
        }
        
        if(!empty($tour))
            $tpl->assign('tour', $tour);
    }
};

class UmFaqEventListener extends DevblocksEventListenerExtension {
    function __construct($manifest) {
        parent::__construct($manifest);
    }
    
    function handleEvent(Model_DevblocksEvent $event) {
        switch($event->id) {
            case 'faq.question_asked':
                echo "I listened to a question being asked ...<br>";
                break;
        }
    }
};
?>