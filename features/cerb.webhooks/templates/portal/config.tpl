{$form_id = uniqid()}
<form id="{$form_id}" action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="community_portal">
<input type="hidden" name="action" value="saveConfigTabJson">
<input type="hidden" name="portal_id" value="{$portal->id}">

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div class="cerb-ui-header--title-sm">Event: Respond to webhook (KATA)</div>
	</div>
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
"}

	{$toolbar = DevblocksPlatform::services()->ui()->toolbar()->parse($toolbar_kata, $toolbar_dict)}

	{* The editor toolbar is the KataEditor's integrated strip below; these hidden <ul>s are its host sections. *}
	<div data-cerb-interaction-toolbar hidden>{DevblocksPlatform::services()->ui()->toolbar()->render($toolbar)}</div>
	{include file="devblocks:cerberusweb.core::automations/triggers/editor_event_handler_toolbar.tpl"}

	<textarea name="params[automations_kata]" data-editor-lines="15" spellcheck="false">{$params.automations_kata}</textarea>

	{if $trigger_ext}
		{include file="devblocks:cerberusweb.core::automations/triggers/editor_event_handler.tpl" trigger_inputs=$trigger_ext->getEventPlaceholders()}
	{/if}
</div>

<div class="buttons cerb-u-mt-2">
	<button type="button" class="cerb-ui-button save"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
</div>
</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#{$form_id}');

	Devblocks.formDisableSubmit($frm);

	$frm.find('button.save').on('click', function(e) {
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

	// Editor — KataEditor with integrated event-handler toolbar (Automation + Placeholders/Test toggles)
	let automation_editor = new CerbUI.KataEditor($frm.find('textarea[name="params[automations_kata]"]')[0], {
		onAutocomplete: CerbUI.KataEditor.kataFieldSource(CerbUI.editorCore.autocompleteSchemas.kataAutomationEvent),
		toolbar: {
			sections: [
				$frm.find('[data-cerb-interaction-toolbar] ul.cerb-ui-toolbar')[0],
				$frm.find('[data-cerb-event-toolbar]')[0]
			],
			toolbarOpts: {
				caller: { name: 'cerb.toolbar.eventHandlers.editor', params: { selected_text: '' } },
				width: '75%',
				start: function(formData) {
					let pos = automation_editor.getCursorPosition();
					formData.set('caller[params][selected_text]', automation_editor.getSelectedText());
					formData.set('caller[params][token_path]', automation_editor.getTokenPath().join(''));
					formData.set('caller[params][cursor_row]', pos.row);
					formData.set('caller[params][cursor_column]', pos.column);
					formData.set('caller[params][trigger]', 'cerb.trigger.webhook.respond');
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
				if(value === 'placeholders') { $frm.find('[data-cerb-event-placeholders]').toggle(!!(item && item.pressed)); return true; }
				if(value === 'tester')       { $frm.find('[data-cerb-event-tester]').toggle(!!(item && item.pressed)); return true; }
				return false;
			}
		}
	});

	// Tester panel ("Test" → placeholders KataEditor + ▶ Run). The editor lives in a cerb-ui-panel, so the
	// legacy $.fn.cerbCodeEditorToolbarEventHandler (closest('fieldset')) no longer applies — this is the
	// shared replacement. Show-hide toggles are driven by the editor toolbar onAction above.
	CerbUI.editorCore.attachEventHandlerTester($frm, automation_editor);
});
</script>
