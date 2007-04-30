<?php
class ChCoreTour extends DevblocksHttpResponseListenerExtension implements IDevblocksTourListener {
    function __construct($manifest) {
        parent::__construct($manifest);
    }

    /**
     * @return DevblocksTourCallout[]
     */
    function registerCallouts() {
        return array(
            'tourDashboardActions' => new DevblocksTourCallout('tourDashboardActions','Dashboard Actions','This is where you may change your dashboard.'),
            'tourDashboardViews' => new DevblocksTourCallout('tourDashboardViews','Ticket Lists','This is where your customized lists of tickets are displayed.'),
            'tourDashboardShortcuts' => new DevblocksTourCallout('tourDashboardShortcuts','Shortcuts','Here you may quickly perform multiple pre-defined actions to a list of tickets.'), 
            'tourDashboardBatch' => new DevblocksTourCallout('tourDashboardBatch','Batch Updates','Here you may perform multiple actions to any list of tickets.'),
            'tourDisplayProperties' => new DevblocksTourCallout('tourDisplayProperties','Properties','This is where you can change the properties of the current ticket.'),
            'tourDisplayTasks' => new DevblocksTourCallout('tourDisplayTasks','Tasks','This is where you can view or modify the tasks associated with this ticket.'),
            'tourDisplayRequesters' => new DevblocksTourCallout('tourDisplayRequesters','Requesters','These are the people who will receive updates about this ticket.'), 
            'tourDisplayConversation' => new DevblocksTourCallout('tourDisplayConversation','Conversation','This is where all e-mail replies will be displayed for this ticket.'), 
            'tourConfigMaintPurge' => new DevblocksTourCallout('tourConfigMaintPurge','Purge Deleted','Here you may purge any deleted tickets from the database.'),
            '' => new DevblocksTourCallout('',''),
        );
    }
    
    function run(DevblocksHttpResponse $response, Smarty $tpl) {
        $path = $response->path;
        $visit = CerberusApplication::getVisit();
        
        if(!$visit || !$visit->get('TOUR_ENABLED',0))
            return;
        
        $callouts = CerberusApplication::getTourCallouts();
            
        switch(array_shift($path)) {
            case "display":
		        $tour = array(
		            'title' => 'Display Ticket',
		            'callouts' => array(
		                $callouts['tourDisplayProperties'],
		                $callouts['tourDisplayTasks'],
		                $callouts['tourDisplayRequesters'],
		                $callouts['tourDisplayConversation'],
		            )
		        );
                break;

            case "preferences":
		        $tour = array(
		            'title' => 'Preferences'
		        );
                break;

            case "config":
                switch(array_shift($path)) {
                    default:
                    case NULL:
				        $tour = array(
				            'title' => 'Configuration',
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
				            'title' => 'Maintenance',
				            'callouts' => array(
		                        $callouts['tourConfigMaintPurge'],
				            )
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
						            'title' => 'Dashboards',
						            'callouts' => array(
		                                $callouts['tourDashboardActions'],
		                                $callouts['tourDashboardViews'],
		                                $callouts['tourDashboardShortcuts'],
		                                $callouts['tourDashboardBatch'],
						            )
						        );
                                break;
                            
//                            case 'team':
//						        $tour = array(
//						            'title' => 'Team Dashboards'
//						        );
//                                break;
//                                                    
//                            case 'my':
//						        $tour = array(
//						            'title' => 'My Tasks'
//						        );
//                                break;
                                                       
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