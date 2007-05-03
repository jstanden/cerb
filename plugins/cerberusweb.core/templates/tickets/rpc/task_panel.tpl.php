<table cellpadding="0" cellspacing="0" border="0" width="98%">
	<tr>
		<td align="left" width="0%" nowrap="nowrap" style="padding-right:5px;"><img src="{devblocks_url}images/hand_paper.gif{/devblocks_url}" align="absmiddle"></td>
		<td align="left" width="100%" nowrap="nowrap"><h1>Tasks</h1></td>
		<td align="right" width="0%" nowrap="nowrap"><form><input type="button" value=" X " onclick="genericPanel.hide();"></form></td>
	</tr>
</table>

<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formTaskPanel" name="formTaskPanel">
<input type="hidden" name="id" value="{$id}">
<input type="hidden" name="ticket_id" value="{$ticket->id}">
<input type="hidden" name="c" value="tickets">
<input type="hidden" name="a" value="saveTaskPanel">
<div style="height:250px;overflow:auto;border:1px solid rgb(180,180,180);margin:2px;padding:3px;">

<table cellspacing="0" cellpadding="2" width="100%">
	<tr>
		<td width="0%" nowrap="nowrap">Title:</td>
		<td width="100%">
			<input type="text" size="35" name="title" value="{$task->title|escape:"htmlall"}" style="width:98%;">
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" valign="top">Due: [<a href="javascript:;" onclick="toggleDiv('formTaskPanelDue');">?</a>]</td>
		<td width="100%">
			<input type="text" name="due_date" value="{$task->due_date|date_format}" size="24" style="width:98%;"><br>
			<div id="formTaskPanelDue" style="display:none;">
			<b>Date Examples:</b><br>
			<ul style="margin:0px;">
				<li>+3 days</li>
				<li>+1 month +2 hours</li>
				<li>next Friday</li>
				<li>January 9 2010</li>
			</ul>
			</div>
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap">Completed:</td>
		<td width="100%">
			<input type="checkbox" name="completed" value="1" {if $task->is_completed}checked{/if}>
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" valign="top">{$translate->_('task.comments')}:</td>
		<td width="100%">
			<textarea name="content" rows="5" cols="24" style="width:98%;">{if !is_null($task)}{$task->getContent()}{/if}</textarea>
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" valign="top">{$translate->_('task.owners')}:</td>
		<td width="100%">
			<b>Teams:</b><br>
			<select name="team_ids[]" style="width:98%;" size="5" multiple="multiple">
				{foreach from=$teams item=team key=team_id}
					<option value="{$team->id}" {if isset($owners->teams.$team_id)}selected{/if}>{$team->name}</option>
				{/foreach}
			</select>
			<br>
			(select multiple with the Control/Command key)
			<br>
			<br>
			<b>Workers:</b><br>
			<select name="worker_ids[]" style="width:98%;" size="5" multiple="multiple">
				{foreach from=$workers item=worker key=worker_id}
					<option value="{$worker->id}" {if isset($owners->workers.$worker_id)}selected{/if}>{$worker->getName()}</option>
				{/foreach}
			</select>
			<br>
			(select multiple with the Control/Command key)
		</td>
	</tr>
	
</table>

</div>

<input type="button" value="{$translate->_('common.save_changes')}" onclick="saveGenericAjaxPanel('formTaskPanel',true,displayAjax.reloadTicketTasks);"> 
{if !empty($id)}<label><input type="checkbox" name="delete" value="1"> {$translate->_('common.remove')|capitalize}</label>{/if}
<br>
</form>