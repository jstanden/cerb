{$sp_uid = uniqid()}
<div id="widget{$widget->id}Config" class="cerb-u-mt-3">

	<div class="cerb-ui-form--field">
		<label class="cerb-ui-form--label">Series Axes</label>
		<div>
			<input type="hidden" name="params[axes_independent]" id="spAxes{$sp_uid}" value="{if $widget->params.axes_independent}1{else}0{/if}">
			<div class="cerb-ui-switcher" data-cerb-input="spAxes{$sp_uid}">
				<button type="button" data-value="1"{if $widget->params.axes_independent} class="cerb-ui-switcher--active"{/if}>Independent</button>
				<button type="button" data-value="0"{if empty($widget->params.axes_independent)} class="cerb-ui-switcher--active"{/if}>Shared</button>
			</div>
		</div>
	</div>

	<div id="widget{$widget->id}ConfigTabs">
		<ul style="display:none;">
			<li><a href="#widget{$widget->id}ConfigTabDatasource">Data Sources</a></li>
		</ul>

		<div id="widget{$widget->id}ConfigTabDatasource">
			{section start=0 loop=5 name=series}
			{$series_idx = $smarty.section.series.index}
			{$series_prefix = "[series][{$series_idx}]"}

			<div id="widget{$widget->id}Datasource{$series_idx}" data-cerb-series class="cerb-ui-panel cerb-ui-panel--spaced cerb-u-mt-2">
				<div class="cerb-ui-header cerb-ui-header--tight">
					<div class="cerb-ui-header--title-sm">Source #{$smarty.section.series.iteration}</div>
				</div>

				<div class="cerb-ui-form">
					<div class="cerb-ui-form--field">
						<label class="cerb-ui-form--label">Data from</label>
						{$source = $widget->params.series[{$series_idx}].datasource}
						<select name="params[series][{$series_idx}][datasource]" class="datasource-selector" params_prefix="{$series_prefix}">
							<option value=""></option>
							{foreach from=$datasource_mfts item=datasource_mft}
								<option value="{$datasource_mft->id}" {if $source==$datasource_mft->id}selected="selected"{/if}>{$datasource_mft->name}</option>
							{/foreach}
						</select>
					</div>

					<div class="cerb-ui-form--field">
						<label class="cerb-ui-form--label">Label</label>
						<input type="text" name="params[series][{$series_idx}][label]" value="{$widget->params.series[{$series_idx}].label}">
					</div>

					<div class="datasource-params">
						{$datasource = Extension_WorkspaceWidgetDatasource::get($source)}
						{if !empty($datasource) && method_exists($datasource, 'renderConfig')}
							{$datasource->renderConfig($widget, $widget->params.series[{$series_idx}], $series_prefix)}
						{/if}
					</div>

					<div class="cerb-ui-form--field">
						<label class="cerb-ui-form--label">Color</label>
						<div><input type="text" name="params[series][{$series_idx}][line_color]" value="{$widget->params.series[{$series_idx}].line_color|default:'#058DC7'}" class="color-picker"></div>
					</div>
				</div>
			</div>
			{/section}
		</div>

	</div>

</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var $config = $('#widget{$widget->id}Config');
	var $tabs = $('#widget{$widget->id}ConfigTabs');
	$tabs.find('> ul').each(function() { if(window.CerbUI && CerbUI.Tabs) new CerbUI.Tabs(this); });

	if(window.CerbUI && CerbUI.Switcher) {
		var $axes = $config.find('#spAxes{$sp_uid}');
		var axesEl = $config.find('[data-cerb-input="spAxes{$sp_uid}"]')[0];
		if(axesEl)
			new CerbUI.Switcher(axesEl, { value: $axes.val(), onSelect: function(value) { $axes.val(value); } });
	}

	$tabs.find('input:text.color-picker').each(function() {
		new CerbUI.ColorPicker(this, {
			palette: ['#CF2C1D','#FEAF03','#57970A','#007CBD','#7047BA','#D5D5D5','#ADADAD','#34434E']
		});
	});

	$tabs.find('select.datasource-selector').change(function() {
		var datasource = $(this).val();
		var $div_params = $(this).closest('[data-cerb-series]').find('DIV.datasource-params');

		if(datasource.length == 0) {
			$div_params.html('');
		} else {
			var series_prefix = $(this).attr('params_prefix');
			genericAjaxGet($div_params, 'c=profiles&a=invoke&module=workspace_widget&action=getWidgetDatasourceConfig&params_prefix=' + encodeURIComponent(series_prefix) + '&widget_id={$widget->id}&ext_id=' + datasource);
		}
	});
});
</script>
