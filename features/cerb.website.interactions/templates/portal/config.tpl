{$form_id = uniqid()}
<form id="{$form_id}" action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="community_portal">
<input type="hidden" name="action" value="saveConfigTabJson">
<input type="hidden" name="portal_id" value="{$portal->id}">

<div class="cerb-ui-header">
	<div class="cerb-ui-header--title">Website Widget</div>
</div>

{* Static, syntax-highlighted snippet (not an editor) + a copy-to-clipboard button in the panel's --right. *}
<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight cerb-ui-header--center">
		<div class="cerb-ui-header--title-sm">Code Snippet</div>
		<div class="cerb-ui-header--right">
			<button type="button" class="cerb-ui-button cerb-ui-button--subtle" data-cerb-copy-embed><span class="cerb-icons cerb-icon-copy"></span> Copy</button>
		</div>
	</div>
	<p>Paste this above <code>&lt;/BODY&gt;</code> on your website:</p>
	<pre data-cerb-embed-code style="margin:0;white-space:pre-wrap;word-break:break-all;font-family:var(--cerb-font-stack-fixed);font-size:0.9em;line-height:1.6;"><span style="color:var(--cerb-editor-syntax-oper);">&lt;</span><span style="color:var(--cerb-editor-syntax-tag);">script</span> <span style="color:var(--cerb-editor-syntax-var);">id</span>=<span style="color:var(--cerb-editor-syntax-string);">"cerb-interactions"</span>
  <span style="color:var(--cerb-editor-syntax-var);">data-cerb-badge-interaction</span>=<span style="color:var(--cerb-editor-syntax-string);">"menu"</span>
  <span style="color:var(--cerb-editor-syntax-var);">type</span>=<span style="color:var(--cerb-editor-syntax-string);">"text/javascript"</span>
  <span style="color:var(--cerb-editor-syntax-var);">src</span>=<span style="color:var(--cerb-editor-syntax-string);">"{devblocks_url full=true}c=portal&a={if $portal->uri}{$portal->uri}{else}{$portal->code}{/if}&path=assets/cerb.js{/devblocks_url}"</span>
  <span style="color:var(--cerb-editor-syntax-var);">crossorigin</span>=<span style="color:var(--cerb-editor-syntax-string);">"anonymous"</span>
  <span style="color:var(--cerb-editor-syntax-var);">defer</span>
<span style="color:var(--cerb-editor-syntax-oper);">&gt;&lt;/</span><span style="color:var(--cerb-editor-syntax-tag);">script</span><span style="color:var(--cerb-editor-syntax-oper);">&gt;</span></pre>
</div>

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div class="cerb-ui-header--title-sm">Cross-Origin Request Sharing (CORS)</div>
	</div>

	<div class="cerb-ui-form">
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">Only allow these origins to make requests: <span class="cerb-ui-form--hint">one per line</span></label>
			<textarea name="params[cors_origins_allowed]" data-editor-lines="20" spellcheck="false">{$params.cors_origins_allowed}</textarea>
			<div class="cerb-ui-form--help">
				Enter origins like <code>https://example.com</code>. Leave blank to allow any origin.
			</div>
		</div>
	</div>
</div>

<div class="cerb-ui-header">
	<div class="cerb-ui-header--title">Interactions</div>
</div>

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div class="cerb-ui-header--title-sm">Event: Website Interaction (KATA)</div>
	</div>
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

<div class="cerb-ui-header">
	<div class="cerb-ui-header--title">Portal Website</div>
</div>

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div class="cerb-ui-header--title-sm">Schema (KATA)</div>
	</div>
	<textarea name="params[portal_kata]" data-editor-lines="15" spellcheck="false">{$params.portal_kata}</textarea>
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

	// Embed code — a static highlighted panel (no editor); the copy icon copies the raw snippet text.
	$frm.find('[data-cerb-copy-embed]').on('click', function(e) {
		e.preventDefault();
		let code = $frm.find('[data-cerb-embed-code]')[0];
		if(!code) return;
		let text = code.textContent;
		let done = function() { Devblocks.createAlert('Copied to clipboard.', 'success', 3000); };
		if(navigator.clipboard && navigator.clipboard.writeText)
			navigator.clipboard.writeText(text).then(done, function() {});
		else {
			let ta = document.createElement('textarea');
			ta.value = text;
			document.body.appendChild(ta);
			ta.select();
			try { document.execCommand('copy'); done(); } catch(err) {}
			ta.remove();
		}
	});

	// Website Interaction event — KataEditor with integrated event-handler toolbar (Automation + Placeholders/Test).
	let automation_editor = new CerbUI.KataEditor($frm.find('textarea[name="params[automations_kata]"]')[0], {
		onAutocomplete: CerbUI.KataEditor.kataFieldSource(CerbUI.editorCore.autocompleteSchemas.kataAutomationEvent, {
			autocomplete_type_defaults: {
				'cerb-uri': {
					'automation': {
						'triggers': [
							'cerb.trigger.interaction.website'
						]
					}
				}
			}
		}),
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
					formData.set('caller[params][trigger]', 'cerb.trigger.interaction.website');
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

	// CORS origins — a plain ScriptingEditor (one origin per line; no autocomplete, no Twig in the content).
	let corsEl = $frm.find('textarea[name="params[cors_origins_allowed]"]')[0];
	if(corsEl && window.CerbUI && CerbUI.ScriptingEditor)
		new CerbUI.ScriptingEditor(corsEl);

	// Portal schema — a plain KataEditor (no event toolbar) with the website-portal schema autocomplete.
	new CerbUI.KataEditor($frm.find('textarea[name="params[portal_kata]"]')[0], {
		onAutocomplete: CerbUI.KataEditor.kataFieldSource({CerberusApplication::kataAutocompletions()->portalInteractionWebsite()|json_encode nofilter})
	});
});
</script>
