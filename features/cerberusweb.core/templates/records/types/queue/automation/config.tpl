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

	<div class="cerb-code-editor-toolbar">
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

		{DevblocksPlatform::services()->ui()->toolbar()->render($toolbar)}

		<div class="cerb-code-editor-toolbar-divider"></div>

		{include file="devblocks:cerberusweb.core::automations/triggers/editor_event_handler_buttons.tpl"}
	</div>

	<textarea name="extension_params[automations_kata]" data-editor-mode="ace/mode/cerb_kata">{$automations_kata}</textarea>

	{if $trigger_ext}
		{include file="devblocks:cerberusweb.core::automations/triggers/editor_event_handler.tpl" trigger_inputs=$trigger_inputs}
	{/if}
</fieldset>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
(function() {
	const $fieldset = $('#{$config_uid}');

	const $editor = $fieldset.find('textarea[name="extension_params[automations_kata]"]')
		.cerbCodeEditor()
		.cerbCodeEditorAutocompleteKata({
			autocomplete_suggestions: cerbAutocompleteSuggestions.kataAutomationEvent
		})
		.nextAll('pre.ace_editor')
	;

	const editor = ace.edit($editor.attr('id'));

	const $toolbar = $fieldset.find('.cerb-code-editor-toolbar').cerbToolbar({
		caller: {
			name: 'cerb.toolbar.eventHandlers.editor',
			params: {
				selected_text: ''
			}
		},
		width: '75%',
		start: function(formData) {
			const pos = editor.getCursorPosition();
			const token_path = Devblocks.cerbCodeEditor.getKataTokenPath(pos, editor).join('');

			formData.set('caller[params][selected_text]', editor.getSelectedText());
			formData.set('caller[params][token_path]', token_path);
			formData.set('caller[params][cursor_row]', pos.row);
			formData.set('caller[params][cursor_column]', pos.column);
			formData.set('caller[params][trigger]', 'cerb.trigger.queue.consumer');
			formData.set('caller[params][value]', editor.getValue());
		},
		done: function(e) {
			e.stopPropagation();

			const $target = e.trigger;

			if(!$target.is('.cerb-bot-trigger'))
				return;

			if(e.eventData.exit === 'return') {
				Devblocks.interactionWorkerPostActions(e.eventData, editor);
			}
		}
	});

	$toolbar.cerbCodeEditorToolbarEventHandler({
		editor: editor
	});
})();
</script>
