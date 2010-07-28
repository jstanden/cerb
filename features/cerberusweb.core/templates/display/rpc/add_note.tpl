<form id="reply{$message->id}_form" action="{devblocks_url}{/devblocks_url}" method="POST" enctype="multipart/form-data">
<input type="hidden" name="c" value="display">
<input type="hidden" name="a" value="doAddNote">
<input type="hidden" name="id" value="{$message->id}">
<input type="hidden" name="ticket_id" value="{$message->ticket_id}">
<div class="block" style="width:98%;margin:10px;">
<table cellpadding="2" cellspacing="0" border="0" width="100%">
	<tr>
		<td><h2>Add Sticky Note</h2></td>
	</tr>
	<tr>
		<td><b>Author:</b> {$worker->getName()}</td>
	</tr>
	<tr>
		<td>
			<textarea name="content" rows="8" cols="80" id="note_content" class="reply" style="width:98%;border:1px solid rgb(180,180,180);padding:5px;"></textarea>
		</td>
	</tr>
	<tr>
		<td>
			<b>Notify workers</b>:<br>
			<div style="margin-left:20px;margin-bottom:1em;">
				<button type="button" class="chooser_worker"><span class="cerb-sprite sprite-add"></span></button>
			</div>
		</td>
	</tr>
	<tr>
		<td nowrap="nowrap" valign="top">
			<button type="button" onclick="genericAjaxPost('reply{$message->id}_form','{$message->id}notes','c=display&a=doAddNote');$('#reply{$message->id}').html('');"><span class="cerb-sprite sprite-check"></span> Add Note</button>
			<button type="button" onclick="$('#reply{$message->id}').html('');"><span class="cerb-sprite sprite-delete"></span> Cancel</button>
		</td>
	</tr>
</table>
</div>
</form>

<script type="text/javascript">
	$('#reply{$message->id}_form button.chooser_worker').each(function() {
		ajax.chooser(this,'cerberusweb.contexts.worker','notify_worker_ids');
	});
</script>