<div id="widget{$widget->id}Config">

<div class="option-type">
	<b>Type: </b>
	<label><input type="radio" name="params[chart_type]" value="line" {if empty($widget->params.chart_type) || $widget->params.chart_type == 'line'}checked="checked"{/if}> Line Chart</label>
	<label><input type="radio" name="params[chart_type]" value="bar" {if $widget->params.chart_type == 'bar'}checked="checked"{/if}> Stacked Bar Chart</label>
</div>

<div class="option-display">
	<b>Display:</b>
	<label><input type="radio" name="params[chart_display]" value="" {if empty($widget->params.chart_display)}checked="checked"{/if}> Image &amp; Table</label>
	<label><input type="radio" name="params[chart_display]" value="image" {if 'image' == $widget->params.chart_display}checked="checked"{/if}> Image</label>
	<label><input type="radio" name="params[chart_display]" value="table" {if 'table' == $widget->params.chart_display}checked="checked"{/if}> Table</label>
</div>

<fieldset style="margin-left:20px;margin-top:5px;{if in_array($widget->params.chart_display, ['','table'])}{else}display:none;{/if}" class="option-subtotals">
	<legend>Chart Subtotals</legend>

	<div class="option-subtotals-column">
		<b>Columns:</b>
		{$series_subtotals = DevblocksPlatform::importVar($widget->params.chart_subtotal_series, 'array', [])}
		<label><input type="checkbox" name="params[chart_subtotal_series][]" value="sum" {if in_array('sum', $series_subtotals)}checked="checked"{/if}> Sum</label>
		<label><input type="checkbox" name="params[chart_subtotal_series][]" value="mean" {if in_array('mean', $series_subtotals)}checked="checked"{/if}> Mean</label>
		<label><input type="checkbox" name="params[chart_subtotal_series][]" value="min" {if in_array('min', $series_subtotals)}checked="checked"{/if}> Min</label>
		<label><input type="checkbox" name="params[chart_subtotal_series][]" value="max" {if in_array('max', $series_subtotals)}checked="checked"{/if}> Max</label>
	</div>
	
	<div class="option-subtotals-row" style="{if in_array($widget->params.chart_type,['bar'])}{else}display:none;{/if}">
		<b>Rows:</b>
		{$row_subtotals = DevblocksPlatform::importVar($widget->params.chart_subtotal_row, 'array', [])}
		<label><input type="checkbox" name="params[chart_subtotal_row][]" value="sum" {if in_array('sum', $row_subtotals)}checked="checked"{/if}> Sum</label>
		<label><input type="checkbox" name="params[chart_subtotal_row][]" value="mean" {if in_array('mean', $row_subtotals)}checked="checked"{/if}> Mean</label>
		<label><input type="checkbox" name="params[chart_subtotal_row][]" value="min" {if in_array('min', $row_subtotals)}checked="checked"{/if}> Min</label>
		<label><input type="checkbox" name="params[chart_subtotal_row][]" value="max" {if in_array('max', $row_subtotals)}checked="checked"{/if}> Max</label>
	</div>
</fieldset>

<div id="widget{$widget->id}ConfigTabs">
	<ul style="display:none;">
		<li><a href="#widget{$widget->id}ConfigTabDatasource">Data Sources</a></li>
	</ul>
	
	<div id="widget{$widget->id}ConfigTabDatasource">
		{section start=0 loop=5 name=series}
		{$series_idx = $smarty.section.series.index}
		{$series_prefix = "[series][{$series_idx}]"}
		
		<fieldset id="widget{$widget->id}Datasource{$series_idx}" class="peek">
			<legend>Series #{$smarty.section.series.iteration}</legend>
		
			<b>Data</b> from
			{$source = $widget->params.series[{$series_idx}].datasource}
			
			<select name="params[series][{$series_idx}][datasource]" class="datasource-selector" params_prefix="{$series_prefix}">
				<option value=""></option>
				{foreach from=$datasource_mfts item=datasource_mft}
					<option value="{$datasource_mft->id}" {if $source==$datasource_mft->id}selected="selected"{/if}>{$datasource_mft->name}</option>
				{/foreach}
			</select>

			<div style="margin-left: 10px;">

				<b>Label</b> it 
				<input type="text" name="params[series][{$series_idx}][label]" value="{$widget->params.series[{$series_idx}].label}" size="45">
				<br>
				
				<div class="datasource-params">
					{$datasource = Extension_WorkspaceWidgetDatasource::get($source)}
					{if !empty($datasource) && method_exists($datasource, 'renderConfig')}
						{$datasource->renderConfig($widget, $widget->params.series[{$series_idx}], $series_prefix)}
					{/if}
				</div>
				
				<b>Color</b> it 
				<input type="text" name="params[series][{$series_idx}][line_color]" value="{$widget->params.series[{$series_idx}].line_color|default:'#058DC7'}" size="7" class="color-picker">
				<br>
				
			</div>
				
		</fieldset>

		{/section}

	</div>
	
</div>

</div>

<script type="text/javascript">
$(function() {
	var $config = $('#widget{$widget->id}Config');
	var $tabs = $('#widget{$widget->id}ConfigTabs').tabs();
	var $fieldset_subtotals = $config.find('fieldset.option-subtotals');
	var $option_subtotals_column = $fieldset_subtotals.find('div.option-subtotals-column');
	var $option_subtotals_row = $fieldset_subtotals.find('div.option-subtotals-row');
	
	$tabs.find('input:text.color-picker').minicolors({
		swatches: ['#CF2C1D','#FEAF03','#57970A','#007CBD','#7047BA','#D5D5D5','#ADADAD','#34434E']
	});
	
	$tabs.find('select.datasource-selector').change(function() {
		datasource=$(this).val();
		$div_params=$(this).closest('fieldset').find('DIV.datasource-params');
		
		if(datasource.length==0) { 
			$div_params.html('');
			
		} else {
			series_prefix = $(this).attr('params_prefix');
			genericAjaxGet($div_params, 'c=profiles&a=invoke&module=workspace_widget&action=getWidgetDatasourceConfig&params_prefix=' + encodeURIComponent(series_prefix) + '&widget_id={$widget->id}&ext_id=' + datasource);
		}
	});
	
	$config.find('input[name="params[chart_display]"]').on('change', function(e) {
		chart_display = $(this).val();
		
		if(chart_display == '') {
			$fieldset_subtotals.fadeIn();
			
		} else if(chart_display == 'table') {
			$fieldset_subtotals.fadeIn();
			
		} else {
			$fieldset_subtotals.hide();
		}
		
	});
	
	$config.find('input[name="params[chart_type]"]').on('change', function(e) {
		chart_type = $(this).val();
		
		if(chart_type == 'bar') {
			$option_subtotals_row.fadeIn();
			
		} else {
			$option_subtotals_row.hide();
		}
		
	});
});
</script>