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
				<tr>
					<td width="0%" nowrap="nowrap" valign="top"><b>From:</b></td>
					<td width="100%">[[ team address via agent address ]]</td>
				</tr>
				<tr>
					<td width="0%" nowrap="nowrap" valign="top"><b>To:</b></td>
					<td width="100%">
					{foreach from=$ticket->getRequesters() item=requester name=requesters}
						{$requester->email}
						{if !$smarty.foreach.requesters.last}, {/if}
					{/foreach}
					</td>
				</tr>
				<tr>
					<td width="0%" nowrap="nowrap" valign="top"><b>Cc:</b></td>
					<td width="100%"><textarea rows="2" cols="80" name="cc" class="cc"></textarea></td>
				</tr>
				<tr>
					<td width="0%" nowrap="nowrap" valign="top"><b>Bcc:</b></td>
					<td width="100%"><textarea rows="2" cols="80" name="bcc" class="cc"></textarea></td>
				</tr>
			</table>
		</td>
	</tr>
	<tr>
		<td><textarea name="content" rows="10" cols="80" class="reply">{$message->getContent()|trim|wordwrap:70|indent:1:'> '}</textarea></td>
	</tr>
	<tr>
		<td>
			<b>Attachments:</b><br>
			<input type="file" name="attachment[]"></input> 
			<a href="javascript:;" onclick="appendFileInput('displayReplyAttachments','attachment[]');">attach another file</a>
			<div id="displayReplyAttachments"></div>
		</td>
	</tr>
	<tr>
		<td>
			<table cellpadding="2" cellspacing="0" border="0" width="100%">
				<tr>
					<td width="0%" nowrap="nowrap" valign="top"><b>Actions:</b></td>
					<td width="100%" valign="top">
						Set priority: 
						<label><input type="radio" name="priority" value="0" {if $ticket->priority==0}checked{/if}><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/star_alpha.gif{/devblocks_url}"></label>
						<label><input type="radio" name="priority" value="25" {if $ticket->priority==25}checked{/if}><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/star_green.gif{/devblocks_url}"></label>
						<label><input type="radio" name="priority" value="50" {if $ticket->priority==50}checked{/if}><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/star_yellow.gif{/devblocks_url}"></label>
						<label><input type="radio" name="priority" value="75" {if $ticket->priority==75}checked{/if}><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/star_red.gif{/devblocks_url}"></label>
						<br>
						Set status: 
						<label><input type="radio" name="status" value="O" {if $ticket->status=='O'}checked{/if}>open</label>
						<label><input type="radio" name="status" value="W" {if $ticket->status=='W'}checked{/if}>waiting for reply</label>
						<label><input type="radio" name="status" value="C" {if $ticket->status=='C'}checked{/if}>closed</label>
						<label><input type="radio" name="status" value="D" {if $ticket->status=='D'}checked{/if}>deleted</label>
						<br>
					</td>
				</tr>
			</table>
		</td>
	</tr>
	<tr>
		<td>
			<input type="submit" value="Send">
			<input type="button" value="Discard" onclick="ajax.discard('{$message->id}');">
		</td>
	</tr>
</table>