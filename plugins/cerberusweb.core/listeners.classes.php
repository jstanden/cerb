<?php
class ChCoreTour extends DevblocksHttpResponseListenerExtension {
    function __construct($manifest) {
        parent::__construct($manifest);
    }
    
    function run(DevblocksHttpResponse $response, Smarty $tpl) {
        $path = $response->path;
        
        switch(array_shift($path)) {
            case "display":
		        $tour = array(
		            'title' => 'Display Ticket'
		        );
                break;

            case "config":
                switch(array_shift($path)) {
                    default:
                    case NULL:
				        $tour = array(
				            'title' => 'Configuration'
				        );
                        break;
                        
                    case "general":
				        $tour = array(
				            'title' => 'General Configuration'
				        );
                        break;
                        
                    case "workflow":
				        $tour = array(
				            'title' => 'Team Configuration'
				        );
                        break;
                        
                    case "mail":
				        $tour = array(
				            'title' => 'Mail Configuration'
				        );
                        break;
                        
                    case "maintenance":
				        $tour = array(
				            'title' => 'Maintenance'
				        );
                        break;
                        
                    case "extensions":
				        $tour = array(
				            'title' => 'Extensions'
				        );
                        break;
                }
                break;

            case NULL:
            case "tickets":
                switch(array_shift($path)) {
                    case NULL:
                    case 'dashboards':
                        
                        switch(array_shift($path)) {
                            default:
                            case NULL:
						        $tour = array(
						            'title' => 'Dashboards'
						        );
                                break;
                            
                            case 'team':
						        $tour = array(
						            'title' => 'Team Dashboards'
						        );
                                break;
                                                    
                            case 'my':
						        $tour = array(
						            'title' => 'My Tasks'
						        );
                                break;
                                                       
                        }
                        break;
                        
                    case 'search':
				        $tour = array(
				            'title' => 'Searching Tickets'
				        );
                        break;
                        
                    case 'create':
				        $tour = array(
				            'title' => 'Creating Tickets'
				        );
                        break;
                }
                break;
        }
        
        if(!empty($tour))
            $tpl->assign('tour', $tour);
    }
}
?>