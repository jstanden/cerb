{$peek_context = CerberusContexts::CONTEXT_CALENDAR}
{$peek_context_id = $model->id}
{$form_id = "frmCalendarPeek{uniqid()}"}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="calendar">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

{if !$model->id}
<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-form">
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'common.owner'|devblocks_translate|capitalize}</label>
			<div>
				{include file="devblocks:cerberusweb.core::internal/peek/menu_actor_owner.tpl"}
			</div>
		</div>
	</div>
</div>
{/if}

<div class="cerb-tabs">
	{if !$model->id && $packages}
	<ul>
		<li><a href="#calendar-library_{$form_id}">{'common.library'|devblocks_translate|capitalize}</a></li>
		<li><a href="#calendar-builder_{$form_id}">{'common.build'|devblocks_translate|capitalize}</a></li>
	</ul>
	{/if}
	
	{if !$model->id && $packages}
	<div id="calendar-library_{$form_id}" class="package-library">
		{include file="devblocks:cerberusweb.core::internal/package_library/editor_chooser.tpl"}
	</div>
	{/if}
	
	<div id="calendar-builder_{$form_id}">
		<div class="cerb-ui-panel cerb-ui-panel--spaced">
			<div class="cerb-ui-form">
				<div class="cerb-ui-form--field">
					<label class="cerb-ui-form--label">{'common.name'|devblocks_translate|capitalize}</label>
					<input type="text" name="name" value="{$model->name}" autofocus="autofocus">
				</div>

				{if $model->id}
				<div class="cerb-ui-form--field">
					<label class="cerb-ui-form--label">{'common.owner'|devblocks_translate|capitalize}</label>
					<div>
						{include file="devblocks:cerberusweb.core::internal/peek/menu_actor_owner.tpl"}
					</div>
				</div>
				{/if}

				<div class="cerb-ui-form--field">
					<label class="cerb-ui-form--label">{'common.timezone'|devblocks_translate|capitalize}</label>
					<select name="timezone">
						<option value="">(use viewer's timezone)</option>
						{foreach from=$timezones item=timezone}
							<option value="{$timezone}" {if $timezone == $model->timezone}selected="selected"{/if}>{$timezone}</option>
						{/foreach}
					</select>
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

		{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

		{* Datasources *}

		<div class="cerb-ui-panel cerb-ui-panel--spaced">
			<div class="cerb-ui-header cerb-ui-header--tight">
				<div class="cerb-ui-header--title-sm">Calendar Events</div>
			</div>
			<div class="cerb-ui-form">
				<div class="cerb-ui-form--field">
					<label class="cerb-ui-form--label">Creating events</label>
					<div>
						<input type="hidden" name="params[manual_disabled]" id="manualDisabled_{$form_id}" value="{if !empty($model->params.manual_disabled)}1{else}0{/if}">
						<div class="cerb-ui-switcher" data-cerb-input="manualDisabled_{$form_id}">
							<button type="button" data-value="0"{if empty($model->params.manual_disabled)} class="cerb-ui-switcher--active"{/if}>{'common.enabled'|devblocks_translate|capitalize}</button>
							<button type="button" data-value="1"{if !empty($model->params.manual_disabled)} class="cerb-ui-switcher--active"{/if}>{'common.disabled'|devblocks_translate|capitalize}</button>
						</div>
					</div>
				</div>

				<div class="cerb-ui-form--field">
					<label class="cerb-ui-form--label">Synchronizing events</label>
					<div>
						<input type="hidden" name="params[sync_enabled]" id="syncEnabled_{$form_id}" value="{if !empty($model->params.sync_enabled)}1{else}0{/if}">
						<div class="cerb-ui-switcher" data-cerb-input="syncEnabled_{$form_id}">
							<button type="button" data-value="1"{if !empty($model->params.sync_enabled)} class="cerb-ui-switcher--active"{/if}>{'common.enabled'|devblocks_translate|capitalize}</button>
							<button type="button" data-value="0"{if empty($model->params.sync_enabled)} class="cerb-ui-switcher--active"{/if}>{'common.disabled'|devblocks_translate|capitalize}</button>
						</div>
					</div>
				</div>

				<div class="cerb-ui-form--field">
					<label class="cerb-ui-form--label">Start weeks on</label>
					<div>
						<input type="hidden" name="params[start_on_mon]" id="startOnMon_{$form_id}" value="{if !empty($model->params.start_on_mon)}1{else}0{/if}">
						<div class="cerb-ui-switcher" data-cerb-input="startOnMon_{$form_id}">
							<button type="button" data-value="0"{if empty($model->params.start_on_mon)} class="cerb-ui-switcher--active"{/if}>{'common.day.sunday'|devblocks_translate|capitalize}</button>
							<button type="button" data-value="1"{if !empty($model->params.start_on_mon)} class="cerb-ui-switcher--active"{/if}>{'common.day.monday'|devblocks_translate|capitalize}</button>
						</div>
					</div>
				</div>

				<div class="cerb-ui-form--field">
					<label class="cerb-ui-form--label">Start times</label>
					<div>
						<input type="hidden" name="params[hide_start_time]" id="hideStartTime_{$form_id}" value="{if !empty($model->params.hide_start_time)}1{else}0{/if}">
						<div class="cerb-ui-switcher" data-cerb-input="hideStartTime_{$form_id}">
							<button type="button" data-value="0"{if empty($model->params.hide_start_time)} class="cerb-ui-switcher--active"{/if}>Visible</button>
							<button type="button" data-value="1"{if !empty($model->params.hide_start_time)} class="cerb-ui-switcher--active"{/if}>Hidden</button>
						</div>
					</div>
				</div>
			</div>
		</div>

		<div class="cerb-ui-panel cerb-ui-panel--spaced calendar-events" style="{if !empty($model->params.manual_disabled)}display:none;{/if}">
			<div class="cerb-ui-header cerb-ui-header--tight">
				<div class="cerb-ui-header--title-sm">Created Events</div>
			</div>
			<div class="cerb-ui-form">
				<div class="cerb-ui-form--field">
					<label class="cerb-ui-form--label">{'common.available'|devblocks_translate|capitalize} events color</label>
					<input type="text" name="params[color_available]" value="{$model->params.color_available|default:'#A0D95B'}" class="color-picker">
				</div>

				<div class="cerb-ui-form--field">
					<label class="cerb-ui-form--label">{'common.busy'|devblocks_translate|capitalize} events color</label>
					<input type="text" name="params[color_busy]" value="{$model->params.color_busy|default:'C8C8C8'}" class="color-picker">
				</div>
			</div>
		</div>

		{section start=0 loop=5 name=series}
		{$series_idx = $smarty.section.series.index}
		{$series_prefix = "[series][{$series_idx}]"}

		<div id="calendar{$model->id}Datasource{$series_idx}" class="cerb-ui-panel cerb-ui-panel--spaced sync-events" style="{if empty($model->params.sync_enabled)}display:none;{/if}">
			<div class="cerb-ui-header cerb-ui-header--tight">
				<div class="cerb-ui-header--title-sm">Synchronize</div>
			</div>
			<div class="cerb-ui-form">
				<div class="cerb-ui-form--field">
					<label class="cerb-ui-form--label">Events from</label>
					{$source = $model->params.series[{$series_idx}].datasource}
					<select name="params{$series_prefix}[datasource]" class="datasource-selector" params_prefix="{$series_prefix}">
						<option value=""></option>
						{foreach from=$datasource_extensions item=datasource_ext key=datasource_ext_id}
						<option value="{$datasource_ext_id}" {if $datasource_ext_id==$source}selected="selected"{/if}>{$datasource_ext->name}</option>
						{/foreach}
					</select>
				</div>

				<div class="calendar-datasource-params" style="margin-top:5px;">
					{$datasource_extension = Extension_CalendarDatasource::get($source)}
					{if !empty($datasource_extension) && method_exists($datasource_extension, 'renderConfig')}
						{$datasource_extension->renderConfig($model, $model->params.series[{$series_idx}], $series_prefix)}
					{/if}
				</div>
			</div>
		</div>

		{/section}

		{if !empty($model->id)}
			{include file="devblocks:cerberusweb.core::internal/peek/delete_confirm.tpl" noun="calendar"}
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
		$popup.dialog('option','title',"{'common.calendar'|devblocks_translate|capitalize|escape:'javascript'}");
		$popup.css('overflow', 'inherit');
		
		// Buttons

		$popup.find('button.save').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		if(window.CerbUI && CerbUI.Form) CerbUI.Form.ConfirmDelete($popup[0]);

		// Timezone — native <select> keeps the POST value; enhance with type-to-filter
		if(window.CerbUI && CerbUI.SelectMenu)
			$popup.find('form select[name=timezone]').each(function() { new CerbUI.SelectMenu(this); });

		// Switchers (open/closed style) bound to their hidden inputs. Two of them toggle a dependent panel
		// (the color pickers / the datasource sections) — handled by input name in onSelect.
		if(window.CerbUI && CerbUI.Switcher) {
			$popup.find('.cerb-ui-switcher[data-cerb-input]').each(function() {
				var input = document.getElementById(this.getAttribute('data-cerb-input'));
				if(!input) return;
				new CerbUI.Switcher(this, {
					value: input.value,
					onSelect: function(value) {
						input.value = value;
						if(input.name === 'params[manual_disabled]')
							$popup.find('.calendar-events')[value === '1' ? 'fadeOut' : 'fadeIn']();
						if(input.name === 'params[sync_enabled]')
							$popup.find('.sync-events')[value === '1' ? 'fadeIn' : 'fadeOut']();
					}
				});
			});
		}

		// Package Library
		
		{if !$model->id && $packages}
			var $tabs = $popup.find('.cerb-tabs');
			$tabs.find('> ul').each(function() {
				if(window.CerbUI && CerbUI.Tabs) new CerbUI.Tabs(this);
			});
			var $library_container = $tabs;
			{include file="devblocks:cerberusweb.core::internal/package_library/editor_chooser.js.tpl"}
			
			$library_container.on('cerb-package-library-form-submit', function() {
				$popup.one('peek_saved peek_error', function() {
					$library_container.triggerHandler('cerb-package-library-form-submit--done');
				});

				$popup.find('button.save').click();
			});
		{/if}
		
		
		// Options

		$popup.find('.calendar-events input.color-picker').each(function() {
			new CerbUI.ColorPicker(this, {
				palette: ['#A0D95B','#FEAF03','#FCB3B3','#FF6666','#C5DCFA','#85BAFF','#E8F554','#F4A3FE','#C8C8C8']
			});
		});

		$popup.find('select.datasource-selector').change(function(e) {
			var $select = $(this);
			var extension_id = $select.val();
			var owner = $frm.find('input[name=owner]').val();

			if(0 === extension_id.length)
				return;

			var params_prefix = $select.attr('params_prefix');
			genericAjaxGet($select.closest('.cerb-ui-form--field').siblings('div.calendar-datasource-params'), 'c=internal&a=invoke&module=calendars&action=getCalendarDatasourceParams&extension_id=' + encodeURIComponent(extension_id) + '&owner=' + encodeURIComponent(owner) + '&params_prefix=' + encodeURIComponent(params_prefix));
		});

		$popup.find('input[name=name]').focus();
		
	});
});
</script>