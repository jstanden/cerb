<div id="widget{$widget->id}Config" style="margin-top:10px;">
	<fieldset id="widget{$widget->id}QueryEditor" class="peek">
		<legend>
			Run this data query: 
		</legend>

		<div class="cerb-code-editor-toolbar">
			<button type="button" class="cerb-code-editor-toolbar-button cerb-button-sample-query" title="Test query"><span class="glyphicons glyphicons-play"></span></button>
			<button type="button" style="float:right;" class="cerb-code-editor-toolbar-button cerb-editor-button-help"><a href="https://cerb.ai/docs/data-queries/" target="_blank"><span class="glyphicons glyphicons-circle-question-mark"></span></a></button>
		</div>

		<textarea name="params[data_query]" class="cerb-data-query-editor placeholders" data-editor-mode="ace/mode/cerb_query" style="width:95%;height:50px;">{$widget->params.data_query}</textarea>
		
		<div>
			<b>Cache</b> query results for 
			<input type="text" size="5" maxlength="6" name="params[cache_secs]" placeholder="e.g. 300" value="{$widget->params.cache_secs}"> seconds
		</div>
		
		<div style="margin:5px 0 0 20px;">
			<div>
				<div>
					<legend>Simulate placeholders:</b> (KATA)</legend>
				</div>
				<textarea name="params[placeholder_simulator_kata]" class="cerb-data-query-editor-placeholders" data-editor-mode="ace/mode/yaml">{$widget->params.placeholder_simulator_kata}</textarea>
			</div>
			
			<fieldset style="display:none;position:relative;">
				<span class="glyphicons glyphicons-circle-remove" style="position:absolute;right:-5px;top:-10px;cursor:pointer;color:rgb(80,80,80);zoom:1.5;" onclick="$(this).closest('fieldset').hide();"></span>
				<legend>{'common.results'|devblocks_translate|capitalize}</legend>
				<textarea class="cerb-json-results-editor" data-editor-mode="ace/mode/json"></textarea>
			</fieldset>
		</div>
	</fieldset>
	
	<fieldset id="widget{$widget->id}Columns" class="peek">
		<legend>
			Display this sheet schema: <small>(KATA)</small>
		</legend>

		<div class="cerb-code-editor-toolbar">
			<button type="button" class="cerb-code-editor-toolbar-button cerb-button-preview-sheet" title="Preview sheet"><span class="glyphicons glyphicons-play"></span></button>
			<div class="cerb-code-editor-toolbar-divider"></div>
			<button type="button" class="cerb-code-editor-toolbar-button cerb-button-sheet-column-add" title="Add column"><span class="glyphicons glyphicons-circle-plus"></span></button>
			<ul class="cerb-float" style="display:none;">
				<li>
					<b>Column</b>
					<ul>
						<li data-type="card">Card</li>
						<li data-type="date">Date</li>
						<li data-type="link">Link</li>
						<li data-type="search">Search</li>
						<li data-type="search_button">Search Button</li>
						<li data-type="slider">Slider</li>
						<li data-type="text">Text</li>
						<li data-type="time_elapsed">Time Elapsed</li>
					</ul>
				</li>
			</ul>
			<button type="button" style="float:right;" class="cerb-code-editor-toolbar-button cerb-editor-button-help"><a href="https://cerb.ai/docs/sheets/" target="_blank"><span class="glyphicons glyphicons-circle-question-mark"></span></a></button>
		</div>

		<textarea name="params[sheet_kata]" class="cerb-sheet-yaml-editor" data-editor-mode="ace/mode/yaml" style="width:95%;height:50px;">{$widget->params.sheet_kata}</textarea>
		
		<div style="margin:5px 0 0 20px;">
			<fieldset style="display:none;position:relative;">
				<span class="glyphicons glyphicons-circle-remove" style="position:absolute;right:-5px;top:-10px;cursor:pointer;color:rgb(80,80,80);zoom:1.5;" onclick="$(this).closest('fieldset').hide();"></span>
				<legend>{'common.preview'|devblocks_translate|capitalize}</legend>
				<div class="cerb-sheet-preview"></div>
			</fieldset>
		</div>

	</fieldset>
