{$peek_context = CerberusContexts::CONTEXT_WEBHOOK_LISTENER}
{$peek_context_id = $model->id}
{$form_id = uniqid()}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}" onsubmit="return false;">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="webhook_listener">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<table cellspacing="0" cellpadding="2" border="0" width="98%" style="margin-bottom:10px;">
	<tr>
		<td width="1%" nowrap="nowrap"><b>{'common.name'|devblocks_translate}:</b></td>
		<td width="99%">
			<input type="text" name="name" value="{$model->name}" style="width:98%;" autofocus="autofocus">
		</td>
	</tr>
	
	{if !empty($custom_fields)}
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false tbody=true}
	{/if}
</table>

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

<fieldset>
	<legend>Event: Respond to webhook (KATA)</legend>
	<div class="cerb-code-editor-toolbar">
		{$toolbar_dict = DevblocksDictionaryDelegate::instance([
		'caller_name' => 'cerb.toolbar.eventHandlers.editor',
		
		'webhook__context' => $peek_context,
		'webhook_id' => $peek_context_id
		])}

		{$toolbar_kata =
"menu/add:
  icon: circle-plus
  items:
    interaction/automation:
      label: Automation
      uri: ai.cerb.eventHandler.automation
      inputs:
        trigger: cerb.trigger.webhook.respond
"}

		{$toolbar = DevblocksPlatform::services()->ui()->toolbar()->parse($toolbar_kata, $toolbar_dict)}

		{DevblocksPlatform::services()->ui()->toolbar()->render($toolbar)}

		<div class="cerb-code-editor-toolbar-divider"></div>
		{include file="devblocks:cerberusweb.core::automations/triggers/editor_event_handler_buttons.tpl"}
	</div>
	
	<textarea name="automations_kata" data-editor-mode="ace/mode/cerb_kata">{$model->automations_kata}</textarea>

	{$trigger_ext = Extension_AutomationTrigger::get(AutomationTrigger_WebhookRespond::ID, true)}
	{if $trigger_ext}
		{include file="devblocks:cerberusweb.core::automations/triggers/editor_event_handler.tpl" trigger_inputs=$trigger_ext->getEventPlaceholders()}
	{/if}
</fieldset>

{if !empty($model->id)}
<fieldset style="display:none;" class="delete">
	<legend>{'common.delete'|devblocks_translate|capitalize}</legend>
	
	<div>
		Are you sure you want to permanently delete this webhook listener?
	</div>
	
	<button type="button" class="delete red">{'common.yes'|devblocks_translate|capitalize}</button>
	<button type="button" onclick="$(this).closest('form').find('div.buttons').fadeIn();$(this).closest('fieldset.delete').fadeOut();">{'common.no'|devblocks_translate|capitalize}</button>
</fieldset>
{/if}

<div class="status"></div>

<div class="buttons">
	<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	{if !empty($model->id) && $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" onclick="$(this).parent().siblings('fieldset.delete').fadeIn();$(this).closest('div').fadeOut();"><span class="glyphicons glyphicons-circle-remove" style="color:rgb(200,0,0);"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>

</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#{$form_id}');
	var $popup = genericAjaxPopupFind($frm);
	
	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"{'webhooks.common.webhook'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		$popup.css('overflow', 'inherit');

		// Buttons
		$popup.find('button.submit').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);

		// Editors
		var $automation_editor = $popup.find('textarea[name=automations_kata]')
			.cerbCodeEditor()
			.cerbCodeEditorAutocompleteKata({
				autocomplete_suggestions: cerbAutocompleteSuggestions.kataAutomationEvent
			})
			.nextAll('pre.ace_editor')
		;

		var automation_editor = ace.edit($automation_editor.attr('id'));

		// Toolbars
		var $toolbar = $popup.find('.cerb-code-editor-toolbar').cerbToolbar({
			caller: {
				name: 'cerb.toolbar.eventHandlers.editor',
				params: {
					trigger: 'cerb.trigger.webhook.respond',
					selected_text: ''
				}
			},
			start: function(formData) {
				formData.set('caller[params][selected_text]', automation_editor.getSelectedText())
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

		// Webhook listener engine fieldsets
		
		var $fieldsets = $popup.find('fieldset');
		
		$popup.find('fieldset legend input:radio').on('click', function() {
			$fieldsets.find('> div').hide();
			$(this).closest('fieldset').find('> div').fadeIn();
		});
		
	});
});
</script>
