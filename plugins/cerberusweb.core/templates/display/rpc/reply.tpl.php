<input type="hidden" name="c" value="display">
<input type="hidden" name="a" value="sendReply">
<input type="hidden" name="id" value="{$message->id}">
<input type="hidden" name="ticket_id" value="{$ticket->id}">
<div class="block" style="margin:20px;">
<table cellpadding="2" cellspacing="0" border="0" width="100%">
	<tr>
		<td><h2>Reply</h2></td>
	</tr>
	<tr>
		<td>
			<table cellpadding="1" cellspacing="0" border="0" width="100%">
				<!-- 
				<tr>
					<td width="0%" nowrap="nowrap" valign="top"><b>From:</b></td>
					<td width="100%" align="left">[[ team address via agent address ]]</td>
				</tr>
				 -->
				<tr>
					<td width="100%" nowrap="nowrap" valign="top" colspan="2">
						{foreach from=$ticket->getRequesters() item=requester name=requesters}
						<!-- 
						<select name="to_type[]">
							<option value="to">To:</option>
							<option value="to">Cc:</option>
							<option value="to">Bcc:</option>
						</select> --><!--  
						<input type="text" size="65" name="to[]" value="{$requester->email}">-->
						<b>To: </b> {$requester->email}
						{*
						{if $smarty.foreach.requesters.first}
							<button type="button" onclick="">add</button>
						{/if}
						*}
						<br>
						{/foreach}
					</td>
				</tr>
				<tr>
					<td width="0%" nowrap="nowrap"><b>Subject: </b></td>
					<td width="100%" align="left">
						<input type="text" size="45" name="subject" value="{$ticket->subject|escape:"htmlall"}" style="width:98%;border:1px solid rgb(180,180,180);padding:2px;">					
					</td>
				</tr>
				
				<!-- 
				<tr id="replyCc" style="display:none;">
					<td width="0%" nowrap="nowrap" valign="top"><b>Cc:</b></td>
					<td width="100%" align="left"><textarea rows="2" cols="80" name="cc"></textarea></td>
				</tr>
				<tr id="replyBcc" style="display:none;">
					<td width="0%" nowrap="nowrap" valign="top"><b>Bcc:</b></td>
					<td width="100%" align="left"><textarea rows="2" cols="80" name="bcc"></textarea></td>
				</tr>
				<tr>
					<td colspan="2">
						<a href="javascript:;" onclick="toggleDiv('replyCc','inline');">cc</a>
						| 
						<a href="javascript:;" onclick="toggleDiv('replyBcc','inline');">bcc</a>
						| 
						<a href="javascript:;">bigger reply</a> 
						| 
						<a href="javascript:;">smaller reply</a>
					</td>
				</tr>
				 -->
			</table>
		</td>
	</tr>
	<tr>
		<td>
		{assign var=ticket_team_id value=$ticket->team_id}
<textarea name="content" rows="20" cols="80" id="reply_content" class="reply" style="width:98%;border:1px solid rgb(180,180,180);padding:5px;">{$message->getContent()|trim|indent:1:'> '}

{if !empty($signature)}{$signature}{/if}
</textarea>
		</td>
	</tr>
	<tr>
		<td nowrap="nowrap" valign="top">
			<div style="display:none"><textarea name="team_signature" id="team_signature">{$signature}</textarea></div>
								
			<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> Send Message</button>
			<button type="button" onclick="clearDiv('reply{$message->id}');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete.gif{/devblocks_url}" align="top"> Discard</button>
			
			&nbsp; &nbsp; 

			<button type="button" onclick="toggleDiv('replyAttachments{$message->id}','block');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/document_attachment.gif{/devblocks_url}" align="top"> Add Attachments</button>
			<button type="button" onclick="txtReply=document.getElementById('reply_content');sigDiv=document.getElementById('team_signature');txtReply.value += '\n'+sigDiv.value+'\n';scrollElementToBottom(txtReply);txtReply.focus();"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/mail_write.gif{/devblocks_url}" align="top"> Insert Signature</button>
		</td>
	</tr>
	<tr>
		<td>
			<div id="replyAttachments{$message->id}" style="display:none;">
			<br>
			<H2>Attachments:</H2>
			(The maximum attachment size is {$upload_max_filesize})<br>
			<table cellpadding="2" cellspacing="0" border="0" width="100%">
				<tr>
					<td width="100%" valign="top">
						<input type="file" name="attachment[]" size="45"></input> 
						<a href="javascript:;" onclick="appendFileInput('displayReplyAttachments','attachment[]');">attach more</a>
						<div id="displayReplyAttachments"></div>
					</td>
				</tr>
			</table>
			</div>
		</td>
	</tr>
	<tr>
		<td>
		<br>
		<H2>Next:</H2>
			<table cellpadding="2" cellspacing="0" border="0">
				<tr>
					<td nowrap="nowrap" valign="top" colspan="2">
						<label><input type="checkbox" name="closed" value="1" onchange="toggleDiv('replyOpen{$message->id}',this.checked?'none':'block');toggleDiv('replyClosed{$message->id}',this.checked?'block':'none');" {if $ticket->is_closed}checked{/if}>This conversation is completed for now.</label>

						<div id="replyOpen{$message->id}" style="display:{if $ticket->is_closed}none{else}block{/if};margin:5px;padding:5px;border:1px solid rgb(180,180,180);">
						<b>What is the next action that needs to happen?</b> (max 255 chars)<br>  
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
				      	</select>
				      	</div>
				      	
				      	<div id="replyClosed{$message->id}" style="display:{if $ticket->is_closed}block{else}none{/if};margin:5px;padding:5px;border:1px solid rgb(180,180,180);">
				      	<b>When would you like to resume this conversation?</b><br> 
				      	<input type="text" name="ticket_reopen" size="55" value="{if !empty($ticket->due_date)}{$ticket->due_date|date_format:"%a, %b %d %Y %I:%M %p"}{/if}"><br>
				      	examples: "Friday", "+7 days", "Tomorrow 11:15AM", "Dec 31 2010"<br>
				      	(leave blank to wait for a reply before resuming)<br>
				      	</div>
				      	
				      	<br>
						
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
</div>