{$peek_context = 'cerb.contexts.service.token'}
{$peek_context_id = $model->id}
{$form_id = uniqid()}

<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="service_token">
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
			<label class="cerb-ui-form--label">{'common.token'|devblocks_translate|capitalize}</label>
			{if $model->id}
				<div class="cerb-u-text-muted">{$model->token_hint}</div>
			{else}
				<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-1">
					<input type="text" name="token" value="{$model->token_hint}" style="flex:1 1 auto;min-width:0;" spellcheck="false" readonly="readonly">
					<button type="button" class="cerb-ui-button cerb-ui-button--subtle" data-cerb-button-copy-token><span class="cerb-icons cerb-icon-copy"></span></button>
				</div>
				<div class="cerb-ui-form--help">(this will only be displayed once)</div>
			{/if}
		</div>

		{if !empty($custom_fields)}
		{include file="devblocks:cerberusweb.core::internal/custom_fields/form.tpl" custom_fields=$custom_fields}
		{/if}
	</div>
</div>

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div class="cerb-ui-header--title-sm">{'common.scopes'|devblocks_translate|capitalize}</div>
	</div>

	<table cellspacing="0" cellpadding="4" border="0" width="100%" class="cerb-scope-list">
		{foreach $available_scopes as $group_key => $group}
		<tr style="border-top:1px solid rgba(128,128,128,0.15);">
			<td width="40%" nowrap="nowrap">
				<label>
					<input type="checkbox" name="scope[]" value="{$group_key}" data-cerb-scope-parent="{$group_key}"
						{if isset($scopes[$group_key])}checked="checked"{/if}
					> <b>{$group_key}</b>
				</label>
			</td>
			<td width="60%" class="cerb-u-text-muted">{$group.label}</td>
		</tr>
		{foreach $group.children as $scope_key => $child}
		<tr>
			<td style="padding-left:24px;" nowrap="nowrap">
				<label>
					<input type="checkbox" name="scope[]" value="{$scope_key}" data-cerb-scope-child="{$group_key}"
						{if isset($scopes[$group_key]) || isset($scopes[$scope_key])}checked="checked"{/if}
						{if isset($scopes[$group_key])}disabled="disabled"{/if}
					> {$child.name}
				</label>
			</td>
			<td class="cerb-u-text-muted">{$child.label}</td>
		</tr>
		{/foreach}
		{/foreach}
	</table>
</div>

{if !empty($model->id)}
	{include file="devblocks:cerberusweb.core::internal/peek/delete_confirm.tpl" noun="service token"}
{/if}

<div class="buttons" style="margin-top:10px;">
	{if $model->id}
		<button type="button" class="cerb-ui-button save"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
		<button type="button" class="cerb-ui-button cerb-ui-button--subtle save-continue"><span class="cerb-icons cerb-icon-circle-arrow-right"></span> {'common.save_and_continue'|devblocks_translate|capitalize}</button>
		{if $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" class="cerb-ui-button cerb-ui-button--subtle delete-prompt"><span class="cerb-icons cerb-icon-trash"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
	{else}
		<button type="button" class="cerb-ui-button save"><span class="cerb-icons cerb-icon-circle-plus"></span> {'common.create'|devblocks_translate|capitalize}</button>
	{/if}
</div>

</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#{$form_id}');
	let $popup = genericAjaxPopupFind($frm);

	Devblocks.formDisableSubmit($frm);

	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"{'Service Tokens'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		$popup.find('[autofocus]:first').focus();
		$popup.css('overflow', 'inherit');

		$popup.find('button.save').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.save-continue').click({ mode: 'continue' }, Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		if(window.CerbUI && CerbUI.Form) CerbUI.Form.ConfirmDelete($popup[0]);

		let $input_token = $frm.find('input[name=token]');

		$popup.find('button[data-cerb-button-copy-token]').on('click', function(e) {
			e.stopPropagation();
			navigator.clipboard.writeText($input_token.val());
			Devblocks.createAlert('Copied to clipboard!');
		});

		$popup.find('.cerb-scope-list').each(function() { if(window.CerbUI && CerbUI.utils) CerbUI.utils.disableSelection(this); });

		// Scope parent/child checkbox interaction
		$popup.find('[data-cerb-scope-parent]').on('change', function() {
			let $parent = $(this);
			let group = $parent.data('cerb-scope-parent');
			let $children = $popup.find('[data-cerb-scope-child="' + group + '"]');

			if($parent.prop('checked')) {
				$children.prop('checked', true).prop('disabled', true);
			} else {
				$children.prop('checked', false).prop('disabled', false);
			}
		});
	});
});
</script>
