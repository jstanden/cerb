<div id="widget{$widget->id}Config" style="margin-top:10px;">
	<fieldset id="widget{$widget->id}Behavior" class="peek">
		<legend>Event: Render card widget</legend>
		
		<div class="cerb-code-editor-toolbar">
			{$toolbar_dict = DevblocksDictionaryDelegate::instance([
			'caller_name' => 'cerb.toolbar.eventHandlers.editor',

			'widget__context' => CerberusContexts::CONTEXT_CARD_WIDGET,
			'widget_id' => $widget->id,

			'worker__context' => CerberusContexts::CONTEXT_WORKER,
			'worker_id' => $active_worker->id
			])}

			{$toolbar_kata =
"interaction/automation:
  uri: ai.cerb.eventHandler.automation
  tooltip: Automation
  icon: circle-plus
  inputs:
    trigger: cerb.trigger.ui.widget
"}

			{$toolbar = DevblocksPlatform::services()->ui()->toolbar()->parse($toolbar_kata, $toolbar_dict)}

			{DevblocksPlatform::services()->ui()->toolbar()->render($toolbar)}

			<div class="cerb-code-editor-toolbar-divider"></div>
			{include file="devblocks:cerberusweb.core::automations/triggers/editor_event_handler_buttons.tpl"}
		</div>

		<textarea name="params[automations_kata]" data-editor-mode="ace/mode/cerb_kata">{$widget->extension_params.automations_kata}</textarea>

		{$trigger_ext = Extension_AutomationTrigger::get(AutomationTrigger_UiWidget::ID, true)}
		{if $trigger_ext}
			{include file="devblocks:cerberusweb.core::automations/triggers/editor_event_handler.tpl" trigger_inputs=$trigger_ext->getEventPlaceholders()}
		{/if}
	</fieldset>
</div>

<script type="text/javascript">
$(function() {
	var $fieldset = $('fieldset#widget{$widget->id}Behavior');

	// Editors

	var $automations_editor = $fieldset.find('textarea[name="params[automations_kata]"]')
		.cerbCodeEditor()
		.nextAll('pre.ace_editor')
	;

	var automations_editor = ace.edit($automations_editor.attr('id'));

	// Toolbar

	var $automations_toolbar = $fieldset.find('.cerb-code-editor-toolbar').cerbToolbar({
		caller: {
			name: 'cerb.toolbar.eventHandlers.editor',
			params: {
				selected_text: ''
			}
		},
		width: '75%',
		start: function(formData) {
			var pos = automations_editor.getCursorPosition();
			var token_path = Devblocks.cerbCodeEditor.getKataTokenPath(pos, automations_editor).join('');

			formData.set('caller[params][selected_text]', automations_editor.getSelectedText());
			formData.set('caller[params][token_path]', token_path);
			formData.set('caller[params][cursor_row]', pos.row);
			formData.set('caller[params][cursor_column]', pos.column);
			formData.set('caller[params][trigger]', 'cerb.trigger.ui.widget');
			formData.set('caller[params][value]', automations_editor.getValue());
		},
		done: function(e) {
			e.stopPropagation();

			var $target = e.trigger;

			if (!$target.is('.cerb-bot-trigger'))
				return;

			if (e.eventData.exit === 'error') {

			} else if(e.eventData.exit === 'return') {
				Devblocks.interactionWorkerPostActions(e.eventData, automations_editor);
			}
		}
	});

	$automations_toolbar.cerbCodeEditorToolbarEventHandler({
		editor: automations_editor
	});	
});
</script>