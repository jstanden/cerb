<input type="hidden" name="c" value="display">
<input type="hidden" name="a" value="sendComment">
<input type="hidden" name="id" value="{$message->id}">
<input type="hidden" name="ticket_id" value="{$ticket->id}">
<table cellpadding="2" cellspacing="0" border="0" width="100%" class="displayReplyTable">
	<tr>
		<th>Comment</th>
	</tr>
	<tr>
		<td>
			<table cellpadding="1" cellspacing="0" border="0" width="100%">
				<tr>
					<td width="0%" nowrap="nowrap" valign="top"><b>From:</b></td>
					<td width="100%">[[ agent from ]]</td>
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
			<a href="javascript:;" onclick="appendFileInput('displayCommentAttachments','attachment[]');">attach another file</a>
			<div id="displayCommentAttachments"></div>
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
						<label><input type="checkbox" name="closed" value="1" {if $ticket->is_closed}checked{/if}>closed</label>
						<br>
					</td>
				</tr>
			</table>
		</td>
	</tr>
	<tr>
		<td>
			<input type="submit" value="Save Comment">
			<input type="button" value="Discard" onclick="clearDiv('reply{$message->id}');">
		</td>
	</tr>
</table>