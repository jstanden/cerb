<div id="widget{$widget->id}Config" class="cerb-ui-form" style="margin-top:10px;">
	<div class="cerb-ui-panel cerb-ui-panel--spaced">
		<div class="cerb-ui-header cerb-ui-header--tight">
			<div class="cerb-ui-header--title-sm">Datasets: (KATA)</div>
		</div>

		<div class="cerb-ui-kataeditor-wrap">
			<ul class="cerb-ui-toolbar" id="widget{$widget->id}DatasetsToolbar">
				<li data-icon="autocomplete" data-value="autocomplete" title="{'common.autocomplete'|devblocks_translate|capitalize} (Ctrl+Space)"></li>
				<li data-icon="play" data-value="test" title="Test datasets"></li>
			</ul>

			<div class="cerb-ui-kataeditor" id="widget{$widget->id}DatasetsEditor">
				<div class="cerb-ui-kataeditor--gutter" aria-hidden="true"></div>
				<div class="cerb-ui-kataeditor--field">
					<div class="cerb-ui-kataeditor--highlight" aria-hidden="true"></div>
					<textarea class="cerb-ui-kataeditor--input" name="params[datasets_kata]" data-editor-lines="8" spellcheck="false">{$widget->extension_params.datasets_kata}</textarea>
					<span class="cerb-ui-kataeditor--caret-anchor"></span>
				</div>
			</div>
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">Simulate placeholders: (KATA)</label>
			<div class="cerb-ui-kataeditor" id="widget{$widget->id}SimulatorEditor">
				<div class="cerb-ui-kataeditor--gutter" aria-hidden="true"></div>
				<div class="cerb-ui-kataeditor--field">
					<div class="cerb-ui-kataeditor--highlight" aria-hidden="true"></div>
					<textarea class="cerb-ui-kataeditor--input" name="params[placeholder_simulator_kata]" data-editor-lines="4" spellcheck="false">{$widget->extension_params.placeholder_simulator_kata}</textarea>
					<span class="cerb-ui-kataeditor--caret-anchor"></span>
				</div>
			</div>
		</div>

		<div class="cerb-ui-panel" style="display:none;" data-cerb-results-datasets>
			<div class="cerb-ui-header cerb-ui-header--tight cerb-ui-header--center">
				<div class="cerb-ui-header--title-sm">{'common.results'|devblocks_translate|capitalize}</div>
				<div class="cerb-ui-header--right">
					<button type="button" class="cerb-ui-button cerb-ui-button--transparent" data-cerb-results-close><span class="cerb-icons cerb-icon-circle-remove"></span></button>
				</div>
			</div>
			<div class="cerb-ui-jsoneditor" id="widget{$widget->id}ResultsEditor">
				<div class="cerb-ui-jsoneditor--gutter" aria-hidden="true"></div>
				<div class="cerb-ui-jsoneditor--field">
					<div class="cerb-ui-jsoneditor--highlight" aria-hidden="true"></div>
					<textarea class="cerb-ui-jsoneditor--input" data-editor-lines="15" spellcheck="false"></textarea>
					<span class="cerb-ui-jsoneditor--caret-anchor"></span>
				</div>
			</div>
		</div>
	</div>

	<div class="cerb-ui-panel cerb-ui-panel--spaced">
		<div class="cerb-ui-header cerb-ui-header--tight">
			<div class="cerb-ui-header--title-sm">Chart: (KATA)</div>
		</div>

		<div class="cerb-ui-kataeditor-wrap">
			<ul class="cerb-ui-toolbar" id="widget{$widget->id}ChartToolbar">
				<li data-icon="autocomplete" data-value="autocomplete" title="{'common.autocomplete'|devblocks_translate|capitalize} (Ctrl+Space)"></li>
				<li data-icon="play" data-value="test" title="Test chart"></li>
			</ul>

			<div class="cerb-ui-kataeditor" id="widget{$widget->id}ChartEditor">
				<div class="cerb-ui-kataeditor--gutter" aria-hidden="true"></div>
				<div class="cerb-ui-kataeditor--field">
					<div class="cerb-ui-kataeditor--highlight" aria-hidden="true"></div>
					<textarea class="cerb-ui-kataeditor--input" name="params[chart_kata]" data-editor-lines="10" spellcheck="false">{$widget->extension_params.chart_kata}</textarea>
					<span class="cerb-ui-kataeditor--caret-anchor"></span>
				</div>
			</div>
		</div>

		<div class="cerb-ui-panel" style="display:none;" data-cerb-results-chart-wrap>
			<div class="cerb-ui-header cerb-ui-header--tight cerb-ui-header--center">
				<div class="cerb-ui-header--title-sm">{'common.preview'|devblocks_translate|capitalize}</div>
				<div class="cerb-ui-header--right">
					<button type="button" class="cerb-ui-button cerb-ui-button--transparent" data-cerb-results-close><span class="cerb-icons cerb-icon-circle-remove"></span></button>
				</div>
			</div>
			<div data-cerb-results-chart></div>
		</div>
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	const $config = $('#widget{$widget->id}Config');
	const $frm = $config.closest('form');

	// Close buttons on the results / preview panels
	$config.find('[data-cerb-results-close]').on('click', function(e) {
		e.stopPropagation();
		$(this).closest('.cerb-ui-panel').hide();
	});

	// Editors

	const datasets_editor = new CerbUI.KataEditor($config.find('#widget{$widget->id}DatasetsEditor')[0], {
		onAutocomplete: CerbUI.KataEditor.kataFieldSource({$datasets_autocomplete_json nofilter})
	});

	const chart_editor = new CerbUI.KataEditor($config.find('#widget{$widget->id}ChartEditor')[0], {
		onAutocomplete: CerbUI.KataEditor.kataFieldSource({$chart_autocomplete_json nofilter})
	});

	new CerbUI.KataEditor($config.find('#widget{$widget->id}SimulatorEditor')[0]);

	const results_editor = new CerbUI.JsonEditor($config.find('#widget{$widget->id}ResultsEditor')[0], {
		readOnly: true,
		minLines: 1
	});

	// Test datasets

	const testDatasets = function(e) {
		const $panel = $config.find('[data-cerb-results-datasets]');

		// alt+click clears the results
		if(e && e.altKey) {
			$panel.hide();
			results_editor.setValue('');
			return;
		}

		const $toolbar = $config.find('#widget{$widget->id}DatasetsToolbar').next('.cerb-ui-toolbar--strip');

		const $spinner = Devblocks.getSpinner()
			.css('max-width', '16px')
			.css('margin-right', '5px')
			.insertAfter($toolbar)
		;

		$panel.hide();

		const formData = new FormData($frm.get(0));
		formData.set('c', 'profiles');
		formData.set('a', 'invoke');
		formData.set('module', 'profile_widget');
		formData.set('action', 'invokeConfig');
		formData.set('config_action', 'previewDataset');

		genericAjaxPost(formData, '', '', function(json) {
			$spinner.remove();

			if(null == json || 'object' != typeof json) {
				Devblocks.createAlertError('An unexpected error occurred.');

			} else if(json.hasOwnProperty('error')) {
				results_editor.setValue(json.error);
				$panel.show();

			} else {
				results_editor.setValue(JSON.stringify(json, null, 2));
				$panel.show();
			}
		});
	};

	// Test chart

	const testChart = function(e) {
		const $panel = $config.find('[data-cerb-results-chart-wrap]');
		const $chart_preview = $panel.find('[data-cerb-results-chart]');

		$panel.hide();
		$chart_preview.html('');

		// alt+click clears the preview
		if(e && e.altKey)
			return;

		const $toolbar = $config.find('#widget{$widget->id}ChartToolbar').next('.cerb-ui-toolbar--strip');

		const $spinner = Devblocks.getSpinner()
			.css('max-width', '16px')
			.css('margin-right', '5px')
			.insertAfter($toolbar)
		;

		const formData = new FormData($frm.get(0));
		formData.set('c', 'profiles');
		formData.set('a', 'invoke');
		formData.set('module', 'profile_widget');
		formData.set('action', 'invokeConfig');
		formData.set('config_action', 'previewChart');

		genericAjaxPost(formData, '', '', function(html) {
			$spinner.remove();

			if('string' == typeof html) {
				$chart_preview.html(html);
				$panel.show();
			}
		});
	};

	// Toolbars

	const datasets_toolbar = document.getElementById('widget{$widget->id}DatasetsToolbar');
	if(datasets_toolbar && window.CerbUI && CerbUI.Toolbar) {
		new CerbUI.Toolbar(datasets_toolbar, {
			bare: false,
			onSelect: function(item, sourceLi, e) {
				if('autocomplete' === item.value) {
					datasets_editor.openAutocomplete();
				} else if('test' === item.value) {
					testDatasets(e);
				}
			}
		});
	}

	const chart_toolbar = document.getElementById('widget{$widget->id}ChartToolbar');
	if(chart_toolbar && window.CerbUI && CerbUI.Toolbar) {
		new CerbUI.Toolbar(chart_toolbar, {
			bare: false,
			onSelect: function(item, sourceLi, e) {
				if('autocomplete' === item.value) {
					chart_editor.openAutocomplete();
				} else if('test' === item.value) {
					testChart(e);
				}
			}
		});
	}
});
</script>
