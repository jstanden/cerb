<div id="widget{$widget->id}Config" style="margin-top:10px;">
	<fieldset id="widget{$widget->id}QueryEditor" class="peek">
		<legend>Run this data query:</legend>
		
		<textarea name="params[data_query]" data-editor-mode="ace/mode/twig" class="placeholders" style="width:95%;height:50px;">{$widget->params.data_query}</textarea>
	</fieldset>
	
	<fieldset class="peek">
		<legend>Chart options:</legend>
		
		<div>
			<b>Display</b> the chart as:
		</div>
		
		<div>
			{$chart_types = [ 'donut' => 'donut', 'pie' => 'pie' ] }
			<select name="params[chart_as]">
				{foreach from=$chart_types item=label key=key}
				<option value="{$key}" {if $widget->params.chart_as == $key}selected="selected"{/if}>{$label}</option>
				{/foreach}
			</select>
		</div>
	</fieldset>
</div>

<script type="text/javascript">
$(function() {
	var $config = $('#widget{$widget->id}Config');
	$config.find('textarea.placeholders').cerbCodeEditor();
});
</script>