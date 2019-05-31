<div id="widget{$widget->id}Config" style="margin-top:10px;">
	<fieldset id="widget{$widget->id}QueryEditor" class="peek">
		<legend>
			Run this data query: 
			{include file="devblocks:cerberusweb.core::help/docs_button.tpl" url="https://cerb.ai/docs/data-queries/"} 
		</legend>
		
		<textarea name="params[data_query]" data-editor-mode="ace/mode/cerb_query" class="placeholders" style="width:95%;height:50px;">{$widget->extension_params.data_query}</textarea>
		
		<div style="margin-top:5px;">
			<b>Cache</b> results for 
			<input type="text" name="params[cache_ttl]" value="{$widget->extension_params.cache_ttl}" placeholder="0" size="6" style="width:6em;"> seconds 
			<label>
				<input type="radio" name="params[cache_by_worker]" value="0" {if !$widget->extension_params.cache_by_worker}checked="checked"{/if}> for everyone
			</label>
			<label>
				<input type="radio" name="params[cache_by_worker]" value="1" {if $widget->extension_params.cache_by_worker}checked="checked"{/if}> per worker
			</label>
		</div>
	</fieldset>
	
	<fieldset id="widget{$widget->id}Editor" class="peek">
		<legend>Render this template:</legend>
		
		<div>
			The results of the above query are available as <b>{literal}{{json}}{/literal}</b>
		</div>
		
		<div>
			<textarea name="params[template]" data-editor-mode="ace/mode/twig" class="placeholders" style="width:95%;height:50px;">{$widget->extension_params.template}</textarea>
		</div>
	</fieldset>
</div>

<script type="text/javascript">
$(function() {
	var $fieldset_query = $('fieldset#widget{$widget->id}QueryEditor');
	var $textarea_query = $fieldset_query.find('textarea[name="params[data_query]"]');
	
	var $fieldset = $('fieldset#widget{$widget->id}Editor');
	var $textarea = $fieldset.find('textarea[name="params[template]"]');
	
	var $editor = $textarea
		.cerbCodeEditor()
		.nextAll('pre.ace_editor')
	
		;
	var $editor_query = $textarea_query
		.cerbCodeEditor()
		.cerbCodeEditorAutocompleteDataQueries()
		.nextAll('pre.ace_editor')
		;
});
</script>