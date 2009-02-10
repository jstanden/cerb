<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmDisplayRecipients">
<input type="hidden" name="c" value="display">
<input type="hidden" name="a" value="saveProperties">
<input type="hidden" name="ticket_id" value="{$ticket->id}">

<h2>Properties</h2>
<blockquote style="margin:10px;">
	<b>Subject:</b><br>
	<input type="text" name="subject" size="45" maxlength="255" value="{$ticket->subject|escape}" style="width:90%;"><br>
	<br>
	
	<b>{$translate->_('ticket.status')|capitalize}:</b><br>
	<label><input type="radio" name="closed" value="0" onclick="toggleDiv('ticketClosed','none');" {if !$ticket->is_closed && !$ticket->is_waiting}checked{/if}>{$translate->_('status.open')|capitalize}</label>
	<label><input type="radio" name="closed" value="2" onclick="toggleDiv('ticketClosed','block');" {if !$ticket->is_closed && $ticket->is_waiting}checked{/if}>{$translate->_('status.waiting')|capitalize}</label>
	<label><input type="radio" name="closed" value="1" onclick="toggleDiv('ticketClosed','block');" {if $ticket->is_closed && !$ticket->is_deleted}checked{/if}>{$translate->_('status.closed')|capitalize}</label>
	<label><input type="radio" name="closed" value="3" onclick="toggleDiv('ticketClosed','none');" {if $ticket->is_deleted}checked{/if}>{$translate->_('status.deleted')|capitalize}</label>
	<br>
	<br>
	
	<div id="ticketClosed" style="display:{if $ticket->is_waiting || ($ticket->is_closed && !$ticket->is_deleted)}block{else}none{/if};margin-left:10px;">
	<b>{$translate->_('display.reply.next.resume')}</b> {$translate->_('display.reply.next.resume_eg')}<br> 
	<input type="text" name="ticket_reopen" size="55" value="{if !empty($ticket->due_date)}{$ticket->due_date|devblocks_date}{/if}"><br>
	{$translate->_('display.reply.next.resume_blank')}<br>
	<br>
	</div>
	
	<b>Who should handle the next reply?</b><br> 
	<select name="next_worker_id" onchange="toggleDiv('ticketPropsUnlockDate',this.selectedIndex?'block':'none');">
		<option value="0" {if 0==$ticket->next_worker_id}selected{/if}>Anybody
		{foreach from=$workers item=worker key=worker_id name=workers}
			{if $worker_id==$active_worker->id}{assign var=next_worker_id_sel value=$smarty.foreach.workers.iteration}{/if}
			<option value="{$worker_id}" {if $worker_id==$ticket->next_worker_id}selected{/if}>{$worker->getName()}
		{/foreach}
	</select>&nbsp;
   	{if !empty($next_worker_id_sel)}
   		<button type="button" onclick="this.form.next_worker_id.selectedIndex = {$next_worker_id_sel};toggleDiv('ticketPropsUnlockDate','block');">me</button>
   		<button type="button" onclick="this.form.next_worker_id.selectedIndex = 0;toggleDiv('ticketPropsUnlockDate','none');">anybody</button>
   	{/if}
	<br>
	<br>
	
	<div id="ticketPropsUnlockDate" style="display:{if $ticket->next_worker_id}block{else}none{/if};margin-left:10px;">	
		<b>Allow anybody to handle the next reply after:</b> (e.g. "2 hours", "5pm", {*"Tuesday", "June 30", *}or leave blank to keep assigned)<br>  
		<input type="text" name="unlock_date" size="32" maxlength="255" value="{if $ticket->unlock_date}{$ticket->unlock_date|devblocks_date}{/if}">
		<button type="button" onclick="this.form.unlock_date.value='+2 hours';">+2 hours</button>
		<br>
		<br>
	</div>
</blockquote>

