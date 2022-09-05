<div style="margin-top:10px;">
	<fieldset class="peek">
		<legend>Enable these form interactions: <small>(KATA)</small></legend>

		<div class="cerb-code-editor-toolbar">
			<div data-cerb-toolbar style="display:inline-block;">
				{$toolbar_dict = DevblocksDictionaryDelegate::instance([
					'caller_name' => 'cerb.toolbar.editor',
					
					'widget__context' => CerberusContexts::CONTEXT_WORKSPACE_WIDGET,
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

			<div class="cerb-code-editor-toolbar-divider"></div>
			<button type="button" data-cerb-button="interactions-preview" class="cerb-code-editor-toolbar-button"><span class="glyphicons glyphicons-play"></span></button>

			<button type="button" style="float:right;" class="cerb-code-editor-toolbar-button cerb-editor-button-help"><a href="https://cerb.ai/docs/automations/triggers/interaction.worker/callers/cerb.toolbar.workspaceWidget.interactions/" target="_blank"><span class="glyphicons glyphicons-circle-question-mark"></span></a></button>
		</div>
		<textarea name="params[interactions_kata]" class="cerb-code-editor placeholders" data-editor-mode="ace/mode/cerb_kata" style="width:100%;">{$widget->params.interactions_kata}</textarea>
		<div class="cerb-code-editor-preview-output"></div>
	</fieldset>
</div>

{$script_uid = uniqid('script')}
<script type="text/javascript" id="{$script_uid}">
$(function() {
	var $script = $('#{$script_uid}');
	var $config = $script.prev('div');
	var $form = $config.closest('form');

	var $editor = $config.find('.cerb-code-editor')
		.cerbCodeEditor()
		.cerbCodeEditorAutocompleteKata({
			autocomplete_suggestions: cerbAutocompleteSuggestions.kataToolbar
		})
		.next('pre.ace_editor')
		;

	var editor = ace.edit($editor.attr('id'));

	var $placeholder_output = $config.find('.cerb-code-editor-preview-output');

	$config.find('button[data-cerb-button="interactions-preview"]').on('click', function (e) {
		e.stopPropagation();
		$placeholder_output.html('').append(Devblocks.getSpinner());

		var formData = new FormData();
		formData.set('c', 'profiles');
		formData.set('a', 'invoke');
		formData.set('module', 'workspace_widget');
		formData.set('action', 'invokeConfig');
		formData.set('config_action', 'previewInteractions');
		formData.set('interactions_kata', editor.getValue());

		var $hidden = $form.find('input[name=id]');

		if(0 === $hidden.length) {
			var $select = $form.find('select[name=extension_id]');

			if(0 === $select.length)
				return;

			formData.set('id', $select.val());
			formData.set('record_type', $form.find('input[name=record_type]').val());

		} else {
			formData.set('id', $hidden.val());
		}
		genericAjaxPost(formData, null, null, function (html) {
			$placeholder_output.html(html);
		});
	})
	;

	var doneFunc = function(e) {
		e.stopPropagation();

		var $target = e.trigger;

		if(!$target.is('.cerb-bot-trigger'))
			return;

		if(!e.eventData || !e.eventData.exit)
			return;

		if (e.eventData.exit === 'error') {
			// [TODO] Show error

		} else if(e.eventData.exit === 'return' && e.eventData.return.snippet) {
			editor.insertSnippet(e.eventData.return.snippet);
		}
	};

	var resetFunc = function(e) {
		e.stopPropagation();
	};

	var errorFunc = function(e) {
		e.stopPropagation();
	};

	$config.find('[data-cerb-toolbar]').cerbToolbar({
		caller: {
			name: 'cerb.toolbar.editor',
			params: {
				toolbar: 'cerb.toolbar.workspaceWidget.interactions',
				selected_text: ''
			}
		},
		start: function(formData) {
			formData.set('caller[params][selected_text]', editor.getSelectedText())
		},
		done: doneFunc,
		reset: resetFunc,
		error: errorFunc
	});
});
</script>