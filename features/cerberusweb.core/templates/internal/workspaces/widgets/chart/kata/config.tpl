<div id="widget{$widget->id}Config" style="margin-top:10px;">
	<fieldset class="peek" data-cerb-editor-datasets>
		<legend>Datasets: (KATA)</legend>

		<div class="cerb-code-editor-toolbar">
			<button type="button" class="cerb-code-editor-toolbar-button cerb-button-sample-datasets" title="Test datasets"><span class="glyphicons glyphicons-play"></span></button>
		</div>

		<textarea name="params[datasets_kata]" data-editor-mode="ace/mode/cerb_kata" class="placeholders" style="width:95%;height:50px;">{$widget->params.datasets_kata}</textarea>

		<div style="margin:5px 0 0 20px;">
			<div>
				<div>
					<legend>Simulate placeholders:</b> (KATA)</legend>
				</div>
				<textarea name="params[placeholder_simulator_kata]" class="cerb-datasets-editor-placeholders" data-editor-mode="ace/mode/cerb_kata">{$widget->params.placeholder_simulator_kata}</textarea>
			</div>

			<fieldset style="display:none;position:relative;">
				<span class="glyphicons glyphicons-circle-remove" style="position:absolute;right:-5px;top:-10px;cursor:pointer;color:rgb(80,80,80);zoom:1.5;background-color:var(--cerb-color-background);" onclick="$(this).closest('fieldset').hide();"></span>
				<legend>{'common.results'|devblocks_translate|capitalize}</legend>
				<textarea class="cerb-json-results-editor" data-editor-mode="ace/mode/json"></textarea>
			</fieldset>
		</div>
	</fieldset>
	
	<fieldset class="peek" data-cerb-editor-chart>
		<legend>Chart: (KATA)</legend>

		<div class="cerb-code-editor-toolbar">
			<button type="button" class="cerb-code-editor-toolbar-button cerb-button-sample-chart" title="Test chart"><span class="glyphicons glyphicons-play"></span></button>
		</div>

		<textarea name="params[chart_kata]" data-editor-mode="ace/mode/cerb_kata" class="placeholders" style="width:95%;height:50px;">{$widget->params.chart_kata}</textarea>

		<div style="margin:5px 0 0 20px;">
			<fieldset style="display:none;position:relative;">
				<span class="glyphicons glyphicons-circle-remove" style="position:absolute;right:-5px;top:-10px;cursor:pointer;color:rgb(80,80,80);zoom:1.5;background-color:var(--cerb-color-background);" onclick="$(this).closest('fieldset').hide();"></span>
				<legend>{'common.preview'|devblocks_translate|capitalize}</legend>
				<div data-cerb-results-chart></div>
			</fieldset>
		</div>
	</fieldset>
</div>

<script type="text/javascript">
$(function() {
	var $config = $('#widget{$widget->id}Config');
	var $frm = $config.closest('form');
	
	// Datasets

	var $editor_datasets = $config.find('textarea[name="params[datasets_kata]"]')
		.cerbCodeEditor()
		.cerbCodeEditorAutocompleteKata({
			autocomplete_suggestions: cerbAutocompleteSuggestions.kataSchemaDataset
		})
		.nextAll('pre.ace_editor')
	;

	var editor_datasets = ace.edit($editor_datasets.attr('id'));	

	// Chart
	
	var $editor_chart = $config.find('textarea[name="params[chart_kata]"]')
		.cerbCodeEditor()
		.cerbCodeEditorAutocompleteKata({
			autocomplete_suggestions: cerbAutocompleteSuggestions.kataSchemaChart
		})
		.nextAll('pre.ace_editor')
	;

	var editor_chart = ace.edit($editor_chart.attr('id'));
	
	// Sample datasets
	
	var $query_button = $config.find('button.cerb-button-sample-datasets');

	$config.find('textarea.cerb-datasets-editor-placeholders')
		.cerbCodeEditor()
		.nextAll('pre.ace_editor')
	;
	
	var $json_results = $config.find('textarea.cerb-json-results-editor')
		.cerbCodeEditor()
		.nextAll('pre.ace_editor')
	;

	var editor_results = ace.edit($json_results.attr('id'));
	
	$query_button.on('click', function(e) {
		e.stopPropagation();

		// If alt+click, clear the results
		if(e.altKey) {
			var json_results = ace.edit($json_results.attr('id'));
			$json_results.closest('fieldset').hide();
			json_results.setValue('');
			return;
		}

		var formData = new FormData($frm.get(0));
		formData.set('c', 'profiles');
		formData.set('a', 'invoke');
		formData.set('module', 'workspace_widget');
		formData.set('action', 'invokeConfig');
		formData.set('config_action', 'previewDataset');
		
		genericAjaxPost(formData, '', '', function(json) {
			if(null == json || 'object' != typeof json) {
				Devblocks.createAlertError('An unexpected error occurred.');
			
			} else if(json.hasOwnProperty('error')) {
				editor_results.session.setMode('ace/mode/text');
				editor_results.setReadOnly(true);
				editor_results.setValue(json.error);
				editor_results.clearSelection();
				$json_results.closest('fieldset').show();
				
			} else {
				editor_results.session.setMode('ace/mode/json');
				editor_results.setReadOnly(true);
				editor_results.setValue(JSON.stringify(json, null, 2));
				editor_results.clearSelection();
				$json_results.closest('fieldset').show();
			}
		});
	});
	
	// Preview chart

	var $chart_button = $config.find('button.cerb-button-sample-chart');
	let $chart_preview = $config.find('[data-cerb-results-chart]');
	
	$chart_button.on('click', function(e) {
		e.stopPropagation();
		
		$chart_preview.closest('fieldset').hide();
		$chart_preview.html('');
		
		// If alt+click, clear the results
		if(e.altKey) {
			return;
		}
		
		var formData = new FormData($frm.get(0));
		formData.set('c', 'profiles');
		formData.set('a', 'invoke');
		formData.set('module', 'workspace_widget');
		formData.set('action', 'invokeConfig');
		formData.set('config_action', 'previewChart');

		genericAjaxPost(formData, '', '', function(html) {
			if('string' == typeof html) {
				$chart_preview.html(html);
				$chart_preview.closest('fieldset').show();
			}
		});
	});
});
</script>