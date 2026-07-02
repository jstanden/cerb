{$peek_context = CerberusContexts::CONTEXT_CLASSIFIER}
{$peek_context_id = $model->id}
{$frm_id = uniqid()}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$frm_id}">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="classifier">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-form">
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'common.name'|devblocks_translate|capitalize}</label>
			<input type="text" name="name" value="{$model->name}" autofocus="autofocus">
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'common.owner'|devblocks_translate|capitalize}</label>
			{include file="devblocks:cerberusweb.core::internal/peek/menu_actor_owner.tpl"}
		</div>

		{if !empty($model)}
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'common.training'|devblocks_translate|capitalize}</label>
			<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2">
				<label class="cerb-ui-toggle" id="doRetrain{$frm_id}">
					<input type="checkbox" name="do_retrain" value="1">
					<span class="cerb-ui-toggle--slider"></span>
				</label>
				<label for="doRetrain{$frm_id}">Build a new model using the training examples</label>
			</div>
		</div>
		{/if}

		{if !empty($custom_fields)}
		{include file="devblocks:cerberusweb.core::internal/custom_fields/form.tpl" custom_fields=$custom_fields}
		{/if}
	</div>
</div>

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

{if !empty($model->id)}
	{include file="devblocks:cerberusweb.core::internal/peek/delete_confirm.tpl" noun="classifier, its classes, and training data"}
{/if}

<div class="buttons" style="margin-top:10px;">
	<button type="button" class="cerb-ui-button save"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	{if !empty($model->id) && $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" class="cerb-ui-button cerb-ui-button--subtle delete-prompt"><span class="cerb-icons cerb-icon-trash"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>

</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#{$frm_id}');
	let $popup = genericAjaxPopupFind($frm);

	Devblocks.formDisableSubmit($frm);

	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"{'common.classifier'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		$popup.css('overflow', 'inherit');

		// Buttons
		$popup.find('button.save').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		if(window.CerbUI && CerbUI.Form) CerbUI.Form.ConfirmDelete($popup[0]);

		$popup.find('.cerb-peek-trigger').cerbPeekTrigger();

		// Focus
		$popup.find('input:text[name=name]').focus();

	});
});
</script>
