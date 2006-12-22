<table cellpadding="0" cellspacing="0" border="0" width="98%">
	<tr>
		<td align="left" width="0%" nowrap="nowrap"><img src="images/businessman2.gif" align="absmiddle"><img src="spacer.gif" width="5" height="1"></td>
		<td align="left" width="100%" nowrap="nowrap"><h1>Worker: {$agent->login}</h1></td>
		<td align="right" width="0%" nowrap="nowrap"><form><input type="button" value=" X " onclick="displayAjax.agentDialog.hide();"></form></td>
	</tr>
</table>
<form method="POST" action="javascript:;" id="agentForm">
	<input type="hidden" name="c" value="core.display.module.workflow">
	<input type="hidden" name="a" value="saveAgentDialog">
	<input type="hidden" name="id" value="{$agent->id}">
	
	<div style="height:100px;overflow:auto;background-color:rgb(255,255,255);border:1px solid rgb(230,230,230);margin:2px;padding:3px;">
		<b>Assigned Tickets:</b> 0<br>
		<b>Suggested Tickets:</b> 0<br>
		<b>Last Login:</b> 0000-00-00 00:00<br>
	</div>
	
	{if !empty($ticket_id)}
	<br>
	<input type="hidden" name="ticket_id" value="{$ticket_id}">
	<label><input type="checkbox" name="unassign" value="1"> Remove '{$agent->login}' from current ticket</label><br>
	{/if}
	
	<br>
	<input type="button" value="{$translate->say('common.save_changes')}" onclick="displayAjax.postShowAgent();">
</form>