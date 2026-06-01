{$peek_context = CerberusContexts::CONTEXT_QUEUE_JOB}
{$peek_context_id = $model->id}
{$form_id = uniqid()}

<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}">
	<input type="hidden" name="c" value="profiles">
	<input type="hidden" name="a" value="invoke">
	<input type="hidden" name="module" value="queue_job">
	<input type="hidden" name="action" value="savePeekJson">
	<input type="hidden" name="view_id" value="{$view_id}">
	{if $model->id}<input type="hidden" name="id" value="{$model->id}">{/if}
	<input type="hidden" name="do_delete" value="0">
	<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

	<table cellspacing="0" cellpadding="2" border="0" width="98%">
		<tr>
			<td width="1%" nowrap="nowrap"><b>{'common.status'|devblocks_translate|capitalize}:</b></td>
			<td width="99%">
				{if $model->status_id == 0}
					<span class="cerb-icons cerb-icon-play"></span> Running
				{elseif $model->status_id == 1}
					<span class="cerb-icons cerb-icon-pause"></span> Paused
				{elseif $model->status_id == 2}
					<span class="cerb-icons cerb-icon-circle-ok"></span> {'common.done'|devblocks_translate|capitalize}
				{/if}
			</td>
		</tr>
		{if !empty($metadata_json)}
		<tr>
			<td valign="top" nowrap="nowrap"><b>Metadata:</b></td>
			<td>
				<textarea readonly spellcheck="false" rows="8" style="width:99%;font-family:monospace;font-size:0.9em;white-space:pre;word-wrap:normal;">{$metadata_json}</textarea>
			</td>
		</tr>
		{/if}
	</table>

	{if $model->id}
		<fieldset style="display:none;" class="delete">
			<legend>{'common.delete'|devblocks_translate|capitalize}</legend>
			<div>Are you sure you want to permanently delete this queue job and all of its messages?</div>
			<button type="button" class="delete red">{'common.yes'|devblocks_translate|capitalize}</button>
			<button type="button" class="delete-cancel">{'common.no'|devblocks_translate|capitalize}</button>
		</fieldset>
	{/if}

	<div class="buttons" style="margin-top:10px;">
		{if $model->id}
			{if $model->status_id != 2}
				<button type="button" class="save"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
			{/if}
			{if $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" class="delete-prompt"><span class="cerb-icons cerb-icon-circle-remove"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
		{/if}
	</div>
</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#{$form_id}');
	let $popup = genericAjaxPopupFind($frm);

	Devblocks.formDisableSubmit($frm);

	$popup.one('popup_open', function() {
		$popup.dialog('option', 'title', "Queue Job");
		$popup.css('overflow', 'inherit');

		$popup.find('button.save').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		$popup.find('button.delete-prompt').click(Devblocks.callbackPeekEditDeletePrompt);
		$popup.find('button.delete-cancel').click(Devblocks.callbackPeekEditDeleteCancel);
	});
});
</script>
