<h2>Setting up Workflow</h2>

<form action="index.php" method="POST">
<input type="hidden" name="step" value="{$smarty.const.STEP_WORKFLOW}">
<input type="hidden" name="form_submit" value="2">

This step will help you quickly create your initial workflow.  Once installed you may always add 
additional workers and teams from the Configuration page.  To skip a section simply 
leave it blank.<br>

{if !empty($worker_ids)}
<H3>Worker Details</H3>

<table cellpadding="0" cellspacing="0" border="0">
	<tr>
		<th>Worker</th>
		<th>First Name</th>
		<th>Last Name</th>
		<th>Title</th>
		<th>Admin?</th>
	</tr>
{foreach from=$worker_ids key=worker_id item=worker}
	<tr>
		<td style="padding-right:10px;"><input type="hidden" name="worker_ids[]" value="{$worker_id}">{$worker}</td>
		<td style="padding-right:10px;"><input type="text" name="worker_first[]"></td>
		<td style="padding-right:10px;"><input type="text" name="worker_last[]"></td>
		<td style="padding-right:10px;"><input type="text" name="worker_title[]"></td>
		<td align="center"><input type="checkbox" name="worker_superuser[]" value="1"></td>
	</tr>
{/foreach}
</table>
{/if}

<!--
{if !empty($mailbox_ids)}
<H3>Mailbox Details</H3>

<table cellpadding="0" cellspacing="0" border="0">
	<tr>
		<th>Mailbox</th>
		<th>Reply As E-mail Address</th>
	</tr>
{foreach from=$mailbox_ids key=mailbox_id item=mailbox}
	<tr>
		<td style="padding-right:10px;"><input type="hidden" name="mailbox_ids[]" value="{$mailbox_id}">{$mailbox}</td>
		<td><input type="text" name="mailbox_from[]" size="45" value="{$default_reply_from}"></td>
	</tr>
{/foreach}
</table>
{/if}
-->

{if !empty($team_ids)}
<H3>Team Details</H3>

<table cellpadding="0" cellspacing="0" border="0">
	<tr>
		<th>Team</th>
		<th>Members</th>
	</tr>
{foreach from=$team_ids key=team_id item=team_name}
	<tr>
		<td valign="top" style="padding-right:10px;"><input type="hidden" name="team_ids[]" value="{$team_id}">{$team_name}</td>
		<td valign="top" style="padding-right:10px;">
			{if !empty($worker_ids)}
			{foreach from=$worker_ids item=worker key=worker_id}
				<label><input type="checkbox" name="team_members_{$team_id}[]" value="{$worker_id}">{$worker}</label><br>
			{/foreach}
			{/if}
		</td>
	</tr>
	<tr>
		<td colspan="3">&nbsp;</td>
	</tr>
{/foreach}
</table>
{/if}

<br>
<input type="submit" value="Create Workflow &gt;&gt;">
</form>

<br>