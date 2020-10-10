{$config_uniqid = uniqid('widgetConfig_')}
<div style="margin-top:10px;">
	<fieldset class="peek">
		<legend>Enable these form interactions: <small>(Kata)</small></legend>

		<div class="cerb-code-editor-toolbar">
			<div data-cerb-toolbar style="display:inline-block;">
				{$toolbar_dict = DevblocksDictionaryDelegate::instance([
				'worker__context' => CerberusContexts::CONTEXT_WORKER,
				'worker_id' => $active_worker->id
				])}

				{$toolbar_kata =
"menu/insert:
  icon: circle-plus
  items:
    interaction/interaction:
      label: Interaction
      name: cerb.toolbarBuilder.interaction
    interaction/function:
      label: Function
      name: cerb.toolbarBuilder.function
    interaction/menu:
      label: Menu
      name: cerb.toolbarBuilder.menu
"
				}

				{$toolbar = DevblocksPlatform::services()->ui()->toolbar()->parse($toolbar_kata, $toolbar_dict)}

				{DevblocksPlatform::services()->ui()->toolbar()->render($toolbar)}
			</div>

			<div class="cerb-code-editor-toolbar-divider"></div>
			<button type="button" data-cerb-button="interactions-preview" class="cerb-code-editor-toolbar-button"><span class="glyphicons glyphicons-play"></span></button>

			<button type="button" style="float:right;" class="cerb-code-editor-toolbar-button cerb-editor-button-help"><a href="https://cerb.ai/docs/bots/interactions/forms/" target="_blank"><span class="glyphicons glyphicons-circle-question-mark"></span></a></button>
		</div>
		<textarea name="params[interactions_kata]" class="cerb-code-editor placeholders" data-editor-mode="ace/mode/cerb_kata">{$widget->extension_params.interactions_kata}</textarea>
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
		.next('pre.ace_editor')
		;

	var editor = ace.edit($editor.attr('id'));

	var $placeholder_output = $config.find('.cerb-code-editor-preview-output');

	$config.find('button[data-cerb-button="interactions-preview"]').on('click', function (e) {
		e.stopPropagation();
		$placeholder_output.html('');

		Devblocks.getSpinner().appendTo($placeholder_output);

		var formData = new FormData();
		formData.set('c', 'profiles');
		formData.set('a', 'invoke');
		formData.set('module', 'card_widget');
		formData.set('action', 'invokeConfig');
		formData.set('config_action', 'previewInteractions');
		formData.set('interactions_kata', editor.getValue());

		var $hidden = $form.find('input[name=id]');

		if(0 === $hidden.length) {
			var $select = $form.find('select[name=extension_id]');

			if(0 === $select.length)
				return;

			formData.set('id', $select.val());
			formData.set('record_type', $form.find('input[name=record_type]'));

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

		if(e.eventData.snippet) {
			editor.insertSnippet(e.eventData.snippet);
		}
	};

	var resetFunc = function(e) {
		e.stopPropagation();
	};

	$config.find('[data-cerb-toolbar]').cerbToolbar({
		done: doneFunc,
		reset: resetFunc,
	});
});
</script>