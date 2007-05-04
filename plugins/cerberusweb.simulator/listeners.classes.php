<?php
class ChSimulatorTour extends DevblocksHttpResponseListenerExtension implements IDevblocksTourListener {
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
            case "simulator":
		        $tour = array(
		            'title' => 'Simulator',
		            'body' => "With the Simulator you can create any number of high-quality sample tickets, which allows you to immediately experiment with how the helpdesk works. Sample tickets may be created in various \"flavors\", such as Retail or Spam.  These flavors allow you to test your FAQ, e-mail templates and anti-spam filtering.",
		        );
                break;
        }
        
        if(!empty($tour))
            $tpl->assign('tour', $tour);
    }
}
?>