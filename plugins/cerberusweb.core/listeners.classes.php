<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2007, WebGroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Cerberus Public License.
| The latest version of this license can be found here:
| http://www.cerberusweb.com/license.php
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerberusweb.com	  http://www.webgroupmedia.com/
***********************************************************************/
/*
 * IMPORTANT LICENSING NOTE from your friends on the Cerberus Helpdesk Team
 * 
 * Sure, it would be so easy to just cheat and edit this file to use the 
 * software without paying for it.  But we trust you anyway.  In fact, we're 
 * writing this software for you! 
 * 
 * Quality software backed by a dedicated team takes money to develop.  We 
 * don't want to be out of the office bagging groceries when you call up 
 * needing a helping hand.  We'd rather spend our free time coding your 
 * feature requests than mowing the neighbors' lawns for rent money. 
 * 
 * We've never believed in encoding our source code out of paranoia over not 
 * getting paid.  We want you to have the full source code and be able to 
 * make the tweaks your organization requires to get more done -- despite 
 * having less of everything than you might need (time, people, money, 
 * energy).  We shouldn't be your bottleneck.
 * 
 * We've been building our expertise with this project since January 2002.  We 
 * promise spending a couple bucks [Euro, Yuan, Rupees, Galactic Credits] to 
 * let us take over your shared e-mail headache is a worthwhile investment.  
 * It will give you a sense of control over your in-box that you probably 
 * haven't had since spammers found you in a game of "E-mail Address 
 * Battleship".  Miss. Miss. You sunk my in-box!
 * 
 * A legitimate license entitles you to support, access to the developer 
 * mailing list, the ability to participate in betas and the warm fuzzy 
 * feeling of feeding a couple obsessed developers who want to help you get 
 * more done than 'the other guy'.
 *
 * - Jeff Standen, Mike Fogg, Brenan Cavish, Darren Sugita, Dan Hildebrandt
 * 		and Joe Geck.
 *   WEBGROUP MEDIA LLC. - Developers of Cerberus Helpdesk
 */
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
        'tourDashboardBatch' => new DevblocksTourCallout('tourDashboardBatch','Bulk Updates','Here you may perform multiple actions to any list of tickets.  Use a bulk update for actions you use infrequently.'),
        'tourDisplayProperties' => new DevblocksTourCallout('tourDisplayProperties','Properties','This is where you can change the properties of the current ticket.'),
        'tourDisplayManageRecipients' => new DevblocksTourCallout('tourDisplayManageRecipients','Recipients','Situations often arise where your points-of-contact change.  These are the people who will currently receive updates about this ticket.'),
        'tourDisplayContactHistory' => new DevblocksTourCallout('tourDisplayContactHistory','Contact History','All of your previous conversations with this customer are a click away.'),
        'tourDisplayConversation' => new DevblocksTourCallout('tourDisplayConversation','Conversation','This is where all e-mail replies will be displayed for this ticket.  Your responses will be sent to all requesters.'),
        'tourConfigMaintPurge' => new DevblocksTourCallout('tourConfigMaintPurge','Purge Deleted','Here you may purge any deleted tickets from the database.'),
        'tourDashboardSearchCriteria' => new DevblocksTourCallout('tourDashboardSearchCriteria','Search Criteria','Here you can change the criteria of the current search.'),
        'tourConfigMenu' => new DevblocksTourCallout('tourConfigMenu','Menu','This is where you may choose to configure various components of the helpdesk.'),
        'tourConfigMailRouting' => new DevblocksTourCallout('tourConfigMailRouting','Mail Routing','This is where you instruct the helpdesk how to deliver new messages.'),
        '' => new DevblocksTourCallout('',''),
        );
    }

    function run(DevblocksHttpResponse $response, Smarty $tpl) {
        $path = $response->path;

        $callouts = CerberusApplication::getTourCallouts();

        switch(array_shift($path)) {
            case 'welcome':
                $tour = array(
                'title' => 'Welcome!',
                'body' => "This assistant will help you become familiar with the helpdesk by following along and providing information about the current page.  You may follow the 'Points of Interest' links highlighted below to read tips about nearby functionality.",
                'callouts' => array(
                $callouts['tourHeaderMenu'],
                )
                );
                break;

            case "display":
                $tour = array(
                'title' => 'Display Ticket',
                'body' => "This screen displays the currently selected ticket.  Here you can modify the ticket or send a new reply to all requesters.",
                'callouts' => array(
                $callouts['tourDisplayProperties'],
                $callouts['tourDisplayManageRecipients'],
                $callouts['tourDisplayContactHistory'],
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
                        )
                        );
                        break;
                    case "jobs":
                        $tour = array(
                        'title' => 'Scheduler',
                        'body' => "The scheduler is where you can set up tasks that will periodically run behind-the-scenes.",
                        'callouts' => array(
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
};

class ChCoreEventListener extends DevblocksEventListenerExtension {
    function __construct($manifest) {
        parent::__construct($manifest);
    }

    /**
     * @param Model_DevblocksEvent $event
     */
    function handleEvent(Model_DevblocksEvent $event) {
        // Cerberus Helpdesk Workflow
        switch($event->id) {
            case 'ticket.property.changed':
	            $this->_handleTicketMoved($event);
                break;

            case 'cron.heartbeat':
            	$this->_handleCronHeartbeat($event);
	            break;
        }
    }

    private function _handleCronHeartbeat($event) {
    	// Re-open any conversations past their 'reopen at' due time
		$fields = array(
			DAO_Ticket::IS_CLOSED => 0,
			DAO_Ticket::DUE_DATE => 0
		);
		$where = sprintf("%s = %d AND %s > 0 AND %s < %d",
			DAO_Ticket::IS_CLOSED,
			CerberusTicketStatus::CLOSED,
			DAO_Ticket::DUE_DATE,
			DAO_Ticket::DUE_DATE,
			time()
		);
		DAO_Ticket::updateWhere($fields, $where);
    }
    
    private function _handleTicketMoved($event) {
        @$ticket_ids = $event->params['ticket_ids'];
        @$changed_fields = $event->params['changed_fields'];
        
        if(!isset($changed_fields[DAO_Ticket::TEAM_ID]) 
        	|| !isset($changed_fields[DAO_Ticket::CATEGORY_ID]))
        	return;
        
        @$team_id = $changed_fields[DAO_Ticket::TEAM_ID];
        @$bucket_id = $changed_fields[DAO_Ticket::CATEGORY_ID];

        //============ Check Team Inbox Rules ================
        if(!empty($ticket_ids) && !empty($team_id) && empty($bucket_id)) { // moving to an inbox
            // [JAS]: Build hashes for our event ([TODO] clean up)
			$tickets = DAO_Ticket::getTickets($ticket_ids);
                
            $from_ids = array();
            foreach($tickets as $ticket) { /* @var $ticket CerberusTicket */
                $from_ids[$ticket->id] = $ticket->first_wrote_address_id;
            }
            
            $from_addresses = DAO_Address::getWhere(
            	sprintf("%s IN (%s)",
            		DAO_Address::ID,
            		implode(',', $from_ids)
            ));
            unset($from_ids);

            foreach($tickets as $ticket_id => $ticket) {
                $rule = CerberusApplication::parseTeamRules($team_id, $ticket_id, @$from_addresses[$ticket->first_wrote_address_id], $ticket->subject);
            }
            unset($from_addresses);
        }
        
    }
};
?>