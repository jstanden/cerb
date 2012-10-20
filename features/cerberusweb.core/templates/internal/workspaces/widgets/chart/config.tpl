<div id="widget{$widget->id}ConfigTabs">
	<ul>
		<li><a href="#widget{$widget->id}ConfigTabDatasource">Data Sources</a></li>
		<li><a href="#widget{$widget->id}ConfigTabChartType">Chart Type</a></li>
	</ul>
	
    <div id="widget{$widget->id}ConfigTabDatasource">
    	{section start=0 loop=3 name=series}
    	{$series_idx = $smarty.section.series.index}
    	
    	<fieldset id="widget{$widget->id}Datasource{$series_idx}" class="peek">
    		<legend>Series #{$smarty.section.series.iteration}</legend>
    	
			<b>Data</b> from
			{$source = $widget->params.series[{$series_idx}].datasource}
			
			<select name="params[series][{$series_idx}][datasource]">
				<option value=""></option>
				{foreach from=$datasource_mfts item=datasource_mft}
					<option value="{$datasource_mft->id}" {if $source==$datasource_mft->id}selected="selected"{/if}>{$datasource_mft->name}</option>
				{/foreach}
			</select>
			
			<br>

			<b>Label</b> it 
			<input type="text" name="params[series][{$series_idx}][label]" value="{$widget->params.series[{$series_idx}].label}" size="45">

			<br>
			
			<div>
				{$datasource = Extension_WorkspaceWidgetDatasource::get($source)}
				{if !empty($datasource) && method_exists($datasource, 'renderConfig')}
					{$datasource->renderConfig($widget, $widget->params.series[{$series_idx}], $series_idx)}
				{/if}
			</div>
			
			<b>Color</b> it 
			
			<input type="hidden" name="params[series][{$series_idx}][line_color]" value="{$widget->params.series[{$series_idx}].line_color|default:'#058DC7'}" size="7" class="color-picker">
			
			<br>
			
			<script type="text/javascript">
				$fieldset = $('fieldset#widget{$widget->id}Datasource{$series_idx}');

				$fieldset.find('input:hidden.color-picker').miniColors({
					color_favorites: ['#CF2C1D','#FEAF03','#57970A','#007CBD','#7047BA','#D5D5D5','#ADADAD','#34434E']
				});
			</script>
		</fieldset>

		{/section}

	</div>
	
    <div id="widget{$widget->id}ConfigTabChartType">
    	<label><input type="radio" name="params[chart_type]" value="line" {if empty($widget->params.chart_type) || $widget->params.chart_type == 'line'}checked="checked"{/if}> Line Chart</label>
    	<label><input type="radio" name="params[chart_type]" value="bar" {if $widget->params.chart_type == 'bar'}checked="checked"{/if}> Bar Chart</label>
    	<label><input type="radio" name="params[chart_type]" value="scatterplot" {if $widget->params.chart_type == 'scatterplot'}checked="checked"{/if}> Scatter Plot</label>
    </div>
    
</div>

<script type="text/javascript">
	$('#widget{$widget->id}ConfigTabs').tabs();
</script>