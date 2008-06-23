<input type="hidden" name="c" value="display">
<input type="hidden" name="a" value="sendReply">
<input type="hidden" name="id" value="{$message->id}">
<input type="hidden" name="ticket_id" value="{$ticket->id}">
<div class="block" style="width:98%;margin:10px;">
<table cellpadding="2" cellspacing="0" border="0" width="100%">
	<tr>
		<td><h2>{if $is_forward}Forward{else}Reply{/if}</h2></td>
	</tr>
	<tr>
		<td>
			<table cellpadding="1" cellspacing="0" border="0" width="100%">
				{assign var=assigned_worker_id value=$ticket->next_worker_id}
				{if $assigned_worker_id > 0 && $assigned_worker_id != $active_worker->id && isset($workers.$assigned_worker_id)}
				<tr>
					<td width="100%" colspan="2">
						<div class="error">
							Warning: You are replying to a ticket assigned to {$workers.$assigned_worker_id->getName()}.
						</div>
					</td>
				</tr>
				{/if}
				
				{if $is_forward}
					<tr>
						<td width="0%" nowrap="nowrap"><b>To: </b></td>
						<td width="100%" align="left">
							<input type="text" size="45" id="replyForm_to" name="to" value="" style="width:50%;border:1px solid rgb(180,180,180);padding:2px;">
						</td>
					</tr>
				{else}
					<tr>
						<td width="0%" nowrap="nowrap">Requesters: </td>
						<td width="100%" align="left">
							<span id="displayRequesters{$message->id}">
							{foreach from=$ticket->getRequesters() item=requester name=requesters}
							<b>{$requester->email}</b>{if !$smarty.foreach.requesters.last}, {/if}
							{/foreach}
							</span>
							(<a href="javascript:;" onclick="genericAjaxPanel('c=display&a=showRequestersPanel&msg_id={$message->id}&ticket_id={$ticket->id}',this,false);" style="color:rgb(00,120,0);">change</a>)
							<!-- 
							<input type="text" size="45" name="to" value="" style="width:98%;border:1px solid rgb(180,180,180);padding:2px;">
							-->					
						</td>
					</tr>
				{/if}
				
				<tr>
					<td width="0%" nowrap="nowrap">Cc: </td>
					<td width="100%" align="left">
						<input type="text" size="45" id="replyForm_cc" name="cc" value="" style="width:50%;border:1px solid rgb(180,180,180);padding:2px;">					
					</td>
				</tr>
				<tr>
					<td width="0%" nowrap="nowrap">Bcc: </td>
					<td width="100%" align="left">
						<input type="text" size="45" id="replyForm_bcc" name="bcc" value="" style="width:50%;border:1px solid rgb(180,180,180);padding:2px;">					
					</td>
				</tr>
				<tr>
					<td width="0%" nowrap="nowrap">Subject: </td>
					<td width="100%" align="left">
						<input type="text" size="45" id="replyForm_subject" name="subject" value="{if $is_forward}Fwd: {/if}{$ticket->subject|escape:"htmlall"}" style="width:50%;border:1px solid rgb(180,180,180);padding:2px;">					
					</td>
				</tr>
			</table>
		</td>
	</tr>
	<tr>
		<td>
		{assign var=ticket_team_id value=$ticket->team_id}
		{assign var=headers value=$message->getHeaders()}
{*<button type="button" onclick="toggleDiv('replyAttachments{$message->id}','block');document.location='#replyAttachments{$message->id}';"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/document_attachment.gif{/devblocks_url}" align="top"> Add Files</button>*}
<button type="button" onclick="toggleDiv('kbSearch{$message->id}');document.getElementById('kbQuery{$message->id}').focus();"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/book_open2.gif{/devblocks_url}" align="top"> Knowledgebase</button>
<button type="button" onclick="genericAjaxPanel('c=display&a=showTemplatesPanel&type=2&reply_id={$message->id}&txt_name=reply_{$message->id}',this,false,'550px');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/text_rich.gif{/devblocks_url}" align="top"> E-mail Templates</button>
<button type="button" onclick="genericAjaxPanel('c=display&a=showFnrPanel',this,false,'550px');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/book_blue_view.gif{/devblocks_url}" align="top"> Fetch & Retrieve</button>
<button type="button" onclick="txtReply=document.getElementById('reply_{$message->id}');sigDiv=document.getElementById('team_signature');txtReply.value += '\n'+sigDiv.value+'\n';scrollElementToBottom(txtReply);txtReply.focus();"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/document_edit.gif{/devblocks_url}" align="top"> Insert Signature</button>
<br>

{* BEGIN KB *}
<div id="kbSearch{$message->id}" style="display:none;background-color:rgb(240,240,240);margin:5px;padding:5px;">
	<table cellpadding="0" cellspacing="0" border="0" width="100%">
	<tr>
		<td style="background-color:rgb(251,97,0);width:10px;"></td>
		<td style="padding-left:5px;">
			<h2>Search Knowledgebase:</h2>
			<select name="kbQueryTopic{$message->id}" style="border:solid 1px rgb(180,180,180);">
				<option value="">- all topics -</option>
				{foreach from=$kb_topics item=topic key=topic_id}
					<option value="{$topic_id}">{$topic->name}</option>
				{/foreach}
			</select>
			 for 
			<input id="kbQuery{$message->id}" type="text" value="" onkeypress="return interceptInputCRLF(event,function(){literal}{{/literal}document.getElementById('kbQueryBtn{$message->id}').click();document.getElementById('kbQuery{$message->id}').select();{literal}}{/literal});" size="45" style="border:solid 1px rgb(180,180,180);">
			<button id="kbQueryBtn{$message->id}" type="button" onclick="genericAjaxGet('kbResults{$message->id}','c=display&a=doReplyKbSearch&q='+escape(document.getElementById('kbQuery{$message->id}').value)+'&topic_id='+escape(selectValue(this.form.kbQueryTopic{$message->id})));" style="display:none;">{$translate->_('common.search')|lower}</button>
			<div id="kbResults{$message->id}" style="margin-top:5px;background-color:rgb(240,240,240);"></div>
		</td>
	</tr>
	</table>
</div>
{* END KB *}

{if $is_forward}
<textarea name="content" rows="20" cols="80" id="reply_{$message->id}" class="reply" style="width:98%;border:1px solid rgb(180,180,180);padding:5px;">
{if !empty($signature)}{$signature}{/if}


---- Forwarded message ----
{if isset($headers.subject)}Subject: {$headers.subject|escape:"htmlall"|cat:"\n"}{/if}
{if isset($headers.from)}From: {$headers.from|escape:"htmlall"|cat:"\n"}{/if}
{if isset($headers.date)}Date: {$headers.date|escape:"htmlall"|cat:"\n"}{/if}
{if isset($headers.to)}To: {$headers.to|escape:"htmlall"|cat:"\n"}{/if}

{$message->getContent()|trim|escape}
</textarea>
{else}
<textarea name="content" rows="20" cols="80" id="reply_{$message->id}" class="reply" style="width:98%;border:1px solid rgb(180,180,180);padding:5px;">
{if !empty($signature) && $signature_pos}

{$signature}{*Sig above, 2 lines necessary whitespace*}


{/if}On {$message->created_date|devblocks_date}, {$headers.from} wrote:
{$message->getContent()|trim|escape|indent:1:'> '}

{if !empty($signature) && !$signature_pos}{$signature}{/if}{*Sig below*}
</textarea>
{/if}
			<div style="display:none"><textarea name="team_signature" id="team_signature">{$signature}</textarea></div>
		</td>
	</tr>
	<tr>
		<td>
			<div id="replyAttachments{$message->id}" style="display:block;margin:5px;padding:5px;background-color:rgb(240,240,240);">
			<table cellpadding="0" cellspacing="0" border="0" width="100%">
			<tr>
				<td style="background-color:rgb(0,184,4);width:10px;"></td>
				<td style="padding-left:5px;">
					<H2>Attachments:</H2>
					(The maximum attachment size is {$upload_max_filesize})<br>
					
					{if $is_forward && !empty($forward_attachments)}
						<br>
						<b>Forward attachments:</b><br>
						{foreach from=$forward_attachments item=attach key=attach_id}
							<label><input type="checkbox" name="forward_files[]" value="{$attach->id}" checked> {$attach->display_name|escape}</label><br>
						{/foreach}
						<br>
					{/if}
					
					<b>Add Attachments:</b><br>
					<table cellpadding="2" cellspacing="0" border="0" width="100%">
						<tr>
							<td width="100%" valign="top">
								<input type="file" name="attachment[]" size="45"></input> 
								<a href="javascript:;" onclick="appendFileInput('displayReplyAttachments','attachment[]');">attach more</a>
								<div id="displayReplyAttachments"></div>
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
				<H2>Next:</H2>
					<table cellpadding="2" cellspacing="0" border="0">
						<tr>
							<td nowrap="nowrap" valign="top" colspan="2">
								<label><input type="radio" name="closed" value="0" onclick="toggleDiv('replyOpen{$message->id}','block');toggleDiv('replyClosed{$message->id}','none');">Open</label>
								<label><input type="radio" name="closed" value="2" onclick="toggleDiv('replyOpen{$message->id}','block');toggleDiv('replyClosed{$message->id}','none');" {if !$ticket->is_closed}checked{/if}>Waiting for reply</label>
								<label><input type="radio" name="closed" value="1" onclick="toggleDiv('replyOpen{$message->id}','none');toggleDiv('replyClosed{$message->id}','block');" {if $ticket->is_closed}checked{/if}>Closed</label>
								<br>
								<br>
		
								<b>Who should handle the next reply?</b><br>
						      	<select name="next_worker_id" onchange="toggleDiv('replySurrender{$message->id}',this.selectedIndex?'block':'none');">
						      		<option value="0" {if 0==$ticket->next_worker_id}selected{/if}>Anybody
						      		{foreach from=$workers item=worker key=worker_id name=workers}
						      			{if $worker_id==$active_worker->id}{assign var=next_worker_id_sel value=$smarty.foreach.workers.iteration}{/if}
						      			<option value="{$worker_id}" {if $worker_id==$ticket->next_worker_id}selected{/if}>{$worker->getName()}
						      		{/foreach}
						      	</select>&nbsp;
						      	{if !empty($next_worker_id_sel)}
						      		<button type="button" onclick="this.form.next_worker_id.selectedIndex = {$next_worker_id_sel};toggleDiv('replySurrender{$message->id}','block');">me</button>
						      		<button type="button" onclick="this.form.next_worker_id.selectedIndex = 0;toggleDiv('replySurrender{$message->id}','none');">anybody</button>
						      	{/if}
						      	<br>
						      	<br>
						      	
						      	<div id="replySurrender{$message->id}" style="display:{if $ticket->next_worker_id}block{else}none{/if};margin-left:10px;">
									<b>Allow anybody to handle the next reply after:</b> (e.g. "2 hours", "5pm", {*"Tuesday", "June 30", *}or leave blank to keep assigned)<br>  
							      	<input type="text" name="unlock_date" size="32" maxlength="255" value="">
							      	<button type="button" onclick="this.form.unlock_date.value='+2 hours';">+2 hours</button>
							      	<br>
							      	<br>
							    </div>
		
								<b>What is the next action that needs to happen?</b> (optional, max 255 chars)<br>  
						      	<input type="text" name="next_action" size="80" maxlength="255" value="{$ticket->next_action|escape:"htmlall"}"><br>
						      	<br>
		
								<b>Would you like to move this conversation?</b><br>  
						      	<select name="bucket_id">
						      		<option value="">-- no thanks! --</option>
						      		{if empty($ticket->category_id)}{assign var=t_or_c value="t"}{else}{assign var=t_or_c value="c"}{/if}
						      		<optgroup label="Inboxes">
						      		{foreach from=$teams item=team}
						      			<option value="t{$team->id}">{$team->name}{if $t_or_c=='t' && $ticket->team_id==$team->id} (current bucket){/if}</option>
						      		{/foreach}
						      		</optgroup>
						      		{foreach from=$team_categories item=categories key=teamId}
						      			{assign var=team value=$teams.$teamId}
						      			<optgroup label="-- {$team->name} --">
						      			{foreach from=$categories item=category}
						    				<option value="c{$category->id}">{$category->name}{if $t_or_c=='c' && $ticket->category_id==$category->id} (current bucket){/if}</option>
						    			{/foreach}
						    			</optgroup>
						     		{/foreach}
						      	</select><br>
						      	<br>
						      	
								<div id="replyOpen{$message->id}" style="display:{if $ticket->is_closed}none{else}block{/if};">
						      	</div>
						      	
						      	<div id="replyClosed{$message->id}" style="display:{if $ticket->is_closed}block{else}none{/if};">
						      	<b>When would you like to resume this conversation?</b> (e.g. "Friday", "7 days", "Tomorrow 11:15AM", "Dec 31")<br> 
						      	<input type="text" name="ticket_reopen" size="55" value="{if !empty($ticket->due_date)}{$ticket->due_date|devblocks_date}{/if}"><br>
						      	(leave blank to wait for a reply before resuming)<br>
						      	<br>
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
			{if $is_forward}
				<button type="button" onclick="if(this.form.to.value.length > 0) this.form.submit();"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> Forward Message</button>
			{else}
				<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> Send Message</button>
			{/if}
			<button type="button" onclick="clearDiv('reply{$message->id}');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete.gif{/devblocks_url}" align="top"> Discard</button>
			<button type="button" onclick="clearDiv('reply{$message->id}');genericAjaxGet('','c=display&a=discardAndSurrender&ticket_id={$ticket->id}');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/flag_white.gif{/devblocks_url}" align="top"> Discard &amp; Surrender</button>
		</td>
	</tr>
</table>
</div>