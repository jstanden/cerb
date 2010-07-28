<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmTicketPeek" onsubmit="return false;">
<input type="hidden" name="c" value="tickets">
<input type="hidden" name="a" value="savePreview">
<input type="hidden" name="id" value="{$ticket->id}">
<input type="hidden" name="view_id" value="{$view_id}">

<div id="peekTabs">
	<ul>
		{if !$edit_mode}<li><a href="#ticketPeekMessage">Message</a></li>{/if}
		<li><a href="#ticketPeekProps">Properties</a></li>
	</ul>
		
	{if !$edit_mode}
    <div id="ticketPeekMessage">
			{assign var=headers value=$message->getHeaders()}
			<b>To:</b> {$headers.to|escape}<br>
			<b>From:</b> {$headers.from|escape}<br>
			<div id="ticketPeekContent" style="width:400;height:250px;overflow:auto;border:1px solid rgb(180,180,180);margin:2px;padding:3px;background-color:rgb(255,255,255);" ondblclick="genericAjaxPopupClose('peek');">
				<pre class="emailbody">{$content|trim|escape|devblocks_hyperlinks|devblocks_hideemailquotes}</pre>
			</div>
			
			<b>URL:</b> <a href="{devblocks_url}c=display&id={$ticket->mask}{/devblocks_url}">{devblocks_url full=true}c=display&id={$ticket->mask}{/devblocks_url}</a>
    </div>
	{/if}
	
    <div id="ticketPeekProps" style="display:none;">
		<table cellpadding="0" cellspacing="2" border="0" width="98%">
			<tr>
				<td width="0%" nowrap="nowrap" align="right">{$translate->_('ticket.status')|capitalize}: </td>
				<td width="100%">
					<label><input type="radio" name="closed" value="0" onclick="toggleDiv('ticketClosed','none');" {if !$ticket->is_closed && !$ticket->is_waiting}checked{/if}>{$translate->_('status.open')|capitalize}</label>
					<label><input type="radio" name="closed" value="2" onclick="toggleDiv('ticketClosed','block');" {if !$ticket->is_closed && $ticket->is_waiting}checked{/if}>{$translate->_('status.waiting')|capitalize}</label>
					{if $active_worker->hasPriv('core.ticket.actions.close') || ($ticket->is_closed && !$ticket->is_deleted)}<label><input type="radio" name="closed" value="1" onclick="toggleDiv('ticketClosed','block');" {if $ticket->is_closed && !$ticket->is_deleted}checked{/if}>{$translate->_('status.closed')|capitalize}</label>{/if}
					{if $active_worker->hasPriv('core.ticket.actions.delete') || ($ticket->is_deleted)}<label><input type="radio" name="closed" value="3" onclick="toggleDiv('ticketClosed','none');" {if $ticket->is_deleted}checked{/if}>{$translate->_('status.deleted')|capitalize}</label>{/if}
				</td>
			</tr>
			<tr>
				<td width="0%" nowrap="nowrap" align="right">Subject: </td>
				<td width="100%">
					<input type="text" name="subject" size="45" maxlength="255" value="{$ticket->subject|escape}">
				</td>
			</tr>
			
			{* Owners *}
			<tr>
				<td width="0%" nowrap="nowrap" valign="top" align="right">{$translate->_('common.owners')|capitalize}: </td>
				<td width="100%">
					<button type="button" class="chooser_worker"><span class="cerb-sprite sprite-add"></span></button>
					{if !empty($context_workers)}
					<ul class="chooser-container bubbles">
						{foreach from=$context_workers item=context_worker}
						<li>{$context_worker->getName()|escape}<input type="hidden" name="worker_id[]" value="{$context_worker->id}"><a href="javascript:;" onclick="$(this).parent().remove();"><span class="ui-icon ui-icon-trash" style="display:inline-block;width:14px;height:14px;"></span></a></li>
						{/foreach}
					</ul>
					{/if}
				</td>
			</tr>
			
			{if $active_worker->hasPriv('core.ticket.actions.move')}
			<tr>
				<td width="0%" nowrap="nowrap" align="right">Bucket: </td>
				<td width="100%">
					<select name="bucket_id">
					<option value="">-- move to --</option>
					{if empty($ticket->category_id)}{assign var=t_or_c value="t"}{else}{assign var=t_or_c value="c"}{/if}
					<optgroup label="Inboxes">
					{foreach from=$teams item=team}
						<option value="t{$team->id}">{$team->name}{if $t_or_c=='t' && $ticket->team_id==$team->id} (*){/if}</option>
					{/foreach}
					</optgroup>
					{foreach from=$team_categories item=categories key=teamId}
						{assign var=team value=$teams.$teamId}
						{if !empty($active_worker_memberships.$teamId)}
							<optgroup label="-- {$team->name} --">
							{foreach from=$categories item=category}
							<option value="c{$category->id}">{$category->name}{if $t_or_c=='c' && $ticket->category_id==$category->id} (current bucket){/if}</option>
							{/foreach}
							</optgroup>
						{/if}
					{/foreach}
					</select>
				</td>
			</tr>
			{/if}
			
			{if '' == $ticket->spam_training && $active_worker->hasPriv('core.ticket.actions.spam')}
			<tr>
				<td width="0%" nowrap="nowrap" align="right">Spam Training: </td>
				<td width="100%">
					<label><input type="radio" name="spam_training" value="" checked="checked"> Unknown</label>
					<label><input type="radio" name="spam_training" value="S"> Spam</label>
					<label><input type="radio" name="spam_training" value="N"> Not Spam</label> 
				</td>
			</tr>
			{/if}
		</table>
		
		<table cellpadding="2" cellspacing="1" border="0">
		{assign var=last_group_id value=-1}
		{foreach from=$custom_fields item=f key=f_id}
		{assign var=field_group_id value=$f->group_id}
		{if $field_group_id == 0 || $field_group_id == $ticket->team_id}
			{assign var=show_submit value=1}
			{if $field_group_id != $last_group_id}
				<tr>
					<td colspan="2" align="center"><b>{if $f->group_id==0}&nbsp;{else}{$groups.$field_group_id->name}{/if}</b></td>
				</tr>
			{/if}
				<tr>
					<td valign="top" width="1%" align="right" nowrap="nowrap">
						<input type="hidden" name="field_ids[]" value="{$f_id}">
						{$f->name}:
					</td>
					<td valign="top" width="99%">
						{if $f->type=='S'}
							<input type="text" name="field_{$f_id}" size="45" maxlength="255" value="{$custom_field_values.$f_id|escape}"><br>
						{elseif $f->type=='U'}
							<input type="text" name="field_{$f_id}" size="40" maxlength="255" value="{$custom_field_values.$f_id|escape}">
							{if !empty($custom_field_values.$f_id)}<a href="{$custom_field_values.$f_id|escape}" target="_blank">URL</a>{else}<i>(URL)</i>{/if}
						{elseif $f->type=='N'}
							<input type="text" name="field_{$f_id}" size="45" maxlength="255" value="{$custom_field_values.$f_id|escape}"><br>
						{elseif $f->type=='T'}
							<textarea name="field_{$f_id}" rows="4" cols="50" style="width:98%;">{$custom_field_values.$f_id}</textarea><br>
						{elseif $f->type=='C'}
							<input type="checkbox" name="field_{$f_id}" value="1" {if $custom_field_values.$f_id}checked{/if}><br>
						{elseif $f->type=='X'}
							{foreach from=$f->options item=opt}
							<label><input type="checkbox" name="field_{$f_id}[]" value="{$opt|escape}" {if isset($custom_field_values.$f_id.$opt)}checked="checked"{/if}> {$opt}</label><br>
							{/foreach}
						{elseif $f->type=='D'}
							<select name="field_{$f_id}">{* [TODO] Fix selected *}
								<option value=""></option>
								{foreach from=$f->options item=opt}
								<option value="{$opt|escape}" {if $opt==$custom_field_values.$f_id}selected{/if}>{$opt}</option>
								{/foreach}
							</select><br>
						{elseif $f->type=='M'}
							<select name="field_{$f_id}[]" size="5" multiple="multiple">
								{foreach from=$f->options item=opt}
								<option value="{$opt|escape}" {if isset($custom_field_values.$f_id.$opt)}selected="selected"{/if}>{$opt}</option>
								{/foreach}
							</select><br>
							<i><small>{$translate->_('common.tips.multi_select')}</small></i>
						{elseif $f->type=='E'}
							<input type="text" name="field_{$f_id}" size="35" maxlength="255" value="{if !empty($custom_field_values.$f_id)}{$custom_field_values.$f_id|devblocks_date}{/if}"><button type="button" onclick="devblocksAjaxDateChooser(this.form.field_{$f_id},'#dateCustom{$f_id}');">&nbsp;<span class="cerb-sprite sprite-calendar"></span>&nbsp;</button>
							<div id="dateCustom{$f_id}"></div>
						{elseif $f->type=='W'}
							{if empty($workers)}
								{$workers = DAO_Worker::getAllActive()}
							{/if}
							<select name="field_{$f_id}">
								<option value=""></option>
								{foreach from=$workers item=worker}
								<option value="{$worker->id}" {if $worker->id==$custom_field_values.$f_id}selected="selected"{/if}>{$worker->getName()}</option>
								{/foreach}
							</select>
						{/if}	
					</td>
				</tr>
			{assign var=last_group_id value=$f->group_id}
		{/if}
		{/foreach}
		</table>
		<br>
		
		<button type="button" onclick="genericAjaxPopupClose('peek', 'ticket_save');genericAjaxPost('frmTicketPeek', 'view{$view_id}');"><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')}</button>
    </div><!--tab2-->		
</div> 
<br>

</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open',function(event,ui) {
		$(this).dialog('option','title',"{$ticket->subject|escape}");
		$("#peekTabs").tabs();
		$("#ticketPeekContent").css('width','100%');
		$("#ticketPeekProps").show();
		$(this).focus();
	});
	$('#frmTicketPeek button.chooser_worker').each(function() {
		ajax.chooser(this, 'cerberusweb.contexts.worker', 'worker_id')
	});
</script>