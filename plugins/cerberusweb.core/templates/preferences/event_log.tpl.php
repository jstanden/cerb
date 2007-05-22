{include file="$tpl_path/preferences/menu.tpl.php"}

<div class="block">
<h2>Event Log</h2>
<br>

<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="preferences">
<input type="hidden" name="a" value="saveNotifications">

<table border="0" cellspacing="0" cellpadding="5" width="100%">
<tr>
	<td><b>Time</b></td>
	<td><b>Event</b></td>
	<td><b>Log</b></td>
</tr>
{foreach from=$event_log item=event key=notify_id name=events}
	{assign var=event_id value=$event.n_event_id}
	{assign var=point value=$points.$event_id}
	<tr>
		<td nowrap="nowrap" width="0%" valign="top">{$event.n_created|date_format}</td>
		<td nowrap="nowrap" width="0%" valign="top">{$point->name}</td>
		<td width="100%" valign="top">{$event_strings.$notify_id}</td>
	</tr>
{/foreach}
</table>

</form>
</div>
