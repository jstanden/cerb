{$config_uid = uniqid('queueconfig')}
{$batch_size = $model->extension_params.batch_size|default:1}
{$automations_kata = $model->extension_params.automations_kata|default:''}

<fieldset class="peek" data-cerb-queue-automation-config id="{$config_uid}">
	<legend>{'common.automation'|devblocks_translate|capitalize}</legend>

	<table cellspacing="0" cellpadding="2" border="0" width="98%" style="margin-bottom:8px;">
		<tr>
			<td width="1%" nowrap="nowrap"><b>Batch size:</b></td>
			<td width="99%">
				<input type="number" name="extension_params[batch_size]" value="{$batch_size}" min="1" max="1000" style="width:6em;">
				<span class="cerb-helptext" style="margin-left:8px;">Maximum number of messages dispatched to the automation per invocation.</span>
			</td>
		</tr>
	</table>

	{$toolbar_dict = DevblocksDictionaryDelegate::instance([
		'caller_name' => 'cerb.toolbar.eventHandlers.editor',
		'worker__context' => CerberusContexts::CONTEXT_WORKER,
		'worker_id' => $active_worker->id
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

	<textarea name="extension_params[automations_kata]" data-editor-lines="15" spellcheck="false">{$automations_kata}</textarea>

	{if $trigger_ext}
		{include file="devblocks:cerberusweb.core::automations/triggers/editor_event_handler.tpl" trigger_inputs=$trigger_inputs}
	{/if}
</fieldset>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
(function() {
	const $fieldset = $('#{$config_uid}');

	// KataEditor with its integrated toolbar: the Automation interaction + Placeholders/Test toggles merge in as
	// host `sections`; interactions fire via `toolbarOpts`, the toggles route through `onAction`.
	const editor = new CerbUI.KataEditor($fieldset.find('textarea[name="extension_params[automations_kata]"]')[0], {
		onAutocomplete: CerbUI.KataEditor.kataFieldSource(CerbUI.editorCore.autocompleteSchemas.kataAutomationEvent),
		toolbar: {
			sections: [
				$fieldset.find('[data-cerb-interaction-toolbar] ul.cerb-ui-toolbar')[0],
				$fieldset.find('[data-cerb-event-toolbar]')[0]
			],
			toolbarOpts: {
				caller: { name: 'cerb.toolbar.eventHandlers.editor', params: { selected_text: '' } },
				width: '75%',
				start: function(formData) {
					const pos = editor.getCursorPosition();
					formData.set('caller[params][selected_text]', editor.getSelectedText());
					formData.set('caller[params][token_path]', editor.getTokenPath().join(''));
					formData.set('caller[params][cursor_row]', pos.row);
					formData.set('caller[params][cursor_column]', pos.column);
					formData.set('caller[params][trigger]', 'cerb.trigger.queue.consumer');
					formData.set('caller[params][value]', editor.getValue());
				},
				done: function(e) {
					e.stopPropagation();
					if(!e.trigger.is('.cerb-bot-trigger'))
						return;
					if(e.eventData.exit === 'return')
						Devblocks.interactionWorkerPostActions(e.eventData, editor);
				}
			},
			onAction: function(value, ed, item) {
				if(value === 'placeholders') { $fieldset.find('[data-cerb-event-placeholders]').toggle(!!(item && item.pressed)); return true; }
				if(value === 'tester')       { $fieldset.find('[data-cerb-event-tester]').toggle(!!(item && item.pressed)); return true; }
				return false;
			}
		}
	});

	CerbUI.editorCore.attachEventHandlerTester($fieldset, editor);
})();
</script>
