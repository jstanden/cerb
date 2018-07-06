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
		
		{$chart_types = [ 'line' => 'lines', 'spline' => 'splines', 'area' => 'areas', 'bar' => 'bars', 'bar_stacked' => 'bars (stacked)' ] }
		
		<select name="params[chart_as]">
			{foreach from=$chart_types item=label key=key}
			<option value="{$key}" {if $widget->params.chart_as == $key}selected="selected"{/if}>{$label}</option>
			{/foreach}
		</select>
		
		<div>
			The <b>x-axis dates</b> are in the key:
		</div>
		
		<div>
			<textarea name="params[xaxis_key]" data-editor-mode="ace/mode/twig" class="placeholders" style="width:95%;height:50px;">{$widget->params.xaxis_key|default:'ts'}</textarea>
		</div>
		
		<div>
			Parse the <b>x-axis data</b> using this <b>date format</b>:
		</div>
		
		<div>
			<textarea name="params[xaxis_format]" data-editor-mode="ace/mode/twig" class="placeholders" style="width:95%;height:50px;">{$widget->params.xaxis_format|default:'%Y-%m-%d'}</textarea>
		</div>
		
		<div>
			Format <b>x-axis</b> dates using this <b>date format</b> (optional):
		</div>
		
		<div>
			<textarea name="params[xaxis_tick_format]" data-editor-mode="ace/mode/twig" class="placeholders" style="width:95%;height:50px;">{$widget->params.xaxis_tick_format|default:''}</textarea>
		</div>
		
		<div>
			Use these <b>options</b>:
		</div>
		
		<div>
			<div>
				<label><input type="checkbox" name="params[options][subchart]" value="1" {if $widget->params.options.subchart}checked="checked"{/if}> Show zoomable timeline</label>
			</div>
			<div>
				<label><input type="checkbox" name="params[options][show_points]" value="1" {if $widget->params.options.show_points}checked="checked"{/if}> Show data points</label>
			</div>
		</div>
	</fieldset>
</div>

<script type="text/javascript">
$(function() {
	var $config = $('#widget{$widget->id}Config');
	$config.find('textarea.placeholders').cerbCodeEditor();
});
</script>