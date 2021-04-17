{$peek_context = CerberusContexts::CONTEXT_REMINDER}
{$peek_context_id = $model->id}
{$form_id = uniqid()}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}" onsubmit="return false;">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="reminder">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<table cellspacing="0" cellpadding="2" border="0" width="98%" style="margin-bottom:10px;">
	<tr>
		<td width="1%" nowrap="nowrap"><b>{'common.reminder'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			<input type="text" name="name" value="{$model->name}" style="width:98%;" autofocus="autofocus">
		</td>
	</tr>
	<tr>
		<td width="1%" nowrap="nowrap"><b>{'common.when'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			<input type="text" name="remind_at" value="{$model->remind_at|devblocks_date}" style="width:98%;">
		</td>
	</tr>
	<tr>
		<td width="1%" nowrap="nowrap"><b>{'common.for'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			<button type="button" class="chooser-abstract" data-field-name="worker_id" data-context="{CerberusContexts::CONTEXT_WORKER}" data-single="true" data-query="isDisabled:n" data-autocomplete="isDisabled:n" data-autocomplete-if-empty="true"><span class="glyphicons glyphicons-search"></span></button>
			
			<ul class="bubbles chooser-container">
				{if $model}
					{$worker = $model->getWorker()}
					{if $worker}
						<li><img class="cerb-avatar" src="{devblocks_url}c=avatars&context=worker&context_id={$worker->id}{/devblocks_url}?v={$worker->updated}"><input type="hidden" name="worker_id" value="{$worker->id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_WORKER}" data-context-id="{$worker->id}">{$worker->getName()}</a></li>
					{/if}
				{/if}
			</ul>
		</td>
	</tr>
	
	{if !empty($custom_fields)}
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false tbody=true}
	{/if}
</table>

<fieldset>
	<legend>Event: Remind (KATA)</legend>
	<div class="cerb-code-editor-toolbar">
		{$toolbar_dict = DevblocksDictionaryDelegate::instance([
			'caller_name' => 'cerb.toolbar.eventHandlers.editor',
			
			'worker__context' => CerberusContexts::CONTEXT_WORKER,
			'worker_id' => $active_worker->id
		])}

		{$toolbar_kata =
"menu/add:
  icon: circle-plus
  items:
    interaction/automation:
      label: Automation
      uri: ai.cerb.eventHandler.automation
      inputs:
        trigger: cerb.trigger.reminder.remind
"}

		{$toolbar = DevblocksPlatform::services()->ui()->toolbar()->parse($toolbar_kata, $toolbar_dict)}

		{DevblocksPlatform::services()->ui()->toolbar()->render($toolbar)}

		<div class="cerb-code-editor-toolbar-divider"></div>
		{include file="devblocks:cerberusweb.core::automations/triggers/editor_event_handler_buttons.tpl"}
	</div>

	<textarea name="automations_kata" data-editor-mode="ace/mode/cerb_kata">{$model->automations_kata}</textarea>

	{$trigger_ext = Extension_AutomationTrigger::get(AutomationTrigger_ReminderRemind::ID, true)}
	{if $trigger_ext}
		{include file="devblocks:cerberusweb.core::automations/triggers/editor_event_handler.tpl" trigger_inputs=$trigger_ext->getInputsMeta()}
	{/if}
</fieldset>

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

{if !empty($model->id)}
<fieldset style="display:none;" class="delete">
	<legend>{'common.delete'|devblocks_translate|capitalize}</legend>
	
	<div>
		Are you sure you want to permanently delete this reminder?
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
	
	$popup.one('popup_open', function() {
		$popup.dialog('option','title',"{'Reminder'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
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
					trigger: 'cerb.trigger.reminder.remind',
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

		// Helpers
		
		$popup.find('input[name=remind_at]')
			.cerbDateInputHelper()
		;
		
		$popup.find('.cerb-peek-trigger')
			.cerbPeekTrigger()
		;
		
		$popup.find('.chooser-abstract')
			.cerbChooserTrigger()
		;
		
	});
});
</script>
