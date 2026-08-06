<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">Event: Run automation function<span class="cerb-ui-form--hint">KATA</span></label>
	<div data-cerb-editor-functions>
	{$toolbar_dict = DevblocksDictionaryDelegate::instance([
	'caller_name' => 'cerb.toolbar.eventHandlers.editor',

	'behavior__context' => CerberusContexts::CONTEXT_BEHAVIOR,
	'behavior_id' => $behavior->id
	])}

	{$toolbar_kata =
"interaction/automation:
  uri: ai.cerb.eventHandler.automation
  tooltip: Automation
  icon: circle-plus
"}

	{$toolbar = DevblocksPlatform::services()->ui()->toolbar()->parse($toolbar_kata, $toolbar_dict)}

	{* The editor toolbar is the KataEditor's integrated strip below; these hidden <ul>s are its host sections. *}
	<div data-cerb-interaction-toolbar hidden>{DevblocksPlatform::services()->ui()->toolbar()->render($toolbar)}</div>
	{include file="devblocks:cerberusweb.core::automations/triggers/editor_event_handler_toolbar.tpl"}

	<textarea name="{$namePrefix}[automations_kata]" data-editor-lines="15" spellcheck="false">{$params.automations_kata}</textarea>

	{if $trigger_ext}
		{include file="devblocks:cerberusweb.core::automations/triggers/editor_event_handler.tpl" trigger_inputs=$trigger_ext->getEventPlaceholders()}
	{/if}
	</div>
</div>

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">Run automations in simulator mode</label>
	<div>
		<label><input type="radio" name="{$namePrefix}[run_in_simulator]" value="1" {if $params.run_in_simulator}checked="checked"{/if}> {'common.yes'|devblocks_translate|capitalize}</label>
		<label><input type="radio" name="{$namePrefix}[run_in_simulator]" value="0" {if !$params.run_in_simulator}checked="checked"{/if}> {'common.no'|devblocks_translate|capitalize}</label>
	</div>
</div>

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">Save result to a placeholder named</label>
	<div>
		&#123;&#123;<input type="text" name="{$namePrefix}[object_placeholder]" value="{$params.object_placeholder|default:"_results"}" required="required" spellcheck="false" size="32" placeholder="e.g. _results">&#125;&#125;
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
var $action = $('#{$namePrefix}_{$nonce}');

// Cards

var $functions = $action.find('[data-cerb-editor-functions]');

// KataEditor with integrated event-handler toolbar (Automation + Placeholders/Test toggles)
var editor_functions = new CerbUI.KataEditor($functions.find('textarea[name="{$namePrefix}[automations_kata]"]')[0], {
	onAutocomplete: CerbUI.KataEditor.kataFieldSource(CerbUI.editorCore.autocompleteSchemas.kataAutomationEvent),
	toolbar: {
		sections: [
			$functions.find('[data-cerb-interaction-toolbar] ul.cerb-ui-toolbar')[0],
			$functions.find('[data-cerb-event-toolbar]')[0]
		],
		toolbarOpts: {
			caller: { name: 'cerb.toolbar.eventHandlers.editor', params: { selected_text: '' } },
			width: '75%',
			start: function(formData) {
				var pos = editor_functions.getCursorPosition();
				formData.set('caller[params][selected_text]', editor_functions.getSelectedText());
				formData.set('caller[params][token_path]', editor_functions.getTokenPath().join(''));
				formData.set('caller[params][cursor_row]', pos.row);
				formData.set('caller[params][cursor_column]', pos.column);
				formData.set('caller[params][trigger]', 'cerb.trigger.behavior.action');
				formData.set('caller[params][value]', editor_functions.getValue());
			},
			done: function(e) {
				e.stopPropagation();
				if(!e.trigger.is('.cerb-bot-trigger'))
					return;
				if(e.eventData.exit === 'return')
					Devblocks.interactionWorkerPostActions(e.eventData, editor_functions);
			}
		},
		onAction: function(value, ed, item) {
			if(value === 'placeholders') { $functions.find('[data-cerb-event-placeholders]').toggle(!!(item && item.pressed)); return true; }
			if(value === 'tester')       { $functions.find('[data-cerb-event-tester]').toggle(!!(item && item.pressed)); return true; }
			return false;
		}
	}
});

CerbUI.editorCore.attachEventHandlerTester($functions, editor_functions);
</script>
