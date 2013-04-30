<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmTicketPeek" onsubmit="return false;">
<input type="hidden" name="c" value="tickets">
<input type="hidden" name="a" value="savePreview">
<input type="hidden" name="id" value="{$ticket->id}">
{if !empty($link_context)}
<input type="hidden" name="link_context" value="{$link_context}">
<input type="hidden" name="link_context_id" value="{$link_context_id}">
{/if}

{if !$edit_mode && !empty($message)}
<div id="ticketPeekMessage" style="margin-bottom:10px;">
		{assign var=headers value=$message->getHeaders()}
		{if !empty($headers.to)}<b>{'message.header.to'|devblocks_translate|capitalize}:</b> {$headers.to}<br>{/if}
		{if !empty($headers.from)}<b>{'message.header.from'|devblocks_translate|capitalize}:</b> {$headers.from}<br>{/if}
		{if !empty($headers.date)}<b>{'message.header.date'|devblocks_translate|capitalize}:</b> {$headers.date}<br>{/if}
		<div id="ticketPeekContent" style="width:400;height:200px;overflow:auto;border:1px solid rgb(180,180,180);margin:2px;padding:3px;background-color:rgb(255,255,255);">
			<pre class="emailbody">{$content|trim|escape|devblocks_hyperlinks|devblocks_hideemailquotes nofilter}</pre>
		</div>
		
		<div style="float:left;">
			<b>{'common.url'|devblocks_translate}:</b> <a href="{devblocks_url}c=profiles&type=ticket&id={$ticket->mask}{/devblocks_url}">{devblocks_url full=true}c=profiles&type=ticket&id={$ticket->mask}{/devblocks_url}</a>
		</div>
		
		{if !is_null($p) && !is_null($p_count)}
		<div style="float:right;">
			{if 0 != $p}<a href="javascript:;" onclick="genericAjaxPopup('peek','c=internal&a=showPeekPopup&context={CerberusContexts::CONTEXT_TICKET}&context_id={$ticket->id}&view_id={$view_id}&msgid={$ticket->first_message_id}', null, false, '650');">&lt;&lt;</a>{/if}
			{if isset($p_prev) && $p_prev}<a href="javascript:;" onclick="genericAjaxPopup('peek','c=internal&a=showPeekPopup&context={CerberusContexts::CONTEXT_TICKET}&context_id={$ticket->id}&view_id={$view_id}&msgid={$p_prev}', null, false, '650');">&lt;{'common.previous_short'|devblocks_translate|capitalize}</a>{/if}
			({$p+1} of {$p_count})
			{if isset($p_next) && $p_next}<a href="javascript:;" onclick="genericAjaxPopup('peek','c=internal&a=showPeekPopup&context={CerberusContexts::CONTEXT_TICKET}&context_id={$ticket->id}&view_id={$view_id}&msgid={$p_next}', null, false, '650');">{'common.next'|devblocks_translate|capitalize}&gt;</a>{/if}
			{if $p+1 != $p_count}<a href="javascript:;" onclick="genericAjaxPopup('peek','c=internal&a=showPeekPopup&context={CerberusContexts::CONTEXT_TICKET}&context_id={$ticket->id}&view_id={$view_id}&msgid={$ticket->last_message_id}', null, false, '650');">&gt;&gt;</a>{/if}
		</div>
		{/if}
		
		<br clear="all" style="clear:all;">
</div>
{/if}
	
