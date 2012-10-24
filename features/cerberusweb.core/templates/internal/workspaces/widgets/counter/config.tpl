<fieldset class="peek" style="margin-top:10px;" id="widget{$widget->id}Config">
	<legend>Data source</legend>

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
				<td>
					<b>Color</b>
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
				<td>
					<input type="hidden" name="params[color]" value="{$widget->params.color|default:'#34434E'}" style="width:100%;" class="color-picker">
				</td>
			</tr>
		</table>
	</div>
</fieldset>

<script type="text/javascript">
	$config = $('#widget{$widget->id}Config');
	
	$config.find('input:hidden.color-picker').miniColors({
		color_favorites: ['#CF2C1D','#FEAF03','#57970A','#007CBD','#7047BA','#D5D5D5','#ADADAD','#34434E']
	});
	
	$config.find('select.datasource-selector').change(function() {
		datasource=$(this).val();
		$div_params=$(this).next('DIV.datasource-params');
		
		if(datasource.length==0) { 
			$div_params.html(''); 
		} else { 
			genericAjaxGet($div_params, 'c=internal&a=handleSectionAction&section=dashboards&action=getWidgetDatasourceConfig&widget_id={$widget->id}&ext_id=' + datasource);
		}
	});
</script>