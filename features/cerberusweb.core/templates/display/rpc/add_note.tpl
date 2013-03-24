<form id="reply{$message->id}_form" action="{devblocks_url}{/devblocks_url}" method="POST" enctype="multipart/form-data">
<input type="hidden" name="c" value="display">
<input type="hidden" name="a" value="doAddNote">
<input type="hidden" name="id" value="{$message->id}">
<input type="hidden" name="ticket_id" value="{$message->ticket_id}">

<div class="block" style="width:98%;margin:10px;">
<table cellpadding="2" cellspacing="0" border="0" width="100%">
	<tr>
		<td><h2 style="color:rgb(50,50,50);">Add Sticky Note</h2></td>
	</tr>
	<tr>
		<td><b>Author:</b> {$worker->getName()}</td>
	</tr>
	<tr>
		<td>
			<textarea name="content" rows="8" cols="80" id="note_content" class="reply" style="width:98%;border:1px solid rgb(180,180,180);padding:5px;"></textarea>
			<div>
				<button type="button" onclick="ajax.chooserSnippet('snippets',$('#note_content'), { '{CerberusContexts::CONTEXT_TICKET}':'{$message->ticket_id}', '{CerberusContexts::CONTEXT_MESSAGE}':'{$message->id}', '{CerberusContexts::CONTEXT_WORKER}':'{$active_worker->id}' });">{'common.snippets'|devblocks_translate|capitalize}</button>
			</div>
		</td>
	</tr>
	<tr>
		<td style="padding-top:5px;">
			<b>{'common.notify_watchers_and'|devblocks_translate}</b>:<br>
			<div style="margin-left:20px;margin-bottom:1em;">
				<button type="button" class="chooser_worker"><span class="cerb-sprite sprite-view"></span></button>
			</div>
		</td>
	</tr>
	<tr>
		<td nowrap="nowrap" valign="top">
			<button type="button" onclick="genericAjaxPost('reply{$message->id}_form','{$message->id}notes','c=display&a=doAddNote');$('#reply{$message->id}').html('');"><span class="cerb-sprite2 sprite-tick-circle"></span> Add Note</button>
			<button type="button" onclick="$('#reply{$message->id}').html('');"><span class="cerb-sprite2 sprite-cross-circle"></span> Cancel</button>
		</td>
	</tr>
</table>
</div>
</form>

<script type="text/javascript">
	$('#reply{$message->id}_form button.chooser_worker').each(function() {
		ajax.chooser(this,'cerberusweb.contexts.worker','notify_worker_ids', { autocomplete:true });
	});
</script>