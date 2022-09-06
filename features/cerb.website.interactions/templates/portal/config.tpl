{$form_id = uniqid()}
<form id="{$form_id}" action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="community_portal">
<input type="hidden" name="action" value="saveConfigTabJson">
<input type="hidden" name="portal_id" value="{$portal->id}">

<h1>Website Widget</h1>

<fieldset class="peek">
	<legend>Code Snippet</legend>
	
	<b>Paste this code fragment above <code>&lt;/BODY&gt;</code> on your website:</b>
	
	<textarea data-editor-mode="ace/mode/html" data-editor-line-numbers="false" data-editor-readonly="true">&lt;script id="cerb-interactions" 
  data-cerb-badge-interaction="menu" 
  type="text/javascript" 
  src="{devblocks_url full=true}c=portal&a={if $portal->uri}{$portal->uri}{else}{$portal->code}{/if}&path=assets/cerb.js{/devblocks_url}" 
  crossorigin="anonymous" 
  defer
&gt;&lt;/script&gt;</textarea>
</fieldset>
	
<fieldset class="peek">
	<legend>Cross-Origin Request Sharing (CORS)</legend>
	
	<div>
		<b>Only allow these origins to make requests:</b> (one per line)
	</div>
	
	<textarea name="params[cors_origins_allowed]" data-editor-mode="ace/mode/text">{$params.cors_origins_allowed}</textarea>
	
	<div style="margin:10px 0 0 15px;">
		<p>
			Enter origins like: <code>https://example.com</code>
		</p>
		<p>
			Leave blank to allow any origin.
		</p>
	</div>
</fieldset>

<h1>Interactions</h1>
	
<fieldset class="peek">
	<legend>Event: Website Interaction (KATA)</legend>
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
  tooltip: Add automation
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

<h1>Portal Website</h1>
	
<fieldset class="peek">
	<legend>Schema (KATA)</legend>
	<textarea name="params[portal_kata]" data-editor-mode="ace/mode/cerb_kata">{$params.portal_kata}</textarea>
</fieldset>

<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#{$form_id}');
		
	$frm.find('button.submit').on('click', function(e) {
		Devblocks.clearAlerts();
		genericAjaxPost($frm, '', null, function(json) {
			if(json && typeof json == 'object') {
				if(json.error) {
					Devblocks.createAlertError(json.message);
				} else if (json.message) {
					Devblocks.createAlert(json.message, 'success', 5000);
				} else {
					Devblocks.createAlert('Saved!', 'success', 5000);
				}
			}
		});
	});

	// Editors
	
	$frm.find('textarea[data-editor-mode="ace/mode/html"]')
		.cerbCodeEditor()
	;
	
	var $automation_editor = $frm.find('textarea[name="params[automations_kata]"]')
		.cerbCodeEditor()
		.cerbCodeEditorAutocompleteKata({
			autocomplete_suggestions: cerbAutocompleteSuggestions.kataAutomationEvent,
			autocomplete_type_defaults: {
				'cerb-uri': {
					'automation': {
						'triggers': [
							'cerb.trigger.interaction.website'
						]
					}
				}
			}
		})
		.nextAll('pre.ace_editor')
	;

	var automation_editor = ace.edit($automation_editor.attr('id'));

	$frm.find('textarea[name="params[cors_origins_allowed]"]')
		.cerbCodeEditor()
	;
	
	var $portal_editor = $frm.find('textarea[name="params[portal_kata]"]')
		.cerbCodeEditor()
		.cerbCodeEditorAutocompleteKata({
			autocomplete_suggestions: {CerberusApplication::kataAutocompletions()->portalInteractionWebsite()|json_encode nofilter}
		})
		.nextAll('pre.ace_editor')
	;

	ace.edit($portal_editor.attr('id'));

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
			formData.set('caller[params][trigger]', 'cerb.trigger.interaction.website');
			formData.set('caller[params][value]', automation_editor.getValue());
		},
		done: function(e) {
			e.stopPropagation();

			var $target = e.trigger;

			if(!$target.is('.cerb-bot-trigger'))
				return;

			if (e.eventData.exit === 'error') {

			} else if(e.eventData.exit === 'return') {
				Devblocks.interactionWorkerPostActions(e.eventData, automation_editor);
			}
		}
	});

	$toolbar.cerbCodeEditorToolbarEventHandler({
		editor: automation_editor
	});
});
</script>