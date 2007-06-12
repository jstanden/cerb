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
						<select name="to_type[]">
							<option value="to">To:</option>
							<option value="to">Cc:</option>
							<option value="to">Bcc:</option>
						</select><!--  
						--><input type="text" size="65" name="to[]" value="{$requester->email}">
						{if $smarty.foreach.requesters.first}
							<button type="button" onclick="">add</button>
						{/if}
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
			</table>
		</td>
	</tr>
	<tr>
		<td>
			<textarea name="content" rows="15" cols="80" class="reply">{$message->getContent()|trim|wordwrap:70|indent:1:'> '}</textarea>
		</td>
	</tr>
	<tr>
		<td>
			<table cellpadding="2" cellspacing="0" border="0" width="100%">
				<tr>
					<td rowspan="4" width="0%" nowrap="nowrap" valign="top"><b>Properties:</b></td>
				</tr>
				<tr>
					<td width="0%" nowrap="nowrap" valign="top">Status:</td>
					<td width="100%" valign="top">
						<label><input type="checkbox" name="closed" value="1" {if $ticket->is_closed}checked{/if}>closed</label>
					</td>
				</tr>
				<tr>
					<td width="0%" nowrap="nowrap" valign="top">Move to:</td>
					<td width="100%" valign="top">
				      	<select name="category_id">
				      		{if empty($ticket->category_id)}{assign var=t_or_c value="t"}{else}{assign var=t_or_c value="c"}{/if}
				      		<optgroup label="Team (No Category)">
				      		{foreach from=$teams item=team}
				      			<option value="t{$team->id}" {if $t_or_c=='t' && $ticket->team_id==$team->id}selected{/if}>{$team->name}</option>
				      		{/foreach}
				      		</optgroup>
				      		{foreach from=$team_categories item=categories key=teamId}
				      			{assign var=team value=$teams.$teamId}
				      			<optgroup label="{$team->name}">
				      			{foreach from=$categories item=category}
				    				<option value="c{$category->id}" {if $t_or_c=='c' && $ticket->category_id==$category->id}selected{/if}>{$category->name}</option>
				    			{/foreach}
				    			</optgroup>
				     		{/foreach}
				      	</select>
					</td>
				</tr>
				<tr>
					<td width="0%" nowrap="nowrap" valign="top">Priority:</td>
					<td width="100%" valign="top">
						<label><input type="radio" name="priority" value="0" {if $ticket->priority==0}checked{/if}><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/star_alpha.gif{/devblocks_url}"></label>
						<label><input type="radio" name="priority" value="25" {if $ticket->priority==25}checked{/if}><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/star_green.gif{/devblocks_url}"></label>
						<label><input type="radio" name="priority" value="50" {if $ticket->priority==50}checked{/if}><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/star_yellow.gif{/devblocks_url}"></label>
						<label><input type="radio" name="priority" value="75" {if $ticket->priority==75}checked{/if}><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/star_red.gif{/devblocks_url}"></label>
					</td>
				</tr>
			</table>
		</td>
	</tr>
	<tr>
		<td>
			<table cellpadding="2" cellspacing="0" border="0" width="100%">
				<tr>
					<td rowspan="2" width="0%" nowrap="nowrap" valign="top"><b>Send Attachments:</b></td>
				</tr>
				<tr>
					<td width="100%" valign="top">
						<input type="file" name="attachment[]"></input> 
						<a href="javascript:;" onclick="appendFileInput('displayReplyAttachments','attachment[]');">attach another file</a>
						<div id="displayReplyAttachments"></div>
					</td>
				</tr>
			</table>
		</td>
	</tr>
	<tr>
		<td>
			<input type="submit" value="Send">
			<input type="button" value="Discard" onclick="clearDiv('reply{$message->id}');">
		</td>
	</tr>
</table>