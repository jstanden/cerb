{$peek_context = CerberusContexts::CONTEXT_CUSTOM_FIELDSET}
{$peek_context_id = $model->id}
{$is_writeable = !$model->id || CerberusContexts::isWriteableByActor(CerberusContexts::CONTEXT_CUSTOM_FIELDSET, $model, $active_worker)}
{$form_id = uniqid()}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="custom_fieldset">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

{include file="devblocks:cerberusweb.core::records/types/workflow/managed_callout.tpl" workflow=$workflow workflow_url=$workflow_url noun="custom fieldset"}

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-form">
		<div class="cerb-ui-form--row">
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'common.name'|devblocks_translate|capitalize}</label>
				<input type="text" name="name" value="{$model->name}" autofocus="autofocus">
			</div>

			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'common.type'|devblocks_translate|capitalize}</label>
				{if !empty($model->id)}
					<input type="hidden" name="context" value="{$model->context}">
					<div class="cerb-u-text-muted">{if $contexts.{$model->context}}{$contexts.{$model->context}->name}{/if}</div>
				{else}
					<select name="context" data-cerb-fieldset-selectmenu>
						{foreach from=$contexts item=ctx key=k}
						<option value="{$k}" {if $model->context==$k}selected="selected"{/if}>{$ctx->name}</option>
						{/foreach}
					</select>
				{/if}
			</div>
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'common.owner'|devblocks_translate|capitalize}</label>
			<div>
				{include file="devblocks:cerberusweb.core::internal/peek/menu_actor_owner.tpl" model=$model}
			</div>
		</div>
	</div>
</div>

{if !empty($model->id) && $is_writeable}
	{include file="devblocks:cerberusweb.core::internal/peek/delete_confirm.tpl" noun="custom fieldset"}
{/if}

<div class="buttons" style="margin-top:10px;">
{if $active_worker->hasPriv("contexts.{$peek_context}.update") && (empty($model->id) || $is_writeable)}
	<button type="button" class="cerb-ui-button save"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.save_changes'|devblocks_translate}</button>
{/if}
{if $model->id && $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" class="cerb-ui-button cerb-ui-button--subtle delete-prompt"><span class="cerb-icons cerb-icon-trash"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>

</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#{$form_id}');
	let $popup = genericAjaxPopupFind($frm);

	Devblocks.formDisableSubmit($frm);

	$popup.one('popup_open', function() {
		$popup.dialog('option','title', '{'common.custom_fieldset'|devblocks_translate|capitalize|escape:'javascript' nofilter}');
		$popup.css('overflow', 'inherit');

		{if $is_writeable && $active_worker->hasPriv("contexts.{$peek_context}.update")}
		$popup.find('input:text:first').focus().select();
		{else}
		$popup.find('input,select,textarea').attr('disabled','disabled');
		{/if}

		$popup.find('button.save').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		if(window.CerbUI && CerbUI.Form) CerbUI.Form.ConfirmDelete($popup[0]);

		$popup.find('a.cerb-peek-trigger').cerbPeekTrigger();

		if(window.CerbUI && CerbUI.SelectMenu)
			$popup.find('select[data-cerb-fieldset-selectmenu]').each(function() { new CerbUI.SelectMenu(this, { filter: true }); });

	});
});
</script>
