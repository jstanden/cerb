{$wizard_uid = uniqid('abwiz')}
<style>{literal}
/* Wizard layout only. */
.cerb-ab-wizard--title { display:flex; align-items:center; gap:0.4em; margin-bottom:0.75em; font-weight:bold; font-size: 1.5em; }
.cerb-ab-wizard--split { min-height:340px; }
.cerb-ab-wizard--left, .cerb-ab-wizard--right { min-width:0; padding:0 0.6em; box-sizing:border-box; overflow:auto; }
.cerb-ab-wizard--section { margin-bottom:1em; }
.cerb-ab-wizard--hint { opacity:0.75; margin-top:0.25em; }
/* The SelectMenu trigger is inline-flex with a 12em min-width; match the sibling text input instead. It is
   inserted directly after the source select, so it is a child of the field. */
.cerb-ab-wizard--left .cerb-ui-form--field > .cerb-ui-selectmenu { display:flex; width:100%; }
.cerb-ab-wizard--mount { display:flex; align-items:center; gap:0.5em; margin-bottom:0.4em; }
.cerb-ab-wizard--mount-name { flex:1 1 auto; min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
{/literal}</style>

<div id="{$wizard_uid}" data-cerb-agent-chat-wizard>
	<script type="application/json" data-cerb-wizard-components>{$components_json nofilter}</script>

	<div class="cerb-ab-wizard--title"><span class="cerb-icons cerb-icon-bot-message"></span> Agent Chat</div>

	<div class="cerb-ab-wizard--errors cerb-ui-panel--alert" data-cerb-wizard-errors hidden style="margin-bottom:0.75em;"></div>

	<div class="cerb-ab-wizard--split">
		<div class="cerb-ab-wizard--left">
			<div class="cerb-ab-wizard--section cerb-ui-form">
				<div class="cerb-ui-form--field">
					<label class="cerb-ui-form--label">Chat title</label>
					<input type="text" data-cerb-wizard-title value="Agent Chat" spellcheck="false" style="width:100%; box-sizing:border-box;">
				</div>
				<div class="cerb-ui-form--field">
					<label class="cerb-ui-form--label">Where it lives</label>
					<select data-cerb-wizard-component style="width:100%; box-sizing:border-box;"></select>
					<div class="cerb-ab-wizard--hint" data-cerb-wizard-component-hint></div>
					<div class="cerb-ab-wizard--hint">Its system prompt is written from this choice and what you mount. You can edit it in the script afterwards.</div>
				</div>
			</div>

			<div class="cerb-ab-wizard--section"><div class="cerb-ui-record-chooser" data-cerb-agent-tool-picker></div></div>
		</div>

		<div class="cerb-ab-wizard--right">
			<div class="cerb-ab-wizard--section cerb-ui-form">
				<label class="cerb-ui-form--label">Filesystems</label>
				<div class="cerb-ab-wizard--hint" style="margin-bottom:0.5em;">Volumes the agent can reach. It always gets a writable <code>/tmp</code> to work in.</div>
				<div data-cerb-wizard-mounts></div>
				<div data-cerb-wizard-mount-adder></div>
			</div>
		</div>
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
{literal}
(function() {
	const root = document.getElementById('{/literal}{$wizard_uid}{literal}');
	if(!root) return;

	const parse = function(sel) { try { return JSON.parse(root.querySelector(sel).textContent); } catch(e) { return null; } };
	const COMPONENTS = parse('[data-cerb-wizard-components]') || [];

	const hasAP = window.CerbUI && CerbUI.AgentPrompt;

	// A tool mount carries nothing but the record, so a multi-chooser is the whole control. `create` lets a
	// tool be defined without leaving the wizard.
	const tools = (window.CerbUI && CerbUI.RecordChooser)
		? new CerbUI.RecordChooser(root.querySelector('[data-cerb-agent-tool-picker]'), {
			context: 'cerb.contexts.agent.tool',
			multiple: true,
			emptyIcon: 'wrench',
			query: 'status:[available,unlisted]',
			create: true
		})
		: null;

	// Which editor the chat is written FOR. This shapes the system prompt only -- the orientation an agent in
	// the Icon Builder needs differs from one in the automation editor. The tools themselves are contributed
	// by the trigger at runtime, off whichever pane the chat is actually opened in, so the answer here is
	// guidance rather than a lock.
	//
	// Blank is the DEFAULT and is not "nowhere": a chat still goes on any toolbar you add it to, it just gets
	// a generic prompt. Hosts that advertise no uiCommands yet (the command bar) land here too, so the option
	// is named for where the chat runs rather than for what it lacks.
	const componentEl = root.querySelector('[data-cerb-wizard-component]');
	const hintEl = root.querySelector('[data-cerb-wizard-component-hint]');

	const blank = document.createElement('option');
	blank.value = '';
	blank.textContent = '(Any toolbar)';
	// `toolbox` is what Setup > Toolbars already falls back to for a toolbar with no icon of its own
	// (Controller_ConfigToolbars), so "any toolbar" wears the same glyph the toolbar list does.
	blank.dataset.cerbUiIcon = 'toolbox';
	componentEl.appendChild(blank);

	COMPONENTS.forEach(c => {
		const opt = document.createElement('option');
		opt.value = c.key;
		opt.textContent = c.label;
		if(c.icon)
			opt.dataset.cerbUiIcon = c.icon;
		componentEl.appendChild(opt);
	});

	// Every option is in place first -- SelectMenu reads `select.options` once, at construction. No
	// `placeholder:` opt: "(Any toolbar)" is a real default, and a placeholder would render it grayed and
	// unadorned. The source select still fires `change` on pick, so syncHint stays bound to it.
	if(window.CerbUI && CerbUI.SelectMenu)
		new CerbUI.SelectMenu(componentEl, { filter: false });

	const syncHint = function() {
		const c = COMPONENTS.filter(x => x.key === componentEl.value)[0];
		const noTools = 'It gets no editor tools, so it can talk but not touch what is on screen.';

		// A catalogued component with no commands is a real place whose host has no command bridge yet, so say
		// what it can't do rather than boasting "0 tools".
		hintEl.textContent = c
			? c.description + ' ' + (c.commands
				? ('The agent gets ' + c.commands + ' tool' + (c.commands === 1 ? '' : 's') + ' for it.')
				: noTools)
			: 'Add it to any toolbar and it runs there. ' + noTools;
	};

	componentEl.addEventListener('change', syncHint);
	syncHint();

	// Mounts. A chooser adds them and each becomes a row with an ro|rw switcher -- the same shape as the
	// Setup filesystem terminal, and for the same reason: an install can have dozens of volumes, so a
	// checkbox list of all of them is unreadable and mostly irrelevant.
	const mounts = [];
	const mountsEl = root.querySelector('[data-cerb-wizard-mounts]');

	const renderMounts = function() {
		mountsEl.replaceChildren();

		mounts.forEach((mount, idx) => {
			const row = document.createElement('div');
			row.className = 'cerb-ab-wizard--mount';

			const name = document.createElement('span');
			name.className = 'cerb-ab-wizard--mount-name';
			name.innerHTML = '<span class="cerb-icons cerb-icon-folder"></span> ';
			name.appendChild(document.createTextNode('/' + mount.name));
			row.appendChild(name);

			const sw = document.createElement('div');
			sw.className = 'cerb-ui-switcher';
			sw.innerHTML = '<button type="button" data-value="ro">ro</button><button type="button" data-value="rw">rw</button>';
			row.appendChild(sw);

			const remove = document.createElement('button');
			remove.type = 'button';
			remove.className = 'cerb-ui-button cerb-ui-button--transparent';
			remove.innerHTML = '<span class="cerb-icons cerb-icon-circle-remove"></span>';
			remove.addEventListener('click', () => { mounts.splice(idx, 1); renderMounts(); });
			row.appendChild(remove);

			mountsEl.appendChild(row);

			if(window.CerbUI && CerbUI.Switcher) {
				// Seed the active class before constructing: the Switcher syncs to `value` without firing
				// onSelect, so an unseeded row would render with neither segment lit.
				sw.querySelectorAll('button').forEach(b => {
					if(b.getAttribute('data-value') === mount.mode)
						b.classList.add('cerb-ui-switcher--active');
				});

				new CerbUI.Switcher(sw, { value: mount.mode, onSelect: v => { mount.mode = v; } });
			}
		});
	};

	if(window.CerbUI && CerbUI.RecordChooser) {
		const mountChooser = new CerbUI.RecordChooser(root.querySelector('[data-cerb-wizard-mount-adder]'), {
			context: 'agent_filesystem',
			emptyIcon: 'folder',
			searchPlaceholder: 'Mount a filesystem...',
			onSelect: (item) => {
				if(!item || !item.id) return;
				if(!mounts.some(m => m.id == item.id)) {
					mounts.push({ id: item.id, name: item.label, mode: 'ro' });
					renderMounts();
				}
				// An ADDER, so it has to keep suggesting. clear(false) skips its own refocus (the input is
				// already focused, so no `focus` event fires and the list would stay shut until you blurred
				// and came back); openAutocomplete() reopens it explicitly.
				mountChooser.clear(false);
				mountChooser.openAutocomplete();
			}
		});
	}

	renderMounts();

	// 2:1 split -- the config on the left, the (narrower) filesystem list on the right.
	if(window.CerbUI && CerbUI.SplitPane) {
		try { new CerbUI.SplitPane(root.querySelector('.cerb-ab-wizard--split'), { orientation: 'horizontal', ratio: 0.667, min: 240 }); } catch(e) {}
	}

	const errorsEl = root.querySelector('[data-cerb-wizard-errors]');
	const showErrors = function(list) {
		if(!errorsEl) return;
		errorsEl.textContent = (list && list.length) ? list.join(' ') : '';
		errorsEl.hidden = !(list && list.length);
	};

	// The Automation Builder reads answers off the wizard-body element on Apply.
	const body = root.closest('[data-cerb-template-wizard-body]') || root.parentElement;
	body._cerbGetAnswers = function() {
		const titleEl = root.querySelector('[data-cerb-wizard-title]');

		// No system prompt here -- the server generates it from the location and the mounts, and the author
		// edits it in the automation editor afterwards.
		return {
			title: (titleEl && titleEl.value) || '',
			component: componentEl.value || '',
			tools: tools ? tools.values.map(v => ({ id: v.id, name: String(v.label || '') })) : [],
			filesystems: mounts.map(m => ({ name: m.name, mode: m.mode }))
		};
	};

	// Block "Use this template" until the config is valid (returns true when OK). Nothing here is required --
	// models come from the default router, and a chat with no editor and no tools is still a working chat.
	body._cerbValidate = function() {
		showErrors([]);
		return true;
	};
})();
{/literal}
</script>
