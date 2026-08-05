{$uniqid = uniqid('botScriptingTester')}
<div id="{$uniqid}">
<div class="cerb-ui-header">
	<div>
		<div class="cerb-ui-header--title">Automation Scripting Tester</div>
		<div class="cerb-ui-header--subtitle"></div>
	</div>
</div>

<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmSetupBotScriptingTester">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="bot_scripting_tester">
<input type="hidden" name="action" value="runScript">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<fieldset>
	<legend>
		Run this script:
		{include file="devblocks:cerberusweb.core::help/docs_button.tpl" url="https://cerb.ai/docs/scripting/"}
	</legend>

	<textarea id="botScriptEditor" name="bot_script" data-editor-lines="12" spellcheck="false"></textarea>
	<br>
	
	<button type="button" class="submit"><span class="cerb-icons cerb-icon-play"></span> {'common.run'|devblocks_translate|capitalize}</button>
</fieldset>

<div class="status" style="margin-top:10px;"></div>
</form>
</div>{* #mount — AgentPane wraps this *}

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var $frm = $('#frmSetupBotScriptingTester');
	var $status = $frm.find('div.status');
	var $button = $frm.find('BUTTON.submit');
	var $spinner = Devblocks.getSpinner();

	Devblocks.formDisableSubmit($frm);
	
	// diffGutter marks lines changed since the last run (baseline captured on open, re-captured on each run below).
	const scriptEditor = new CerbUI.ScriptingEditor($frm.find('#botScriptEditor')[0], { minLines: 5, diffGutter: true });

	$button
		.click(function(e) {
			Devblocks.clearAlerts();

			// Re-baseline the diff to what we're about to run — the gutter then marks edits made since this run.
			scriptEditor.resetDiffBaseline();

			$button.hide();
			$spinner.detach();
			$status.html('').append($spinner);
			
			genericAjaxPost('frmSetupBotScriptingTester','',null,function(json) {
				$button.fadeIn();
				$status.html('');
				
				if(null == json || false == json.status) {
					Devblocks.createAlertError(json.error);
					
				} else if (json.html) {
					$status.html(json.html);
					
				} else {
					Devblocks.createAlertError("An unknown error occurred.");
				}
			});
		})
	;

	// The collapsible agent chat sidebar. AgentPane wraps this page's content in an outer split and hosts the
	// agent.pane toolbar as "New Agent Chat" tiles; each launches an interaction inline, carrying a command
	// bridge into the live scripting editor (getEditorValue / setEditorValue) so you can teach it Twig/KataScript.
	// No toolbar authored → the pane hides its toggle.
	const agentToolbarHtml = {$agent_toolbar_html_json|default:'""' nofilter};
	new CerbUI.AgentPane(document.getElementById('{$uniqid}'), {
		component: 'bot_scripting',
		capabilities: 'getEditorValue,setEditorValue,editField,grepField,highlightLine,getDiff',
		mutatingCommands: 'setEditorValue,editField',   // write the editor → guard against accidental navigation loss
		toolbarHtml: agentToolbarHtml,
		storageKey: 'cerb-bot-scripting-tester-agent-chat',
		runCommand: function(name, params) {
			params = params || {};
			switch(name) {
				case 'getEditorValue': return scriptEditor.getValue();
				case 'setEditorValue': scriptEditor.setValue(params.value || ''); return 'ok';
				// Surgical single-editor edits (setEditorValue stays for a wholesale replace/reset). editField =
				// undo-safe exactly-once search/replace; grepField locates a query → line numbers (no KATA path here).
				case 'editField': return CerbUI.editorCore.applyUniqueEdit(scriptEditor, (params.old == null) ? '' : String(params.old), (params.new == null) ? '' : String(params.new));
				case 'grepField': {
					const query = (params.query == null) ? '' : String(params.query);
					if(query === '') return 'error: grepField needs a non-empty query.';
					return CerbUI.editorCore.grepEditor(scriptEditor, query, parseInt(params.limit, 10));
				}
				// Jump to + flash a 1-based line (pairs with grepField's line numbers for "where is X" nav).
				case 'highlightLine': {
					const line = parseInt(params.line, 10);
					if(isNaN(line) || line < 1) return 'invalid line: ' + params.line;
					const row = line - 1;
					scriptEditor.gotoLine(line);
					scriptEditor.flashLine(row, {});
					return 'ok';
				}
				// Read the unsaved diff vs the last run (like the automation editor's getDiff): 1-based line spans +
				// the added/removed text of each change hunk. `tracked` is false when nothing has been run yet.
				case 'getDiff': {
					const st = scriptEditor.getDiffState();
					return JSON.stringify({
						tracked: st.baseline != null,
						hunks: st.hunks.map(function(h) {
							return { status: h.status, line: h.rowStart + 1, endLine: h.rowEnd, added: h.added, removed: h.removed };
						})
					});
				}
			}
			return '';
		},
	});
});
</script>
