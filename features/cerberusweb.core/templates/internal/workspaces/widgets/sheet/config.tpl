<div id="widget{$widget->id}Config" style="margin-top:10px;">
	<fieldset id="widget{$widget->id}QueryEditor" class="peek">
		<legend>
			Run this data query: 
			{include file="devblocks:cerberusweb.core::help/docs_button.tpl" url="https://cerb.ai/docs/data-queries/"}
		</legend>
		
		<textarea name="params[data_query]" class="cerb-data-query-editor placeholders" data-editor-mode="ace/mode/cerb_query" style="width:95%;height:50px;">{$widget->params.data_query}</textarea>
		
		<div>
			<b>Cache</b> query results for 
			<input type="text" size="5" maxlength="6" name="params[cache_secs]" placeholder="e.g. 300" value="{$widget->params.cache_secs}"> seconds
		</div>
		
		<div style="margin-top:5px;">
			<button type="button" class="cerb-button-sample-query">Test query</button>
			
			<div style="display:none;">
				<div>
					<b>Simulate placeholders:</b> (YAML)
				</div>
				<textarea name="params[placeholder_simulator_yaml]" class="cerb-data-query-editor-placeholders" data-editor-mode="ace/mode/yaml">{$model->params.placeholder_simulator_yaml}</textarea>
			</div>
			
			<div style="display:none;">
				<div>
					<b>{'common.results'|devblocks_translate|capitalize}:</b>
				</div>
				<textarea class="cerb-json-results-editor" data-editor-mode="ace/mode/json"></textarea>
			</div>
		</div>
	</fieldset>
	
	<fieldset id="widget{$widget->id}Columns" class="peek">
		<legend>
			Display this sheet schema: <small>(YAML)</small> 
			{include file="devblocks:cerberusweb.core::help/docs_button.tpl" url="https://cerb.ai/docs/sheets/"}
		</legend>
		
		<textarea name="params[sheet_yaml]" class="cerb-sheet-yaml-editor" data-editor-mode="ace/mode/yaml" style="width:95%;height:50px;">{$widget->params.sheet_yaml}</textarea>
		
		<div style="margin-top:5px;">
			<button type="button" class="cerb-button-preview-sheet">Preview sheet</button>
			<div class="cerb-sheet-preview"></div>
		</div>
		
	</fieldset>
</div>

<script type="text/javascript">
$(function() {
	var $config = $('#widget{$widget->id}Config');
	var $frm = $config.closest('form');
	var $query_button = $config.find('button.cerb-button-sample-query');
	
	var $query_editor = $config.find('textarea.cerb-data-query-editor')
		.cerbCodeEditor()
		.cerbCodeEditorAutocompleteDataQueries()
		.nextAll('pre.ace_editor')
		;
	
	var $query_placeholders_editor = $config.find('textarea.cerb-data-query-editor-placeholders')
		.cerbCodeEditor()
		.nextAll('pre.ace_editor')
		;
	
	var $json_results = $config.find('textarea.cerb-json-results-editor')
		.cerbCodeEditor()
		.nextAll('pre.ace_editor')
		;
	
	$query_button.on('click', function(e) {
		e.stopPropagation();
		
		// If alt+click, clear the results
		if(e.altKey) {
			var json_results = ace.edit($json_results.attr('id'));
			$json_results.parent().hide();
			json_results.setValue('');
			$query_placeholders_editor.parent().hide();
			return;
		}
		
		var query_editor = ace.edit($query_editor.attr('id'));
		$query_placeholders_editor.parent().show();
		
		var $frm = $config.closest('form');
		var field_key = 'params[data_query]';
		
		genericAjaxPost($frm, '', 'c=profiles&a=handleSectionAction&section=workspace_widget&action=testWidgetTemplate&format=json&template_key=' + encodeURIComponent(field_key), function(json) {
			if(false == json.status) {
				var editor = ace.edit($json_results.attr('id'));
				
				editor.session.setMode('ace/mode/text');
				editor.setReadOnly(true);
				editor.renderer.setOption('showLineNumbers', false);
				editor.setValue(json.response);
				editor.clearSelection();
				
				$json_results.parent().show();
				return;
			}
			
			var formData = new FormData();
			formData.append('q', json.response);
			
			genericAjaxPost(formData, '', 'c=ui&a=dataQuery', function(json) {
				var editor = ace.edit($json_results.attr('id'));
				
				editor.session.setMode('ace/mode/json');
				editor.setReadOnly(true);
				editor.renderer.setOption('showLineNumbers', false);
				editor.setValue(JSON.stringify(json, null, 2));
				editor.clearSelection();
				
				$json_results.parent().show();
			});
		});
	});
	
	var $yaml_editor = $config.find('textarea.cerb-sheet-yaml-editor')
		.cerbCodeEditor()
		.cerbCodeEditorAutocompleteYaml({
			autocomplete_suggestions: cerbAutocompleteSuggestions.yamlSheetSchema
		})
		.nextAll('pre.ace_editor')
		;
	
	var $sheet_button = $config.find('.cerb-button-preview-sheet');
	var $sheet_preview = $config.find('.cerb-sheet-preview');
	
	$sheet_button.on('click', function(e) {
		e.stopPropagation();
		
		$sheet_preview.html('');
		
		// If alt+click, clear the results
		if(e.altKey) {
			return;
		}
		
		var formData = new FormData($frm.get(0));
		formData.append('template_key', 'params[data_query]');
		formData.append('format', 'json');
		
		genericAjaxPost(formData, '', 'c=profiles&a=handleSectionAction&section=workspace_widget&action=testWidgetTemplate', function(json) {
			if(false == json.status) {
				$sheet_preview.text(json.response);
				return;
			}
			
			var editor = ace.edit($yaml_editor.attr('id'));
			
			var formData = new FormData();
			formData.append('data_query', json.response);
			formData.append('sheet_yaml', editor.getValue());
			formData.append('types[]', 'card');
			formData.append('types[]', 'date');
			formData.append('types[]', 'icon');
			formData.append('types[]', 'link');
			formData.append('types[]', 'search');
			formData.append('types[]', 'search_button');
			formData.append('types[]', 'slider');
			formData.append('types[]', 'text');
			formData.append('types[]', 'time_elapsed');
			
			genericAjaxPost(formData, '', 'c=ui&a=sheet', function(html) {
				$sheet_preview.html(html);
			});
		});
	});
});
</script>