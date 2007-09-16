<h2>Advanced Configuration</h2>

<form action="index.php" method="POST">
<input type="hidden" name="step" value="{$smarty.const.STEP_CATCHALL}">
<input type="hidden" name="form_submit" value="1">

<H3>Default Routing</H3>

Cerberus Helpdesk allows you to create flexible routing rules to 
determine how incoming mail is assigned to groups.<br>
<br>
In the event none of your routing rules specify a destination for a message 
you have two choices:<br>
<ul>
	<li>You may bounce the message back to the sender.</li>
	<li>You may assign a Default Group to catch unrouted mail.</li>
</ul>
<br>

<b>Which group should receive any unrouted mail?</b><br>
<select name="default_team_id">
	<option value="">-- None (Bounce) --
	{foreach from=$teams item=team key=team_id name=teams}
	<option value="{$team_id}" {if $team->name=="Dispatch"}selected{/if}>{$team->name}
	{/foreach}
</select>
<br>
<br>

<input type="submit" value="Continue &gt;&gt;">
</form>