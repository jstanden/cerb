{$peek_context = CerberusContexts::CONTEXT_PROFILE_TAB}
{$peek_context_id = $model->id}
{$form_id = uniqid()}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="profile_tab">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<div class="cerb-tabs">
	{if !$model->id && $model->context && $packages}
	<ul>
		<li><a href="#package-library_{$form_id}">{'common.library'|devblocks_translate|capitalize}</a></li>
		<li><a href="#tab-build_{$form_id}">{'common.build'|devblocks_translate|capitalize}</a></li>
	</ul>
	{/if}
	
	{if !$model->id && $model->context && $packages}
	<div id="package-library_{$form_id}" class="package-library">
		<input type="hidden" name="package_context" value="{$model->context}">
		{include file="devblocks:cerberusweb.core::internal/package_library/editor_chooser.tpl"}
	</div>
	{/if}
	
	<div id="tab-build_{$form_id}">
		<div class="cerb-ui-panel cerb-ui-panel--spaced">
			<div class="cerb-ui-form">
				<div class="cerb-ui-form--field">
					<label class="cerb-ui-form--label">{'common.name'|devblocks_translate|capitalize}</label>
					<input type="text" name="name" value="{$model->name}" autofocus="autofocus">
				</div>

				<div class="cerb-ui-form--field">
					<label class="cerb-ui-form--label">{'common.record'|devblocks_translate|capitalize}</label>
					{if $model->id}
						<div>
							{$model_context = $model->getContextExtension(false)}
							{if $model_context}{$model_context->name}{else}{$model->context}{/if}
						</div>
					{else}
						<select name="context">
							<option value=""></option>
							{foreach from=$context_mfts item=context_mft}
							<option value="{$context_mft->id}" data-cerb-ui-icon="{$context_mft->params.icon|default:'collection'}" {if $context_mft->id == $model->context}selected="selected"{/if}>{$context_mft->name}</option>
							{/foreach}
						</select>
					{/if}
				</div>

				<div class="cerb-ui-form--field cerb-tab-extension" style="{if $model->context}{else}display:none;{/if}">
					<label class="cerb-ui-form--label">{'common.type'|devblocks_translate|capitalize}</label>
					{if $model->id}
						<div>
							{$tab_extension = $model->getExtension()}
							{if $tab_extension}{$tab_extension->manifest->name}{else}{$model->extension_id}{/if}
						</div>
					{else}
						<select name="extension_id">
							<option value=""></option>
							{foreach from=$tab_manifests item=tab_manifest}
							<option value="{$tab_manifest->id}">{$tab_manifest->name}</option>
							{/foreach}
						</select>
					{/if}
				</div>
			</div>
		</div>

		{if !empty($custom_fields)}
		<div class="cerb-ui-panel cerb-ui-panel--spaced">
			<div class="cerb-ui-form">
				{include file="devblocks:cerberusweb.core::internal/custom_fields/form.tpl" custom_fields=$custom_fields}
			</div>
		</div>
		{/if}

		{* The rest of config comes from the tab *}
		<div class="cerb-tab-params">
		{if $model->id}
			{$tab_extension = $model->getExtension()}
			{if $tab_extension && method_exists($tab_extension,'renderConfig')}
				{$tab_extension->renderConfig($model)}
			{/if}
		{/if}
		</div>
		
		{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

		<div data-cerb-fieldset-advanced class="cerb-ui-panel cerb-ui-panel--spaced">
			<div class="cerb-ui-header cerb-ui-header--tight">
				<div class="cerb-ui-header--title-sm">Advanced options</div>
			</div>
			{* Integrated editor toolbar: a single "suggest" (autocomplete) button merged into the KataEditor's strip. *}
			<ul class="cerb-ui-toolbar" data-cerb-editor-toolbar hidden>
				<li data-value="suggest" data-icon="autocomplete" title="{'common.autocomplete'|devblocks_translate|capitalize} (Ctrl/⌘+Space)"></li>
			</ul>
			<textarea name="options_kata" data-editor-lines="10" spellcheck="false">{$model->options_kata}</textarea>
		</div>

		{if !empty($model->id)}
			{include file="devblocks:cerberusweb.core::internal/peek/delete_confirm.tpl" noun="profile tab"}
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
	
	$popup.one('popup_open', function() {
		$popup.dialog('option','title',"{'common.profile.tab'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		$popup.css('overflow', 'inherit');
		
		var $select_context = $popup.find('select[name=context]');
		var $select_extension = $popup.find('select[name=extension_id]');
		var $tbody_extension = $popup.find('.cerb-tab-extension');
		var $params = $popup.find('.cerb-tab-params');
		
		// Buttons
		$popup.find('button.save').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		if(window.CerbUI && CerbUI.Form) CerbUI.Form.ConfirmDelete($popup[0]);

		// Record — native <select> keeps the POST value; enhance with type-to-filter (re-fires change below)
		if(window.CerbUI && CerbUI.SelectMenu)
			$select_context.each(function() { new CerbUI.SelectMenu(this); });

		// Package Library
		
		{if !$model->id && $model->context && $packages}
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
		
		// Events
		$select_context.on('change', function(e) {
			var context = $select_context.val();
			
			$select_extension.hide().empty();
			
			if(0 == context.length) {
				$tbody_extension.hide();
				
			} else {
				genericAjaxGet('', 'c=profiles&a=invoke&module=profile_tab&action=getExtensionsByContextJson&context=' + encodeURIComponent(context), function(json) {
					for(k in json) {
						if(json.hasOwnProperty(k)) {
							var $option = $('<option/>')
								.attr('value', k)
								.text(json[k])
								;
							
							$option.appendTo($select_extension);
						}
					}
					
					$select_extension.fadeIn();

					// Auto-load the config for the auto-selected first extension. Picking an option that's
					// already shown (e.g. the only/first type) wouldn't fire 'change', so the config would
					// otherwise stay empty on a fresh create (context picked from scratch).
					$select_extension.trigger('change');
				});

				$tbody_extension.show();
			}
		});
		
		// Load per-extension configuration on change
		$select_extension.on('change', function(e) {
			var extension_id = $select_extension.val();
			$params.empty();
			
			if(0 == extension_id)
				return;
			
			genericAjaxGet($params, 'c=profiles&a=invoke&module=profile_tab&action=getExtensionConfig&extension_id=' + encodeURIComponent(extension_id), function() {
				$params.fadeIn();
			});
		});

		// Options Editor

		let $fieldset_advanced = $frm.find('[data-cerb-fieldset-advanced]');

		let advanced_editor = new CerbUI.KataEditor($fieldset_advanced.find('textarea[name=options_kata]')[0], {
			onAutocomplete: CerbUI.KataEditor.kataFieldSource({
				'': [
					'hidden@bool:'
				],
				'hidden:': [
					'yes',
					'no',
					"{literal}{{worker_id == 123}}{/literal}",
					"{literal}{{not worker_is_superuser}}{/literal}",
					"{literal}{{record__type is record type ('ticket') and record_id == 123}}{/literal}"
				]
			}),
			toolbar: {
				sections: [ $fieldset_advanced.find('[data-cerb-editor-toolbar]')[0] ],
				onAction: function(value, ed) {
					if(value === 'suggest') { ed.openAutocomplete(); return true; }
					return false;
				}
			}
		});
	});
});
</script>
