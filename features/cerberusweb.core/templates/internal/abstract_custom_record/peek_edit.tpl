{$peek_context = $custom_record->getContext()}
{$peek_context_id = $model->id}
{$form_id = uniqid()}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="abstract_custom_record">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="_record_id" value="{$custom_record->id}">
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

		{if $owners_menu && 1 == count($owners_menu) && $owners_menu['App']}
			<input type="hidden" name="owner" value="{$owners_menu['App']->key}">
		{elseif $owners_menu}
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'common.owner'|devblocks_translate|capitalize}</label>
			{if 1 == count($owners_menu) && array_key_exists('App', $owners_menu)}
				{$owner = array_shift($owners_menu)}
				{$owner_parts = explode(':', $owner->key)}
				<div>
					<a class="cerb-ui-pill cerb-peek-trigger" data-context="{$owner_parts.0}" data-context-id="{$owner_parts.1}"><img class="cerb-avatar" src="{devblocks_url}c=avatars&ctx=cerberusweb.contexts.app&id=0{/devblocks_url}?v="> {$owner->label}</a>
					<input type="hidden" name="owner" value="{$owner->key}">
				</div>
			{else}
				{include file="devblocks:cerberusweb.core::internal/peek/menu_actor_owner.tpl"}
			{/if}
		</div>
		{/if}

		{if $custom_record->hasOption('avatars')}
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'common.photo'|devblocks_translate|capitalize}</label>
			<div>
				<span class="cerb-ui-avatar" style="width:50px;height:50px;font-size:21px;"
					data-cerb-image-editor data-context="{$custom_record->getContext()}" data-context-id="{$model->id}" data-name="avatar_image"
					data-avatar="{$model->name}" data-avatar-seed="{$custom_record->uri}:{$model->id}"
					data-avatar-image="{devblocks_url}c=avatars&context={$custom_record->uri}&context_id={$model->id}{/devblocks_url}?v={$model->updated_at}"></span>
				<input type="hidden" name="avatar_image" value="">
			</div>
		</div>
		{/if}

		{if !empty($custom_fields)}
			{include file="devblocks:cerberusweb.core::internal/custom_fields/form.tpl" custom_fields=$custom_fields}
		{/if}
	</div>
</div>

{if $custom_record->hasOption('attachments')}
<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div class="cerb-ui-header--title-sm">{'common.attachments'|devblocks_translate|capitalize}</div>
	</div>
	<div class="cerb-ui-file-upload" data-name="file_ids" data-multiple="1">
		{if !empty($attachments)}
			{foreach from=$attachments item=attachment name=attachments}
				<li data-file-id="{$attachment->id}" data-file-name="{$attachment->name}" data-file-size="{$attachment->storage_size}"></li>
			{/foreach}
		{/if}
	</div>
</div>
{/if}

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

{if !empty($model->id)}
	{include file="devblocks:cerberusweb.core::internal/peek/delete_confirm.tpl" noun=$custom_record->name|lower}
{/if}

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
		$popup.dialog('option','title',"{$custom_record->name|escape:'javascript' nofilter}");
		$popup.css('overflow', 'inherit');

		// Buttons
		$popup.find('button.save').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		if(window.CerbUI && CerbUI.Form) CerbUI.Form.ConfirmDelete($popup[0]);

		$popup.find('.cerb-peek-trigger').cerbPeekTrigger();

		// Avatar chooser
		if(window.CerbUI && CerbUI.ImageEditor)
			$popup.find('[data-cerb-image-editor]').each(function() { new CerbUI.ImageEditor(this); });

		// Attachments
		if(window.CerbUI && CerbUI.FileUpload)
			$popup.find('.cerb-ui-file-upload').each(function() { new CerbUI.FileUpload(this, { name: 'file_ids', multiple: true }); });
	});
});
</script>
