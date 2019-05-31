<div id="widget{$widget->id}Config" style="margin-top:10px;">
	<fieldset id="widget{$widget->id}QueryEditor" class="peek">
		<legend>
			Run this data query: 
			{include file="devblocks:cerberusweb.core::help/docs_button.tpl" url="https://cerb.ai/docs/data-queries/"}
		</legend>
		
		<textarea name="params[data_query]" data-editor-mode="ace/mode/cerb_query" class="placeholders" style="width:95%;height:50px;">{$widget->extension_params.data_query}</textarea>
	</fieldset>
	
	<fieldset class="peek">
		<legend>Chart options:</legend>
		
		<div>
			<b>Display</b> the chart as:
		</div>
		
		<div style="margin:0 0 5px 10px;">
			{$chart_types = [ 'donut' => 'donut', 'pie' => 'pie' ] }
			<select name="params[chart_as]">
				{foreach from=$chart_types item=label key=key}
				<option value="{$key}" {if $widget->extension_params.chart_as == $key}selected="selected"{/if}>{$label}</option>
				{/foreach}
			</select>
		</div>
		
		<div>
			The <b>chart height</b> is:
		</div>
		
		<div style="margin:0 0 5px 10px;">
			<input type="text" size="5" maxlength="4" name="params[height]" placeholder="(auto)" value="{$widget->extension_params.height}"> pixels
		</div>
		
		<div>
			Use these <b>options</b>:
		</div>
		
		<div style="margin:0 0 5px 10px;">
			<div>
				<label><input type="checkbox" name="params[options][show_legend]" value="1" {if $widget->extension_params.options.show_legend}checked="checked"{/if}> Show legend</label>
			</div>
		</div>
	</fieldset>
</div>

<script type="text/javascript">
$(function() {
	var $config = $('#widget{$widget->id}Config');
	
	$config.find('textarea.placeholders')
		.cerbCodeEditor()
		.cerbCodeEditorAutocompleteDataQueries()
		;
});
</script>