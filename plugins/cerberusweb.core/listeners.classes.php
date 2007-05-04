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
            'tourHeaderMenu' => new DevblocksTourCallout('tourHeaderMenu','Helpdesk Menu','This is where you can change between major helpdesk sections.'),
            'tourHeaderMyTasks' => new DevblocksTourCallout('tourHeaderMyTasks','My Tasks','Here you can quickly jump to a summary of your current tasks.'),
            'tourHeaderTeamLoads' => new DevblocksTourCallout('tourHeaderTeamLoads','My Team Loads','Here you can quickly display the workload of any of your teams.  You can display a team\'s dashboard by clicking them.'),
            'tourHeaderGetTickets' => new DevblocksTourCallout('tourHeaderGetTickets','Get Tickets',"The 'Get Tickets' link will assign you available tickets from your desired teams."),
            'tourHeaderQuickLookup' => new DevblocksTourCallout('tourHeaderQuickLookup','Quick Lookup','Here you can quickly search for tickets from anywhere in the helpdesk.  This is generally most useful when someone calls up and you need to promptly locate their ticket.'),
            'tourDashboardActions' => new DevblocksTourCallout('tourDashboardActions','Dashboard Actions','This is where you may change your active dashboard.'),
            'tourDashboardViews' => new DevblocksTourCallout('tourDashboardViews','Ticket Lists','This is where your customized lists of tickets are displayed.'),
            'tourDashboardShortcuts' => new DevblocksTourCallout('tourDashboardShortcuts','Shortcuts','Here you may quickly perform multiple pre-defined actions to a list of tickets.  Use a shortcut if you\'re frequently using the same actions on different groups of tickets.'), 
            'tourDashboardBatch' => new DevblocksTourCallout('tourDashboardBatch','Batch Updates','Here you may perform multiple actions to any list of tickets.  Use a batch update for actions you use infrequently.'),
            'tourDisplayTasks' => new DevblocksTourCallout('tourDisplayTasks','Tasks','Tasks allow a team to share responsibilities with other teams or individual workers.  Here you can manage this ticket\'s tasks.'),
            'tourDisplayProperties' => new DevblocksTourCallout('tourDisplayProperties','Properties','This is where you can change the properties of the current ticket.'),
            'tourDisplayRequesters' => new DevblocksTourCallout('tourDisplayRequesters','Requesters','Situations often arise where your points-of-contact change.  These are the people who will currently receive updates about this ticket.'), 
            'tourDisplayConversation' => new DevblocksTourCallout('tourDisplayConversation','Conversation','This is where all e-mail replies will be displayed for this ticket.  Your responses will be sent to all requesters.'), 
            'tourConfigMaintPurge' => new DevblocksTourCallout('tourConfigMaintPurge','Purge Deleted','Here you may purge any deleted tickets from the database.'),
            'tourDashboardSearchCriteria' => new DevblocksTourCallout('tourDashboardSearchCriteria','Search Criteria','Here you can change the criteria of the current search.'),
            'tourConfigMenu' => new DevblocksTourCallout('tourConfigMenu','Menu','This is where you may choose to configure various components of the helpdesk.'),
            'tourConfigMailRouting' => new DevblocksTourCallout('tourConfigMailRouting','Mail Routing','This is where you instruct the helpdesk how to deliver new messages.'),
            'tourConfigExtensionsRefresh' => new DevblocksTourCallout('tourConfigExtensionsRefresh','Synchronize','This button will detect any plug-in changes.  Click this after installing or upgrading plug-ins.'),
            '' => new DevblocksTourCallout('',''),
        );
    }
    
    function run(DevblocksHttpResponse $response, Smarty $tpl) {
        $path = $response->path;
        $visit = CerberusApplication::getVisit();
        
        // [TODO] This should be more shared in the listener/parent
        if(!$visit || !$visit->get('TOUR_ENABLED',0))
            return;
        
        $callouts = CerberusApplication::getTourCallouts();
            
        switch(array_shift($path)) {
            case 'welcome':
		        $tour = array(
		            'title' => 'Welcome!',
		            'body' => "This assistant will help you become familiar with the helpdesk by following along and providing information about the current page.  You may follow the 'Points of Interest' links highlighted below to read tips about nearby functionality.",
		            'callouts' => array(
		                $callouts['tourHeaderMenu'],
		                $callouts['tourHeaderMyTasks'],
		                $callouts['tourHeaderTeamLoads'],
		                $callouts['tourHeaderGetTickets'],
		                $callouts['tourHeaderQuickLookup'],
		            )
		        );
                break;
                
            case "display":
		        $tour = array(
		            'title' => 'Display Ticket',
		            'body' => "This screen displays the currently selected ticket.  Here you can modify the ticket or send a new reply to all requesters.",
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
		            'title' => 'Preferences',
		            'body' => 'This screen allows you to change the personal preferences on your helpdesk account.',
		        );
                break;

            case "config":
                switch(array_shift($path)) {
                    default:
                    case NULL:
				        $tour = array(
				            'title' => 'Configuration',
				            'body' => 'This section is where you may modify the global configuration of the helpdesk.',
				            'callouts' => array(
				                $callouts['tourConfigMenu']
				            )
				        );
                        break;
                        
                    case "general":
				        $tour = array(
				            'title' => 'General Settings',
				            'body' => 'These settings control the overall behavior of the helpdesk.',
				        );
                        break;
                        
                    case "workflow":
				        $tour = array(
				            'title' => 'Team Configuration',
				            'body' => "Here you may create new helpdesk workers and organize them into teams.  Common teams often include departments (such as: Support, Sales, Development, Marketing, Billing, etc.) or various projects that warrant their own workloads.",
				        );
                        break;
                        
                    case "mail":
				        $tour = array(
				            'title' => 'Mail Configuration',
				            'body' => "This section controls the heart of your helpdesk: e-mail.  Here you may define the mailboxes to check for new messages, as well as the routing rules that determine what to do with those messages.  This is also where you set your preferences for sending mail out of the helpdesk.",
				            'callouts' => array(
				                $callouts['tourConfigMailRouting']
				            )
				        );
                        break;
                        
                    case "maintenance":
				        $tour = array(
				            'title' => 'Maintenance',
				            'body' => 'This section is dedicated to ensuring your helpdesk continues to operate lightly and quickly.',
				            'callouts' => array(
		                        $callouts['tourConfigMaintPurge'],
				            )
				        );
                        break;
                        
                    case "extensions":
				        $tour = array(
				            'title' => 'Extensions',
				            'body' => "This is where you may extend Cerberus Helpdesk by installing new functionality through plug-ins.",
				            'callouts' => array(
				                $callouts['tourConfigExtensionsRefresh'],
				            )
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
						            'body' => "Dashboards organize your ticket lists.  By default you have a personal dashboard and one for each team you are a member of.  You may also create your own flexible custom dashboards to adapt the helpdesk to your personal workflow.",
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
				            'title' => 'Searching Tickets',
				            'body' => '',
				            'callouts' => array(
				                $callouts['tourDashboardSearchCriteria']
				            )
				        );
                        break;
                        
                    case 'create':
				        $tour = array(
				            'title' => 'Creating Tickets',
				            'body' => '',
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