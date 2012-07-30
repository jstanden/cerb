<div id="widget{$widget->id}ConfigTabs">
	<ul>
		<li><a href="#widget{$widget->id}ConfigTabDatasource">Data Source</a></li>
		<li><a href="#widget{$widget->id}ConfigTabChartType">Chart Type</a></li>
	</ul>
	
    <div id="widget{$widget->id}ConfigTabDatasource">
    	<input type="hidden" name="params[datasource]" value="worklist">
    	
    	{section start=0 loop=3 name=series}
    	{$series_idx = $smarty.section.series.index}
    	
    	<fieldset id="widget{$widget->id}Datasource{$series_idx}" class="peek">
    		<legend>Series #{$smarty.section.series.iteration}</legend>
    	
    		{$div_popup_worklist = uniqid()}

			{$series_ctx_id = $widget->params.series[{$series_idx}].view_context}
			
			{$series_ctx = null}
			{$series_ctx_view = null}
			{$series_ctx_fields = []}
			
			{if !empty($series_ctx_id)}
				{$series_ctx = Extension_DevblocksContext::get($series_ctx_id)}
				{*$series_ctx_view_id = "widget{$widget->id}_worklist{$series_idx}"*}
				{$series_ctx_view = $series_ctx->getChooserView()} 
				{$series_ctx_fields = $series_ctx_view->getParamsAvailable()}
			{/if}

			<b>Label</b> it 
			<input type="text" name="params[series][{$series_idx}][label]" value="{$widget->params.series[{$series_idx}].label}" size="45">
			
			<br>
			
			<b>Load </b>
			
			<select name="params[series][{$series_idx}][view_context]" class="context">
				<option value=""> - {'common.choose'|devblocks_translate|lower} - </option>
				{foreach from=$context_mfts item=context_mft key=context_id}
				<option value="{$context_id}" {if $series_ctx_id==$context_id}selected="selected"{/if}>{$context_mft->name}</option>
				{/foreach}
			</select>
			
			 data using  
			
			<div id="popup{$div_popup_worklist}" class="badge badge-lightgray" style="font-weight:bold;color:rgb(80,80,80);cursor:pointer;display:inline;"><span class="name">Worklist</span> &#x25be;</div>
			
			<input type="hidden" name="params[series][{$series_idx}][view_id]" value="widget{$widget->id}_worklist{$series_idx}">
			<input type="hidden" name="params[series][{$series_idx}][view_model]" value="{$widget->params.series[{$series_idx}].view_model}" class="model">
			
			<br>
			
			<abbr title="horizontal axis" style="font-weight:bold;">X-axis</abbr> is   
			
			<select name="params[series][{$series_idx}][xaxis_field]" class="xaxis_field">
				{if !empty($series_ctx_fields)}
				{foreach from=$series_ctx_fields item=field}
					{if !empty($field->db_label)}
						{if $field->type == Model_CustomField::TYPE_DATE || $field->type == Model_CustomField::TYPE_NUMBER}
						<option value="{$field->token}" {if $widget->params.series[{$series_idx}].xaxis_field==$field->token}selected="selected"{/if}>{$field->db_label|lower}</option>
						{/if}
					{/if}
				{/foreach}
				{/if}
			</select>
			
			{$xaxis_ticks = [hour,day,week,month,year]}
			
			 by 
			
			<select name="params[series][{$series_idx}][xaxis_tick]">
				{foreach from=$xaxis_ticks item=xaxis_tick}
				<option value="{$xaxis_tick}" {if $widget->params.series[{$series_idx}].xaxis_tick==$xaxis_tick}selected="selected"{/if}>{$xaxis_tick}</option>
				{/foreach}
			</select>
			
			<br>
			
			<abbr title="vertical axis" style="font-weight:bold;">Y-axis</abbr> is 
			 
			<select name="params[series][{$series_idx}][yaxis_field]" class="yaxis_field" style="display:{if empty($series_ctx_fields)}none{else}inline{/if};">
				{if !empty($series_ctx_fields)}
				{foreach from=$series_ctx_fields item=field}
					{if !empty($field->db_label)}
						{if $field->type == Model_CustomField::TYPE_NUMBER}
						<option value="{$field->token}" {if $widget->params.series[{$series_idx}].yaxis_field==$field->token}selected="selected"{/if}>{$field->db_label|lower}</option>
						{/if}
					{/if}
				{/foreach}
				{/if}
			</select>
			
			<select name="params[series][{$series_idx}][yaxis_func]" class="yaxis_func">
				<option value="value" {if 'value'==$widget->params.series[{$series_idx}].yaxis_func}selected="selected"{/if}>value</option>
				<option value="count" {if 'count'==$widget->params.series[{$series_idx}].yaxis_func}selected="selected"{/if}>count</option>
				<option value="avg" class="number" {if 'avg'==$widget->params.series[{$series_idx}].yaxis_func}selected="selected"{/if}>average</option>
				<option value="sum" class="number" {if 'sum'==$widget->params.series[{$series_idx}].yaxis_func}selected="selected"{/if}>sum</option>
				<option value="min" class="number" {if 'min'==$widget->params.series[{$series_idx}].yaxis_func}selected="selected"{/if}>min</option>
				<option value="max" class="number" {if 'max'==$widget->params.series[{$series_idx}].yaxis_func}selected="selected"{/if}>max</option>
			</select>
			
			<br>
			
			<b>Color</b> it 
			
			<input type="hidden" name="params[series][{$series_idx}][line_color]" value="{$widget->params.series[{$series_idx}].line_color|default:'#058DC7'}" size="7" class="color-picker">
			
			<script type="text/javascript">
				$fieldset = $('fieldset#widget{$widget->id}Datasource{$series_idx}');
				
				$fieldset.find('input:hidden.color-picker').miniColors({
				});
				
				$fieldset.find('select.yaxis_func').change(function(e) {
					val = $(this).val();
					
					var $select_yaxis = $(this).siblings('select.yaxis_field');
					
					if(val == 'count')
						$select_yaxis.hide();
					else
						$select_yaxis.show();
				});
				
				$fieldset.find('select.context').change(function(e) {
					ctx = $(this).val();
					
					// [TODO] Hide options until we know the context
					var $select = $(this);
					
					if(0 == ctx.length)
						return;
					
					genericAjaxGet('','c=internal&a=handleSectionAction&section=dashboards&action=getContextFieldsJson&context=' + ctx, function(json) {
						if('object' == typeof(json) && json.length > 0) {
							$select_xaxis = $select.siblings('select.xaxis_field').html('');
							$select_yaxis = $select.siblings('select.yaxis_field').html('');
							
							for(idx in json) {
								field = json[idx];
								
								$option = $('<option value="'+field.key+'">'+field.label+'</option>');
								
								// X-Axis
								// Number or date
								if(field.type=='N' || field.type=='E')
									$select_xaxis.append($option);
								
								// Y-Axis
								// Number
								if(field.type=='N')
									$select_yaxis.append($option);
							}
						}
					});
				});
				
				$fieldset.find('select.xaxis_field').change(function(e) {
				});
				
				$fieldset.find('select.yaxis_field').change(function(e) {
				});
			
				$('#popup{$div_popup_worklist}').click(function(e) {
					context = $(this).siblings('select.context').val();
					$chooser=genericAjaxPopup('chooser','c=internal&a=chooserOpenParams&context='+context+'&view_id={"widget{$widget->id}_worklist{$series_idx}"}',null,true,'750');
					$chooser.bind('chooser_save',function(event) {
						if(null != event.view_model) {
							$('#popup{$div_popup_worklist}').parent().find('input:hidden.model').val(event.view_model);
						}
					});
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