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
$fields = array(
	'columns' => serialize(array(
		't.mask',
		't.status',
		't.priority',
		't.last_wrote',
		't.updated_date'
	))
);

$view_id = CerberusDashboardDAO::createView("My Tickets",$dashboardId);
CerberusDashboardDAO::updateView($view_id,$fields);

$view_id = CerberusDashboardDAO::createView("Suggested Tickets",$dashboardId);
CerberusDashboardDAO::updateView($view_id,$fields);

// Agents
CerberusAgentDAO::createAgent('superuser','superuser',1);

?>