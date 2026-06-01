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

<table cellspacing="0" cellpadding="2" border="0" width="98%">
	<tr>
		<td width="1%" nowrap="nowrap"><b>{'common.name'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			<input type="text" name="name" value="{$model->name}" style="width:98%;" autofocus="autofocus">
		</td>
	</tr>

	<tr>
		<td width="1%" valign="top" nowrap="nowrap"><b>{'common.token'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			{if $model->id}
				{$model->token_hint}
			{else}
				<input type="text" name="token" value="{$model->token_hint}" style="width:90%;" spellcheck="false" readonly="readonly">
				<button type="button" data-cerb-button-copy-token><span class="cerb-icons cerb-icon-copy"></span></button>
				<div>
					(this will only be displayed once)
				</div>
			{/if}
		</td>
	</tr>

	{if !empty($custom_fields)}
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false tbody=true}
	{/if}
</table>

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

<br>

<fieldset class="peek">
	<legend>{'common.scopes'|devblocks_translate|capitalize}</legend>

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
			<td width="60%" style="color:var(--cerb-color-background-contrast-125);">{$group.label}</td>
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
			<td style="color:var(--cerb-color-background-contrast-125);">{$child.label}</td>
		</tr>
		{/foreach}
		{/foreach}
	</table>
</fieldset>

{if !empty($model->id)}
<fieldset style="display:none;" class="delete">
	<legend>{'common.delete'|devblocks_translate|capitalize}</legend>

	<div>
		Are you sure you want to permanently delete this service token?
	</div>

	<button type="button" class="delete red">{'common.yes'|devblocks_translate|capitalize}</button>
	<button type="button" class="delete-cancel">{'common.no'|devblocks_translate|capitalize}</button>
</fieldset>
{/if}

<div class="buttons" style="margin-top:10px;">
	{if $model->id}
		<button type="button" class="save"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
		<button type="button" class="save-continue"><span class="cerb-icons cerb-icon-circle-arrow-right"></span> {'common.save_and_continue'|devblocks_translate|capitalize}</button>
		{if $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" class="delete-prompt"><span class="cerb-icons cerb-icon-circle-remove"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
	{else}
		<button type="button" class="save"><span class="cerb-icons cerb-icon-circle-plus"></span> {'common.create'|devblocks_translate|capitalize}</button>
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
		$popup.find('button.delete-prompt').click(Devblocks.callbackPeekEditDeletePrompt);
		$popup.find('button.delete-cancel').click(Devblocks.callbackPeekEditDeleteCancel);

		let $input_token = $frm.find('input[name=token]');

		$popup.find('button[data-cerb-button-copy-token]').on('click', function(e) {
			e.stopPropagation();
			navigator.clipboard.writeText($input_token.val());
			Devblocks.createAlert('Copied to clipboard!');
		});

		$popup.find('.cerb-scope-list').disableSelection();

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
