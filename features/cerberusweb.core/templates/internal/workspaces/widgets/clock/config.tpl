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
	</fieldset>
</div>