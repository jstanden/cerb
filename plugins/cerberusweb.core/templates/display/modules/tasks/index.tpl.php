{if !empty($tasks)}
<table cellpadding="2" cellspacing="0" width="100%">
	<tr style="background-color:rgb(240,240,240);">
		<td style="border-bottom:1px solid rgb(200,200,200);border-right:1px solid rgb(200,200,200);" align="center"><b>{$translate->_('task.complete')}</b></td>
		<td style="border-bottom:1px solid rgb(200,200,200);border-right:1px solid rgb(200,200,200);"><b>{$translate->_('common.task')}</b></td>
		<td style="border-bottom:1px solid rgb(200,200,200);" align="center"><b>{$translate->_('task.due_date')}</b></td>
	</tr>

	{foreach from=$tasks item=task name=tasks key=task_id}
	<tr>
		<td width="0%" nowrap="nowrap" style="border-right:1px solid rgb(220,220,220);border-bottom:1px solid rgb(220,220,220);" valign="top" align="center">
		{if $task->is_completed}
			<b>X</b>
		{else}
			&nbsp;
		{/if}
		</td>
		<td width="100%" valign="top" style="border-right:1px solid rgb(220,220,220);border-bottom:1px solid rgb(220,220,220);">
			<a href="javascript:;" onclick="genericAjaxPanel('c=tickets&a=showTaskPanel&id={$task->id}&ticket_id={$ticket->id}',this,true,'400px');" class="ticketLink"><b style="{if $task->is_completed}text-decoration:line-through;{/if}">{$task->title}</b></a>
			{if !$task->is_completed}
				<br>
				<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/spacer.gif{/devblocks_url}" height="3" width="1"><br>
				{assign var=owners value=$task_owners.$task_id}
				{foreach from=$owners->teams item=owner name=owners}
					<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/businessmen.gif{/devblocks_url}" border="0" align="top">
					<a href="javascript:;">{$owner->name}</a>
				{/foreach}
				{foreach from=$owners->workers item=owner name=owners}
					<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/user_headset.gif{/devblocks_url}" border="0" align="top">
					<a href="javascript:;">{$owner->getName()}</a>
				{/foreach}
			{/if}
		</td>
		<td width="0%" nowrap="nowrap" style="border-bottom:1px solid rgb(220,220,220);" valign="top" align="right">
		{if $task->is_completed}
		--
		{else}
			{$task->due_date|date_format}
		{/if}
		</td>
	</tr>
	{/foreach}
</table>
{else}
	No tasks exist for this ticket.
{/if}
[ <a href="javascript:;" onclick="genericAjaxPanel('c=tickets&a=showTaskPanel&id=0&ticket_id={$ticket->id}',this,true,'400px');">add task</a> ]
