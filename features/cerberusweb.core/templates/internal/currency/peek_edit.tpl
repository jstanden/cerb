{$peek_context = CerberusContexts::CONTEXT_CURRENCY}
{$peek_context_id = $model->id}
{$form_id = uniqid()}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="currency">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-form">
		<div class="cerb-ui-form--row">
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'common.name'|devblocks_translate|capitalize} <span class="cerb-ui-form--hint">({'common.singular'|devblocks_translate|lower})</span></label>
				<input type="text" name="name" value="{$model->name}" autofocus="autofocus" placeholder="US Dollar">
			</div>
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'common.name'|devblocks_translate|capitalize} <span class="cerb-ui-form--hint">({'common.plural'|devblocks_translate|lower})</span></label>
				<input type="text" name="name_plural" value="{$model->name_plural}" placeholder="US Dollars">
			</div>
		</div>

		<div class="cerb-ui-form--row">
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'dao.currency.symbol'|devblocks_translate|capitalize}</label>
				<input type="text" name="symbol" maxlength="2" value="{$model->symbol}" placeholder="$">
			</div>
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'dao.currency.code'|devblocks_translate|capitalize}</label>
				<input type="text" name="code" maxlength="3" value="{$model->code}" placeholder="USD">
			</div>
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'dao.currency.decimal_at'|devblocks_translate|capitalize}</label>
				<input type="text" name="decimal_at" maxlength="2" value="{$model->decimal_at}" placeholder="2">
			</div>
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'common.default'|devblocks_translate|capitalize}</label>
			<div>
				<input type="hidden" name="is_default" id="isDefault_{$form_id}" value="{$model->is_default}">
				<div class="cerb-ui-switcher" data-cerb-input="isDefault_{$form_id}">
					<button type="button" data-value="1"{if $model->is_default} class="cerb-ui-switcher--active"{/if}>{'common.yes'|devblocks_translate|capitalize}</button>
					<button type="button" data-value="0"{if !$model->is_default} class="cerb-ui-switcher--active"{/if}>{'common.no'|devblocks_translate|capitalize}</button>
				</div>
			</div>
		</div>

		{if !empty($custom_fields)}
		{include file="devblocks:cerberusweb.core::internal/custom_fields/form.tpl" custom_fields=$custom_fields}
		{/if}
	</div>
</div>

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

{if !empty($model->id)}
	{include file="devblocks:cerberusweb.core::internal/peek/delete_confirm.tpl" noun="currency"}
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

	$popup.one('popup_open', function() {
		$popup.dialog('option','title',"{'common.currency'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		$popup.css('overflow', 'inherit');

		$popup.find('button.save').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		if(window.CerbUI && CerbUI.Form) CerbUI.Form.ConfirmDelete($popup[0]);

		if(window.CerbUI && CerbUI.Switcher) {
			$popup.find('.cerb-ui-switcher[data-cerb-input]').each(function() {
				let input = document.getElementById(this.getAttribute('data-cerb-input'));
				new CerbUI.Switcher(this, {
					value: input ? input.value : null,
					onSelect: function(value) { if(input) input.value = value; }
				});
			});
		}
	});
});
</script>
