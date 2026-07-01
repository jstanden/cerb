{$peek_context = CerberusContexts::CONTEXT_WEBHOOK_LISTENER}
{$peek_context_id = $model->id}
{$form_id = uniqid()}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="webhook_listener">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-form">
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'common.name'|devblocks_translate|capitalize}</label>
			<input type="text" name="name" value="{$model->name}" autofocus="autofocus">
		</div>
	</div>
</div>

{if !empty($custom_fields)}
<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-form">
		{include file="devblocks:cerberusweb.core::internal/custom_fields/form.tpl" custom_fields=$custom_fields}
	</div>
</div>
{/if}

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div class="cerb-ui-header--title-sm">Event: Respond to webhook (KATA)</div>
	</div>
	{$toolbar_dict = DevblocksDictionaryDelegate::instance([
	'caller_name' => 'cerb.toolbar.eventHandlers.editor',

	'webhook__context' => $peek_context,
	'webhook_id' => $peek_context_id
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

	<textarea name="automations_kata" data-editor-lines="15" spellcheck="false">{$model->automations_kata}</textarea>

	{if $trigger_ext}
		{include file="devblocks:cerberusweb.core::automations/triggers/editor_event_handler.tpl" trigger_inputs=$trigger_ext->getEventPlaceholders()}
	{/if}
</div>

{if !empty($model->id)}
	{include file="devblocks:cerberusweb.core::internal/peek/delete_confirm.tpl" noun="webhook listener"}
{/if}

<div class="buttons" style="margin-top:10px;">
	<button type="button" class="cerb-ui-button save"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	{if !empty($model->id) && $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" class="cerb-ui-button cerb-ui-button--subtle delete-prompt"><span class="cerb-icons cerb-icon-trash"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>

</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#{$form_id}');
	let $popup = genericAjaxPopupFind($frm);

	Devblocks.formDisableSubmit($frm);
	
	$popup.one('popup_open', function() {
		$popup.dialog('option','title',"{'webhooks.common.webhook'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		$popup.css('overflow', 'inherit');

		// Buttons
		$popup.find('button.save').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);

		// Inline delete confirm (cerb-ui-panel--alert); the actual delete stays on button.delete above.
		if(window.CerbUI && CerbUI.Form)
			CerbUI.Form.ConfirmDelete($popup[0]);

		// Editor — KataEditor with integrated event-handler toolbar (Automation + Placeholders/Test toggles)
		let automation_editor = new CerbUI.KataEditor($popup.find('textarea[name=automations_kata]')[0], {
			onAutocomplete: CerbUI.KataEditor.kataFieldSource(CerbUI.editorCore.autocompleteSchemas.kataAutomationEvent),
			toolbar: {
				sections: [
					$popup.find('[data-cerb-interaction-toolbar] ul.cerb-ui-toolbar')[0],
					$popup.find('[data-cerb-event-toolbar]')[0]
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
					if(value === 'placeholders') { $popup.find('[data-cerb-event-placeholders]').toggle(!!(item && item.pressed)); return true; }
					if(value === 'tester')       { $popup.find('[data-cerb-event-tester]').toggle(!!(item && item.pressed)); return true; }
					return false;
				}
			}
		});

		// Tester panel ("Test" → placeholders KataEditor + ▶ Run). The editor lives in a cerb-ui-panel, so the
		// legacy $.fn.cerbCodeEditorToolbarEventHandler (closest('fieldset')) no longer applies — this is the
		// shared replacement. Show-hide toggles are driven by the editor toolbar onAction above.
		CerbUI.editorCore.attachEventHandlerTester($popup, automation_editor);
	});
});
</script>
