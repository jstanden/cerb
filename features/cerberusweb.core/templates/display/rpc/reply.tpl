<div class="block" style="width:98%;margin:10px;">

<form id="reply{$message->id}_part1">
<table cellpadding="2" cellspacing="0" border="0" width="100%">
	<tr>
		<td><h2>{if $is_forward}{$translate->_('display.ui.forward')|capitalize}{else}{$translate->_('display.ui.reply')|capitalize}{/if}</h2></td>
	</tr>
	<tr>
		<td width="100%">
			<table cellpadding="1" cellspacing="0" border="0" width="100%">
				{assign var=assigned_worker_id value=$ticket->next_worker_id}
				{if $assigned_worker_id > 0 && $assigned_worker_id != $active_worker->id && isset($workers.$assigned_worker_id)}
				<tr>
					<td width="100%" colspan="2">
						<div class="ui-widget">
							<div class="ui-state-error ui-corner-all" style="padding: 0 .7em; margin: 0.2em; "> 
								<p><span class="ui-icon ui-icon-alert" style="float: left; margin-right: .3em;"></span> 
								{'display.reply.warn_assigned'|devblocks_translate:$workers.$assigned_worker_id->getName()}.</p>
							</div>
						</div>
					</td>
				</tr>
				{/if}
				
				<tr>
					<td width="1%" nowrap="nowrap"><b>{$translate->_('message.header.to')|capitalize}:</b> </td>
					<td width="99%" align="left">
						<input type="text" size="45" name="to" value="{if !empty($draft)}{$draft->params.to|escape}{else}{if $is_forward}{else}{foreach from=$ticket->getRequesters() item=req_addy name=reqs}{$req_addy->email}{if !$smarty.foreach.reqs.last}, {/if}{/foreach}{/if}{/if}" {if $is_forward}class="required"{/if} style="width:100%;border:1px solid rgb(180,180,180);padding:2px;">
					</td>
				</tr>
				
				<tr>
					<td width="1%" nowrap="nowrap">{$translate->_('message.header.cc')|capitalize}: </td>
					<td width="99%" align="left">
						<input type="text" size="45" name="cc" value="{$draft->params.cc|escape}" style="width:100%;border:1px solid rgb(180,180,180);padding:2px;">					
					</td>
				</tr>
				<tr>
					<td width="1%" nowrap="nowrap">{$translate->_('message.header.bcc')|capitalize}: </td>
					<td width="99%" align="left">
						<input type="text" size="45" name="bcc" value="{$draft->params.bcc|escape}" style="width:100%;border:1px solid rgb(180,180,180);padding:2px;">					
					</td>
				</tr>
				<tr>
					<td width="1%" nowrap="nowrap">{$translate->_('message.header.subject')|capitalize}: </td>
					<td width="99%" align="left">
						<input type="text" size="45" name="subject" value="{if !empty($draft)}{$draft->subject|escape}{else}{if $is_forward}Fwd: {/if}{$ticket->subject|escape}{/if}" style="width:100%;border:1px solid rgb(180,180,180);padding:2px;" class="required">					
					</td>
				</tr>
			</table>

			<div id="divDraftStatus{$message->id}"></div>
			{assign var=ticket_team_id value=$ticket->team_id}
			{assign var=headers value=$message->getHeaders()}
			<button name="saveDraft" type="button" onclick="if($(this).attr('disabled'))return;$(this).attr('disabled','disabled');genericAjaxPost('reply{$message->id}_part2',null,'c=display&a=saveDraftReply&is_ajax=1',function(json, ui) { var obj = $.parseJSON(json); $('#divDraftStatus{$message->id}').html(obj.html); $('#reply{$message->id}_part2 input[name=draft_id]').val(obj.draft_id); $('#reply{$message->id}_part1 button[name=saveDraft]').removeAttr('disabled'); } );"><span class="cerb-sprite sprite-check"></span> Save Draft</button>
			<button type="button" onclick="genericAjaxPanel('c=display&a=showSnippets&text=reply_{$message->id}&contexts=cerberusweb.contexts.ticket,cerberusweb.contexts.worker&id={$message->ticket_id}',null,false,'550');"><span class="cerb-sprite sprite-text_rich"></span> {$translate->_('common.snippets')|capitalize}</button>
			<button type="button" onclick="genericAjaxGet('','c=tickets&a=getComposeSignature&group_id={$ticket->team_id}',function(text) { insertAtCursor(document.getElementById('reply_{$message->id}'),text);document.getElementById('reply_{$message->id}').focus(); } );"><span class="cerb-sprite sprite-document_edit"></span> {$translate->_('display.reply.insert_sig')|capitalize}</button>
			{* Plugin Toolbar *}
			{if !empty($reply_toolbaritems)}
				{foreach from=$reply_toolbaritems item=renderer}
					{if !empty($renderer)}{$renderer->render($message)}{/if}
				{/foreach}
			{/if}
		</td>
	</tr>
