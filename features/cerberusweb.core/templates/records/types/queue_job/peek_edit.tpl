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

	<div class="cerb-ui-panel cerb-ui-panel--spaced">
		<div class="cerb-ui-form">
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'common.status'|devblocks_translate|capitalize}</label>
				<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-1">
					{if $model->status_id == 0}
						<span class="cerb-icons cerb-icon-play"></span> Running
					{elseif $model->status_id == 1}
						<span class="cerb-icons cerb-icon-pause"></span> Paused
					{elseif $model->status_id == 2}
						<span class="cerb-icons cerb-icon-circle-ok"></span> {'common.done'|devblocks_translate|capitalize}
					{elseif $model->status_id == 3}
						<span class="cerb-icons cerb-icon-ban"></span> Canceled
					{/if}
				</div>
			</div>

			{if !empty($metadata_json)}
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">Metadata</label>
				<textarea readonly spellcheck="false" rows="8" style="font-family:monospace;font-size:0.9em;white-space:pre;word-wrap:normal;">{$metadata_json}</textarea>
			</div>
			{/if}
		</div>
	</div>

	{if $model->id}
		{include file="devblocks:cerberusweb.core::internal/peek/delete_confirm.tpl" noun="queue job and all of its messages"}
	{/if}

	<div class="buttons" style="margin-top:10px;">
		{if $model->id}
			{if $model->status_id != 2}
				<button type="button" class="cerb-ui-button save"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
			{/if}
			{if $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" class="cerb-ui-button cerb-ui-button--subtle delete-prompt"><span class="cerb-icons cerb-icon-trash"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
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
		if(window.CerbUI && CerbUI.Form) CerbUI.Form.ConfirmDelete($popup[0]);
	});
});
</script>
