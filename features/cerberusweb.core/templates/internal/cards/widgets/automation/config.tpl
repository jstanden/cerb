<div id="widget{$widget->id}Config" class="cerb-u-mt-3">
	<div id="widget{$widget->id}Behavior" class="cerb-ui-panel cerb-ui-panel--spaced">
		<div class="cerb-ui-header cerb-ui-header--tight">
			<div class="cerb-ui-header--title-sm">Event: Render card widget</div>
		</div>

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
"}

		{$toolbar = DevblocksPlatform::services()->ui()->toolbar()->parse($toolbar_kata, $toolbar_dict)}

		{* The editor toolbar is the KataEditor's integrated strip below; these hidden <ul>s are its host sections
		   (the Automation interaction + the Placeholders/Test toggles), merged in via toolbar.sections. *}
		<div data-cerb-interaction-toolbar hidden>{DevblocksPlatform::services()->ui()->toolbar()->render($toolbar)}</div>
		{include file="devblocks:cerberusweb.core::automations/triggers/editor_event_handler_toolbar.tpl"}

		<textarea name="params[automations_kata]" data-editor-lines="15" spellcheck="false">{$widget->extension_params.automations_kata}</textarea>

		{if $trigger_ext}
			{include file="devblocks:cerberusweb.core::automations/triggers/editor_event_handler.tpl" trigger_inputs=$trigger_ext->getEventPlaceholders()}
		{/if}
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var $fieldset = $('#widget{$widget->id}Behavior');

	// Editor — KataEditor with its integrated toolbar: the Automation interaction + the Placeholders/Test toggles
	// are merged in as host `sections`. Interactions fire via `toolbarOpts` (caller/start/done); the toggles route
	// through `onAction` (show/hide the help/tester panels).
	var automations_editor = new CerbUI.KataEditor($fieldset.find('textarea[name="params[automations_kata]"]')[0], {
		toolbar: {
			sections: [
				$fieldset.find('[data-cerb-interaction-toolbar] ul.cerb-ui-toolbar')[0],
				$fieldset.find('[data-cerb-event-toolbar]')[0]
			],
			toolbarOpts: {
				caller: { name: 'cerb.toolbar.eventHandlers.editor', params: { selected_text: '' } },
				width: '75%',
				start: function(formData) {
					var pos = automations_editor.getCursorPosition();
					formData.set('caller[params][selected_text]', automations_editor.getSelectedText());
					formData.set('caller[params][token_path]', automations_editor.getTokenPath().join(''));
					formData.set('caller[params][cursor_row]', pos.row);
					formData.set('caller[params][cursor_column]', pos.column);
					formData.set('caller[params][trigger]', 'cerb.trigger.ui.widget');
					formData.set('caller[params][value]', automations_editor.getValue());
				},
				done: function(e) {
					e.stopPropagation();
					if(!e.trigger.is('.cerb-bot-trigger'))
						return;
					if(e.eventData.exit === 'return')
						Devblocks.interactionWorkerPostActions(e.eventData, automations_editor);
				}
			},
			onAction: function(value, ed, item) {
				if(value === 'placeholders') { $fieldset.find('[data-cerb-event-placeholders]').toggle(!!(item && item.pressed)); return true; }
				if(value === 'tester')       { $fieldset.find('[data-cerb-event-tester]').toggle(!!(item && item.pressed)); return true; }
				return false;
			}
		}
	});

	// Tester panel ("Test": placeholders KataEditor + Run + results) — shared impl in editor-core.
	CerbUI.editorCore.attachEventHandlerTester($fieldset, automations_editor);
});
</script>