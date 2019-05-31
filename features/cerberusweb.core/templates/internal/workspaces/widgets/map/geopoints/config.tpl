<div id="widget{$widget->id}Config" style="margin-top:10px;">
	<fieldset id="widget{$widget->id}Country" class="peek">
		<legend>{'common.map'|devblocks_translate|capitalize}</legend>
		
		<select name="params[projection]">
			<option value="world" {if $widget->params.projection != 'usa'}selected="selected"{/if}>World (Countries)</option>
			<option value="usa" {if $widget->params.projection == 'usa'}selected="selected"{/if}>U.S. (States)</option>
		</select>
	</fieldset>
	
	<fieldset id="widget{$widget->id}QueryEditor" class="peek">
		<legend>
			Run this data query: 
			{include file="devblocks:cerberusweb.core::help/docs_button.tpl" url="https://cerb.ai/docs/data-queries/"}
		</legend>
		
		<textarea name="params[data_query]" data-editor-mode="ace/mode/cerb_query" class="placeholders" style="width:95%;height:50px;">{$widget->params.data_query}</textarea>
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