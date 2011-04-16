<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmTicketPeek" onsubmit="return false;">
<input type="hidden" name="c" value="tickets">
<input type="hidden" name="a" value="savePreview">
<input type="hidden" name="id" value="{$ticket->id}">

<div id="peekTabs">
	<ul>
		{if !$edit_mode && !empty($message)}<li><a href="#ticketPeekMessage">{'common.messages'|devblocks_translate|capitalize}</a></li>{/if}
		<li><a href="#ticketPeekProps">{'common.properties'|devblocks_translate|capitalize}</a></li>
	</ul>
	{if !$edit_mode && !empty($message)}
    <div id="ticketPeekMessage">
			{assign var=headers value=$message->getHeaders()}
			<b>{'message.header.to'|devblocks_translate|capitalize}:</b> {$headers.to}<br>
			<b>{'message.header.from'|devblocks_translate|capitalize}:</b> {$headers.from}<br>
			<b>{'message.header.date'|devblocks_translate|capitalize}:</b> {$headers.date}<br>
			<div id="ticketPeekContent" style="width:400;height:250px;overflow:auto;border:1px solid rgb(180,180,180);margin:2px;padding:3px;background-color:rgb(255,255,255);" ondblclick="genericAjaxPopupClose('peek');">
				<pre class="emailbody">{$content|trim|escape|devblocks_hyperlinks|devblocks_hideemailquotes nofilter}</pre>
			</div>
			
			<div style="float:left;">
				<b>{'common.url'|devblocks_translate}:</b> <a href="{devblocks_url}c=display&id={$ticket->mask}{/devblocks_url}">{devblocks_url full=true}c=display&id={$ticket->mask}{/devblocks_url}</a>
			</div>
			
			{if !is_null($p) && !is_null($p_count)}
			<div style="float:right;">
				{if 0 != $p}<a href="javascript:;" onclick="genericAjaxPopup('peek','c=tickets&a=showPreview&view_id={$view_id}&tid={$ticket->id}&msgid={$ticket->first_message_id}', null, false, '650');">&lt;&lt;</a>{/if}
				{if isset($p_prev) && $p_prev}<a href="javascript:;" onclick="genericAjaxPopup('peek','c=tickets&a=showPreview&view_id={$view_id}&tid={$ticket->id}&msgid={$p_prev}', null, false, '650');">&lt;{'common.previous_short'|devblocks_translate|capitalize}</a>{/if}
				({$p+1} of {$p_count})
				{if isset($p_next) && $p_next}<a href="javascript:;" onclick="genericAjaxPopup('peek','c=tickets&a=showPreview&view_id={$view_id}&tid={$ticket->id}&msgid={$p_next}', null, false, '650');">{'common.next'|devblocks_translate|capitalize}&gt;</a>{/if}
				{if $p+1 != $p_count}<a href="javascript:;" onclick="genericAjaxPopup('peek','c=tickets&a=showPreview&view_id={$view_id}&tid={$ticket->id}&msgid={$ticket->last_message_id}', null, false, '650');">&gt;&gt;</a>{/if}
			</div>
			{/if}
			
			<br clear="all" style="clear:all;">
    </div>
	{/if}
	
    <div id="ticketPeekProps" style="display:none;">
		<fieldset>
			<legend>{'common.properties'|devblocks_translate|capitalize}</legend>
			
			<table cellpadding="0" cellspacing="2" border="0" width="98%">
				<tr>
					<td width="0%" nowrap="nowrap" valign="top" align="right">{$translate->_('ticket.status')|capitalize}: </td>
					<td width="100%">
						<label><input type="radio" name="closed" value="0" onclick="toggleDiv('ticketClosed','none');" {if !$ticket->is_closed && !$ticket->is_waiting}checked{/if}>{$translate->_('status.open')|capitalize}</label>
						<label><input type="radio" name="closed" value="2" onclick="toggleDiv('ticketClosed','block');" {if !$ticket->is_closed && $ticket->is_waiting}checked{/if}>{$translate->_('status.waiting')|capitalize}</label>
						{if $active_worker->hasPriv('core.ticket.actions.close') || ($ticket->is_closed && !$ticket->is_deleted)}<label><input type="radio" name="closed" value="1" onclick="toggleDiv('ticketClosed','block');" {if $ticket->is_closed && !$ticket->is_deleted}checked{/if}>{$translate->_('status.closed')|capitalize}</label>{/if}
						{if $active_worker->hasPriv('core.ticket.actions.delete') || ($ticket->is_deleted)}<label><input type="radio" name="closed" value="3" onclick="toggleDiv('ticketClosed','none');" {if $ticket->is_deleted}checked{/if}>{$translate->_('status.deleted')|capitalize}</label>{/if}
						
						<div id="ticketClosed" style="display:{if $ticket->is_closed || $ticket->is_waiting}block{else}none{/if};margin:5px 0px 5px 15px;">
							<b>{$translate->_('display.reply.next.resume')}:</b><br>
							<i>{$translate->_('display.reply.next.resume_eg')}</i><br>
							<input type="text" name="ticket_reopen" size="55" value="{if !empty($ticket->due_date)}{$ticket->due_date|devblocks_date}{/if}"><br>
							{$translate->_('display.reply.next.resume_blank')}<br>
						</div>
					</td>
				</tr>
				<tr>
					<td width="0%" nowrap="nowrap" align="right">Subject: </td>
					<td width="100%">
						<input type="text" name="subject" size="45" maxlength="255" style="width:98%;" value="{$ticket->subject}">
					</td>
				</tr>
				
				{* Watchers *}
				<tr>
					<td width="0%" nowrap="nowrap" valign="middle" align="right">{$translate->_('common.watchers')|capitalize}: </td>
					<td width="100%">
						{include file="devblocks:cerberusweb.core::internal/watchers/context_follow_button.tpl" context=CerberusContexts::CONTEXT_TICKET context_id=$ticket->id full=true}
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
		</fieldset>
		
		<fieldset>
			<legend>{'common.custom_fields'|devblocks_translate}</legend>
			
			<table cellpadding="2" cellspacing="1" border="0">
			{assign var=last_group_id value=-1}
			{foreach from=$custom_fields item=f key=f_id}
			{assign var=field_group_id value=$f->group_id}
			{if $field_group_id == 0 || $field_group_id == $ticket->team_id}
				{assign var=show_submit value=1}
				{if $field_group_id && $field_group_id != $last_group_id}
					<tr>
						<td colspan="2" align="center"><b>{$groups.$field_group_id->name}</b></td>
					</tr>
				{/if}
					<tr>
						<td valign="top" width="1%" align="right" nowrap="nowrap">
							<input type="hidden" name="field_ids[]" value="{$f_id}">
							{$f->name}:
						</td>
						<td valign="top" width="99%">
							{if $f->type=='S'}
								<input type="text" name="field_{$f_id}" size="45" maxlength="255" style="width:98%;" value="{$custom_field_values.$f_id}"><br>
							{elseif $f->type=='U'}
								<input type="text" name="field_{$f_id}" size="40" maxlength="255" style="width:98%;" value="{$custom_field_values.$f_id}">
								{if !empty($custom_field_values.$f_id)}<a href="{$custom_field_values.$f_id}" target="_blank">URL</a>{else}<i>(URL)</i>{/if}
							{elseif $f->type=='N'}
								<input type="text" name="field_{$f_id}" size="45" maxlength="255" value="{$custom_field_values.$f_id}"><br>
							{elseif $f->type=='T'}
								<textarea name="field_{$f_id}" rows="4" cols="50" style="width:98%;">{$custom_field_values.$f_id}</textarea><br>
							{elseif $f->type=='C'}
								<input type="checkbox" name="field_{$f_id}" value="1" {if $custom_field_values.$f_id}checked{/if}><br>
							{elseif $f->type=='X'}
								{foreach from=$f->options item=opt}
								<label><input type="checkbox" name="field_{$f_id}[]" value="{$opt}" {if isset($custom_field_values.$f_id.$opt)}checked="checked"{/if}> {$opt}</label><br>
								{/foreach}
							{elseif $f->type=='D'}
								<select name="field_{$f_id}">{* [TODO] Fix selected *}
									<option value=""></option>
									{foreach from=$f->options item=opt}
									<option value="{$opt}" {if $opt==$custom_field_values.$f_id}selected{/if}>{$opt}</option>
									{/foreach}
								</select><br>
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
		</fieldset>
		
		{* Comment *}
		{if !empty($last_comment)}
			{include file="devblocks:cerberusweb.core::internal/comments/comment.tpl" readonly=true comment=$last_comment}
		{/if}
		
		<fieldset>
			<legend>{'common.comment'|devblocks_translate|capitalize}</legend>
			<textarea name="comment" rows="5" cols="60" style="width:98%;"></textarea>
			<div class="notify" style="display:none;">
				<b>{'common.notify_watchers_and'|devblocks_translate}:</b>
				<button type="button" class="chooser_notify_worker"><span class="cerb-sprite sprite-view"></span></button>
				<ul class="chooser-container bubbles" style="display:block;"></ul>
			</div>
		</fieldset>
		
		<button type="button" onclick="genericAjaxPopupPostCloseReloadView('peek','frmTicketPeek','{$view_id}',false,'ticket_save');"><span class="cerb-sprite2 sprite-tick-circle-frame"></span> {$translate->_('common.save_changes')}</button>
    </div><!--tab2-->		
</div> 
<br>

</form>

<script type="text/javascript">
	// Popups
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open',function(event,ui) {
		$(this).dialog('option','title',"{$ticket->subject}");
		$("#peekTabs").tabs();
		$("#ticketPeekContent").css('width','100%');
		$("#ticketPeekProps").show();
		$(this).find('textarea[name=comment]')
			.elastic()
			.keyup(function() {
				if($(this).val().length > 0) {
					$(this).next('DIV.notify').show();
				} else {
					$(this).next('DIV.notify').hide();
				}
			})
			;
		$(this).focus();
	});
	
	// Choosers
	$('#frmTicketPeek button.chooser_worker').each(function() {
		ajax.chooser(this, 'cerberusweb.contexts.worker', 'worker_id', { autocomplete:true })
	});
	$('#frmTicketPeek button.chooser_notify_worker').each(function() {
		ajax.chooser(this,'cerberusweb.contexts.worker','notify_worker_ids', { autocomplete:true });
	});
</script>