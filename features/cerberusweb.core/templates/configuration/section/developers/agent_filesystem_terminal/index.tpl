{$form_id = uniqid()}

<style type="text/css" nonce="{DevblocksPlatform::getRequestNonce()}">
#afsOutput_{$form_id} {
	font-family: monospace;
	white-space: pre-wrap;
	word-break: break-word;
	height: 26em;
	overflow: auto;
	margin: 0;
	padding: 0.75em;
	border-radius: 5px;
}
/* Reads as a real console in dark mode; light mode keeps the default surface for now.
   The id beats the `cerb-u-bgg-2` utility on specificity. */
.dark #afsOutput_{$form_id} {
	background-color: rgb(0,0,0);
}
#afsCommand_{$form_id} {
	font-family: monospace;
	flex: 1 1 auto;
	min-width: 0;
}
.afs-prompt {
	font-family: monospace;
	white-space: nowrap;
}
.afs-out--error { color: var(--cerb-color-text-alert, #c0392b); }
.afs-out--echo { opacity: 0.65; }
.afs-out--aux { opacity: 0.55; padding-left: 1.5em; }
/* The terminal bell, seen instead of heard: tab completion has nothing to offer, or too much. */
@keyframes afsFlash {
	0%, 100% { opacity: 1; }
	50% { opacity: 0.4; }
}
#afsOutput_{$form_id}.afs-flash {
	animation: afsFlash 140ms ease-in-out 1;
}
@media (prefers-reduced-motion: reduce) {
	#afsOutput_{$form_id}.afs-flash { animation: none; }
}
</style>

<div class="cerb-ui-header">
	<div class="cerb-ui-header--title"><span class="cerb-icons cerb-icon-folder"></span> Agent Filesystem Terminal</div>
	<div class="cerb-ui-header--subtitle">Mount agent filesystems and drive the same command set an agent gets. Type <code>help</code> to start. <kbd>Tab</kbd> completes commands and paths; <kbd>&uarr;</kbd>/<kbd>&darr;</kbd> recall a command with its payload.</div>
</div>

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight cerb-ui-header--center">
		<div class="cerb-ui-header--title-sm">Mounts</div>
		<div class="cerb-ui-header--right" style="min-width:280px;">
			<div class="cerb-ui-record-chooser" id="afsAddMount_{$form_id}"></div>
		</div>
	</div>

	<div id="afsMounts_{$form_id}" class="cerb-u-flex cerb-u-flex-wrap cerb-u-gap-2"></div>
	<div id="afsMountsEmpty_{$form_id}" class="cerb-u-text-muted">Nothing mounted yet &mdash; add a filesystem to begin.</div>
</div>

{if !empty($cerb_namespaces)}
<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div class="cerb-ui-header--title-sm">Commands <small class="cerb-u-text-muted cerb-u-fw-400">(the <code>cerb</code> command line &mdash; a capability rather than a mount, so it needs nothing mounted. With none enabled the <code>cerb</code> verb disappears, exactly as it does for an agent whose <code>cerb:</code> block doesn't name it.)</small></div>
	</div>

	<div id="afsCerbNamespaces_{$form_id}" class="cerb-u-flex cerb-u-flex-wrap cerb-u-gap-3">
		{foreach from=$cerb_namespaces key=cerb_name item=cerb_summary}
		<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2">
			<label class="cerb-ui-toggle">
				<input type="checkbox" class="afs-cerb-ns" id="afsCerb_{$form_id}_{$cerb_name}" data-namespace="{$cerb_name}" checked="checked">
				<span class="cerb-ui-toggle--slider"></span>
			</label>
			<label for="afsCerb_{$form_id}_{$cerb_name}"><code>cerb {$cerb_name}</code> <span class="cerb-u-text-muted">{$cerb_summary}</span></label>
		</div>
		{/foreach}
	</div>
</div>
{/if}

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-toolbar-strip cerb-u-mb-2">
		<button type="button" class="cerb-ui-toolbar-button" id="afsClear_{$form_id}"><span class="cerb-icons cerb-icon-erase"></span> Clear</button>
	</div>

	<pre id="afsOutput_{$form_id}" class="cerb-u-bgg-2"></pre>

	<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2 cerb-u-mt-2">
		<span class="afs-prompt cerb-u-text-muted" id="afsPrompt_{$form_id}">/ $</span>
		<input type="text" id="afsCommand_{$form_id}" placeholder="help" autocomplete="off" spellcheck="false" autofocus="autofocus">
		<button type="button" class="cerb-ui-button" id="afsRun_{$form_id}"><span class="cerb-icons cerb-icon-play-button"></span> Run</button>
	</div>
</div>

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div class="cerb-ui-header--title-sm">Payload <small class="cerb-u-text-muted cerb-u-fw-400">(out-of-band multi-line payload)</small></div>
	</div>
	<textarea id="afsPayload_{$form_id}" rows="4" spellcheck="false" style="width:100%;font-family:monospace;"></textarea>
</div>

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div class="cerb-ui-header--title-sm">Find <small class="cerb-u-text-muted cerb-u-fw-400">(out-of-band search needle for <code>edit</code> only &mdash; the exact snippet to replace, which must match exactly ONE place in the file; the Payload box above holds what it's replaced with)</small></div>
	</div>
	<textarea id="afsFind_{$form_id}" rows="4" spellcheck="false" style="width:100%;font-family:monospace;"></textarea>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	const uid = '{$form_id}';
	const outputEl = document.getElementById('afsOutput_' + uid);
	const promptEl = document.getElementById('afsPrompt_' + uid);
	const commandEl = document.getElementById('afsCommand_' + uid);
	const payloadEl = document.getElementById('afsPayload_' + uid);
	const findEl = document.getElementById('afsFind_' + uid);
	const mountsEl = document.getElementById('afsMounts_' + uid);
	const mountsEmptyEl = document.getElementById('afsMountsEmpty_' + uid);

	const state = { mounts: [], cwd: '/', tmp: {} };

	// The `cerb` CLI namespaces this session has, read from the toggles. Live rather than cached: unchecking
	// one has to take the verb away on the very next command, the same way the server would for an agent.
	function cerbNamespaces() {
		return Array.prototype.slice
			.call(document.querySelectorAll('#afsCerbNamespaces_' + uid + ' .afs-cerb-ns'))
			.filter(el => el.checked)
			.map(el => el.dataset.namespace);
	}
	// A history entry is the whole command line: the command plus its out-of-band boxes, since the server
	// interprets payload/find by verb. Recalling one restores all three so a full command repeats verbatim.
	const history = [];
	let historyAt = 0;
	let draft = null;

	function setPrompt() {
		promptEl.textContent = state.cwd + ' $';
	}

	function append(text, cls) {
		if(null === text || undefined === text || '' === text)
			return;

		const line = document.createElement('div');

		if(cls)
			line.className = cls;

		line.textContent = text;
		outputEl.appendChild(line);
		outputEl.scrollTop = outputEl.scrollHeight;
	}

	function renderMounts() {
		mountsEl.replaceChildren();
		mountsEmptyEl.style.display = state.mounts.length ? 'none' : '';

		state.mounts.forEach(function(mount, idx) {
			const row = document.createElement('div');
			row.className = 'cerb-ui-panel cerb-u-flex cerb-u-items-center cerb-u-gap-2 cerb-u-p-2';

			const label = document.createElement('span');
			label.innerHTML = '<span class="cerb-icons cerb-icon-folder"></span> ';
			label.appendChild(document.createTextNode('/' + mount.name));
			row.appendChild(label);

			// Mode: ro (default) | rw
			const sw = document.createElement('div');
			sw.className = 'cerb-ui-switcher';
			sw.innerHTML = '<button type="button" data-value="ro">ro</button><button type="button" data-value="rw">rw</button>';
			row.appendChild(sw);

			const remove = document.createElement('button');
			remove.type = 'button';
			remove.className = 'cerb-ui-button cerb-ui-button--transparent';
			remove.innerHTML = '<span class="cerb-icons cerb-icon-circle-remove"></span>';
			remove.addEventListener('click', function() {
				state.mounts.splice(idx, 1);
				renderMounts();
			});
			row.appendChild(remove);

			mountsEl.appendChild(row);

			if(window.CerbUI && CerbUI.Switcher) {
				sw.querySelectorAll('button').forEach(function(b) {
					if(b.getAttribute('data-value') === mount.mode)
						b.classList.add('cerb-ui-switcher--active');
				});

				new CerbUI.Switcher(sw, {
					value: mount.mode,
					onSelect: function(v) { mount.mode = v; }
				});
			}
		});
	}

	function exec(entry, done) {
		const formData = new FormData();
		formData.set('c', 'config');
		formData.set('a', 'invoke');
		formData.set('module', 'agent_filesystem_terminal');
		formData.set('action', 'execJson');
		formData.set('mounts', JSON.stringify(state.mounts.map(function(m) {
			return { filesystem_id: m.id, mode: m.mode };
		})));
		formData.set('cerb', JSON.stringify(cerbNamespaces()));
		formData.set('cwd', state.cwd);
		formData.set('command', entry.command);
		formData.set('payload', entry.payload);
		formData.set('find', entry.find);
		// The client owns the /tmp scratch store between commands, same as mounts + cwd — the server is
		// stateless. (An automation host keeps this on its dict, where it rides the continuation instead.)
		formData.set('tmp', JSON.stringify(state.tmp));

		genericAjaxPost(formData, null, null, function(resp) {
			let json = resp;

			if(typeof resp === 'string') {
				try { json = JSON.parse(resp); } catch(e) { json = null; }
			}

			done(json);
		});
	}

	function run(entry) {
		exec(entry, function(json) {
			if(!json || !json.status) {
				append((json && json.error) ? json.error : 'Request failed.', 'afs-out--error');
				return;
			}

			append(json.output, json.error ? 'afs-out--error' : null);

			if(json.cwd) {
				state.cwd = json.cwd;
				setPrompt();
			}

			if(json.tmp && typeof json.tmp === 'object')
				state.tmp = json.tmp;
		});
	}

	// ---- Tab completion -------------------------------------------------------------------------------
	// The first word completes against the verb list; anything after it completes against the filesystem.
	// Flags aren't completed — they're per-command and `help` is one keystroke away.

	// Kept here rather than fetched: the verbs are a fixed vocabulary (Cerb\Agent\Filesystem::exec), and
	// parsing them back out of `help` would be worse than restating twelve words.
	const COMMANDS = ['append', 'cat', 'cd', 'copy', 'cp', 'dir', 'edit', 'find', 'grep', 'help', 'ls', 'pwd', 'read', 'rm', 'search', 'write'];

	// `cerb` completes only while it exists, so the terminal never offers a verb the server would reject.
	function commands() {
		return cerbNamespaces().length ? COMMANDS.concat(['cerb']).sort() : COMMANDS;
	}
	const COMPLETE_MAX_LIST = 100;
	{literal}
	// The listing script. It's a whole Twig template, so it carries its own moustaches — which is why this
	// is wrapped, since Smarty would otherwise parse those braces as its own. The escaped `\\n` keeps the
	// newline escaped in the Twig SOURCE instead of breaking the string literal Twig has to read.
	const COMPLETE_SCRIPT = '{{files|map(f => f.name ~ (f.is_dir ? "/" : ""))|join("\\n")}}';
	{/literal}
	let completing = false;

	// The terminal bell, seen instead of heard: nothing to complete, or too many to be worth listing.
	function flash() {
		outputEl.classList.remove('afs-flash');
		void outputEl.offsetWidth; // restart the animation even on consecutive tabs
		outputEl.classList.add('afs-flash');
	}

	function commonPrefix(values) {
		return values.reduce(function(prefix, value) {
			let i = 0;

			while(i < prefix.length && i < value.length && prefix[i] === value[i])
				i++;

			return prefix.slice(0, i);
		});
	}

	// Replace [start, caret) with the completion, leaving the rest of the line (and the caret) sane.
	function insertCompletion(text, start, caret) {
		const value = commandEl.value;
		commandEl.value = value.slice(0, start) + text + value.slice(caret);
		const at = start + text.length;
		commandEl.setSelectionRange(at, at);
	}

	function applyCompletion(matches, prefix, start, caret) {
		if(!matches.length)
			return flash();

		if(1 === matches.length) {
			// A directory keeps the caret inside it so you can tab straight through the tree; anything else is
			// a finished word and gets the space.
			const match = matches[0];
			return insertCompletion(match.endsWith('/') ? match : (match + ' '), start, caret);
		}

		const shared = commonPrefix(matches);

		// Extend to the longest common prefix when that adds anything; otherwise it's genuinely ambiguous, so
		// show the options the way a shell does.
		if(shared.length > prefix.length)
			return insertCompletion(shared, start, caret);

		if(matches.length > COMPLETE_MAX_LIST)
			return flash();

		append(matches.join('  '), 'afs-out--echo');
	}

	function complete() {
		if(completing)
			return;

		const value = commandEl.value;
		const caret = commandEl.selectionStart ?? value.length;
		const head = value.slice(0, caret);
		// Whitespace-delimited; a quoted path with a space in it isn't worth the parser here.
		const start = head.lastIndexOf(' ') + 1;
		const token = head.slice(start);

		if('' === head.slice(0, start).trim()) {
			applyCompletion(commands().filter(c => c.startsWith(token)), token, start, caret);
			return;
		}

		// Split the token into the directory to list and the prefix to match inside it. `@volume/x` works
		// unchanged: the server resolves the `@` form, so it's just another directory to list.
		const slash = token.lastIndexOf('/');
		const dir = (slash < 0) ? '' : (token.slice(0, slash) || '/');
		const prefix = token.slice(slash + 1);

		completing = true;

		// The listing rides the ordinary exec endpoint — `ls` already knows how to resolve a path against the
		// mounts and the cwd, and the script turns its records into exactly the names we match against.
		exec({
			command: 'ls' + (dir ? (' "' + dir + '"') : ''),
			payload: COMPLETE_SCRIPT,
			find: ''
		}, function(json) {
			completing = false;

			// The completion is a side query: its `cwd` and `tmp` are the caller's own, and adopting them
			// would let a stray spill from a listing land in the terminal's scratch state.
			if(!json || !json.status || json.error)
				return flash();

			const names = String(json.output || '').split('\n').filter(n => '' !== n);

			applyCompletion(
				names.filter(n => n.startsWith(prefix)),
				prefix,
				start + slash + 1,
				caret
			);
		});
	}

	function currentEntry() {
		return {
			command: commandEl.value.trim(),
			// Raw, not trimmed -- leading whitespace is significant in file content.
			payload: payloadEl.value,
			find: findEl.value
		};
	}

	function recall(entry) {
		commandEl.value = entry ? entry.command : '';
		payloadEl.value = entry ? entry.payload : '';
		findEl.value = entry ? entry.find : '';
	}

	function sameEntry(a, b) {
		return a && b && a.command === b.command && a.payload === b.payload && a.find === b.find;
	}

	// Echo what rode along out-of-band, so a box emptying by itself is never a surprise. The roles mirror the
	// server's verb mapping in `_configAction_execJson()`.
	function echoAux(entry) {
		const verb = entry.command.split(/\s+/)[0].toLowerCase();
		const lines = function(text) {
			const n = text.split('\n').length;
			return n + (1 === n ? ' line' : ' lines');
		};

		if(entry.find.length)
			append('↳ find: ' + lines(entry.find), 'afs-out--aux');

		if(!entry.payload.length)
			return;

		let role = 'script';

		if('write' === verb || 'append' === verb)
			role = 'content';
		else if('edit' === verb)
			role = 'replace';

		append('↳ ' + role + ': ' + lines(entry.payload), 'afs-out--aux');
	}

	function submitCommand() {
		const entry = currentEntry();

		if('' === entry.command)
			return;

		append(state.cwd + ' $ ' + entry.command, 'afs-out--echo');
		echoAux(entry);

		if(!sameEntry(history[history.length - 1], entry))
			history.push(entry);

		historyAt = history.length;
		draft = null;

		// A run consumes its whole command line. Otherwise a stale payload keeps riding along as a script on
		// every later command, and the terminal prints the payload instead of the output.
		recall(null);
		run(entry);
	}

	commandEl.addEventListener('keydown', function(e) {
		if('Enter' === e.key) {
			e.preventDefault();
			submitCommand();

		} else if('Tab' === e.key && !e.shiftKey) {
			e.preventDefault(); // Tab is completion here, not "leave the field"
			complete();

		} else if('ArrowUp' === e.key && history.length) {
			e.preventDefault();

			if(historyAt === history.length)
				draft = currentEntry();

			historyAt = Math.max(0, historyAt - 1);
			recall(history[historyAt]);

		} else if('ArrowDown' === e.key && history.length) {
			e.preventDefault();
			historyAt = Math.min(history.length, historyAt + 1);
			recall(historyAt === history.length ? draft : history[historyAt]);
		}
	});

	document.getElementById('afsRun_' + uid).addEventListener('click', submitCommand);

	// Clears the scrollback only — mounts, cwd, and /tmp survive, same as a shell's `clear`.
	document.getElementById('afsClear_' + uid).addEventListener('click', function() {
		outputEl.replaceChildren();
		commandEl.focus();
	});

	if(window.CerbUI && CerbUI.Toggle)
		document.querySelectorAll('#afsCerbNamespaces_' + uid + ' .cerb-ui-toggle').forEach(function(el) { new CerbUI.Toggle(el); });

	if(window.CerbUI && CerbUI.RecordChooser) {
		const chooser = new CerbUI.RecordChooser(document.getElementById('afsAddMount_' + uid), {
			context: 'agent_filesystem',
			emptyIcon: 'folder',
			searchPlaceholder: 'Mount a filesystem…',
			onSelect: function(item) {
				if(!state.mounts.some(function(m) { return m.id == item.id; })) {
					state.mounts.push({ id: item.id, name: item.label, mode: 'ro' });
					renderMounts();
				}
				// This chooser is an ADDER — each pick becomes its own mount row, so it has to keep suggesting.
				// clear(false) skips its own refocus (the input already has focus, so re-focusing fires no
				// `focus` event and the list would stay shut until you blurred and came back); openAutocomplete()
				// then reopens it explicitly.
				chooser.clear(false);
				chooser.openAutocomplete();
			}
		});
	}

	setPrompt();
	renderMounts();
	append('Agent Filesystem Terminal — mount a filesystem, then run `help`.', 'afs-out--echo');
});
</script>
