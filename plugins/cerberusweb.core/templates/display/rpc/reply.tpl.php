<input type="hidden" name="c" value="display">
<input type="hidden" name="a" value="sendReply">
<input type="hidden" name="id" value="{$message->id}">
<input type="hidden" name="ticket_id" value="{$ticket->id}">
<table cellpadding="2" cellspacing="0" border="0" width="100%" class="displayReplyTable">
	<tr>
		<th>Reply</th>
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
						<input type="text" size="45" name="subject" value="{$ticket->subject|escape:"htmlall"}" style="width:98%;">					
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
				<tr>
					<td width="100%" nowrap="nowrap" valign="top" colspan="2">
						<div style="display:none">
							<textarea name="team_signature" id="team_signature">{$signature}</textarea>	
						</div>					
						<input type="button" value="Append Signature" onclick="txtReply=document.getElementById('reply_content');sigDiv=document.getElementById('team_signature');txtReply.value += '\n'+sigDiv.value+'\n';scrollElementToBottom(txtReply);txtReply.focus();">						
					</td>
				</tr>
			</table>
		</td>
	</tr>
	<tr>
		<td>
		{assign var=ticket_team_id value=$ticket->team_id}
<textarea name="content" rows="12" cols="80" class="reply" id="reply_content">{$message->getContent()|trim|indent:1:'> '}

{if !empty($signature)}{$signature}{/if}
</textarea>
		</td>
	</tr>
	<tr>
		<td>
			<table cellpadding="2" cellspacing="0" border="0" width="100%">
				<tr>
					<td rowspan="2" width="0%" nowrap="nowrap" valign="top"><b>Add Attachments:</b></td>
				</tr>
				<tr>
					<td width="100%" valign="top">
						<input type="file" name="attachment[]" size="45"></input> 
						<a href="javascript:;" onclick="appendFileInput('displayReplyAttachments','attachment[]');">attach another file</a>
						<div id="displayReplyAttachments"></div>
					</td>
				</tr>
			</table>
			<br>
		</td>
	</tr>
	<tr>
		<td>
			<table cellpadding="2" cellspacing="0" border="0">
				<tr>
					<td rowspan="4" nowrap="nowrap" valign="top">
					<b>Then:</b>
					<!-- <input type="hidden" name="closed" value="{if $ticket->is_closed}1{else}0{/if}"> -->
					</td>
				</tr>
				<tr>
					<td nowrap="nowrap" valign="top" colspan="2">
						<label><input type="checkbox" name="closed" value="1" onchange="toggleDiv('replyOpen{$message->id}',this.checked?'none':'block');toggleDiv('replyClosed{$message->id}',this.checked?'block':'none');" {if $ticket->is_closed}checked{/if}>This conversation is completed for now.</label>

						<div id="replyOpen{$message->id}" style="display:{if $ticket->is_closed}none{else}block{/if};margin:5px;padding:5px;background-color:rgb(235,235,255);">
						<b>What is the next action that needs to happen?</b> (max 255 chars)<br>  
				      	<input type="text" name="next_action" size="55" maxlength="255" value="{$ticket->next_action|escape:"htmlall"}"><br>
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
				      	
				      	<div id="replyClosed{$message->id}" style="display:{if $ticket->is_closed}block{else}none{/if};margin:5px;padding:5px;background-color:rgb(235,235,255);">
				      	<b>When would you like to resume this conversation?</b><br> 
				      	<input type="text" name="ticket_reopen" size="55" value="{if !empty($ticket->due_date)}{$ticket->due_date|date_format:"%a, %b %d %Y %I:%M %p"}{/if}"><br>
				      	examples: "Friday", "+7 days", "Tomorrow 11:15AM", "Dec 31 2010"<br>
				      	(leave blank to wait for a reply before resuming)<br>
				      	</div>
				      	
				      	<br>
						
					</td>
				</tr>
				<!-- 
				<tr>
					<td width="0%" nowrap="nowrap" valign="top">Status:</td>
					<td width="100%" valign="top">
						<label><input type="checkbox" name="closed" value="1" {if $ticket->is_closed}checked{/if}>closed</label>
					</td>
				</tr>
				 -->
				      	<!-- 
				      	Set Priority:
						<label><input type="radio" name="priority" value="0" {if $ticket->priority==0}checked{/if}><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/star_alpha.gif{/devblocks_url}"></label>
						<label><input type="radio" name="priority" value="25" {if $ticket->priority==25}checked{/if}><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/star_green.gif{/devblocks_url}"></label>
						<label><input type="radio" name="priority" value="50" {if $ticket->priority==50}checked{/if}><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/star_yellow.gif{/devblocks_url}"></label>
						<label><input type="radio" name="priority" value="75" {if $ticket->priority==75}checked{/if}><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/star_red.gif{/devblocks_url}"></label>
				      	<br>
				      	 -->
			</table>
		</td>
	</tr>
	<tr>
		<td>
			<input type="submit" value="Send">
			<!-- 
			{if !$ticket->is_closed}<button type="button" onclick="">Send &amp; Close</button>{/if}
			{if $ticket->is_closed}<button type="button" onclick="">Send &amp; Re-open</button>{/if}
			-->
			<input type="button" value="Discard" onclick="clearDiv('reply{$message->id}');">
		</td>
	</tr>
</table>