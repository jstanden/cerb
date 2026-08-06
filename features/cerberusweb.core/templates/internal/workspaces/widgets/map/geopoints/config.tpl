<div id="widget{$widget->id}Config" class="cerb-u-mt-3">
	<div data-cerb-event-map-get-data class="cerb-ui-panel cerb-ui-panel--spaced">
		<div class="cerb-ui-header cerb-ui-header--tight">
			<div class="cerb-ui-header--title-sm">Map configuration <span class="cerb-ui-form--hint">KATA</span></div>
		</div>
		{$toolbar_dict = DevblocksDictionaryDelegate::instance([
			'caller_name' => 'cerb.toolbar.editor.map'
		])}

		{$toolbar_kata =
"interaction/automation:
  icon: circle-plus
  tooltip: Map
  uri: ai.cerb.editor.mapBuilder
  inputs:
"}

		{$toolbar = DevblocksPlatform::services()->ui()->toolbar()->parse($toolbar_kata, $toolbar_dict)}

		{* The editor toolbar is the KataEditor's integrated strip below; this hidden <ul> is its host section. *}
		<div data-cerb-map-toolbar hidden>{DevblocksPlatform::services()->ui()->toolbar()->render($toolbar)}</div>

		<textarea name="params[map_kata]" data-editor-lines="15" spellcheck="false">{$model->params.map_kata}</textarea>
	</div>

	<div data-cerb-event-map-clicked class="cerb-ui-panel cerb-ui-panel--spaced">
		<div class="cerb-ui-header cerb-ui-header--tight">
			<div class="cerb-ui-header--title-sm">Event: Map clicked <span class="cerb-ui-form--hint">KATA</span></div>
		</div>
		{$toolbar_dict = DevblocksDictionaryDelegate::instance([
			'caller_name' => 'cerb.toolbar.eventHandlers.editor'
		])}

		{$toolbar_kata =
"interaction/automation:
  uri: ai.cerb.eventHandler.automation
  icon: circle-plus
  tooltip: Automation
"}

		{$toolbar = DevblocksPlatform::services()->ui()->toolbar()->parse($toolbar_kata, $toolbar_dict)}

		{* The editor toolbar is the KataEditor's integrated strip below; these hidden <ul>s are its host sections. *}
		<div data-cerb-interaction-toolbar hidden>{DevblocksPlatform::services()->ui()->toolbar()->render($toolbar)}</div>
		{include file="devblocks:cerberusweb.core::automations/triggers/editor_event_handler_toolbar.tpl"}

		<textarea name="params[automation][map_clicked]" data-editor-lines="15" spellcheck="false">{$model->params.automation.map_clicked}</textarea>

		{if $trigger_ext}
			{include file="devblocks:cerberusweb.core::automations/triggers/editor_event_handler.tpl" trigger_inputs=$trigger_ext->getEventPlaceholders()}
		{/if}
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var $config = $('#widget{$widget->id}Config');
	var $fieldset_map_get_data = $config.find('[data-cerb-event-map-get-data]');
	var $fieldset_map_clicked = $config.find('[data-cerb-event-map-clicked]');

	// Map configuration — KataEditor with the Map builder interaction merged in as a host section (no toggles).
	var map_editor = new CerbUI.KataEditor($fieldset_map_get_data.find('textarea[name="params[map_kata]"]')[0], {
		onAutocomplete: CerbUI.KataEditor.kataFieldSource(CerbUI.editorCore.autocompleteSchemas.kataSchemaMap),
		toolbar: {
			sections: [
				$fieldset_map_get_data.find('[data-cerb-map-toolbar] ul.cerb-ui-toolbar')[0]
			],
			toolbarOpts: {
				caller: { name: 'cerb.toolbar.editor.map', params: { selected_text: '' } },
				done: function(e) {
					e.stopPropagation();
					if(!e.trigger.is('.cerb-bot-trigger'))
						return;
					if(e.eventData.exit === 'return')
						Devblocks.interactionWorkerPostActions(e.eventData, map_editor);
				}
			}
		}
	});

	// Map clicked event — KataEditor with integrated event-handler toolbar (Automation + Placeholders/Test toggles).
	var automation_editor = new CerbUI.KataEditor($fieldset_map_clicked.find('textarea[name="params[automation][map_clicked]"]')[0], {
		toolbar: {
			sections: [
				$fieldset_map_clicked.find('[data-cerb-interaction-toolbar] ul.cerb-ui-toolbar')[0],
				$fieldset_map_clicked.find('[data-cerb-event-toolbar]')[0]
			],
			toolbarOpts: {
				caller: { name: 'cerb.toolbar.eventHandlers.editor', params: { selected_text: '' } },
				width: '75%',
				start: function(formData) {
					var pos = automation_editor.getCursorPosition();
					formData.set('caller[params][selected_text]', automation_editor.getSelectedText());
					formData.set('caller[params][token_path]', automation_editor.getTokenPath().join(''));
					formData.set('caller[params][cursor_row]', pos.row);
					formData.set('caller[params][cursor_column]', pos.column);
					formData.set('caller[params][trigger]', 'cerb.trigger.map.clicked');
					formData.set('caller[params][value]', automation_editor.getValue());
				},
				done: function(e) {
					e.stopPropagation();
					if(!e.trigger.is('.cerb-bot-trigger'))
						return;
					if(e.eventData.exit === 'return')
						Devblocks.interactionWorkerPostActions(e.eventData, automation_editor);
				}
			},
			onAction: function(value, ed, item) {
				if(value === 'placeholders') { $fieldset_map_clicked.find('[data-cerb-event-placeholders]').toggle(!!(item && item.pressed)); return true; }
				if(value === 'tester')       { $fieldset_map_clicked.find('[data-cerb-event-tester]').toggle(!!(item && item.pressed)); return true; }
				return false;
			}
		}
	});

	// Tester panel ("Test": placeholders KataEditor + Run + results) — shared impl in editor-core.
	CerbUI.editorCore.attachEventHandlerTester($fieldset_map_clicked, automation_editor);
});
</script>
