{$form_id = uniqid()}
<form id="{$form_id}" action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="community_portal">
<input type="hidden" name="action" value="saveConfigTabJson">
<input type="hidden" name="portal_id" value="{$portal->id}">

<fieldset>
	<legend>Event: Respond to webhook (KATA)</legend>
	<div class="cerb-code-editor-toolbar">
		{$toolbar_dict = DevblocksDictionaryDelegate::instance([
		'caller_name' => 'cerb.toolbar.eventHandlers.editor',

		'portal__context' => CerberusContexts::CONTEXT_PORTAL,
		'portal_id' => $portal->id,
			
		'webhook__context' => CerberusContexts::CONTEXT_WEBHOOK_LISTENER,
		'webhook_id' => 0
		])}

		{$toolbar_kata =
"interaction/automation:
  uri: ai.cerb.eventHandler.automation
  icon: circle-plus
  tooltip: Automation
  inputs:
    trigger: cerb.trigger.webhook.respond
"}

		{$toolbar = DevblocksPlatform::services()->ui()->toolbar()->parse($toolbar_kata, $toolbar_dict)}

		{DevblocksPlatform::services()->ui()->toolbar()->render($toolbar)}

		<div class="cerb-code-editor-toolbar-divider"></div>
		{include file="devblocks:cerberusweb.core::automations/triggers/editor_event_handler_buttons.tpl"}
	</div>

	<textarea name="params[automations_kata]" data-editor-mode="ace/mode/cerb_kata">{$params.automations_kata}</textarea>

	{$trigger_ext = Extension_AutomationTrigger::get(AutomationTrigger_WebhookRespond::ID, true)}
	{if $trigger_ext}
		{include file="devblocks:cerberusweb.core::automations/triggers/editor_event_handler.tpl" trigger_inputs=$trigger_ext->getEventPlaceholders()}
	{/if}
</fieldset>

<button type="button" class="submit" style="margin-top:10px;"><span class="glyphicons glyphicons-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#{$form_id}');
	
	$frm.find('button.submit').on('click', function(e) {
		genericAjaxPost($frm, '', null, function(json) {
			Devblocks.clearAlerts();
			if(json && typeof json == 'object') {
				if(json.error) {
					Devblocks.createAlertError(json.error);
				} else if (json.message) {
					Devblocks.createAlert(json.message, 'success', 5000);
				} else {
					Devblocks.createAlert('Saved!', 'success', 5000);
				}
			}
		});
	});

	// Editors
	var $automation_editor = $frm.find('textarea[name="params[automations_kata]"]')
		.cerbCodeEditor()
		.cerbCodeEditorAutocompleteKata({
			autocomplete_suggestions: cerbAutocompleteSuggestions.kataAutomationEvent
		})
		.nextAll('pre.ace_editor')
	;

	var automation_editor = ace.edit($automation_editor.attr('id'));

	// Toolbars
	var $toolbar = $frm.find('.cerb-code-editor-toolbar').cerbToolbar({
		caller: {
			name: 'cerb.toolbar.eventHandlers.editor',
			params: {
				selected_text: ''
			}
		},
		width: '75%',
		start: function(formData) {
			var pos = automation_editor.getCursorPosition();
			var token_path = Devblocks.cerbCodeEditor.getKataTokenPath(pos, automation_editor).join('');

			formData.set('caller[params][selected_text]', automation_editor.getSelectedText());
			formData.set('caller[params][token_path]', token_path);
			formData.set('caller[params][cursor_row]', pos.row);
			formData.set('caller[params][cursor_column]', pos.column);
			formData.set('caller[params][trigger]', 'cerb.trigger.webhook.respond');
			formData.set('caller[params][value]', automation_editor.getValue());
		},
		done: function(e) {
			e.stopPropagation();

			var $target = e.trigger;

			if(!$target.is('.cerb-bot-trigger'))
				return;

			if(!e.eventData || !e.eventData.exit)
				return;

			if (e.eventData.exit === 'error') {
				// [TODO] Show error

			} else if(e.eventData.exit === 'return' && e.eventData.return.snippet) {
				automation_editor.insertSnippet(e.eventData.return.snippet);
			}
		}
	});

	$toolbar.cerbCodeEditorToolbarEventHandler({
		editor: automation_editor
	});	
});
</script>