</div>

<script type="text/javascript">
$(function() {
	var $config = $('#widget{$widget->id}Config');
	var $frm = $config.closest('form');
	var $query_button = $config.find('button.cerb-button-sample-query');
	
	$config.find('textarea.cerb-data-query-editor')
		.cerbCodeEditor()
		.cerbCodeEditorAutocompleteDataQueries()
		.nextAll('pre.ace_editor')
		;
	
	$config.find('textarea.cerb-data-query-editor-placeholders')
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
			$json_results.closest('fieldset').hide();
			json_results.setValue('');
			return;
		}
		
		var field_key = 'params[data_query]';

		var formData = new FormData($config.closest('form').get(0));
		formData.set('c', 'profiles');
		formData.set('a', 'invoke');
		formData.set('module', 'workspace_widget');
		formData.set('action', 'testWidgetTemplate');
		formData.set('template_key', field_key);
		formData.set('format', 'json');

		genericAjaxPost(formData, '', '', function(json) {
			if(false === json.status) {
				var editor = ace.edit($json_results.attr('id'));
				
				editor.session.setMode('ace/mode/text');
				editor.setReadOnly(true);
				editor.renderer.setOption('showLineNumbers', false);
				editor.setValue(json.response);
				editor.clearSelection();
				
				$json_results.closest('fieldset').show();
				return;
			}
			
			var formData = new FormData();
			formData.set('c', 'ui');
			formData.set('a', 'dataQuery');
			formData.set('q', json.response);

			genericAjaxPost(formData, '', 'c=ui&a=dataQuery', function(json) {
				var editor = ace.edit($json_results.attr('id'));
				
				editor.session.setMode('ace/mode/json');
				editor.setReadOnly(true);
				editor.renderer.setOption('showLineNumbers', false);
				editor.setValue(JSON.stringify(json, null, 2));
				editor.clearSelection();
				
				$json_results.closest('fieldset').show();
			});
		});
	});
	
	var $yaml_editor = $config.find('textarea.cerb-sheet-yaml-editor')
		.cerbCodeEditor()
		// [TODO]
		// .cerbCodeEditorAutocompleteYaml({
		// 	autocomplete_suggestions: cerbAutocompleteSuggestions.yamlSheetSchema
		// })
		.nextAll('pre.ace_editor')
		;

	var $sheet_button_preview = $config.find('.cerb-button-preview-sheet');
	var $sheet_button_add = $config.find('.cerb-button-sheet-column-add');
	var $sheet_preview = $config.find('.cerb-sheet-preview');
	
	$sheet_button_preview.on('click', function(e) {
		e.stopPropagation();
		
		$sheet_preview.html('').closest('fieldset').hide();
		
		// If alt+click, clear the results
		if(e.altKey) {
			return;
		}
		
		var formData = new FormData($frm.get(0));
		formData.set('c', 'profiles');
		formData.set('a', 'invoke');
		formData.set('module', 'workspace_widget');
		formData.set('action', 'testWidgetTemplate');
		formData.set('template_key', 'params[data_query]');
		formData.set('format', 'json');

		genericAjaxPost(formData, '', '', function(json) {
			if(false === json.status) {
				$sheet_preview.text(json.response).closest('fieldset').hide();
				return;
			}

			var yaml_editor = ace.edit($yaml_editor.attr('id'));
			
			var formData = new FormData();
			formData.set('c', 'ui');
			formData.set('a', 'sheet');
			formData.set('data_query', json.response);
			formData.set('sheet_kata', yaml_editor.getValue());
			formData.append('types[]', 'card');
			formData.append('types[]', 'date');
			formData.append('types[]', 'icon');
			formData.append('types[]', 'link');
			formData.append('types[]', 'search');
			formData.append('types[]', 'search_button');
			formData.append('types[]', 'slider');
			formData.append('types[]', 'text');
			formData.append('types[]', 'time_elapsed');
			
			genericAjaxPost(formData, '', '', function(html) {
				$sheet_preview.html(html).closest('fieldset').fadeIn();
			});
		});
	});
});
</script>