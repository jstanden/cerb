<b>Run this data query:</b> 
{include file="devblocks:cerberusweb.core::help/docs_button.tpl" url="https://cerb.ai/docs/data-queries/"}

<div style="margin-left:10px;margin-bottom:0.5em;">
	<textarea name="{$namePrefix}[data_query]" class="cerb-data-query-editor placeholders" data-editor-mode="ace/mode/cerb_query" style="width:95%;height:50px;">{$params.data_query}</textarea>
	
	<div style="margin-top:5px;">
		<button type="button" class="cerb-button-sample-query">Test query</button>
		
		<div style="display:none;">
			<div>
				<b>Simulate placeholders:</b> (YAML)
			</div>
			<textarea name="{$namePrefix}[placeholder_simulator_yaml]" class="cerb-data-query-editor-placeholders" data-editor-mode="ace/mode/yaml">{$params.placeholder_simulator_yaml}</textarea>
		</div>
		
		<div style="display:none;">
			<div>
				<b>{'common.results'|devblocks_translate|capitalize}:</b>
			</div>
			<textarea class="cerb-json-results-editor" data-editor-mode="ace/mode/json"></textarea>
		</div>
	</div>
</div>

<b>Display this sheet schema:</b> <small>(YAML)</small> {include file="devblocks:cerberusweb.core::help/docs_button.tpl" url="https://cerb.ai/docs/sheets/"}

<div style="margin-left:10px;margin-bottom:0.5em;">
	<textarea name="{$namePrefix}[sheet_yaml]" class="cerb-sheet-yaml-editor" data-editor-mode="ace/mode/yaml" style="width:95%;height:50px;">{$params.sheet_yaml}</textarea>
	
	<div style="margin-top:5px;">
		<button type="button" class="cerb-button-preview-sheet">Preview sheet</button>
		
		<div class="cerb-sheet-preview"></div>
	</div>
</div>

<script type="text/javascript">
$(function() {
	var $action = $('#{$namePrefix}_{$nonce}');
	var $frm = $action.closest('form');
	var $query_button = $action.find('button.cerb-button-sample-query');
	
	var $query_editor = $action.find('textarea.cerb-data-query-editor')
		.cerbCodeEditor()
		.cerbCodeEditorAutocompleteDataQueries()
		.nextAll('pre.ace_editor')
		;
	
	var $query_placeholders_editor = $action.find('textarea.cerb-data-query-editor-placeholders')
		.cerbCodeEditor()
		.nextAll('pre.ace_editor')
		;
	
	var $json_results = $action.find('textarea.cerb-json-results-editor')
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
		
		// Substitute placeholders
		
		var formData = new FormData($frm.get(0));
		formData.append('prefix', '{$namePrefix}');
		formData.append('field', '[data_query]');
		formData.append('format', 'json');
		
		genericAjaxPost(formData, '', 'c=internal&a=testDecisionEventSnippets', function(json) {
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
	
	var $yaml_editor = $action.find('textarea.cerb-sheet-yaml-editor')
		.cerbCodeEditor()
		.cerbCodeEditorAutocompleteYaml({
			autocomplete_suggestions: cerbAutocompleteSuggestions.yamlSheetSchema
		})
		.nextAll('pre.ace_editor')
		;
	
	var $sheet_button = $action.find('.cerb-button-preview-sheet');
	var $sheet_preview = $action.find('.cerb-sheet-preview');
	
	$sheet_button.on('click', function(e) {
		e.stopPropagation();
		
		$sheet_preview.html('');
		
		// If alt+click, clear the results
		if(e.altKey) {
			return;
		}
		
		var formData = new FormData($frm.get(0));
		formData.append('prefix', '{$namePrefix}');
		formData.append('field', '[data_query]');
		formData.append('format', 'json');
		
		genericAjaxPost(formData, '', 'c=internal&a=testDecisionEventSnippets', function(json) {
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