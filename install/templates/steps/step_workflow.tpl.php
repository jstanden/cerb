<h2>Setting up Workflow</h2>

<form action="index.php" method="POST">
<input type="hidden" name="step" value="{$smarty.const.STEP_WORKFLOW}">
<input type="hidden" name="form_submit" value="1">

This step will help you quickly create your initial workflow.  Once installed you may always add 
additional workers, teams and mailboxes from the Configuration page.  To skip a section simply 
leave it blank.<br>

<H3>Workers</H3>

Workers are your people, those in your organization who perform various roles through the helpdesk: 
answering e-mail, troubleshooting issues, contacting leads, and so on.  A single worker may belong 
to multiple teams -- such as a person who performs both Support and Sales roles.<br>
<br>
Set up workers by adding <b>one worker e-mail address per line</b> below.  We'll ask for additional 
details on the next step:<br>
<textarea rows="5" cols="50" name="workers"></textarea><br>

<H3>Mailboxes</H3>

Mailboxes are the primary container for tickets in the helpdesk, and are similar to folders in a 
traditional e-mail client.  Tickets in a mailbox are the responsibility of one or more teams.

Teams are groups of workers in a particular department or on a particular project.  How you define 
your teams is based on how you plan to use the helpdesk.  Many people find it helpful to start with 
a couple department-level teams (Support, Sales, Billing, Abuse) which are broken down into smaller 
groups as needed (by project, by location, by escalation).<br>
<br>
Set up mailboxes by adding <b>one mailbox name per line</b> below.  We'll ask for additional 
details on the next step:<br>
<textarea rows="5" cols="50" name="mailboxes"></textarea><br>

<H3>Teams</H3>

Teams are groups of workers and mailboxes.  How you define your teams will depend on you intend to use 
the helpdesk. Many people find it helpful to start with a couple department or project related teams 
(Support, Sales, Billing, Abuse) which are ultimately broken down into smaller groups as needed 
(by project, by location, by escalation).<br>
<br>
Set up teams by adding <b>one team name per line</b> below.  We'll ask for additional 
details on the next step:<br>
<textarea rows="5" cols="50" name="teams"></textarea><br>

<br>

<input type="submit" value="Create Workflow &gt;&gt;">
</form>

<br>