{* [TODO] Display by Group *}
<blockquote style="margin:10px;">
	<table cellpadding="2" cellspacing="1" border="0">
	{assign var=last_group_id value=-1}
	
	{foreach from=$ticket_fields item=f key=f_id}
	{assign var=field_group_id value=$f->group_id}
	{if $field_group_id == 0 || $field_group_id == $ticket->team_id}
		{assign var=show_submit value=1}
		{if $field_group_id != $last_group_id}
			<tr>
				<td colspan="2"><H2>{if $f->group_id==0}Global{else}{$groups.$field_group_id->name}{/if} Fields</H2></td>
			</tr>
		{/if}
			<tr>
				<td valign="top" width="1%" nowrap="nowrap">
					<input type="hidden" name="field_ids[]" value="{$f_id}">
					<b>{$f->name}:</b>
				</td>
				<td valign="top" width="99%">
					{* [TODO]: Filter by groups+global *}
					{if $f->type=='S'}
						<input type="text" name="field_{$f_id}" size="45" maxlength="255" value="{$ticket_field_values.$f_id|escape}"><br>
					{elseif $f->type=='N'}
						<input type="text" name="field_{$f_id}" size="45" maxlength="255" value="{$ticket_field_values.$f_id|escape}"><br>
					{elseif $f->type=='T'}
						<textarea name="field_{$f_id}" rows="4" cols="50" style="width:98%;">{$ticket_field_values.$f_id}</textarea><br>
					{elseif $f->type=='C'}
						<input type="checkbox" name="field_{$f_id}" value="1" {if $ticket_field_values.$f_id}checked{/if}><br>
					{elseif $f->type=='X'}
						{foreach from=$f->options item=opt}
						<label><input type="checkbox" name="field_{$f_id}[]" value="{$opt|escape}" {if $ticket_field_values.$f_id.$opt}checked="checked"{/if}> {$opt}</label><br>
						{/foreach}
					{elseif $f->type=='D'}
						<select name="field_{$f_id}">{* [TODO] Fix selected *}
							<option value=""></option>
							{foreach from=$f->options item=opt}
							<option value="{$opt|escape}" {if $opt==$ticket_field_values.$f_id}selected{/if}>{$opt}</option>
							{/foreach}
						</select><br>
					{elseif $f->type=='M'}
						<select name="field_{$f_id}[]" size="5" multiple="multiple">
							{foreach from=$f->options item=opt}
							<option value="{$opt|escape}" {if $ticket_field_values.$f_id.$opt}selected="selected"{/if}>{$opt}</option>
							{/foreach}
						</select><br>
						<i><small>(hold CTRL or COMMAND to select multiple options)</small></i>
					{elseif $f->type=='E'}
						<input type="text" name="field_{$f_id}" size="45" maxlength="255" value="{if !empty($ticket_field_values.$f_id)}{$ticket_field_values.$f_id|devblocks_date}{/if}"><button type="button" onclick="ajax.getDateChooser('dateCustom{$f_id}',this.form.field_{$f_id});">&nbsp;<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/calendar.gif{/devblocks_url}" align="top">&nbsp;</button>
						<div id="dateCustom{$f_id}" style="display:none;position:absolute;z-index:1;"></div>
					{/if}	
				</td>
			</tr>
		{assign var=last_group_id value=$f->group_id}
	{/if}
	{/foreach}
	</table>
</blockquote>

<h2>Send responses to:</h2>
<blockquote style="margin:10px;">
	{if !empty($requesters)}
		<table cellpadding="2" cellspacing="0" border="0">
		<tr>
			<td><b>E-mail</b></td>
			<td align="center"><b>{$translate->_('common.delete')|capitalize}</b></td>
		</tr>
		{foreach from=$requesters item=requester}
			<tr>
				<td align="left">{$requester->email}</td>
				<td align="center"><input type="checkbox" name="remove[]" value="{$requester->id}"></td>
			</tr>
		{/foreach}
		</table>
		<br>
	{/if}
	
	<b>Add more recipients:</b> (one e-mail address per line)<br>
	<textarea rows="3" cols="50" name="add"></textarea><br>
	
	<br>
	<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
</blockquote>

</form>