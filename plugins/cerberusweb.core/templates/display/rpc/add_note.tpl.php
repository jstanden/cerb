<input type="hidden" name="c" value="display">
<input type="hidden" name="a" value="doAddNote">
<input type="hidden" name="id" value="{$message->id}">
<input type="hidden" name="ticket_id" value="{$message->ticket_id}">
<div class="block" style="width:98%;margin:10px;">
<table cellpadding="2" cellspacing="0" border="0" width="100%">
	<tr>
		<td><h2>Add Note</h2></td>
	</tr>
	<tr>
		<td><b>Author:</b> {$worker->getName()}</td>
	</tr>
	<tr>
		<td>
			<textarea name="content" rows="10" cols="80" id="note_content" class="reply" style="width:98%;border:1px solid rgb(180,180,180);padding:5px;"></textarea>
		</td>
	</tr>
	<tr>
		<td nowrap="nowrap" valign="top">
			<button type="button" onclick="genericAjaxPost('reply{$message->id}','{$message->id}notes','c=display&a=doAddNote');clearDiv('reply{$message->id}');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> Add Note</button>
			<button type="button" onclick="clearDiv('reply{$message->id}');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete.gif{/devblocks_url}" align="top"> Cancel</button>
		</td>
	</tr>
</table>
</div>