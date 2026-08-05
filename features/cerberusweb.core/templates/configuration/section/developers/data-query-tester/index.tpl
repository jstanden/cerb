{$uniqid = uniqid('dataQueryBuilder')}
<div id="{$uniqid}">
<div class="cerb-ui-header">
	<div>
		<div class="cerb-ui-header--title">Data Query Tester</div>
		<div class="cerb-ui-header--subtitle"></div>
	</div>
</div>

<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmSetupDataQueryTester">
<fieldset>
	<legend>
		Run this data query:
		{include file="devblocks:cerberusweb.core::help/docs_button.tpl" url="https://cerb.ai/docs/data-queries/"}
	</legend>
	
	<textarea id="dataQueryEditor" name="data_query" data-editor-lines="14" spellcheck="false"></textarea>
	<br>
	
	<button type="button" class="submit"><span class="cerb-icons cerb-icon-play"></span> {'common.run'|devblocks_translate|capitalize}</button>
	
	<div class="status" style="margin-top:10px;display:none;">
		<textarea id="dataQueryResultsEditor" spellcheck="false"></textarea>
	</div>
</fieldset>
</form>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var $frm = $('#frmSetupDataQueryTester');
	var $status = $frm.find('div.status');
	var $button = $frm.find('BUTTON.submit');
	var $spinner = Devblocks.getSpinner();

	Devblocks.formDisableSubmit($frm);
	
	const resultsEditor = new CerbUI.JsonEditor($frm.find('#dataQueryResultsEditor')[0], { readOnly: true, minLines: 5 });

	const dq = new CerbUI.DataQuery($frm.find('#dataQueryEditor')[0], {
		onAutocomplete: CerbUI.DataQuery.dataQueryFieldSource(),
		toolbar: true, // built-in Suggestions button (top strip)
	});

	$button
		.click(function(e) {
			e.stopPropagation();

			Devblocks.clearAlerts();

			$button.hide();
			$status.hide();
			$spinner.insertBefore($status);
			resultsEditor.setValue('');
			
			var formData = new FormData();
			formData.set('c', 'ui');
			formData.set('a', 'dataQuery');
			formData.set('q', dq.getValue());

			genericAjaxPost(formData, null, null, function(json) {
				$button.fadeIn();
				$spinner.detach();

				if(null == json || false == json.status) {
					Devblocks.createAlertError(json.error);

				} else {
					// Reveal the results panel BEFORE setValue so the editor autosizes against a laid-out
					// element — a display:none textarea reports scrollHeight 0 and would clamp to minLines.
					$status.show();
					resultsEditor.setValue(JSON.stringify(json, null, 2));
				}
			});
		})
	;

	// The collapsible agent chat sidebar. AgentPane wraps this page's content in an outer split and hosts the
	// agent.pane toolbar as "New Agent Chat" tiles; each launches an interaction inline, carrying a command
	// bridge into the live query editor (getEditorValue / setEditorValue). The interaction runs / syntax-tests
	// queries server-side (non-DML) via its own tools. No toolbar authored → the pane hides its toggle.
	const agentToolbarHtml = {$agent_toolbar_html_json|default:'""' nofilter};
	new CerbUI.AgentPane(document.getElementById('{$uniqid}'), {
		component: 'data_query',
		capabilities: 'getEditorValue,setEditorValue,editField,grepField,highlightLine',
		mutatingCommands: 'setEditorValue,editField',   // write the editor → guard against accidental navigation loss
		toolbarHtml: agentToolbarHtml,
		storageKey: 'cerb-data-query-builder-chat',
		runCommand: function(name, params) {
			params = params || {};
			switch(name) {
				case 'getEditorValue': return dq.getValue();
				case 'setEditorValue': dq.setValue(params.value || ''); return 'ok';
				// Surgical single-editor edits (setEditorValue stays for a wholesale replace/reset). editField =
				// undo-safe exactly-once search/replace; grepField locates a query → line numbers (no KATA path here).
				case 'editField': return CerbUI.editorCore.applyUniqueEdit(dq, (params.old == null) ? '' : String(params.old), (params.new == null) ? '' : String(params.new));
				case 'grepField': {
					const query = (params.query == null) ? '' : String(params.query);
					if(query === '') return 'error: grepField needs a non-empty query.';
					return CerbUI.editorCore.grepEditor(dq, query, parseInt(params.limit, 10));
				}
				// Jump to + flash a 1-based line (pairs with grepField's line numbers for "where is X" nav).
				case 'highlightLine': {
					const line = parseInt(params.line, 10);
					if(isNaN(line) || line < 1) return 'invalid line: ' + params.line;
					const row = line - 1;
					dq.gotoLine(line);
					dq.flashLine(row, {});
					return 'ok';
				}
			}
			return '';
		},
	});
});
</script>
