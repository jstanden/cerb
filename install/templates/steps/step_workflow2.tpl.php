<h2>Setting up Workflow</h2>

<form action="index.php" method="POST">
<input type="hidden" name="step" value="{$smarty.const.STEP_WORKFLOW}">
<input type="hidden" name="form_submit" value="2">

This step will help you quickly create your initial workflow.  Once installed you may always add 
additional workers and groups from the Configuration page.  To skip a section simply 
leave it blank.<br>

{if !empty($worker_ids)}
<H3>Worker Details</H3>
By default workers will be automatically e-mailed a randomly-generated password.  Optionally you may directly set a worker's password (such as your own).<br>
<br>

<table cellpadding="0" cellspacing="5" border="0">
	<tr>
		<th>Worker</th>
		<th>First Name</th>
		<th>Last Name</th>
		<th>Title</th>
		<th>Password</th>
		<th>Admin</th>
	</tr>
{foreach from=$worker_ids key=worker_id item=worker name=workers}
	<tr>
		<td><input type="hidden" name="worker_ids[]" value="{$worker_id}">{$worker}</td>
		<td><input type="text" name="worker_first[]"></td>
		<td><input type="text" name="worker_last[]"></td>
		<td><input type="text" name="worker_title[]"></td>
		<td><input type="text" name="worker_pw[]" size="12"></td>
		<td align="center"><input type="radio" name="worker_superuser[]" value="{$worker_id}" {if $smarty.foreach.workers.first}checked{/if}></td>
	</tr>
{/foreach}
</table>
{/if}

{if !empty($team_ids)}
<H3>Group Details</H3>

<table cellpadding="0" cellspacing="0" border="0">
	<tr>
		<th>Group</th>
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