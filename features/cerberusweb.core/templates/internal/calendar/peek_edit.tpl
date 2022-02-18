{$peek_context = CerberusContexts::CONTEXT_CALENDAR}
{$peek_context_id = $model->id}
{$form_id = "frmCalendarPeek{uniqid()}"}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}" onsubmit="return false;">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="calendar">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

{if !$model->id}
<table cellspacing="0" cellpadding="2" border="0" width="98%" style="margin-bottom:10px;">
	<tr>
		<td width="1%" nowrap="nowrap" valign="top">
			<b>{'common.owner'|devblocks_translate|capitalize}:</b>
		</td>
		<td width="99%">
			{include file="devblocks:cerberusweb.core::internal/peek/menu_actor_owner.tpl"}
		</td>
	</tr>
</table>
{/if}

<div class="cerb-tabs">
	{if !$model->id && $packages}
	<ul>
		<li><a href="#calendar-library">{'common.library'|devblocks_translate|capitalize}</a></li>
		<li><a href="#calendar-builder">{'common.build'|devblocks_translate|capitalize}</a></li>
	</ul>
	{/if}
	
	{if !$model->id && $packages}
	<div id="calendar-library" class="package-library">
		{include file="devblocks:cerberusweb.core::internal/package_library/editor_chooser.tpl"}
	</div>
	{/if}
	
	<div id="calendar-builder">
		<table cellspacing="0" cellpadding="2" border="0" width="98%" style="margin-bottom:10px;">
			<tbody>
				<tr>
					<td width="1%" nowrap="nowrap"><b>{'common.name'|devblocks_translate}:</b></td>
					<td width="99%">
						<input type="text" name="name" value="{$model->name}" style="width:100%;" autofocus="autofocus">
					</td>
				</tr>
				
				{if $model->id}
				<tr>
					<td width="1%" nowrap="nowrap" valign="top">
						<b>{'common.owner'|devblocks_translate|capitalize}:</b>
					</td>
					<td width="99%">
						{include file="devblocks:cerberusweb.core::internal/peek/menu_actor_owner.tpl"}
					</td>
				</tr>
				{/if}
				
				<tr>
					<td width="1%" nowrap="nowrap" valign="top">
						<b>{'common.timezone'|devblocks_translate|capitalize}:</b>
					</td>
					<td width="99%">
						<select name="timezone">
							<option value="">(use viewer's timezone)</option>
							{foreach from=$timezones item=timezone}
								<option value="{$timezone}" {if $timezone == $model->timezone}selected="selected"{/if}>{$timezone}</option>
							{/foreach}
						</select>
					</td>
				</tr>
				
				{if !empty($custom_fields)}
					{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false tbody=true}
				{/if}
				
				<tr>
					<td colspan="2">
						{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}
						
						{* Datasources *}
						
						<fieldset class="peek">
							<legend>Calendar Events</legend>
						
							<b>Creating</b> events is 
							<label><input type="radio" name="params[manual_disabled]" value="0" {if empty($model->params.manual_disabled)}checked="checked"{/if}> enabled</label>
							<label><input type="radio" name="params[manual_disabled]" value="1" {if !empty($model->params.manual_disabled)}checked="checked"{/if}> disabled</label>
							<br>
							
							<b>Synchronizing</b> events is 
							<label><input type="radio" name="params[sync_enabled]" value="1" {if !empty($model->params.sync_enabled)}checked="checked"{/if}> enabled</label>
							<label><input type="radio" name="params[sync_enabled]" value="0" {if empty($model->params.sync_enabled)}checked="checked"{/if}> disabled</label>
							<br>
							
							<b>Start weeks</b> on 
							<label><input type="radio" name="params[start_on_mon]" value="0" {if empty($model->params.start_on_mon)}checked="checked"{/if}> Sunday</label>
							<label><input type="radio" name="params[start_on_mon]" value="1" {if !empty($model->params.start_on_mon)}checked="checked"{/if}> Monday</label>
							<br>
						
							<b>Start times</b> are 
							<label><input type="radio" name="params[hide_start_time]" value="0" {if empty($model->params.hide_start_time)}checked="checked"{/if}> visible</label>
							<label><input type="radio" name="params[hide_start_time]" value="1" {if !empty($model->params.hide_start_time)}checked="checked"{/if}> hidden</label>
							<br>
							
						</fieldset>
						
						<fieldset class="calendar-events peek" style="{if !empty($model->params.manual_disabled)}display:none;{/if}">
							<legend>Created Events</legend>
							
							<b>{'common.available'|devblocks_translate|capitalize}</b> events are 
							<input type="text" name="params[color_available]" value="{$model->params.color_available|default:'#A0D95B'}" style="width:100%;" class="color-picker">
							<br>
							
							<b>{'common.busy'|devblocks_translate|capitalize}</b> events are 
							<input type="text" name="params[color_busy]" value="{$model->params.color_busy|default:'C8C8C8'}" style="width:100%;" class="color-picker">
							<br>
						</fieldset>
						
						{section start=0 loop=5 name=series}
						{$series_idx = $smarty.section.series.index}
						{$series_prefix = "[series][{$series_idx}]"}
						
						<fieldset id="calendar{$model->id}Datasource{$series_idx}" class="sync-events peek" style="{if empty($model->params.sync_enabled)}display:none;{/if}">
							<legend>Synchronize</legend>
						
							<b>Events</b> from 
							{$source = $model->params.series[{$series_idx}].datasource}
							
							<select name="params{$series_prefix}[datasource]" class="datasource-selector" params_prefix="{$series_prefix}">
								<option value=""></option>
								{foreach from=$datasource_extensions item=datasource_ext key=datasource_ext_id}
								<option value="{$datasource_ext_id}" {if $datasource_ext_id==$source}selected="selected"{/if}>{$datasource_ext->name}</option>
								{/foreach}
							</select>
						
							<div style="margin:2px 0px 0px 10px;" class="calendar-datasource-params">
								{$datasource_extension = Extension_CalendarDatasource::get($source)}
								{if !empty($datasource_extension) && method_exists($datasource_extension, 'renderConfig')}
									{$datasource_extension->renderConfig($model, $model->params.series[{$series_idx}], $series_prefix)}
								{/if}
							</div>
						</fieldset>
						
						{/section}
					</td>
				</tr>
			</tbody>
		</table>
		
		{if !empty($model->id)}
		<fieldset style="display:none;" class="delete">
			<legend>{'common.delete'|devblocks_translate|capitalize}</legend>
			
			<div>
				Are you sure you want to permanently delete this calendar?
			</div>
			
			<button type="button" class="delete red">{'common.yes'|devblocks_translate|capitalize}</button>
			<button type="button" onclick="$(this).closest('form').find('div.buttons').fadeIn();$(this).closest('fieldset.delete').fadeOut();">{'common.no'|devblocks_translate|capitalize}</button>
		</fieldset>
		{/if}
		
		<div class="buttons">
			<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
			{if !empty($model->id) && $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" onclick="$(this).parent().siblings('fieldset.delete').fadeIn();$(this).closest('div').fadeOut();"><span class="glyphicons glyphicons-circle-remove"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
		</div>
	</div>
</div>

</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#{$form_id}');
	var $popup = genericAjaxPopupFind($frm);
	
	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"{'common.calendar'|devblocks_translate|capitalize|escape:'javascript'}");
		$popup.css('overflow', 'inherit');
		
		// Buttons
		
		$popup.find('button.submit').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);

		// Close confirmation

		$popup.on('dialogbeforeclose', function(e, ui) {
			var keycode = e.keyCode || e.which;
			if(keycode === 27)
				return confirm('{'warning.core.editor.close'|devblocks_translate}');
		});
		
		// Package Library
		
		{if !$model->id && $packages}
			var $tabs = $popup.find('.cerb-tabs').tabs();
			var $library_container = $tabs;
			{include file="devblocks:cerberusweb.core::internal/package_library/editor_chooser.js.tpl"}
			
			$library_container.on('cerb-package-library-form-submit', function(e) {
				$popup.one('peek_saved peek_error', function(e) {
					$library_container.triggerHandler('cerb-package-library-form-submit--done');
				});
				
				$popup.find('button.submit').click();
			});
		{/if}
		
		// Owners
		
		var $owners_menu = $popup.find('ul.owners-menu');
		var $ul = $owners_menu.siblings('ul.chooser-container');
		
		$ul.on('bubble-remove', function(e, ui) {
			e.stopPropagation();
			$(e.target).closest('li').remove();
			$ul.hide();
			$owners_menu.show();
		});
		
		$owners_menu.menu({
			select: function(event, ui) {
				var token = ui.item.attr('data-token');
				var label = ui.item.attr('data-label');
				
				if(undefined == token || undefined == label)
					return;
				
				$owners_menu.hide();
				
				// Build bubble
				
				var context_data = token.split(':');
				var $li = $('<li/>');
				var $label = $('<a href="javascript:;" class="cerb-peek-trigger no-underline" />').attr('data-context',context_data[0]).attr('data-context-id',context_data[1]).text(label);
				$label.cerbPeekTrigger().appendTo($li);
				var $hidden = $('<input type="hidden">').attr('name', 'owner').attr('value',token).appendTo($li);
				ui.item.find('img.cerb-avatar').clone().prependTo($li);
				var $a = $('<a href="javascript:;" onclick="$(this).trigger(\'bubble-remove\');"><span class="glyphicons glyphicons-circle-remove"></span></a>').appendTo($li);
				
				$ul.find('> *').remove();
				$ul.append($li);
				$ul.show();
			}
		});
		
		// Options
		
		$popup.find('fieldset.calendar-events input:text.color-picker').minicolors({
			swatches: ['#A0D95B','#FEAF03','#FCB3B3','#FF6666','#C5DCFA','#85BAFF','#E8F554','#F4A3FE','#C8C8C8']
		});
		
		$popup.find('select.datasource-selector').change(function(e) {
			var $select = $(this);
			var extension_id = $select.val();
			var owner = $frm.find('input[name=owner]').val();
			
			if(0 === extension_id.length)
				return;
			
			var params_prefix = $select.attr('params_prefix');
			genericAjaxGet($select.siblings('div.calendar-datasource-params'), 'c=internal&a=invoke&module=calendars&action=getCalendarDatasourceParams&extension_id=' + encodeURIComponent(extension_id) + '&owner=' + encodeURIComponent(owner) + '&params_prefix=' + encodeURIComponent(params_prefix));
		});
		
		$popup.find('input:radio[name="params[manual_disabled]"]').change(function() {
			var $radio = $(this);
			var $params = $(this).closest('form').find('fieldset.calendar-events');
			
			if($radio.val() == '1') {
				$params.fadeOut();
			} else {
				$params.fadeIn();
			}
		});
		
		$popup.find('input:radio[name="params[sync_enabled]"]').change(function() {
			var $radio = $(this);
			var $params = $(this).closest('form').find('fieldset.sync-events');
			
			if($radio.val() == '1') {
				$params.fadeIn();
			} else {
				$params.fadeOut();
			}
		});
		
		$popup.find('input:text[name=name]').focus();
		
	});
});
</script>