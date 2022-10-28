{$config_uniqid = uniqid('widgetConfig_')}
<div id="{$config_uniqid}" style="margin-top:10px;">
	<fieldset id="{$config_uniqid}QueryEditor" class="peek">
		<legend>
			Run this data query: 
			{include file="devblocks:cerberusweb.core::help/docs_button.tpl" url="https://cerb.ai/docs/data-queries/"}
		</legend>

		<div class="cerb-code-editor-toolbar">
			<button type="button" class="cerb-code-editor-toolbar-button cerb-button-sample-query" title="Test query"><span class="glyphicons glyphicons-play"></span></button>
			<button type="button" style="float:right;" class="cerb-code-editor-toolbar-button cerb-editor-button-help"><a href="https://cerb.ai/docs/data-queries/" target="_blank"><span class="glyphicons glyphicons-circle-question-mark"></span></a></button>
		</div>

		<textarea name="params[data_query]" class="cerb-data-query-editor placeholders" data-editor-mode="ace/mode/cerb_query" style="width:95%;height:50px;">{$widget->extension_params.data_query}</textarea>
		
		<div>
			<b>Cache</b> query results for 
			<input type="text" size="5" maxlength="6" name="params[cache_secs]" placeholder="e.g. 300" value="{$widget->extension_params.cache_secs}"> seconds
		</div>

		<div style="margin:5px 0 0 20px;">
			<div>
				<div>
					<b>Simulate placeholders:</b> (KATA)
				</div>
				<textarea name="params[placeholder_simulator_kata]" class="cerb-data-query-editor-placeholders" data-editor-mode="ace/mode/cerb_kata">{$widget->extension_params.placeholder_simulator_kata}</textarea>
			</div>

			<fieldset style="display:none;position:relative;">
				<span class="glyphicons glyphicons-circle-remove" style="position:absolute;right:-5px;top:-10px;cursor:pointer;color:rgb(80,80,80);zoom:1.5;background-color:var(--cerb-color-background);" onclick="$(this).closest('fieldset').hide();"></span>
				<legend>{'common.results'|devblocks_translate|capitalize}</legend>
				<textarea class="cerb-json-results-editor" data-editor-mode="ace/mode/json"></textarea>
			</fieldset>
		</div>
	</fieldset>
	
	<fieldset id="{$config_uniqid}Schema" class="peek">
		<legend>
			Display this sheet schema: <small>(KATA)</small>
		</legend>

		<div class="cerb-code-editor-toolbar">
			<button type="button" class="cerb-code-editor-toolbar-button cerb-button-preview-sheet" title="Preview sheet"><span class="glyphicons glyphicons-play"></span></button>
			<div class="cerb-code-editor-toolbar-divider"></div>
			<button type="button" class="cerb-code-editor-toolbar-button cerb-button-sheet-column-add" title="Add column"><span class="glyphicons glyphicons-circle-plus"></span></button>
			<ul class="cerb-float" style="display:none;">
				<li>
					<div><b>Column</b></div>
					<ul>
						<li data-type="card"><div>Card</div></li>
						<li data-type="date"><div>Date</div></li>
						<li data-type="link"><div>Link</div></li>
						<li data-type="search"><div>Search</div></li>
						<li data-type="search_button"><div>Search Button</div></li>
						<li data-type="selection"><div>Selection</div></li>
						<li data-type="slider"><div>Slider</div></li>
						<li data-type="text"><div>Text</div></li>
						<li data-type="time_elapsed"><div>Time Elapsed</div></li>
					</ul>
				</li>
			</ul>
			<button type="button" style="float:right;" class="cerb-code-editor-toolbar-button cerb-editor-button-help"><a href="https://cerb.ai/docs/sheets/" target="_blank"><span class="glyphicons glyphicons-circle-question-mark"></span></a></button>
		</div>
		
		<textarea name="params[sheet_kata]" class="cerb-sheet-yaml-editor" data-editor-mode="ace/mode/cerb_kata" style="width:95%;height:50px;">{$widget->extension_params.sheet_kata}</textarea>

		<div style="margin:5px 0 0 20px;">
			<fieldset style="display:none;position:relative;">
				<span class="glyphicons glyphicons-circle-remove" style="position:absolute;right:-5px;top:-10px;cursor:pointer;color:rgb(80,80,80);zoom:1.5;background-color:var(--cerb-color-background);" onclick="$(this).closest('fieldset').hide();"></span>
				<legend>{'common.preview'|devblocks_translate|capitalize}</legend>
				<div class="cerb-sheet-preview"></div>
			</fieldset>
		</div>
	</fieldset>

	<fieldset id="{$config_uniqid}Toolbar" class="peek">
		<legend>
			<label>
				Display this sheet toolbar: <small>(KATA)</small>
			</label>
		</legend>

		<div style="margin-left:10px;">
			<div class="cerb-code-editor-toolbar">
				<button type="button" class="cerb-code-editor-toolbar-button cerb-button-preview-toolbar" title="Preview sheet"><span class="glyphicons glyphicons-play"></span></button>
				<div class="cerb-code-editor-toolbar-divider"></div>

				<div data-cerb-toolbar style="display:inline-block;">
					{$toolbar_dict = DevblocksDictionaryDelegate::instance([
						'caller_id' => 'cerb.toolbar.editor',
						
						'widget_context' => CerberusContexts::CONTEXT_CARD_WIDGET,
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

				<button type="button" class="cerb-code-editor-toolbar-button cerb-button-toolbar-insert" title="Insert placeholder"><span class="glyphicons glyphicons-tags"></span></button>
				<ul class="cerb-float" style="display:none;">
					<li>
						<div>{'common.sheet'|devblocks_translate|capitalize}</div>
						<ul>
							<li data-token="{literal}{{row_selections}}{/literal}"><div><b>Selected row keys</b></div></li>
						</ul>
					</li>
				</ul>
				<button type="button" style="float:right;" class="cerb-code-editor-toolbar-button cerb-editor-button-help"><a href="https://cerb.ai/docs/kata/" target="_blank"><span class="glyphicons glyphicons-circle-question-mark"></span></a></button>
			</div>

			<textarea name="params[toolbar_kata]" class="cerb-toolbar-yaml-editor placeholders" data-editor-mode="ace/mode/cerb_kata" style="width:95%;height:50px;">{$widget->extension_params.toolbar_kata}</textarea>

			<div class="cerb-toolbar-preview"></div>
		</div>
	</fieldset>
</div>

<script type="text/javascript">
$(function() {
	var $config = $('#{$config_uniqid}');
	var $frm = $config.closest('form');
	var $query_button = $config.find('button.cerb-button-sample-query');
	
	$config.find('.cerb-chooser-trigger')
		.cerbChooserTrigger()
		;
	
	$config.find('textarea.cerb-data-query-editor')
		.cerbCodeEditor()
		.cerbCodeEditorAutocompleteDataQueries()
		.nextAll('pre.ace_editor')
		;
	
	// Editors

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
		
		// Substitute placeholders
		
		var $frm = $config.closest('form');
		var field_key = 'params[data_query]';

		var formData = new FormData($frm[0]);
		formData.set('c', 'profiles');
		formData.set('a', 'invoke');
		formData.set('module', 'card_widget');
		formData.set('action', 'testWidgetTemplate');
		formData.set('template_key', field_key);
		formData.set('format', 'json');

		genericAjaxPost(formData, '', '', function(json) {
			if(false == json.status) {
				var editor = ace.edit($json_results.attr('id'));
				
				editor.session.setMode('ace/mode/text');
				editor.setReadOnly(true);
				editor.setValue(json.response);
				editor.clearSelection();

				$json_results.closest('fieldset').show();
				return;
			}
			
			var formData = new FormData();
			formData.set('c', 'ui');
			formData.set('a', 'dataQuery');
			formData.set('q', json.response);

			genericAjaxPost(formData, null, null, function(json) {
				var editor = ace.edit($json_results.attr('id'));
				
				editor.session.setMode('ace/mode/json');
				editor.setReadOnly(true);
				editor.setValue(JSON.stringify(json, null, 2));
				editor.clearSelection();

				$json_results.closest('fieldset').show();
			});
		});
	});
	
	var $yaml_editor = $config.find('textarea.cerb-sheet-yaml-editor')
		.cerbCodeEditor()
		.cerbCodeEditorAutocompleteKata({
			autocomplete_suggestions: cerbAutocompleteSuggestions.kataSchemaSheet
		})
		.nextAll('pre.ace_editor')
		;

	var $sheet_button_preview = $config.find('.cerb-button-preview-sheet');
	var $sheet_button_add = $config.find('.cerb-button-sheet-column-add');
	var $sheet_preview = $config.find('.cerb-sheet-preview');

	$sheet_button_preview.on('click', function(e) {
		e.stopPropagation();
		
		$sheet_preview.html('');
		
		// If alt+click, clear the results
		if(e.altKey) {
			return;
		}
		
		var formData = new FormData($frm.get(0));
		formData.set('c', 'profiles');
		formData.set('a', 'invoke');
		formData.set('module', 'card_widget');
		formData.set('action', 'testWidgetTemplate');
		formData.set('template_key', 'params[data_query]');
		formData.set('format', 'json');
		
		genericAjaxPost(formData, '', '', function(json) {
			if(false == json.status) {
				$sheet_preview.text(json.response).closest('fieldset').hide();
				return;
			}
			
			var editor = ace.edit($yaml_editor.attr('id'));
			
			var formData = new FormData();
			formData.set('c', 'ui');
			formData.set('a', 'sheet');
			formData.set('data_query', json.response);
			formData.set('sheet_kata', editor.getValue());
			formData.append('types[]', 'card');
			formData.append('types[]', 'date');
			formData.append('types[]', 'icon');
			formData.append('types[]', 'link');
			formData.append('types[]', 'search');
			formData.append('types[]', 'search_button');
			formData.append('types[]', 'selection');
			formData.append('types[]', 'slider');
			formData.append('types[]', 'text');
			formData.append('types[]', 'time_elapsed');
			
			genericAjaxPost(formData, '', '', function(html) {
				$sheet_preview.html(html).closest('fieldset').fadeIn();
			});
		});
	});

	var $sheet_button_add_menu = $sheet_button_add.next('ul').menu({
		"select": function(e, $ui) {
			e.stopPropagation();
			$sheet_button_add_menu.hide();

			var column_type = $ui.item.attr('data-type');

			if(null == column_type)
				return;

			var snippet = '';

			{literal}
			if('card' === column_type) {
				snippet = "card/${1:" + Devblocks.uniqueId() + "}:\n  label: ${2:Name}\n  params:\n    context_key: _context\n    id_key: id\n    label_key: _label\n    image@bool: no\n    bold@bool: no\n    underline@bool: no\n";
			} else if('date' === column_type) {
				snippet = "date/${1:" + Devblocks.uniqueId() + "}:\n  label: ${2:Date}\n  params:\n    # See: https://php.net/date\n    format: d-M-Y H:i:s T\n    #value: 1577836800\n    #value_key: updated\n";
			} else if('icon' === column_type) {
				snippet = "icon/${1:" + Devblocks.uniqueId() + "}:\n  label: ${2:Sign}\n  params:\n    #image: circle-ok\n    #image_key: icon_key\n    image_template@raw:\n      {% if can_sign %}\n      circle-ok\n      {% endif %}";
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
				$yaml_editor.triggerHandler($.Event('cerb.insertAtCursor', { content: snippet } ));
			}
		}
	});

	$sheet_button_add.on('click', function() {
		$sheet_button_add_menu.toggle();
	});

	// Toolbar

	var $toolbar_button_preview = $config.find('.cerb-button-preview-toolbar');
	var $toolbar_button_insert = $config.find('.cerb-button-toolbar-insert');
	var $toolbar_preview = $config.find('.cerb-toolbar-preview');

	var $toolbar_editor = $config.find('textarea.cerb-toolbar-yaml-editor')
		.cerbCodeEditor()
		.cerbCodeEditorAutocompleteKata({
			autocomplete_suggestions: cerbAutocompleteSuggestions.kataToolbar
		})
		.nextAll('pre.ace_editor')
	;

	var toolbar_editor = ace.edit($toolbar_editor.attr('id'));

	var $script_custom_toolbar = $config.find('[data-cerb-toolbar]');

	var doneFunc = function(e) {
		e.stopPropagation();

		var $target = e.trigger;

		if(!$target.is('.cerb-bot-trigger'))
			return;

		if (e.eventData.exit === 'error') {

		} else if(e.eventData.exit === 'return') {
			Devblocks.interactionWorkerPostActions(e.eventData, toolbar_editor);
		}
	};

	var resetFunc = function(e) {
		e.stopPropagation();
	};

	$script_custom_toolbar.cerbToolbar({
		caller: {
			name: 'cerb.toolbar.editor',
			params: {
				toolbar: 'cerb.toolbar.cardWidget.sheet',
				selected_text: ''
			}
		},
		start: function(formData) {
			formData.set('caller[params][selected_text]', toolbar_editor.getSelectedText())
		},
		done: doneFunc,
		reset: resetFunc,
	});

	var $toolbar_button_insert_menu = $toolbar_button_insert.next('ul').menu({
		"select": function(e, $ui) {
			e.stopPropagation();
			$toolbar_button_insert_menu.hide();

			var data_token = $ui.item.attr('data-token');

			if (null == data_token)
				return;

			$toolbar_editor.triggerHandler($.Event('cerb.insertAtCursor', { content: data_token } ));
		}
	});

	$toolbar_button_insert.on('click', function() {
		$toolbar_button_insert_menu.toggle();
	});

	$toolbar_button_preview.on('click', function(e) {
		e.stopPropagation();
		$toolbar_preview.html('').append(Devblocks.getSpinner());

		var formData = new FormData($frm.get(0));
		formData.set('c', 'profiles');
		formData.set('a', 'invoke');
		formData.set('module', 'card_widget');
		formData.set('action', 'invokeConfig');
		formData.set('config_action', 'previewToolbar');

		genericAjaxPost(formData, null, null, function(html) {
			$toolbar_preview.html(html);
		});
	})
	;
});
</script>