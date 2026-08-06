<div id="widget{$widget->id}Config" class="cerb-u-mt-3">
	<div id="widget{$widget->id}QueryEditor" class="cerb-ui-panel cerb-ui-panel--spaced">
		<div class="cerb-ui-header cerb-ui-header--tight">
			<div class="cerb-ui-header--title-sm">Run this data query</div>
		</div>

		{* Data-query editor actions (Test/Help) — folded into the DataQuery editor's own toolbar (beside its built-in suggest) *}
		<ul class="cerb-ui-toolbar" data-cerb-query-actions hidden>
			<li data-value="test" data-icon="play" title="Test query"></li>
			<li data-value="help" data-icon="circle-question-mark" title="{'common.help'|devblocks_translate|capitalize}"></li>
		</ul>

		<textarea id="widget{$widget->id}DataQuery" class="placeholders" name="params[data_query]" data-editor-lines="12" spellcheck="false">{$widget->params.data_query}</textarea>

		<div class="cerb-ui-form--field cerb-u-mt-2">
			<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-1 cerb-u-flex-wrap">
				<span class="cerb-u-text-muted">Cache query results for</span>
				<input type="text" maxlength="6" name="params[cache_secs]" placeholder="e.g. 300" value="{$widget->params.cache_secs}" style="width:6em;flex:0 0 auto;">
				<span class="cerb-u-text-muted">seconds</span>
			</div>
		</div>

		<div class="cerb-u-mt-3">
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">Simulate placeholders: <span class="cerb-ui-form--hint">KATA</span></label>
				<textarea name="params[placeholder_simulator_kata]" data-editor-lines="6" spellcheck="false">{$widget->params.placeholder_simulator_kata}</textarea>
			</div>

			<div class="cerb-ui-panel cerb-ui-panel--spaced cerb-u-mt-2" data-cerb-results-panel style="display:none;position:relative;">
				<span data-cerb-link="fieldset_hide" class="cerb-icons cerb-icon-circle-remove" style="position:absolute;right:0.5em;top:0.5em;cursor:pointer;z-index:1;"></span>
				<div class="cerb-ui-header cerb-ui-header--tight"><div class="cerb-ui-header--title-sm">{'common.results'|devblocks_translate|capitalize}</div></div>
				<textarea class="cerb-json-results-editor" spellcheck="false"></textarea>
			</div>
		</div>
	</div>

	<div id="widget{$widget->id}Columns" class="cerb-ui-panel cerb-ui-panel--spaced">
		<div class="cerb-ui-header cerb-ui-header--tight">
			<div class="cerb-ui-header--title-sm">Display this sheet schema: <span class="cerb-ui-form--hint">KATA</span></div>
		</div>

		{* Schema editor actions — folded into the KataEditor's own toolbar *}
		<ul class="cerb-ui-toolbar" data-cerb-schema-actions hidden>
			<li data-value="preview" data-icon="play" title="Preview sheet"></li>
			<li data-icon="circle-plus" title="Add column">
				<ul>
					<li data-value="col:card" data-icon="id-card">Card</li>
					<li data-value="col:date" data-icon="calendar">Date</li>
					<li data-value="col:interaction" data-icon="zap">Interaction</li>
					<li data-value="col:link" data-icon="link">Link</li>
					<li data-value="col:markdown" data-icon="file-document">Markdown</li>
					<li data-value="col:search" data-icon="search">Search</li>
					<li data-value="col:search_button" data-icon="funnel">Search Button</li>
					<li data-value="col:selection" data-icon="checked">Selection</li>
					<li data-value="col:slider" data-icon="slider">Slider</li>
					<li data-value="col:text" data-icon="text">Text</li>
					<li data-value="col:time_elapsed" data-icon="stopwatch">Time Elapsed</li>
				</ul>
			</li>
			<li data-value="help" data-icon="circle-question-mark" title="{'common.help'|devblocks_translate|capitalize}"></li>
		</ul>

		<textarea class="cerb-sheet-yaml-editor" name="params[sheet_kata]" data-editor-lines="20" spellcheck="false">{$widget->params.sheet_kata}</textarea>

		<div class="cerb-u-mt-3">
			<div class="cerb-ui-panel cerb-ui-panel--spaced" data-cerb-results-panel style="display:none;position:relative;">
				<span data-cerb-link="fieldset_hide" class="cerb-icons cerb-icon-circle-remove" style="position:absolute;right:0.5em;top:0.5em;cursor:pointer;z-index:1;"></span>
				<div class="cerb-ui-header cerb-ui-header--tight"><div class="cerb-ui-header--title-sm">{'common.preview'|devblocks_translate|capitalize}</div></div>
				<div class="cerb-sheet-preview"></div>
			</div>
		</div>

		<div id="widget{$widget->id}Toolbar" class="cerb-ui-panel cerb-ui-panel--spaced cerb-u-mt-3">
			<div class="cerb-ui-header cerb-ui-header--tight">
				<div class="cerb-ui-header--title-sm">Display this sheet toolbar: <span class="cerb-ui-form--hint">KATA</span></div>
			</div>

			<div>
				{* Toolbar-builder insert menu — a hidden section folded into the KataEditor's integrated strip *}
				<div data-cerb-toolbar-builder hidden>
					{$toolbar_dict = DevblocksDictionaryDelegate::instance([
						'caller_name' => 'cerb.toolbar.editor',

						'widget_context' => CerberusContexts::CONTEXT_WORKSPACE_WIDGET,
						'widget_id' => $widget->id,

						'worker__context' => CerberusContexts::CONTEXT_WORKER,
						'worker_id' => $active_worker->id
					])}

					{$toolbar_kata =
"menu/insert:
  icon: circle-plus
  items:
    interaction/interaction:
      label: Interaction
      uri: ai.cerb.toolbarBuilder.interaction
    interaction/menu:
      label: Menu
      uri: ai.cerb.toolbarBuilder.menu
"
					}

					{$toolbar = DevblocksPlatform::services()->ui()->toolbar()->parse($toolbar_kata, $toolbar_dict)}

					{DevblocksPlatform::services()->ui()->toolbar()->render($toolbar)}
				</div>

				{* Sheet-toolbar editor actions (preview + insert placeholder + help) *}
				<ul class="cerb-ui-toolbar" data-cerb-toolbar-actions hidden>
					<li data-value="preview" data-icon="play" title="Preview sheet"></li>
					<li data-icon="placeholders" title="Insert placeholder">
						<ul>
							<li data-token="{literal}{{row_selections}}{/literal}">Selected row keys</li>
							<li data-token="{literal}{{rows_visible}}{/literal}">Visible row keys</li>
						</ul>
					</li>
					<li data-value="help" data-icon="circle-question-mark" title="{'common.help'|devblocks_translate|capitalize}"></li>
				</ul>

				<textarea class="cerb-toolbar-yaml-editor" name="params[toolbar_kata]" data-editor-lines="20" spellcheck="false">{$widget->params.toolbar_kata}</textarea>

				<div class="cerb-toolbar-preview"></div>
			</div>
		</div>
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var $config = $('#widget{$widget->id}Config');
	var $frm = $config.closest('form');

	$config.find('[data-cerb-link=fieldset_hide]').on('click', function(e) {
		e.stopPropagation();
		$(this).closest('[data-cerb-results-panel]').hide();
	});

	new CerbUI.KataEditor($config.find('textarea[name="params[placeholder_simulator_kata]"]')[0]);

	var $json_results = $config.find('.cerb-json-results-editor');
	var json_results_editor = new CerbUI.JsonEditor($json_results[0], { readOnly: true, minLines: 1 });

    let doneFunc = function(e) {
        e.stopPropagation();

        if(!e.hasOwnProperty('trigger'))
            return;

        if(e.hasOwnProperty('eventData') && e.eventData.exit === 'return') {
            Devblocks.interactionWorkerPostActions(e.eventData, toolbar_editor);
        }
    }

	var resetFunc = function(e) {
		e.stopPropagation();
	};

	// Test the data query and render the JSON results below (alt-click clears)
	var runTestQuery = function(e) {
		// If alt+click, clear the results
		if(e && e.altKey) {
			$json_results.closest('[data-cerb-results-panel]').hide();
			json_results_editor.setValue('');
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
				json_results_editor.setValue(json.response);
				$json_results.closest('[data-cerb-results-panel]').show();
				return;
			}

			var formData = new FormData();
			formData.set('c', 'ui');
			formData.set('a', 'dataQuery');
			formData.set('q', json.response);

			genericAjaxPost(formData, '', 'c=ui&a=dataQuery', function(json) {
				json_results_editor.setValue(JSON.stringify(json, null, 2));
				$json_results.closest('[data-cerb-results-panel]').show();
			});
		});
	};

	const dq = new CerbUI.DataQuery($config.find('#widget{$widget->id}DataQuery')[0], {
		onAutocomplete: CerbUI.DataQuery.dataQueryFieldSource(),
		toolbar: {
			sections: [ $config.find('[data-cerb-query-actions]')[0] ],
			onAction: function(value, ed, item, sourceLi, e) {
				if('test' === value) { runTestQuery(e); return true; }
				if('help' === value) { window.open('https://cerb.ai/docs/data-queries/', '_blank', 'noopener'); return true; }
				return false;
			}
		},
	});

    let autocomplete_suggestions = CerbUI.editorCore.autocompleteSchemas.kataSchemaSheet;

    autocomplete_suggestions['*']['columns:toolbar:params:kata:(.*):?interaction:after:'] = [
        'refresh_widgets@bool: no',
        'refresh_widgets@csv: Widget Name, Other Widget',
    ];

	var $sheet_preview = $config.find('.cerb-sheet-preview');

	// Render the sheet preview below the schema editor (alt-click clears)
	var runPreviewSheet = function(ed, e) {
		$sheet_preview.html('').closest('[data-cerb-results-panel]').hide();

		// If alt+click, clear the results
		if(e && e.altKey) {
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
				$sheet_preview.text(json.response).closest('[data-cerb-results-panel]').hide();
				return;
			}

			var formData = new FormData();
			formData.set('c', 'ui');
			formData.set('a', 'sheet');
			formData.set('data_query', json.response);
			formData.set('sheet_kata', ed.getValue());
			formData.append('types[]', 'card');
			formData.append('types[]', 'code');
			formData.append('types[]', 'date');
			formData.append('types[]', 'icon');
			formData.append('types[]', 'interaction');
			formData.append('types[]', 'link');
			formData.append('types[]', 'markdown');
			formData.append('types[]', 'search');
			formData.append('types[]', 'search_button');
			formData.append('types[]', 'selection');
			formData.append('types[]', 'slider');
			formData.append('types[]', 'text');
			formData.append('types[]', 'time_elapsed');
			formData.append('types[]', 'toolbar');

			genericAjaxPost(formData, '', '', function(html) {
				$sheet_preview.html(html).closest('[data-cerb-results-panel]').fadeIn();
			});
		});
	};

	// Insert a starter column snippet of the given type into the schema editor
	var insertColumnSnippet = function(ed, column_type) {
		var snippet = '';

		{literal}
		if('card' === column_type) {
			snippet = "card/${1:" + Devblocks.uniqueId() + "}:\n  label: ${2:Name}\n  params:\n    context_key: _context\n    id_key: id\n    label_key: _label\n    image@bool: no\n    bold@bool: no\n    underline@bool: no\n";
		} else if('date' === column_type) {
			snippet = "date/${1:" + Devblocks.uniqueId() + "}:\n  label: ${2:Date}\n  params:\n    # See: https://php.net/date\n    format: d-M-Y H:i:s T\n    #value: 1577836800\n    #value_key: updated\n";
		} else if('icon' === column_type) {
			snippet = "icon/${1:" + Devblocks.uniqueId() + "}:\n  label: ${2:Sign}\n  params:\n    #image: circle-ok\n    #image_key: icon_key\n    image_template@raw:\n      {% if can_sign %}\n      circle-ok\n      {% endif %}";
		} else if('interaction' === column_type) {
			snippet = "interaction/${1:" + Devblocks.uniqueId() + "}:\n  label: ${2:Interaction}\n  params:\n    text: ${3:Link text}\n    uri: cerb:automation:${4:example.interaction.name}\n    #inputs:\n";
		} else if('link' === column_type) {
			snippet = "link/${1:" + Devblocks.uniqueId() + "}:\n  label: ${2:Link}\n  params:\n    #href: https://example.com/\n    href_key: record_url\n    #href_template@raw: /profiles/task/{{id}}-{{title|permalink}}\n    #text: Link title\n    text_key: _label\n    #text_template@raw: {{title}}\n";
		} else if('search' === column_type) {
			snippet = "search/${1:" + Devblocks.uniqueId() + "}:\n  label: ${2:Count}\n  params:\n    context: ticket\n    #query_key: query\n    query_template@raw: owner.id:{{id}}\n";
		} else if('search_button' === column_type) {
			snippet = "search_button/${1:" + Devblocks.uniqueId() + "}:\n  label: ${2:Assignments}\n  params:\n    context: ticket\n    #query_key: query\n    query_template@raw: owner.id:{{id}}\n";
		} else if('selection' === column_type) {
			snippet = "selection/${1:" + Devblocks.uniqueId() + "}:\n  params:\n    value_key: id\n    #value: 123\n    #value_template@raw: {{id}}\n";
		} else if('slider' === column_type) {
			snippet = "slider/${1:" + Devblocks.uniqueId() + "}:\n  label: ${2:Importance}\n  params:\n    min: 0\n    max: 100\n    #value: 50\n    #value_key: importance\n    #value_template@raw: {{importance+10}}\n";
		} else if('text' === column_type) {
			snippet = "text/${1:" + Devblocks.uniqueId() + "}:\n  label: ${2:Gender}\n  params:\n    #value: Female\n    #value_key: gender\n    #value_template@raw: {{gender}}\n    value_map:\n      F: Female\n      M: Male\n";
		} else if('time_elapsed' === column_type) {
			snippet = "time_elapsed/${1:" + Devblocks.uniqueId() + "}:\n  label: ${2:First Response}\n  params:\n    precision: 2\n";
		}
		{/literal}

		if(snippet.length > 0) {
			ed.insertSnippet(snippet.replace({literal}/\$\{\d+:([^}]*)\}/g{/literal}, '$1'));
		}
	};

	var yaml_editor = new CerbUI.KataEditor($config.find('.cerb-sheet-yaml-editor')[0], {
		onAutocomplete: CerbUI.KataEditor.kataFieldSource(autocomplete_suggestions),
		toolbar: {
			sections: [ $config.find('[data-cerb-schema-actions]')[0] ],
			onAction: function(value, ed, item, sourceLi, e) {
				if('preview' === value) { runPreviewSheet(ed, e); return true; }
				if('help' === value) { window.open('https://cerb.ai/docs/sheets/', '_blank', 'noopener'); return true; }
				if(value && 0 === value.indexOf('col:')) { insertColumnSnippet(ed, value.slice(4)); return true; }
				return false;
			}
		}
	});

	$sheet_preview.on('cerb-sheet--refresh', function(e) {
		e.stopPropagation();
		runPreviewSheet(yaml_editor);
	});

	// Toolbar

	var $toolbar_preview = $config.find('.cerb-toolbar-preview');

	autocomplete_suggestions = CerbUI.editorCore.autocompleteSchemas.kataToolbar;

    autocomplete_suggestions['*']['(.*):?interaction:after:'] = [
        'refresh_widgets@bool: no',
        'refresh_widgets@csv: Widget Name, Other Widget',
    ];

	// Preview the sheet toolbar below the editor
	var runPreviewToolbar = function(ed) {
		$toolbar_preview.html('').append(Devblocks.getSpinner());

		var formData = new FormData($frm.get(0));
		formData.set('c', 'profiles');
		formData.set('a', 'invoke');
		formData.set('module', 'workspace_widget');
		formData.set('action', 'invokeConfig');
		formData.set('config_action', 'previewToolbar');

		genericAjaxPost(formData, null, null, function(html) {
			$toolbar_preview.html(html);
		});
	};

	var toolbar_editor = new CerbUI.KataEditor($config.find('.cerb-toolbar-yaml-editor')[0], {
		onAutocomplete: CerbUI.KataEditor.kataFieldSource(CerbUI.editorCore.autocompleteSchemas.kataToolbar),
		toolbar: {
			sections: [
				$config.find('[data-cerb-toolbar-builder] ul.cerb-ui-toolbar')[0],
				$config.find('[data-cerb-toolbar-actions]')[0]
			],
			toolbarOpts: {
				caller: {
					name: 'cerb.toolbar.editor',
					params: {
						toolbar: 'cerb.toolbar.workspaceWidget.sheet',
						selected_text: ''
					}
				},
				start: function(formData) {
					formData.set('caller[params][selected_text]', toolbar_editor.getSelectedText())
				},
				done: doneFunc,
				reset: resetFunc
			},
			onAction: function(value, ed, item, sourceLi, e) {
				if('preview' === value) { runPreviewToolbar(ed); return true; }
				if('help' === value) { window.open('https://cerb.ai/docs/kata/', '_blank', 'noopener'); return true; }
				var tok = sourceLi && sourceLi.getAttribute('data-token');
				if(tok) { ed.insertSnippet(tok); return true; }
				return false;
			}
		}
	});
});
</script>
