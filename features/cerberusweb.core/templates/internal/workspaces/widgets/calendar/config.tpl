<div id="widget{$widget->id}ConfigTabDatasource" style="margin-top:10px;">
	<fieldset id="widget{$widget->id}Datasource" class="peek">
		<legend>Calendar</legend>
	
		<b>Use</b> 
		<select name="params[calendar_id]">
			<option value="">-- {'common.choose'|devblocks_translate} --</option>
			{foreach from=$calendars item=calendar key=calendar_id}
			<option value="{$calendar_id}" {if $calendar_id==$widget->params.calendar_id}selected="selected"{/if}>{$calendar->name}</option>
			{/foreach}
		</select>
	
	</fieldset>
</div>

<script type="text/javascript">
	$fieldset = $('fieldset#widget{$widget->id}Datasource');
</script>
