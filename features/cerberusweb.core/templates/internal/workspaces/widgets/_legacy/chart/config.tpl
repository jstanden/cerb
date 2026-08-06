{$lc_uid = uniqid()}
<div id="widget{$widget->id}Config" class="cerb-u-mt-3">

	<div class="cerb-ui-form">
		<div class="cerb-ui-form--row">
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">Type</label>
				<div>
					<input type="hidden" name="params[chart_type]" id="lcType{$lc_uid}" value="{if $widget->params.chart_type == 'bar'}bar{else}line{/if}">
					<div class="cerb-ui-switcher" data-cerb-input="lcType{$lc_uid}">
						<button type="button" data-value="line"{if empty($widget->params.chart_type) || $widget->params.chart_type == 'line'} class="cerb-ui-switcher--active"{/if}>Line Chart</button>
						<button type="button" data-value="bar"{if $widget->params.chart_type == 'bar'} class="cerb-ui-switcher--active"{/if}>Stacked Bar Chart</button>
					</div>
				</div>
			</div>

			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">Display</label>
				<div>
					<input type="hidden" name="params[chart_display]" id="lcDisplay{$lc_uid}" value="{$widget->params.chart_display}">
					<div class="cerb-ui-switcher" data-cerb-input="lcDisplay{$lc_uid}">
						<button type="button" data-value=""{if empty($widget->params.chart_display)} class="cerb-ui-switcher--active"{/if}>Image &amp; Table</button>
						<button type="button" data-value="image"{if 'image' == $widget->params.chart_display} class="cerb-ui-switcher--active"{/if}>Image</button>
						<button type="button" data-value="table"{if 'table' == $widget->params.chart_display} class="cerb-ui-switcher--active"{/if}>Table</button>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="cerb-ui-panel cerb-ui-panel--spaced cerb-u-mt-2 option-subtotals"{if in_array($widget->params.chart_display, ['','table'])}{else} style="display:none;"{/if}>
		<div class="cerb-ui-header cerb-ui-header--tight">
			<div class="cerb-ui-header--title-sm">Chart Subtotals</div>
		</div>

		<div class="cerb-ui-form--field option-subtotals-column">
			<label class="cerb-ui-form--label">Columns</label>
			{$series_subtotals = DevblocksPlatform::importVar($widget->params.chart_subtotal_series, 'array', [])}
			<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-3 cerb-u-flex-wrap">
				<label><input type="checkbox" name="params[chart_subtotal_series][]" value="sum" {if in_array('sum', $series_subtotals)}checked="checked"{/if}> Sum</label>
				<label><input type="checkbox" name="params[chart_subtotal_series][]" value="mean" {if in_array('mean', $series_subtotals)}checked="checked"{/if}> Mean</label>
				<label><input type="checkbox" name="params[chart_subtotal_series][]" value="min" {if in_array('min', $series_subtotals)}checked="checked"{/if}> Min</label>
				<label><input type="checkbox" name="params[chart_subtotal_series][]" value="max" {if in_array('max', $series_subtotals)}checked="checked"{/if}> Max</label>
			</div>
		</div>

		<div class="cerb-ui-form--field option-subtotals-row cerb-u-mt-2"{if in_array($widget->params.chart_type,['bar'])}{else} style="display:none;"{/if}>
			<label class="cerb-ui-form--label">Rows</label>
			{$row_subtotals = DevblocksPlatform::importVar($widget->params.chart_subtotal_row, 'array', [])}
			<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-3 cerb-u-flex-wrap">
				<label><input type="checkbox" name="params[chart_subtotal_row][]" value="sum" {if in_array('sum', $row_subtotals)}checked="checked"{/if}> Sum</label>
				<label><input type="checkbox" name="params[chart_subtotal_row][]" value="mean" {if in_array('mean', $row_subtotals)}checked="checked"{/if}> Mean</label>
				<label><input type="checkbox" name="params[chart_subtotal_row][]" value="min" {if in_array('min', $row_subtotals)}checked="checked"{/if}> Min</label>
				<label><input type="checkbox" name="params[chart_subtotal_row][]" value="max" {if in_array('max', $row_subtotals)}checked="checked"{/if}> Max</label>
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
					<div class="cerb-ui-header--title-sm">Series #{$smarty.section.series.iteration}</div>
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
	var $fieldset_subtotals = $config.find('.option-subtotals');
	var $option_subtotals_row = $fieldset_subtotals.find('div.option-subtotals-row');

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

	// Type + Display switchers (write the hidden POST value, and drive the subtotals panel visibility)
	if(window.CerbUI && CerbUI.Switcher) {
		var $type = $config.find('#lcType{$lc_uid}');
		var typeEl = $config.find('[data-cerb-input="lcType{$lc_uid}"]')[0];
		if(typeEl)
			new CerbUI.Switcher(typeEl, {
				value: $type.val(),
				onSelect: function(value) {
					$type.val(value);
					if(value === 'bar') $option_subtotals_row.fadeIn(); else $option_subtotals_row.hide();
				}
			});

		var $display = $config.find('#lcDisplay{$lc_uid}');
		var displayEl = $config.find('[data-cerb-input="lcDisplay{$lc_uid}"]')[0];
		if(displayEl)
			new CerbUI.Switcher(displayEl, {
				value: $display.val(),
				onSelect: function(value) {
					$display.val(value);
					if(value === '' || value === 'table') $fieldset_subtotals.fadeIn(); else $fieldset_subtotals.hide();
				}
			});
	}
});
</script>
