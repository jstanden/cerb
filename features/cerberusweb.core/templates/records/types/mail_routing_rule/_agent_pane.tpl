{* The agent pane for a routing_kata editor, shared by the Mail Routing Rule popup and the group editor's
   Mail: Incoming tab. Included INSIDE each host's script block, where `$popup`, `$frm` and `editor` are
   already in scope.

   Params:
     routing_scope
             'rule' (a standalone rule, which picks a group) | 'group' (a group's own rules, which pick a
             bucket within it). Reported to the agent by get_routing -- one component serves both editors,
             so this is the only thing that tells it which document it is looking at.
     mount   id of the host element
     split   true  = SPLIT the host; false = FLOAT the chat in its own dialog

   Both hosts SPLIT. The mount wraps each host's whole <form> from outside it, which is what keeps the chat's
   own form from nesting inside the host's -- including in the group editor, where the routing editor is only
   one tab of four. Splitting the tab PANEL instead would nest the forms, so the pane sits beside the whole
   peek and the toggle stays on the routing toolbar, where it is only reachable from that tab. *}
if(window.CerbUI && CerbUI.AgentPane) {
	const agentHost = document.getElementById('{$mount}');
{if $split}
	const agentDlg0 = CerbUI.Dialog ? CerbUI.Dialog.from($popup[0]) : null;
	const agentDlgOrigPct = (agentDlg0 && agentDlg0._widthPct) ? agentDlg0._widthPct : 80;
{/if}

	const agentPane = agentHost ? new CerbUI.AgentPane(agentHost, {
		component: 'mail_routing',
		capabilities: 'getFields,setField,editField,grepField,getDiff,highlightLine,highlightKey',
		// Commands that WRITE the editor -> arm AgentPane's navigation guard, since the agent's programmatic
		// edits never trip the form's keystroke dirty check.
		mutatingCommands: 'setField,editField',
		toolbarHtml: {$agent_toolbar_html_json|default:'""' nofilter},
		storageKey: 'cerb-mail-routing-agent-chat',
{if $split}
		fit: true,
{else}
		float: true,
{/if}
		toggleInto: (editor._editorToolbar && editor._editorToolbar.el) ? editor._editorToolbar.el : null,
{if $split}
		onToggle: function(collapsed) {
			// Widen the popup to make room for the chat; restore the width it opened at.
			const dlg = (CerbUI.Dialog && $popup.length) ? CerbUI.Dialog.from($popup[0]) : null;
			if(!dlg) return;
			dlg._widthPct = collapsed ? agentDlgOrigPct : 97;
			dlg.w = dlg._computeWidth();
			dlg.el.style.width = dlg.w + 'px';
			dlg._positionDefault();
			CerbUI.Dialog._syncPageHeight();
		},
{/if}
		runCommand: function(name, params) {
			params = params || {};

			if(name === 'getFields') {
				// WHERE this document runs, not just what it says. The two editors share a grammar but not a
				// meaning: a rule picks a group, a group's rules pick a bucket among the ones named here.
				return JSON.stringify({
					scope: '{$routing_scope}',
{if $routing_scope == 'group'}
					group_name: {$group->name|json_encode nofilter},
					buckets: {$routing_buckets_json|default:'[]' nofilter},
{/if}
					routing_kata: editor.getValue()
				});
			}

			if(name === 'setField') {
				// One writable field, and the catalog pins `key`, so the value is the whole argument.
				editor.setValue((params.value == null) ? '' : String(params.value));
				editor.clearSelection();
				return 'ok';
			}

			if(name === 'editField')
				return CerbUI.editorCore.applyUniqueEdit(editor, (params.old == null) ? '' : String(params.old), (params.new == null) ? '' : String(params.new));

			if(name === 'grepField') {
				const query = (params.query == null) ? '' : String(params.query);
				if(query === '') return 'error: grepField needs a non-empty query.';
				return CerbUI.editorCore.grepEditor(editor, query, parseInt(params.limit, 10));
			}

			if(name === 'getDiff') {
				// The UNSAVED diff against the last save, which is the same thing the gutter marks -- so the
				// agent can review its own edits without opening Change History. Lines are 1-based to match
				// grepField and highlightLine. `tracked` is false when there is no baseline yet.
				if(typeof editor.getDiffState !== 'function')
					return 'error: this editor has no diff baseline.';
				const st = editor.getDiffState();
				return JSON.stringify({
					tracked: st.baseline != null,
					hunks: st.hunks.map(function(h) {
						return {
							status:  h.status,
							line:    h.rowStart + 1,
							endLine: h.rowEnd,
							added:   h.added,
							removed: h.removed
						};
					})
				});
			}

			if(name === 'highlightLine') {
				const line = parseInt(params.line, 10);
				if(isNaN(line) || line < 1) return 'invalid line: ' + params.line;
				const row = line - 1;
				editor.scrollToLine(row);
				editor.gotoLine(line);
				if(typeof editor.flashLine === 'function') editor.flashLine(row, { color: 'orange' });
				return 'ok';
			}

			if(name === 'highlightKey') {
				// A KATA key path survives edits that shift line numbers, which is what grep_routing reports.
				const row = editor.getRowByPath(String(params.path || ''));
				if(row === false || row < 0) return 'error: no row for path: ' + params.path;
				editor.scrollToLine(row);
				editor.gotoLine(row + 1);
				if(typeof editor.flashLine === 'function') editor.flashLine(row, { color: 'orange' });
				return 'ok';
			}

			return '';
		}
	}) : null;

	// A successful save persists the agent's edits -> drop the unsaved-navigation guard.
	$popup.on('peek_saved', function() { if(agentPane && agentPane.markClean) agentPane.markClean(); });
}