</table>
</form>

<div id="replyToolbarOptions{$message->id}"></div>

<form id="reply{$message->id}_part2" action="{devblocks_url}{/devblocks_url}" method="POST" enctype="multipart/form-data">
<table cellpadding="2" cellspacing="0" border="0" width="100%">
	<tr>
		<td>
<!-- {* [TODO] This is ugly but gets the job done for now, giving toolbar plugins above their own <form> scope *} -->
<input type="hidden" name="c" value="display">
<input type="hidden" name="a" value="sendReply">
<input type="hidden" name="id" value="{$message->id}">
<input type="hidden" name="ticket_id" value="{$ticket->id}">
<input type="hidden" name="ticket_mask" value="{$ticket->mask|escape}">
<input type="hidden" name="draft_id" value="{$draft->id}">
{if $is_forward}<input type="hidden" name="is_forward" value="1">{/if}

<!-- {* Copy these dynamically so a plugin dev doesn't need to conflict with the reply <form> *} -->
<input type="hidden" name="to" value="{$draft->params.to|escape}">
<input type="hidden" name="cc" value="{$draft->params.cc|escape}">
<input type="hidden" name="bcc" value="{$draft->params.bcc|escape}">
<input type="hidden" name="subject" value="{if !empty($draft)}{$draft->subject|escape}{else}{if $is_forward}Fwd: {/if}{$ticket->subject|escape}{/if}">

{if $is_forward}
<textarea name="content" rows="20" cols="80" id="reply_{$message->id}" class="reply" style="width:98%;border:1px solid rgb(180,180,180);padding:5px;">
{if !empty($draft)}{$draft->body}{else}
{if !empty($signature)}{$signature}{/if}

{$translate->_('display.reply.forward.banner')}
{if isset($headers.subject)}{$translate->_('message.header.subject')|capitalize}: {$headers.subject|escape|cat:"\n"}{/if}
{if isset($headers.from)}{$translate->_('message.header.from')|capitalize}: {$headers.from|escape|cat:"\n"}{/if}
{if isset($headers.date)}{$translate->_('message.header.date')|capitalize}: {$headers.date|escape|cat:"\n"}{/if}
{if isset($headers.to)}{$translate->_('message.header.to')|capitalize}: {$headers.to|escape|cat:"\n"}{/if}

{$message->getContent()|trim|escape}
{/if}
</textarea>
{else}
<textarea name="content" rows="20" cols="80" id="reply_{$message->id}" class="reply" style="width:98%;border:1px solid rgb(180,180,180);padding:5px;">
{if !empty($draft)}{$draft->body|escape}{else}
{if !empty($signature) && $signature_pos}

{$signature}{*Sig above, 2 lines necessary whitespace*}


{/if}{$quote_sender=$message->getSender()}{$quote_sender_personal=$quote_sender->getName()}{if !empty($quote_sender_personal)}{$reply_personal=$quote_sender_personal}{else}{$reply_personal=$quote_sender->email}{/if}{$reply_date=$message->created_date|devblocks_date:'D, d M Y'}{'display.reply.reply_banner'|devblocks_translate:$reply_date:$reply_personal}
{$message->getContent()|trim|escape|indent:1:'> '}

{if !empty($signature) && !$signature_pos}{$signature}{/if}{*Sig below*}
{/if}
</textarea>
{/if}
		</td>
	</tr>
	<tr>
		<td>
			<div id="replyAttachments{$message->id}" style="display:block;margin:5px;padding:5px;background-color:rgb(240,240,240);">
			<table cellpadding="0" cellspacing="0" border="0" width="100%">
			<tr>
				<td style="background-color:rgb(0,184,4);width:10px;"></td>
				<td style="padding-left:5px;">
					<H2>{$translate->_('display.convo.attachments_label')|capitalize}</H2>
					{'display.reply.attachments_limit'|devblocks_translate:$upload_max_filesize}<br>
					
					{if $is_forward && !empty($forward_attachments)}
						<br>
						<b>{$translate->_('display.reply.attachments_forward')|capitalize}</b><br>
						{foreach from=$forward_attachments item=attach key=attach_id}
							<label><input type="checkbox" name="forward_files[]" value="{$attach->id}" checked> {$attach->display_name|escape}</label><br>
						{/foreach}
						<br>
					{/if}
					
					<b>{$translate->_('display.reply.attachments_add')}</b> 
					(<a href="javascript:;" onclick="appendFileInput('displayReplyAttachments','attachment[]');">{$translate->_('display.reply.attachments_more')|lower}</a>)
					(<a href="javascript:;" onclick="$('#displayReplyAttachments').html('');appendFileInput('displayReplyAttachments','attachment[]');">{$translate->_('common.clear')|lower}</a>)
					<br>
					<table cellpadding="2" cellspacing="0" border="0" width="100%">
						<tr>
							<td width="100%" valign="top">
								<div id="displayReplyAttachments">
									<input type="file" name="attachment[]" size="45"></input><br> 
								</div>
							</td>
						</tr>
					</table>
				</td>
			</tr>
			</table>
			</div>
		</td>
	</tr>
	<tr>
		<td>
		<div style="background-color:rgb(240,240,240);margin:5px;padding:5px;">
			<table cellpadding="0" cellspacing="0" border="0" width="100%">
			<tr>
				<td style="background-color:rgb(18,147,195);width:10px;"></td>
				<td style="padding-left:5px;">
				<H2>{$translate->_('display.reply.next_label')|capitalize}</H2>
					<table cellpadding="2" cellspacing="0" border="0">
						<tr>
							<td nowrap="nowrap" valign="top" colspan="2">
								<label><input type="radio" name="closed" value="0" onclick="toggleDiv('replyOpen{$message->id}','block');toggleDiv('replyClosed{$message->id}','none');">{$translate->_('status.open')|capitalize}</label>
								<label><input type="radio" name="closed" value="2" onclick="toggleDiv('replyOpen{$message->id}','block');toggleDiv('replyClosed{$message->id}','block');" {if !$ticket->is_closed}checked{/if}>{$translate->_('status.waiting')|capitalize}</label>
								{if $active_worker->hasPriv('core.ticket.actions.close') || ($ticket->is_closed && !$ticket->is_deleted)}<label><input type="radio" name="closed" value="1" onclick="toggleDiv('replyOpen{$message->id}','none');toggleDiv('replyClosed{$message->id}','block');" {if $ticket->is_closed}checked{/if}>{$translate->_('status.closed')|capitalize}</label>{/if}
								<br>
								<br>
								
						      	<div id="replyClosed{$message->id}" style="display:block;margin-left:10px;">
						      	<b>{$translate->_('display.reply.next.resume')}</b> {$translate->_('display.reply.next.resume_eg')}<br> 
						      	<input type="text" name="ticket_reopen" size="55" value="{if !empty($ticket->due_date)}{$ticket->due_date|devblocks_date}{/if}"><br>
						      	{$translate->_('display.reply.next.resume_blank')}<br>
						      	<br>
						      	</div>
		
								<div style="margin-left:10px;">
								<b>{$translate->_('display.reply.next.handle_reply')}</b><br>
						      	<select name="next_worker_id" onchange="toggleDiv('replySurrender{$message->id}',this.selectedIndex?'block':'none');">
						      		{if $active_worker->id==$ticket->next_worker_id || 0==$ticket->next_worker_id || $active_worker->hasPriv('core.ticket.actions.assign')}<option value="0" {if 0==$ticket->next_worker_id}selected{/if}>{$translate->_('common.anybody')|capitalize}{/if}
						      		{foreach from=$workers item=worker key=worker_id name=workers}
										{if ($worker_id==$active_worker->id && !$ticket->next_worker_id) || $worker_id==$ticket->next_worker_id || $active_worker->hasPriv('core.ticket.actions.assign')}
							      			{if $worker_id==$active_worker->id}{assign var=next_worker_id_sel value=$smarty.foreach.workers.iteration}{/if}
							      			<option value="{$worker_id}" {if $worker_id==$ticket->next_worker_id}selected{/if}>{$worker->getName()}
										{/if}
						      		{/foreach}
						      	</select>&nbsp;
						      	{if $active_worker->hasPriv('core.ticket.actions.assign') && !empty($next_worker_id_sel)}
						      		<button type="button" onclick="this.form.next_worker_id.selectedIndex = {$next_worker_id_sel};toggleDiv('replySurrender{$message->id}','block');">{$translate->_('common.me')|lower}</button>
						      		<button type="button" onclick="this.form.next_worker_id.selectedIndex = 0;toggleDiv('replySurrender{$message->id}','none');">{$translate->_('common.anybody')|lower}</button>
						      	{/if}
						      	</div>
						      	<br>
						      	
						      	<div id="replySurrender{$message->id}" style="display:{if $ticket->next_worker_id}block{else}none{/if};margin-left:10px;">
									<b>{$translate->_('display.reply.next.handle_reply_after')}</b> {$translate->_('display.reply.next.handle_reply_after_eg')}<br>  
							      	<input type="text" name="unlock_date" size="32" maxlength="255" value="">
							      	<button type="button" onclick="this.form.unlock_date.value='+2 hours';">{$translate->_('display.reply.next.handle_reply_after_2hrs')}</button>
							      	<br>
							      	<br>
							    </div>
		
								{if $active_worker->hasPriv('core.ticket.actions.move')}
								<b>{$translate->_('display.reply.next.move')}</b><br>  
						      	<select name="bucket_id">
						      		<option value="">-- {$translate->_('display.reply.next.move.no_thanks')|lower} --</option>
						      		{if empty($ticket->category_id)}{assign var=t_or_c value="t"}{else}{assign var=t_or_c value="c"}{/if}
						      		<optgroup label="{$translate->_('common.inboxes')|capitalize}">
						      		{foreach from=$teams item=team}
						      			<option value="t{$team->id}">{$team->name}{if $t_or_c=='t' && $ticket->team_id==$team->id} {$translate->_('display.reply.next.move.current')}{/if}</option>
						      		{/foreach}
						      		</optgroup>
						      		{foreach from=$team_categories item=categories key=teamId}
						      			{assign var=team value=$teams.$teamId}
						      			{if !empty($active_worker_memberships.$teamId)}
							      			<optgroup label="-- {$team->name} --">
							      			{foreach from=$categories item=category}
							    				<option value="c{$category->id}">{$category->name}{if $t_or_c=='c' && $ticket->category_id==$category->id} {$translate->_('display.reply.next.move.current')}{/if}</option>
							    			{/foreach}
							    			</optgroup>
							    		{/if}
						     		{/foreach}
						      	</select><br>
						      	<br>
						      	{/if}
						      	
								<div id="replyOpen{$message->id}" style="display:{if $ticket->is_closed}none{else}block{/if};">
						      	</div>
		
							</td>
						</tr>
					</table>
				</td>
			</tr>
			</table>
			</div>
		</td>
	</tr>
	<tr>
		<td>
			<button type="button" onclick="if($('#reply{$message->id}_part1').validate().form()) { genericAjaxPost('reply{$message->id}_part2',null,'c=display&a=saveDraftReply&is_ajax=1',function(json) { $('#reply{$message->id}_part2').submit(); } ); } "><span class="cerb-sprite sprite-check"></span> {if $is_forward}{$translate->_('display.ui.forward')|capitalize}{else}{$translate->_('display.ui.send_message')}{/if}</button>
			<button type="button" onclick="if($('#reply{$message->id}_part1').validate().form()) { this.form.a.value='saveDraftReply'; this.form.submit(); } "><span class="cerb-sprite sprite-media_pause"></span> {$translate->_('display.ui.continue_later')|capitalize}</button>
			<button type="button" onclick="if(confirm('Are you sure you want to discard this reply?')) { if(0!==this.form.draft_id.value.length) { genericAjaxGet('', 'c=tickets&a=deleteDraft&draft_id='+escape(this.form.draft_id.value)); $('#draft'+escape(this.form.draft_id.value)).remove(); } $('#reply{$message->id}').html(''); } "><span class="cerb-sprite sprite-delete"></span> {$translate->_('display.ui.discard')|capitalize}</button>
		</td>
	</tr>
</table>
</form>

</div>

<script language="JavaScript1.2" type="text/javascript">
	$(function() {
		// Autocompletes
		ajax.emailAutoComplete('#reply{$message->id}_part1 input[name=to]', { multiple: true } );
		ajax.emailAutoComplete('#reply{$message->id}_part1 input[name=cc]', { multiple: true } );
		ajax.emailAutoComplete('#reply{$message->id}_part1 input[name=bcc]', { multiple: true } );
		
		$('#reply{$message->id}_part1 input:text').blur(function(event) {
			var name = event.target.name;
			$('#reply{$message->id}_part2 input:hidden[name='+name+']').val(event.target.value);
		} );
		
		$('#reply{$message->id}_part1').validate();
		
		$('#reply{$message->id}_part1 button[name=saveDraft]').click(); // save now
		setInterval("$('#reply{$message->id}_part1 button[name=saveDraft]').click();", 30000); // and every 30 sec
	} );
</script>
