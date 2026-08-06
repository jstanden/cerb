{$peek_context = CerberusContexts::CONTEXT_MESSAGE}
{$peek_context_id = $model->id}
{$form_id = "frmMessagePeek{uniqid()}"}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="message">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

{$ticket = $model->getTicket()}
{$headers = $model->getHeaders()}

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-form">
		<div class="cerb-ui-form--row">
			{if $headers.from}
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'message.header.from'|devblocks_translate|capitalize}</label>
				<div>{$headers.from}</div>
			</div>
			{/if}

			{if $headers.to}
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'message.header.to'|devblocks_translate|capitalize}</label>
				<div>{$headers.to}</div>
			</div>
			{/if}
		</div>

		<div class="cerb-ui-form--row">
			{if $headers.subject}
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'message.header.subject'|devblocks_translate|capitalize}</label>
				<div>{$headers.subject}</div>
			</div>
			{/if}

			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'message.header.date'|devblocks_translate|capitalize}</label>
				<div>{$model->created_date|devblocks_date} <span class="cerb-u-text-muted">({$model->created_date|devblocks_prettytime})</span></div>
			</div>
		</div>
	</div>
</div>

{if !empty($custom_fields)}
<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div class="cerb-ui-header--title-sm">{'common.properties'|devblocks_translate|capitalize}</div>
	</div>
	<div class="cerb-ui-form">
		{include file="devblocks:cerberusweb.core::internal/custom_fields/form.tpl" custom_fields=$custom_fields}
	</div>
</div>
{/if}

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

{if !empty($model->id) && $active_worker->hasPriv("contexts.{$peek_context}.delete")}
	{include file="devblocks:cerberusweb.core::internal/peek/delete_confirm.tpl" noun="message"}
{/if}

<div class="buttons" style="margin-top:10px;">
	<button type="button" class="cerb-ui-button save"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	{if !empty($model->id) && $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" class="cerb-ui-button cerb-ui-button--subtle delete-prompt"><span class="cerb-icons cerb-icon-trash"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>
</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $popup = genericAjaxPopupFind('#{$form_id}');

	Devblocks.formDisableSubmit($popup);

	$popup.one('popup_open', function() {
		$popup.dialog('option','title',"{'common.edit'|devblocks_translate|capitalize|escape:'javascript' nofilter}: {'common.message'|devblocks_translate|capitalize|escape:'javascript' nofilter}");

		$popup.find('button.save').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		if(window.CerbUI && CerbUI.Form) CerbUI.Form.ConfirmDelete($popup[0]);
	});
});
</script>
