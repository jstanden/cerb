{$form_id = uniqid()}
<form id="{$form_id}" action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="community_portal">
<input type="hidden" name="action" value="saveConfigTabJson">
<input type="hidden" name="portal_id" value="{$portal->id}">

<fieldset class="peek">
	<legend>Event: Website Interaction (KATA)</legend>
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
        trigger: cerb.trigger.interaction.website
"}

		{$toolbar = DevblocksPlatform::services()->ui()->toolbar()->parse($toolbar_kata, $toolbar_dict)}

		{DevblocksPlatform::services()->ui()->toolbar()->render($toolbar)}

		<div class="cerb-code-editor-toolbar-divider"></div>

		{include file="devblocks:cerberusweb.core::automations/triggers/editor_event_handler_buttons.tpl"}
	</div>

	<textarea name="params[automations_kata]" data-editor-mode="ace/mode/cerb_kata">{$params.automations_kata}</textarea>

	{$trigger_ext = Extension_AutomationTrigger::get(AutomationTrigger_InteractionWebsite::ID, true)}
	{if $trigger_ext}
		{include file="devblocks:cerberusweb.core::automations/triggers/editor_event_handler.tpl" trigger_inputs=$trigger_ext->getEventPlaceholders()}
	{/if}
</fieldset>

<fieldset class="peek">
	<legend>Security: Cross-Origin Request Sharing (CORS)</legend>
	
	<div>
		<b>Only allow these origins to make requests:</b> (one per line)
	</div>
	
	<textarea name="params[cors_origins_allowed]" data-editor-mode="ace/mode/text">{$params.cors_origins_allowed}</textarea>
	
	<div style="margin:10px 0 0 15px;">
		<p>
			Enter origins like: <code>https://example.com</code>
		</p>
		<p>
			Use <code>*</code> (asterisk) to allow any origin.
		</p>
	</div>
</fieldset>	

<div class="status"></div>

<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#{$form_id}');
	var $status = $frm.find('div.status');
		
	$frm.find('button.submit').on('click', function(e) {
		genericAjaxPost($frm, '', null, function(json) {
			if(json && typeof json == 'object') {
				if(json.error) {
					Devblocks.showError($status, json.error);
				} else if (json.message) {
					Devblocks.showSuccess($status, json.message);
				} else {
					Devblocks.showSuccess($status, "Saved!");
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

	$frm.find('textarea[name="params[cors_origins_allowed]"]')
		.cerbCodeEditor()
	;

	// Toolbars
	var $toolbar = $frm.find('.cerb-code-editor-toolbar').cerbToolbar({
		caller: {
			name: 'cerb.toolbar.eventHandlers.editor',
			params: {
				trigger: 'cerb.trigger.interaction.website',
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
});
</script>