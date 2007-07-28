<h2>Setting up Workflow</h2>

<form action="index.php" method="POST">
<input type="hidden" name="step" value="{$smarty.const.STEP_WORKFLOW}">
<input type="hidden" name="form_submit" value="1">

This step will help you quickly create your initial workflow.  Once installed you may always add 
additional workers and groups from the Configuration page.  To skip a section simply 
leave it blank.<br>

<H3>Workers</H3>

{if $failed && empty($workers_str)}
<div class="error">
Oops!  You must create at least one worker to continue.  How about yourself?
</div>
<br>
{/if}

Workers are your people, those in your organization who perform various roles through the helpdesk: 
answering e-mail, troubleshooting issues, contacting leads, and so on.  A single worker may belong 
to multiple groups -- such as a person who performs both Support and Sales roles.<br>
<br>
Set up workers by adding <b>one worker e-mail address per line</b> below.  We'll ask for additional 
details on the next step:<br>
<textarea rows="5" cols="50" name="workers">{$workers_str}</textarea><br>
<i>(Don't forget yourself!)</i><br>

<H3>Groups</H3>

{if $failed && empty($teams_str)}
<div class="error">
Oops!  You must create at least one group to continue.  How about Dispatch?
</div>
<br>
{/if}

How you define your groups will depend on how you intend to use 
the helpdesk. Many organizations find it helpful to start with a couple department or project related groups 
(Support, Sales, Billing, Abuse) which are ultimately broken down into smaller groups as needed 
(by project, location, escalation, etc).<br>
<br>
Set up groups by adding <b>one group name per line</b> below.  We'll ask for additional 
details on the next step:<br>
<textarea rows="5" cols="50" name="teams">{$teams_str}</textarea><br>

<br>

<input type="submit" value="Create Workflow &gt;&gt;">
</form>

<br>