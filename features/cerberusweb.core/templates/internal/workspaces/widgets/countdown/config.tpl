<div id="widget{$widget->id}ConfigTabDatasource" style="margin-top:10px;">
	<fieldset id="widget{$widget->id}Datasource" class="peek">
		<legend>Data source</legend>
	
		<b>Count down</b> to date
		<input type="text" name="params[target_timestamp]" value="{$widget->params.target_timestamp|devblocks_date}" size="45" placeholder="e.g. &quot;Jan 19 2038&quot;, &quot;+1 week&quot;"> 
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