<b>Event: Run automation function (KATA)</b>
<div data-cerb-editor-functions style="margin-left:10px;margin-bottom:10px;">
	<div class="cerb-code-editor-toolbar">
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

		{DevblocksPlatform::services()->ui()->toolbar()->render($toolbar)}

		<div class="cerb-code-editor-toolbar-divider"></div>

		{include file="devblocks:cerberusweb.core::automations/triggers/editor_event_handler_buttons.tpl"}
	</div>
	
	<textarea name="{$namePrefix}[automations_kata]" data-editor-mode="ace/mode/cerb_kata" class="placeholders">{$params.automations_kata}</textarea>

	{if $trigger_ext}
		{include file="devblocks:cerberusweb.core::automations/triggers/editor_event_handler.tpl" trigger_inputs=$trigger_ext->getEventPlaceholders()}
	{/if}
</div>

<b>Run automations in simulator mode:</b>
<div style="margin-left:10px;margin-bottom:10px;">
	<label><input type="radio" name="{$namePrefix}[run_in_simulator]" value="1" {if $params.run_in_simulator}checked="checked"{/if}> {'common.yes'|devblocks_translate|capitalize}</label>
	<label><input type="radio" name="{$namePrefix}[run_in_simulator]" value="0" {if !$params.run_in_simulator}checked="checked"{/if}> {'common.no'|devblocks_translate|capitalize}</label>
</div>

<b>Save result to a placeholder named:</b>
<div style="margin-left:10px;margin-bottom:10px;">
	&#123;&#123;<input type="text" name="{$namePrefix}[object_placeholder]" value="{$params.object_placeholder|default:"_results"}" required="required" spellcheck="false" size="32" placeholder="e.g. _results">&#125;&#125;
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
var $action = $('#{$namePrefix}_{$nonce}');

// Cards

var $functions = $action.find('[data-cerb-editor-functions]');

var $editor_functions = $functions.find('textarea[name="{$namePrefix}[automations_kata]"]')
	.cerbCodeEditor()
	.cerbCodeEditorAutocompleteKata({
		autocomplete_suggestions: cerbAutocompleteSuggestions.kataAutomationEvent
	})
	.nextAll('pre.ace_editor')
;

var editor_functions = ace.edit($editor_functions.attr('id'));

var $toolbar_functions = $functions.find('.cerb-code-editor-toolbar').cerbToolbar({
	caller: {
		name: 'cerb.toolbar.eventHandlers.editor',
		params: {
			selected_text: ''
		}
	},
	width: '75%',
	start: function(formData) {
		var pos = editor_functions.getCursorPosition();
		var token_path = Devblocks.cerbCodeEditor.getKataTokenPath(pos, editor_functions).join('');

		formData.set('caller[params][selected_text]', editor_functions.getSelectedText());
		formData.set('caller[params][token_path]', token_path);
		formData.set('caller[params][cursor_row]', pos.row);
		formData.set('caller[params][cursor_column]', pos.column);
		formData.set('caller[params][trigger]', 'cerb.trigger.behavior.action');
		formData.set('caller[params][value]', editor_functions.getValue());
	},
	done: function(e) {
		e.stopPropagation();

		var $target = e.trigger;

		if(!$target.is('.cerb-bot-trigger'))
			return;

		if (e.eventData.exit === 'error') {

		} else if(e.eventData.exit === 'return') {
			Devblocks.interactionWorkerPostActions(e.eventData, editor_functions);
		}
	}
});

$toolbar_functions.cerbCodeEditorToolbarEventHandler({
	editor: editor_functions
});
</script>
