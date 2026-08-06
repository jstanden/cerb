{$peek_context = CerberusContexts::CONTEXT_CONNECTED_SERVICE}
{$peek_context_id = $model->id}
{$form_id = uniqid()}

{$service_ext = $model->getExtension()}

<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="connected_service">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<div class="cerb-tabs">
	{if !$id}
	<ul>
		{if $packages}<li><a href="#service-library_{$form_id}">{'common.library'|devblocks_translate|capitalize}</a></li>{/if}
		<li><a href="#service-builder_{$form_id}">{'common.build'|devblocks_translate|capitalize}</a></li>
	</ul>
	{/if}

	{if !$id && $packages}
	<div id="service-library_{$form_id}" class="package-library">
		{include file="devblocks:cerberusweb.core::internal/package_library/editor_chooser.tpl"}
	</div>
	{/if}

	<div id="service-builder_{$form_id}">
		<div class="cerb-ui-panel cerb-ui-panel--spaced">
			<div class="cerb-ui-form">
				<div class="cerb-ui-form--row">
					<div class="cerb-ui-form--field">
						<label class="cerb-ui-form--label">{'common.name'|devblocks_translate|capitalize}</label>
						<input type="text" name="name" value="{$model->name}" autofocus="autofocus">
					</div>

					<div class="cerb-ui-form--field">
						<label class="cerb-ui-form--label">{'common.uri'|devblocks_translate}</label>
						<input type="text" name="uri" value="{$model->uri}">
						<div class="cerb-ui-form--help">(letters, numbers, and dashes)</div>
					</div>
				</div>

				<div class="cerb-ui-form--field">
					<label class="cerb-ui-form--label">{'common.type'|devblocks_translate|capitalize}</label>
					{if 0 == $model->id}
						<select name="extension_id" data-cerb-service-selectmenu>
							<option value="">({'common.choose'|devblocks_translate|lower})</option>
							{foreach from=$service_exts item=service_ext}
							<option value="{$service_ext->id}">{$service_ext->name}</option>
							{/foreach}
						</select>
					{elseif $service_ext}
						<div class="cerb-u-text-muted">{$service_ext->manifest->name}</div>
					{/if}
				</div>

				{if !empty($model->id)}
				<div class="cerb-ui-form--field">
					<label class="cerb-ui-form--label">{'common.image'|devblocks_translate|capitalize}</label>
					<div>
						<span class="cerb-ui-avatar" style="width:50px;height:50px;font-size:21px;"
							data-cerb-image-editor data-context="{CerberusContexts::CONTEXT_CONNECTED_SERVICE}" data-context-id="{$model->id}" data-name="avatar_image"
							data-avatar="{$model->name}" data-avatar-seed="connected_service:{$model->id}"
							data-avatar-image="{devblocks_url}c=avatars&context=connected_service&context_id={$model->id}{/devblocks_url}?v={$model->updated_at}"></span>
						<input type="hidden" name="avatar_image" value="">
					</div>
				</div>
				{/if}

				{if !empty($custom_fields)}
				{include file="devblocks:cerberusweb.core::internal/custom_fields/form.tpl" custom_fields=$custom_fields}
				{/if}
			</div>
		</div>

		<div id="{$form_id}Params">
		{if $service_ext}
			{$service_ext->renderConfigForm($model)}
		{/if}
		</div>

		{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

		{if !empty($model->id)}
			{include file="devblocks:cerberusweb.core::internal/peek/delete_confirm.tpl" noun="connected service"}
		{/if}

		<div class="buttons" style="margin-top:10px;">
			<button type="button" class="cerb-ui-button save"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
			{if !empty($model->id) && $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" class="cerb-ui-button cerb-ui-button--subtle delete-prompt"><span class="cerb-icons cerb-icon-trash"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
		</div>
	</div>
</div>

</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#{$form_id}');
	let $popup = genericAjaxPopupFind($frm);

	Devblocks.formDisableSubmit($frm);

	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"{'common.connected_service'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		$popup.css('overflow', 'inherit');

		var $params = $('#{$form_id}Params');

		// Buttons
		$popup.find('button.save').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		if(window.CerbUI && CerbUI.Form) CerbUI.Form.ConfirmDelete($popup[0]);

		// Avatar
		if(window.CerbUI && CerbUI.ImageEditor)
			$popup.find('[data-cerb-image-editor]').each(function() { new CerbUI.ImageEditor(this); });

		// Select
		if(window.CerbUI && CerbUI.SelectMenu)
			$popup.find('select[data-cerb-service-selectmenu]').each(function() { new CerbUI.SelectMenu(this, { filter: true }); });

		var $select = $popup.find('select[name=extension_id]')
			.on('change', function(e) {
				var extension_id = $(this).val();
				genericAjaxGet($params, 'c=profiles&a=invoke&module=connected_service&action=getExtensionParams&id=' + encodeURIComponent(extension_id));
			})
		;

		// Package Library

		{if !$id}
			var $tabs = $popup.find('.cerb-tabs');
			$tabs.find('> ul').each(function() {
				if(window.CerbUI && CerbUI.Tabs) new CerbUI.Tabs(this);
			});
			var $library_container = $tabs;
			{include file="devblocks:cerberusweb.core::internal/package_library/editor_chooser.js.tpl"}

			$library_container.on('cerb-package-library-form-submit', function(e) {
				$popup.one('peek_saved peek_error', function(e) {
					$library_container.triggerHandler('cerb-package-library-form-submit--done');
				});

				$popup.find('button.save').click();
			});
		{/if}

	});
});
</script>
