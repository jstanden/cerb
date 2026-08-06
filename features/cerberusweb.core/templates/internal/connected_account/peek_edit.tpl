{$peek_context = CerberusContexts::CONTEXT_CONNECTED_ACCOUNT}
{$peek_context_id = $service->id}
{$service = $model->getService()}

{$form_id = uniqid()}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="connected_account">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="id" value="{$model->id}">
<input type="hidden" name="service_id" value="{$model->service_id}">
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-form">
		<div class="cerb-ui-form--row">
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'common.name'|devblocks_translate|capitalize}</label>
				<input type="text" name="name" value="{$model->name}" autofocus="autofocus">
			</div>
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'common.uri'|devblocks_translate|capitalize}</label>
				<input type="text" name="uri" value="{$model->uri}">
				<div class="cerb-ui-form--help">The alias used for this account in automations (letters, numbers, dots, and dashes)</div>
			</div>
		</div>

		<div class="cerb-ui-form--row">
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'common.service'|devblocks_translate|capitalize}</label>
				<div class="cerb-u-text-muted">{if $service}{$service->name}{/if}</div>
			</div>

			{if $active_worker->is_superuser}
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'common.owner'|devblocks_translate|capitalize}</label>
				<div>{include file="devblocks:cerberusweb.core::internal/peek/menu_actor_owner.tpl"}</div>
			</div>
			{/if}
		</div>
	</div>
</div>

{if $service}
	{$service_ext = $service->getExtension()}
	{if $service_ext}
		{$service_ext->renderAccountConfigForm($service, $model)}
	{/if}
{/if}

{if !empty($custom_fields)}
<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div class="cerb-ui-header--title-sm">{'common.custom_fields'|devblocks_translate|capitalize}</div>
	</div>
	<div class="cerb-ui-form">
		{include file="devblocks:cerberusweb.core::internal/custom_fields/form.tpl" custom_fields=$custom_fields}
	</div>
</div>
{/if}

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

{if !empty($model->id)}
	{include file="devblocks:cerberusweb.core::internal/peek/delete_confirm.tpl" noun="connected account"}
{/if}

<div class="status"></div>

<div class="buttons" style="margin-top:10px;">
	<button type="button" class="cerb-ui-button save"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	{if !empty($model->id) && $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" class="cerb-ui-button cerb-ui-button--subtle delete-prompt"><span class="cerb-icons cerb-icon-trash"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>

</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#{$form_id}');
	let $popup = genericAjaxPopupFind($frm);

	Devblocks.formDisableSubmit($frm);

	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"{'common.connected_account'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		$popup.css('overflow', 'inherit');

		$popup.find('button.save').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		if(window.CerbUI && CerbUI.Form) CerbUI.Form.ConfirmDelete($popup[0]);

		$popup.find('.cerb-peek-trigger').cerbPeekTrigger();
	});

	$frm.find('input:text:first').focus();
});
</script>
