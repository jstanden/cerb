<div id="widget{$widget->id}ConfigTabs">
	<ul>
		<li><a href="#widget{$widget->id}ConfigTabDatasource">Data Source</a></li>
		<li><a href="#widget{$widget->id}ConfigTabThresholds">Thresholds</a></li>
	</ul>
	
    <div id="widget{$widget->id}ConfigTabThresholds">
    	<table cellpadding="0" cellspacing="0" border="0" width="100%">
    		<tr>
    			<td width="50%"><b>Label</b></td>
    			<td width="30%"><b>Max. Value</b></td>
    			<td width="20%"><b>Color</b></td>
    		</tr>
    		{section name=thresholds loop=4}
    		<tr>
    			<td style="padding-right:10px;" valign="top">
    				<input type="text" name="params[threshold_labels][]" value="{$widget->params.threshold_labels.{$smarty.section.thresholds.index}}" style="width:100%;">
    			</td>
    			<td style="padding-right:10px;" valign="top">
    				<input type="text" name="params[threshold_values][]" value="{$widget->params.threshold_values.{$smarty.section.thresholds.index}}" style="width:100%;">
    			</td>
    			<td valign="top">
    				<input type="hidden" name="params[threshold_colors][]" value="{$widget->params.threshold_colors.{$smarty.section.thresholds.index}}" style="width:100%;" class="color-picker">
    			</td>
    		</tr>
    		{/section}
    	</table>
	</div>
	
    <div id="widget{$widget->id}ConfigTabDatasource">
		
		<b>Data</b> from 
		{$source = $widget->params.datasource}
		
		<select name="params[datasource]" class="datasource-selector">
			<option value=""></option>
		{foreach from=$datasource_mfts item=datasource_mft}
			<option value="{$datasource_mft->id}" {if $source==$datasource_mft->id}selected="selected"{/if}>{$datasource_mft->name}</option>
		{/foreach}
		</select>
		
		<div class="datasource-params" style="margin-left:10px;">
			{$datasource = Extension_WorkspaceWidgetDatasource::get($source)}
			{if !empty($datasource) && method_exists($datasource, 'renderConfig')}
				{$datasource->renderConfig($widget, $widget->params)}
			{/if}
		</div>
		
		<div style="margin:10px 0 0 10px;">
			<table cellspacing="0" cellpadding="0" border="0">
				<tr>
					<td>
						<b>Display as</b>
					</td>
					<td>
						<b>Prepend</b>
					</td>
					<td>
						<b>Append</b>
					</td>
				</tr>
				<tr>
					<td>
						{$types = [['number','number'], ['decimal','decimal'], ['percent','percentage'], ['bytes','bytes'], ['seconds','time elapsed']]}
						<select name="params[metric_type]">
							{foreach from=$types item=type}
							<option value="{$type[0]}" {if $widget->params.metric_type==$type[0]}selected="selected"{/if}>{$type[1]}</option>
							{/foreach}
						</select>
					</td>
					<td>
						<input type="text" name="params[metric_prefix]" value="{$widget->params.metric_prefix}" size="10">
					</td>
					<td>
						<input type="text" name="params[metric_suffix]" value="{$widget->params.metric_suffix}" size="10">
					</td>
				</tr>
			</table>
		</div>
	</div>
</div>

<script type="text/javascript">
	$tabs = $('#widget{$widget->id}ConfigTabs').tabs();
	
	$tabs.find('input:hidden.color-picker').miniColors({
		color_favorites: ['#CF2C1D','#FEAF03','#57970A','#D5D5D5','#ADADAD','#34434E']
	});
	
	$datasource_tab = $('#widget{$widget->id}ConfigTabDatasource');
	
	$datasource_tab.find('select.datasource-selector').change(function() {
		datasource=$(this).val();
		$div_params=$(this).next('DIV.datasource-params');
		
		if(datasource.length==0) { 
			$div_params.html(''); 
		} else { 
			genericAjaxGet($div_params, 'c=internal&a=handleSectionAction&section=dashboards&action=getWidgetDatasourceConfig&widget_id={$widget->id}&ext_id=' + datasource);
		}
	});
</script>