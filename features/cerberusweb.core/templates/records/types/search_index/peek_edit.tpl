{$peek_context = 'cerb.contexts.search.index'}
{$peek_context_id = $model->id}
{$form_id = uniqid()}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}">
	<input type="hidden" name="c" value="profiles">
	<input type="hidden" name="a" value="invoke">
	<input type="hidden" name="module" value="search_index">
	<input type="hidden" name="action" value="savePeekJson">
	<input type="hidden" name="view_id" value="{$view_id}">
	{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
	<input type="hidden" name="do_delete" value="0">
	<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

	<div class="cerb-ui-panel cerb-ui-panel--spaced">
		<div class="cerb-ui-form">
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'common.name'|devblocks_translate|capitalize}</label>
				<input type="text" name="name" value="{$model->name}" placeholder="Example Docs" autofocus="autofocus">
			</div>

			<div class="cerb-ui-form--row">
				<div class="cerb-ui-form--field">
					<label class="cerb-ui-form--label">{'common.uri'|devblocks_translate|capitalize}</label>
					<input type="text" name="uri" value="{$model->uri}" placeholder="(example.text)" spellcheck="false">
					<div class="cerb-ui-form--help">letters, numbers, and dots; globally unique</div>
				</div>
				<div class="cerb-ui-form--field">
					<label class="cerb-ui-form--label">Filter name</label>
					<input type="text" name="record_filter" value="{$model->record_filter}" placeholder="(text)" spellcheck="false">
					<div class="cerb-ui-form--help">letters, numbers, and dots; unique per record type; blank to omit</div>
				</div>
			</div>

			<div class="cerb-ui-form--row">
				<div class="cerb-ui-form--field">
					<label class="cerb-ui-form--label">{'common.priority'|devblocks_translate|capitalize} <span class="cerb-icons cerb-icon-sort-asc" title="0-255, ascending"></span></label>
					<input type="number" name="priority" min="0" max="255" value="{$model->priority|default:100}" style="width:5em;">
				</div>

				<div class="cerb-ui-form--field">
					<label class="cerb-ui-form--label">{'common.record.type'|devblocks_translate|capitalize}</label>
					{if $model && $model->record_type}
						<input type="hidden" name="record_type" value="{$model->record_type}">
						<div class="cerb-u-text-muted">{$model->record_type|capitalize}</div>
					{else}
						<select name="record_type" data-cerb-index-record-type>
							<option value="">({'common.choose'|devblocks_translate|lower})</option>
							{foreach from=$contexts item=ctx key=k}
								<option value="{$ctx->params.alias}" {if $model->record_type==$ctx->params.alias}selected="selected"{/if}>{$ctx->name}</option>
							{/foreach}
						</select>
					{/if}
				</div>

				<div class="cerb-ui-form--field">
					<label class="cerb-ui-form--label">{'common.type'|devblocks_translate|capitalize}</label>
					{if $model}
						<input type="hidden" name="extension_id" value="{$model->extension_id}">
						<div class="cerb-u-text-muted">{if $search_extension}{$search_extension->manifest->name}{else}{$model->extension_id}{/if}</div>
					{else}
						<select name="extension_id" data-cerb-index-selectmenu>
							<option value="">({'common.choose'|devblocks_translate|lower})</option>
							{if !empty($search_extensions)}
								{foreach from=$search_extensions item=search_ext}
									<option value="{$search_ext->id}">{$search_ext->name}</option>
								{/foreach}
							{/if}
						</select>
					{/if}
				</div>
			</div>

			{if !empty($custom_fields)}
			{include file="devblocks:cerberusweb.core::internal/custom_fields/form.tpl" custom_fields=$custom_fields}
			{/if}
		</div>
	</div>

	<div class="search-index-params">
		{if $search_extension}
			{$search_extension->renderConfig($model)}
		{/if}
	</div>

	{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

	{if !empty($model->id)}
		{include file="devblocks:cerberusweb.core::internal/peek/delete_confirm.tpl" noun="search index and all of its documents"}
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

		$popup.one('popup_open', function() {
			$popup.dialog('option','title',"{'Search Index'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
			$popup.find('[autofocus]:first').focus();
			$popup.css('overflow', 'inherit');

			let $record_type = $popup.find('[name=record_type]');
			let $extension = $popup.find('select[name=extension_id]');
			let $params = $popup.find('.search-index-params');

			$popup.find('button.save').click(Devblocks.callbackPeekEditSave);
			$popup.find('button.save-continue').click({ mode: 'continue' }, Devblocks.callbackPeekEditSave);
			$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
			if(window.CerbUI && CerbUI.Form) CerbUI.Form.ConfirmDelete($popup[0]);

			if(window.CerbUI && CerbUI.SelectMenu)
				$popup.find('select[data-cerb-index-selectmenu], select[data-cerb-index-record-type]').each(function() { new CerbUI.SelectMenu(this, { filter: true }); });

			{if !$model->id}
			$extension.on('change', function(e) {
				e.stopPropagation();
				let context = $record_type.val();
				let extension_id = $extension.val();

				if('' === extension_id) {
					$params.html('');
					return;
				}

				let $spinner = Devblocks.getSpinner();
				$params.html('').append($spinner, extension_id, ' ', context);

				let formData = new FormData();
				formData.set('c', 'profiles');
				formData.set('a', 'invoke');
				formData.set('module', 'search_index');
				formData.set('action', 'getExtensionConfig');
				formData.set('extension_id', extension_id);
				formData.set('record_type', context);

				genericAjaxPost(formData, $params);
			});
			{/if}

			$record_type.on('change', function(e) {
				e.stopPropagation();
				$params.trigger('cerb-search-index-params-change-context', $record_type.val());
			});
		});
	});
</script>
