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
		
		{$formats = ['text'=>'Text','number'=>'Number','number.minutes'=>'Time elapsed (minutes)','number.seconds'=>'Time elapsed (seconds)']}
		
		<div>
			<b>Format x-axis</b> values as: (optional)
		</div>
		
		<div style="margin:0 0 5px 10px;">
			<select name="params[xaxis_format]">
				{foreach from=$formats item=label key=k}
				<option value="{$k}" {if $k == $widget->extension_params.xaxis_format}selected="selected"{/if}>{$label}</option>
				{/foreach}
			</select>
		</div>
		
		{$formats = ['number'=>'Number','number.minutes'=>'Time elapsed (minutes)','number.seconds'=>'Time elapsed (seconds)']}
		
		<div>
			<b>Format y-axis</b> values as: (optional)
		</div>
		
		<div style="margin:0 0 5px 10px;">
			<select name="params[yaxis_format]">
				{foreach from=$formats item=label key=k}
				<option value="{$k}" {if $k == $widget->extension_params.yaxis_format}selected="selected"{/if}>{$label}</option>
				{/foreach}
			</select>
		</div>
		
		<div>
			The <b>chart height</b> is:
		</div>
		
		<div style="margin:0 0 5px 10px;">
			<input type="text" size="5" maxlength="4" name="params[height]" placeholder="(auto)" value="{$widget->extension_params.height}"> pixels
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