{$peek_context = CerberusContexts::CONTEXT_PROFILE_WIDGET}
{$form_id = uniqid()}
{$widget_type_icon = 'dashboard'}
{if $model->getExtension()}{$widget_type_icon = $model->getExtension()->getIcon()}{/if}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}" data-cerb-placeholders>
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="profile_widget">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

{if !$model->id}
	{if $profile_tab}
	<input type="hidden" name="profile_tab_id" value="{$profile_tab->id}">
	{else}
	<div class="cerb-ui-form" style="margin-bottom:5px;">
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'dashboard'|devblocks_translate|capitalize}</label>
			<div>
				<div class="cerb-ui-record-chooser" id="profileTabChooser_{$form_id}">
					{if $profile_tab}
						<li data-context-id="{$profile_tab->id}" data-label="{$profile_tab->name}"></li>
					{/if}
				</div>
			</div>
		</div>
	</div>
	{/if}
{/if}

<div class="cerb-tabs">
	{if !$model->id}
	<ul>
		{if $packages}<li><a href="#widget-library_{$form_id}">{'common.library'|devblocks_translate|capitalize}</a></li>{/if}
		<li><a href="#widget-builder_{$form_id}">{'common.build'|devblocks_translate|capitalize}</a></li>
		<li><a href="#widget-import_{$form_id}">{'common.import'|devblocks_translate|capitalize}</a></li>
	</ul>
	{/if}
	
	{if !$model->id && $packages}
	<div id="widget-library_{$form_id}" class="package-library">
		{include file="devblocks:cerberusweb.core::internal/package_library/editor_chooser.tpl"}
	</div>
	{/if}
	
	<div id="widget-builder_{$form_id}">
		<div class="cerb-ui-panel cerb-ui-panel--spaced">
			<div class="cerb-ui-form">
				<div class="cerb-ui-form--field">
					<label class="cerb-ui-form--label">{'common.name'|devblocks_translate|capitalize}</label>
					<input type="text" name="name" value="{$model->name}" autofocus="autofocus">
				</div>

				<div class="cerb-ui-form--row">
					<div class="cerb-ui-form--field" style="flex:0 0 auto;">
						<label class="cerb-ui-form--label">{'common.icon'|devblocks_translate|capitalize}</label>
						<div>
							<input type="text" name="icon" id="widgetIcon_{$form_id}" value="{$model->icon}">
						</div>
					</div>

					<div class="cerb-ui-form--field cerb-widget-extension"{if !$widget_extensions} style="display:none;"{/if}>
						<label class="cerb-ui-form--label">{'common.type'|devblocks_translate|capitalize}</label>
						<div>
							{if $model->id}
								{$widget_extension = $model->getExtension()}
								<div class="cerb-u-text-muted">{$widget_extension->manifest->name}</div>
							{else}
								<select name="extension_id">
									<option value="">-- {'common.choose'|devblocks_translate|lower} --</option>
									{foreach from=$widget_extensions item=widget_extension}
									{if DevblocksPlatform::strStartsWith($widget_extension->name, '(Deprecated)')}
									{else}
									<option value="{$widget_extension->id}" data-cerb-ui-icon="{$widget_extension->params['icon']|default:'dashboard'}">{$widget_extension->name}</option>
									{/if}
									{/foreach}
								</select>
							{/if}
						</div>
					</div>

					<div class="cerb-ui-form--field">
						<label class="cerb-ui-form--label">{'common.width'|devblocks_translate|capitalize}</label>
						<div>
							{$widths = [1 => '25%', 2 => '50%', 3 => '75%', 4 => '100%']}
							{$current_width = $model->width_units|default:2}
							<input type="hidden" name="width_units" id="widthUnits_{$form_id}" value="{$current_width}">
							<div class="cerb-ui-switcher" data-cerb-input="widthUnits_{$form_id}">
								{foreach from=$widths item=width_label key=width}
									<button type="button" data-value="{$width}"{if $current_width == $width} class="cerb-ui-switcher--active"{/if}>{$width_label}</button>
								{/foreach}
							</div>
						</div>
					</div>
				</div>

				{if !empty($custom_fields)}
				{* bulk/form.tpl with tbody=true emits <tbody> rows → needs a <table> wrapper *}
				<table cellspacing="0" cellpadding="2" border="0" width="98%">
					{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false tbody=true}
				</table>
				{/if}
			</div>
		</div>
		
		{* The rest of config comes from the widget *}
		<div class="cerb-widget-params">
		{if $model->id}
			{$widget_extension = $model->getExtension()}
			{if $widget_extension && method_exists($widget_extension,'renderConfig')}
				{$widget_extension->renderConfig($model)}
			{/if}
		{/if}
		</div>
		
		<div data-cerb-fieldset-advanced class="cerb-ui-panel cerb-ui-panel--spaced cerb-u-mt-3">
			<div class="cerb-ui-header cerb-ui-header--tight">
				<div class="cerb-ui-header--title-sm">Advanced options:</div>
			</div>
			<div class="cerb-ui-kataeditor-wrap">
				{* Integrated editor toolbar: a single "suggest" (autocomplete) button merged into the KataEditor's strip. *}
				<ul class="cerb-ui-toolbar" data-cerb-editor-toolbar hidden>
					<li data-value="suggest" data-icon="autocomplete" title="{'common.autocomplete'|devblocks_translate|capitalize} (Ctrl/⌘+Space)"></li>
				</ul>
				<textarea name="options_kata" class="placeholders" data-editor-lines="10" spellcheck="false">{$model->options_kata}</textarea>
			</div>
		</div>
		
		<div class="cerb-placeholder-menu" style="display:none;">
		{include file="devblocks:cerberusweb.core::internal/profiles/tabs/dashboard/toolbar.tpl"}
		</div>
		
		{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}
		
		{if !empty($model->id)}
		{include file="devblocks:cerberusweb.core::internal/peek/delete_confirm.tpl" noun="profile widget"}
		{/if}

		<div class="buttons" style="margin-top:10px;">
			<button type="button" class="cerb-ui-button save"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
			{if $model->id}<button type="button" class="cerb-ui-button cerb-ui-button--subtle save-continue"><span class="cerb-icons cerb-icon-circle-arrow-right"></span> {'common.save_and_continue'|devblocks_translate|capitalize}</button>{/if}
			{if !empty($model->id) && $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" class="cerb-ui-button cerb-ui-button--subtle delete-prompt"><span class="cerb-icons cerb-icon-trash"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
		</div>
	</div>
	
	{if !$model->id}
	<div id="widget-import_{$form_id}">
		<textarea name="import_json" style="width:100%;height:250px;box-sizing:border-box;white-space:pre;word-wrap:normal;" rows="10" cols="45" spellcheck="false" placeholder="Paste a dashboard widget in JSON format"></textarea>
		
		<div>
			<button type="button" class="cerb-ui-button import"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.import'|devblocks_translate|capitalize}</button>
		</div>
	</div>
	{/if}
</div>

</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#{$form_id}');
	let $popup = genericAjaxPopupFind($frm);
	let $fieldset_advanced = $frm.find('[data-cerb-fieldset-advanced]');

	Devblocks.formDisableSubmit($frm);
	
	$popup.one('popup_open', function() {
		$popup.dialog('option','title',"{'common.profile.widget'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		$popup.css('overflow', 'inherit');
		
		// Buttons
		$popup.find('button.save').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.save-continue').click({ mode: 'continue' }, Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		if(window.CerbUI && CerbUI.Form) CerbUI.Form.ConfirmDelete($popup[0]);
		$popup.find('button.import').click(Devblocks.callbackPeekEditSave);

		// Icon picker (empty value inherits the widget type's default icon shown as placeholder)
		if(window.CerbUI && CerbUI.IconPicker)
			$popup.find('#widgetIcon_{$form_id}').each(function() { new CerbUI.IconPicker(this, { emptyIcon: '{$widget_type_icon}', allowClear: true }); });

		// Width switcher (hidden input carries the POST value)
		if(window.CerbUI && CerbUI.Switcher) {
			let $width = $popup.find('#widthUnits_{$form_id}');
			let widthEl = $popup.find('[data-cerb-input="widthUnits_{$form_id}"]')[0];
			if(widthEl)
				new CerbUI.Switcher(widthEl, { value: $width.val(), onSelect: function(value) { $width.val(value); } });
		}
		
		// Package Library
		
		{if !$model->id}
			var $tabs = $popup.find('.cerb-tabs');
			$tabs.find('> ul').each(function() {
				if(window.CerbUI && CerbUI.Tabs) new CerbUI.Tabs(this);
			});
			
			{if $packages}
				var $library_container = $tabs;
				{include file="devblocks:cerberusweb.core::internal/package_library/editor_chooser.js.tpl"}
				
				$library_container.on('cerb-package-library-form-submit', function(e) {
					$popup.one('peek_saved peek_error', function(e) {
						$library_container.triggerHandler('cerb-package-library-form-submit--done');
					});
					
					$popup.find('button.save').click();
				});
			{/if}
		{/if}
		
		// Toolbar
		var $toolbar = $popup.find('.cerb-placeholder-menu').detach();
		var $params = $popup.find('.cerb-widget-params');
		var $select_extension = $popup.find('select[name="extension_id"]');
		var $tbody_widget_extension = $popup.find('.cerb-widget-extension');

		// Type picker (icons per widget type, on the trigger and in the menu); rebuilt when the
		// extension list is repopulated via the profile-tab chooser below.
		var selectMenu = null;
		var buildExtensionSelectMenu = function() {
			if(selectMenu) { selectMenu.destroy(); selectMenu = null; }
			if(window.CerbUI && CerbUI.SelectMenu && $select_extension.length && $select_extension[0].options.length)
				selectMenu = new CerbUI.SelectMenu($select_extension[0], { filter: true });
		};
		buildExtensionSelectMenu();

		// Profile-tab chooser (on pick: load its placeholder toolbar + the matching widget extensions)
		if(window.CerbUI && CerbUI.RecordChooser)
			new CerbUI.RecordChooser($popup.find('#profileTabChooser_{$form_id}')[0], {
				context: '{CerberusContexts::CONTEXT_PROFILE_TAB}',
				name: 'profile_tab_id',
				emptyIcon: 'window-left',
				query: 'type:"cerb.profile.tab.dashboard"',
				onSelect: function(item) {
					var id = item.id;
					if(selectMenu) { selectMenu.destroy(); selectMenu = null; }
					$select_extension.empty();
					$tbody_widget_extension.hide();
					$toolbar.empty().detach();
					$params.hide().empty();
					if(id) {
						genericAjaxGet($toolbar,'c=profiles&a=invoke&module=profile_tab&action=getPlaceholderToolbar&tab_id=' + encodeURIComponent(id));
						genericAjaxGet('', 'c=profiles&a=invoke&module=profile_widget&action=getExtensionsByTabContextJson&tab_id=' + encodeURIComponent(id), function(json) {
							for(k in json) {
								if(json.hasOwnProperty(k))
									$('<option/>').attr('value', k).attr('data-cerb-ui-icon', json[k].icon).text(json[k].name).appendTo($select_extension);
							}
							buildExtensionSelectMenu();
							$tbody_widget_extension.fadeIn();
						});
					} else {
						$tbody_widget_extension.hide();
					}
				}
			});
		
		// Abstract peeks
		$popup.find('.cerb-peek-trigger').cerbPeekTrigger();
		
		// Switching extension params
		var $select = $popup.find('select[name=extension_id]');
		
		$select.on('change', function(e) {
			var extension_id = $select.val();
			
			$toolbar.detach();
			
			if(0 == extension_id.length) {
				$params.hide().empty();
				return;
			}
			
			// Fetch via Ajax
			genericAjaxGet($params, 'c=profiles&a=renderWidgetConfig&extension=' + encodeURIComponent(extension_id), function(html) {
				$params.find('.cerb-peek-trigger').cerbPeekTrigger();
				$params.fadeIn();
			});
		});
		
		// Placeholder toolbar — reveal it below the focused config field
		$popup.delegate(':text.placeholders, textarea.placeholders', 'focus', function(e) {
			e.stopPropagation();

			var $target = $(e.target);

			if(0 == $target.nextAll($toolbar).length) {
				$toolbar.find('div.tester').html('');
				$toolbar.show().insertAfter($target);
				$toolbar.data('src', $target);
			}
		});

		// Options Editor
		let advanced_editor = new CerbUI.KataEditor($fieldset_advanced.find('textarea[name=options_kata]')[0], {
			onAutocomplete: CerbUI.KataEditor.kataFieldSource({
				'': [
					'hidden@bool:'
				],
				'hidden:': [
					'yes',
					'no',
					'{literal}{{record_id == 123}}{/literal}',
					'{literal}{{not current_worker_is_superuser}}{/literal}',
					"{literal}{{'Support' not in current_worker_groups|column('name')}}{/literal}",
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
