<?php
require(getcwd() . '/framework.config.php');
require(UM_PATH . '/libs/ump/UserMeetPlatform.class.php');
require(UM_PATH . '/api/CerberusApplication.class.php');

// Teams
CerberusApplication::createTeam('Support');
CerberusApplication::createTeam('Sales');
CerberusApplication::createTeam('Development');
CerberusApplication::createTeam('Marketing');

// Address
$address_id = CerberusContactDAO::createAddress('example.user@cerberusdemo.com');

// Mailboxes
CerberusApplication::createMailbox('Trial Keys',$address_id);
CerberusApplication::createMailbox('Leads',$address_id);
CerberusApplication::createMailbox('Hosting Support',$address_id);
CerberusApplication::createMailbox('Bugs',$address_id);
CerberusApplication::createMailbox('Wishlist',$address_id);

// Dashboards
$dashboardId = CerberusDashboardDAO::createDashboard("My Dashboard",1);

// Views
CerberusDashboardDAO::createView("My Tickets",$dashboardId);
CerberusDashboardDAO::createView("Suggested Tickets",$dashboardId);

//CerberusContactDAO::createAddress('support@localhost');

// Agents
CerberusAgentDAO::createAgent('superuser','superuser',1);


?>