<div id="widget{$widget->id}ConfigTabDatasource" style="margin-top:10px;">
	<fieldset id="widget{$widget->id}Datasource" class="peek">
		<legend>Data source</legend>
	
		<b>Timezone</b> is 
		<select name="params[timezone]">
			{foreach from=$timezones item=timezone}
			<option value="{$timezone}" {if $widget->params.timezone == $timezone}selected="selected"{/if}>{$timezone}</option>
			{/foreach}
		</select>
		<br>
		
		<b>Format</b> is 
		<label><input type="radio" name="params[format]" value="0" {if empty($widget->params.format)}checked="checked"{/if}> 12-hour</label>
		<label><input type="radio" name="params[format]" value="1" {if !empty($widget->params.format)}checked="checked"{/if}> 24-hour</label>
		<br>
	
		<b>Color</b> it
		<input type="hidden" name="params[color]" value="{$widget->params.color|default:'#34434E'}" style="width:100%;" class="color-picker">
		<br>
	
	</fieldset>
</div>

<script type="text/javascript">
	$fieldset = $('fieldset#widget{$widget->id}Datasource');
	
	$fieldset.find('input:hidden.color-picker').miniColors({
		color_favorites: ['#CF2C1D','#FEAF03','#57970A','#007CBD','#7047BA','#D5D5D5','#ADADAD','#34434E']
	});
</script>