<div id="ticketPeekProps">
	<fieldset class="peek">
		<legend>{'common.properties'|devblocks_translate|capitalize}</legend>
		
		<table cellpadding="0" cellspacing="2" border="0" width="100%">
			<tr>
				<td width="0%" nowrap="nowrap" align="right">Subject: </td>
				<td width="100%">
					<input type="text" name="subject" size="45" maxlength="255" style="width:98%;" value="{$ticket->subject}">
				</td>
			</tr>
			
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
						<input type="text" name="ticket_reopen" size="55" value="{if !empty($ticket->reopen_at)}{$ticket->reopen_at|devblocks_date}{/if}"><br>
						{$translate->_('display.reply.next.resume_blank')}<br>
					</div>
				</td>
			</tr>
			
			<tr>
				<td width="0%" nowrap="nowrap" align="right">{'contact_org.name'|devblocks_translate|capitalize}: </td>
				<td width="100%">
					<input type="hidden" name="org_id" value="{$ticket->org_id}">
					{$ticket_org = $ticket->getOrg()}
					{if !empty($ticket_org)}
					<div>
						<b>{$ticket_org->name}</b>
						(<a href="javascript:;" onclick="$p=$(this).closest('div');$p.next('div').show();$p.remove();">change</a>)
					</div>
					{/if}
					<div style="display:{if !empty($ticket_org)}none{else}block{/if};">
						<input type="text" name="org_name" size="45" maxlength="255" style="width:98%;" value="{if !empty($ticket)}{$ticket_org->name}{/if}">
					</div>
				</td>
			</tr>
			
			<tr>
				<td width="0%" nowrap="nowrap" valign="middle" align="right">{$translate->_('common.owner')|capitalize}: </td>
				<td width="100%">
					<select name="owner_id">
						<option value="0"></option>
						{foreach from=$workers item=owner key=owner_id}
						{if $owner->isGroupMember($ticket->group_id)}
						<option value="{$owner_id}" {if $ticket->owner_id==$owner_id}selected="selected"{/if}>{$owner->getName()}</option>
						{/if}
						{/foreach}
					</select>
					<button type="button" onclick="$(this).prev('select[name=owner_id]').val('{$active_worker->id}');">{'common.me'|devblocks_translate|lower}</button>
					<button type="button" onclick="$(this).prevAll('select[name=owner_id]').first().val('0');">{'common.nobody'|devblocks_translate|lower}</button>
				</td>
			</tr>
			
			{if $active_worker->hasPriv('core.ticket.actions.move')}
			<tr>
				<td width="0%" nowrap="nowrap" align="right">Bucket: </td>
				<td width="100%">
					<select name="bucket_id">
					<option value="">-- move to --</option>
					{if empty($ticket->bucket_id)}{assign var=t_or_c value="t"}{else}{assign var=t_or_c value="c"}{/if}
					<optgroup label="Inboxes">
					{foreach from=$groups item=group}
						<option value="t{$group->id}">{$group->name}{if $t_or_c=='t' && $ticket->group_id==$group->id} (*){/if}</option>
					{/foreach}
					</optgroup>
					{foreach from=$group_buckets item=buckets key=groupId}
						{assign var=group value=$groups.$groupId}
						{if !empty($active_worker_memberships.$groupId)}
							<optgroup label="-- {$group->name} --">
							{foreach from=$buckets item=bucket}
							<option value="c{$bucket->id}">{$bucket->name}{if $t_or_c=='c' && $ticket->bucket_id==$bucket->id} (current bucket){/if}</option>
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
			
			{* Watchers *}
			<tr>
				<td width="0%" nowrap="nowrap" valign="middle" align="right">{$translate->_('common.watchers')|capitalize}: </td>
				<td width="100%">
					{include file="devblocks:cerberusweb.core::internal/watchers/context_follow_button.tpl" context=CerberusContexts::CONTEXT_TICKET context_id=$ticket->id full=true}
				</td>
			</tr>
		</table>
	</fieldset>
	
	{if !empty($custom_fields)}
	<fieldset class="peek">
		<legend>{'common.custom_fields'|devblocks_translate}</legend>
		{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false}
	</fieldset>
	{/if}
	
	{include file="devblocks:cerberusweb.core::internal/custom_field_groups/peek_custom_fieldsets.tpl" context=CerberusContexts::CONTEXT_TICKET context_id=$ticket->id}
	
	{* Comment *}
	{if !empty($last_comment)}
		{include file="devblocks:cerberusweb.core::internal/comments/comment.tpl" readonly=true comment=$last_comment}
	{/if}
	
	<fieldset class="peek">
		<legend>{'common.comment'|devblocks_translate|capitalize}</legend>
		<textarea name="comment" rows="5" cols="60" style="width:98%;"></textarea>
		<div class="notify" style="display:none;">
			<b>{'common.notify_watchers_and'|devblocks_translate}:</b>
			<button type="button" class="chooser_notify_worker"><span class="cerb-sprite sprite-view"></span></button>
			<ul class="chooser-container bubbles" style="display:block;"></ul>
		</div>
	</fieldset>
	
	<button type="button" onclick="genericAjaxPopupPostCloseReloadView(null,'frmTicketPeek','{$view_id}',false,'ticket_save');"><span class="cerb-sprite2 sprite-tick-circle"></span> {$translate->_('common.save_changes')}</button>
</div>
<br>

</form>

<script type="text/javascript">
	// Popups
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open',function(event,ui) {
		$(this).dialog('option','title',"{$ticket->subject}");
		$("#ticketPeekContent").css('width','100%');
		
		ajax.orgAutoComplete('#ticketPeekProps input:text[name=org_name]');
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
	$('#frmTicketPeek button.chooser_notify_worker').each(function() {
		ajax.chooser(this,'cerberusweb.contexts.worker','notify_worker_ids', { autocomplete:true });
	});
</script>