{* A reference over Cerb\Agent\Pane\Components, in the shape Setup already uses for Records, Toolbars
   and Automation Events: a filterable rail beside one card per entry. Nothing here is configurable --
   a surface exists because a host page declares it in JavaScript, and this is what an author reads to
   learn what an agent can do once it is switched on there. *}
<style nonce="{DevblocksPlatform::getRequestNonce()}">
.cerb-surfaces-sub { margin-top:1.1em; padding-top:0.75em; border-top:1px solid var(--cerb-color-background-contrast-220); }
.cerb-surfaces-sub--label { text-transform:uppercase; letter-spacing:0.04em; font-size:0.75em; color:var(--cerb-color-background-contrast-150); margin-bottom:0.5em; }
.cerb-surfaces-legend { text-transform:none; letter-spacing:0; }

.cerb-surfaces-chip { display:inline-flex; align-items:center; gap:0.25em; padding:0.05em 0.5em; border-radius:1em; font-size:0.7em; text-transform:uppercase; letter-spacing:0.04em; background:var(--cerb-color-background-contrast-220); color:var(--cerb-color-background-contrast-125); }
.cerb-surfaces-chip--server { background:var(--cerb-color-tag-purple); color:#fff; }
.cerb-surfaces-chip--fallback { background:var(--cerb-color-tag-red); color:#fff; }

.cerb-surfaces-table { width:100%; border-collapse:collapse; font-size:0.85em; }
.cerb-surfaces-table td { padding:0.5em 0.6em; border-bottom:1px solid var(--cerb-color-background-contrast-230); vertical-align:top; }
.cerb-surfaces-table tr:last-child td { border-bottom:0; }
.cerb-surfaces-table--icon { width:1.4em; text-align:center; }
.cerb-surfaces-table--tool { white-space:nowrap; }
.cerb-surfaces-note { color:var(--cerb-color-background-contrast-150); margin-top:0.25em; line-height:1.35; }

/* Transcript previews: the component's own classes do the work; only the pair's own spacing is ours */
.cerb-surfaces-transcript { margin-top:0.35em; max-width:32em; }

/* Parameter rows: name, then its own description under it, indented under the tool it belongs to */
.cerb-surfaces-params { margin-top:0.5em; padding-left:0.9em; border-left:2px solid var(--cerb-color-background-contrast-225); }
.cerb-surfaces-param { margin-top:0.35em; }
.cerb-surfaces-param:first-child { margin-top:0; }

/* The role prose: pre-wrap so the paragraphs it was written as survive */
.cerb-surfaces-role { white-space:pre-wrap; line-height:1.45; margin-top:0.6em; padding:0.75em 0.9em; border-radius:0.5em; background:var(--cerb-color-background-contrast-245); }
</style>

<div class="cerb-ui-page">
	<div class="cerb-ui-header">
		<div>
			<div class="cerb-ui-header--title">Surfaces</div>
			<div class="cerb-ui-header--subtitle">Where an agent can run, and the tools it answers with on each</div>
		</div>
	</div>

	<div class="cerb-ui-sidebar-layout cerb-u-mt-3 cerb-u-items-start">
		<aside class="cerb-ui-sidebar" id="surfacesNav" style="--cerb-ui-sidebar-width:260px;">
			<div class="cerb-ui-sidebar--body">
				<div class="cerb-ui-sidebar--section">
					<div class="cerb-ui-sidebar--label">Surfaces</div>
					<ul>
						{foreach from=$surfaces item=surface}
							<li data-target="{$surface.slug}" data-icon="{$surface.icon}">{$surface.label}</li>
						{/foreach}
					</ul>
				</div>
			</div>
		</aside>

		<div class="cerb-ui-sidebar-layout--content cerb-surfaces-content">
			{foreach from=$surfaces item=surface}
				<div class="cerb-surfaces-section cerb-ui-panel cerb-ui-panel--spaced" id="{$surface.slug}">
					<div class="cerb-ui-header cerb-ui-header--tight">
						<div>
							<div class="cerb-ui-header--title cerb-u-flex cerb-u-items-center cerb-u-gap-2 cerb-u-flex-wrap">
								<span class="cerb-icons cerb-icon-{$surface.icon}"></span>
								<span>{$surface.label}</span>
							</div>
							{if $surface.description}<div class="cerb-ui-header--subtitle">{$surface.description}</div>{/if}
						</div>
						{* The key an author writes in `components:`, opposite the label rather than beside it --
						   it identifies the card, it doesn't extend its name. *}
						<div class="cerb-ui-header--right"><code>{$surface.key}</code></div>
					</div>
					{if $surface.tagline}<div class="cerb-surfaces-note">Default launcher text: {$surface.tagline}</div>{/if}

					{if $surface.tools}
					<div class="cerb-surfaces-sub">
						<div class="cerb-surfaces-sub--label">Tools</div>
						<table class="cerb-surfaces-table">
							{foreach from=$surface.tools item=tool}
							<tr>
								<td class="cerb-surfaces-table--icon">{if $tool.icon}<span class="cerb-icons cerb-icon-{$tool.icon}"></span>{/if}</td>
								<td class="cerb-surfaces-table--tool">
									<code>{$tool.tool}</code>
									<div class="cerb-u-mt-1"><span class="cerb-surfaces-chip{if $tool.answered_by == 'Server'} cerb-surfaces-chip--server{/if}">{$tool.answered_by}</span></div>
								</td>
								<td>
									{$tool.description}

									{if $tool.parameters}
									<div class="cerb-surfaces-params">
										{foreach from=$tool.parameters item=param}
										<div class="cerb-surfaces-param">
											<code>{$param.name}</code>{if $param.required}<sup>*</sup>{/if}{if $param.enum} <span class="cerb-u-text-muted">one of {foreach from=$param.enum item=value name=enum}<code>{$value}</code>{if !$smarty.foreach.enum.last}, {/if}{/foreach}</span>{/if}
											<div class="cerb-surfaces-note">{$param.description}</div>
										</div>
										{/foreach}
									</div>
									{/if}

									{* Constants the bridge is handed and the model never sees, so a reader
									   isn't left wondering why a documented argument isn't in the schema. *}
									{if $tool.pinned}
									<div class="cerb-surfaces-note">Always sent, never asked of the model: {foreach from=$tool.pinned item=pin name=pinned}<code>{$pin.name}</code> = <code>{$pin.value}</code>{if !$smarty.foreach.pinned.last}, {/if}{/foreach}</div>
									{/if}

									{* The real transcript markup rather than a description of it: the same bubble, avatar
									   and author classes CerbUI.AgentTranscript builds, so what an author sees here is
									   what a worker will see. Inert -- no payload, no disclosure, and the running
									   bubble does not pulse (a reference page is not running anything). *}
									{if $tool.label_active || $tool.label_summary}
									<div class="cerb-surfaces-note">In the transcript</div>
									<div class="cerb-ui-agent-transcript">
										<div class="cerb-ui-agent-transcript--subthread cerb-surfaces-transcript">
											{if $tool.label_active}
											<div class="cerb-ui-agent-transcript--bubble cerb-ui-agent-transcript--bubble-active">
												<div class="cerb-ui-agent-transcript--bubble-avatar-box" data-icon="{$tool.icon}"></div>
												<div class="cerb-ui-agent-transcript--bubble-main">
													<div class="cerb-ui-agent-transcript--bubble-header">
														<span class="cerb-ui-agent-transcript--bubble-author">{$tool.label_active}</span>
													</div>
												</div>
											</div>
											{/if}
											{if $tool.label_summary}
											<div class="cerb-ui-agent-transcript--bubble">
												<div class="cerb-ui-agent-transcript--bubble-avatar-box" data-icon="{$tool.icon}"></div>
												<div class="cerb-ui-agent-transcript--bubble-main">
													<div class="cerb-ui-agent-transcript--bubble-header">
														<span class="cerb-ui-agent-transcript--bubble-author">{$tool.label_summary}</span>
													</div>
												</div>
											</div>
											{/if}
										</div>
									</div>
									{/if}
								</td>
							</tr>
							{/foreach}
						</table>
					</div>
					{/if}

					{if $surface.skills_required || $surface.skills_reference || $surface.docs}
					<div class="cerb-surfaces-sub">
						<div class="cerb-surfaces-sub--label">Reading</div>
						{foreach from=$surface.skills_required item=skill}
						<div class="cerb-surfaces-param">Before it will {$skill.gate}, it reads <code>{$skill.skill}</code>.</div>
						{/foreach}
						{if $surface.skills_reference}
						<div class="cerb-surfaces-param">Reads when it needs one: {foreach from=$surface.skills_reference item=skill name=skills}<code>{$skill}</code>{if !$smarty.foreach.skills.last}, {/if}{/foreach}</div>
						{/if}
						{if $surface.docs}
						<div class="cerb-surfaces-param">Documentation: {foreach from=$surface.docs item=path name=docs}<code>{$path}</code>{if !$smarty.foreach.docs.last}, {/if}{/foreach}</div>
						{/if}
						<div class="cerb-surfaces-note">Each only when that volume is mounted on the agent.</div>
					</div>
					{/if}

					{if $surface.role}
					<div class="cerb-surfaces-sub">
						<div class="cerb-surfaces-sub--label cerb-u-flex cerb-u-items-center cerb-u-gap-2">
							<span>Instructions</span>
							{if $surface.role_is_fallback}<span class="cerb-surfaces-chip cerb-surfaces-chip--fallback">Built-in fallback</span>{/if}
						</div>
						<button type="button" class="cerb-ui-button cerb-ui-button--transparent cerb-surfaces-role-toggle" data-target="{$surface.slug}_role"><span class="cerb-icons cerb-icon-chevron-right"></span> Show this surface's role</button>
						<div class="cerb-surfaces-role" id="{$surface.slug}_role" hidden>{$surface.role}</div>
						{* The role is one block of the system prompt, not all of it -- saying otherwise would
						   send someone hunting for text that is appended at runtime. *}
						<div class="cerb-surfaces-note">A chat here is also given the shared preamble every surface gets, the tools above written out in prose, and pointers into whichever volumes the agent has mounted.</div>
						{if $surface.role_is_fallback}
						<div class="cerb-surfaces-note">This surface's role asset is missing or empty, so the built-in copy is being used. That is a broken deploy rather than a setting.</div>
						{/if}
					</div>
					{/if}
				</div>
			{/foreach}
		</div><!-- /.cerb-surfaces-content -->
	</div><!-- /.cerb-ui-sidebar-layout -->
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}">
$(function() {
	const content = document.querySelector('.cerb-surfaces-content');

	if(!content) return;

	// Same call CerbUI.AgentTranscript makes for a bubble avatar, so the glyph, size and fill match rather
	// than approximate it.
	if(window.CerbUI && CerbUI.Avatar) {
		content.querySelectorAll('.cerb-surfaces-transcript .cerb-ui-agent-transcript--bubble-avatar-box').forEach(function(box) {
			box.appendChild(CerbUI.Avatar.create({
				icon: box.dataset.icon || '',
				size: 32,
				color: 'var(--cerb-color-background-contrast-200)',
				className: 'cerb-ui-agent-transcript--bubble-avatar'
			}));
		});
	}

	content.querySelectorAll('.cerb-surfaces-role-toggle').forEach(function(btn) {
		btn.addEventListener('click', function() {
			const el = document.getElementById(btn.dataset.target);
			if(!el) return;

			el.hidden = !el.hidden;

			const chevron = btn.querySelector('.cerb-icons');
			if(chevron) chevron.className = 'cerb-icons cerb-icon-chevron-' + (el.hidden ? 'right' : 'down');
		});
	});

	// Sidebar: type-to-filter rail + scrollspy (mirrors the Toolbars and Automation Events setup pages)
	(function() {
		const nav = document.getElementById('surfacesNav');
		if(!nav || !(window.CerbUI && CerbUI.Sidebar)) return;

		const sectionFor = function(id) {
			return id ? content.querySelector('#' + ((window.CSS && CSS.escape) ? CSS.escape(id) : id)) : null;
		};

		// While true, scrollspy may update the active item. An explicit sidebar click disables it until the
		// smooth-scroll settles, so passing sections don't yank the selection off the clicked target.
		let spyEnabled = true;
		let spyTimer = null;

		const goTo = function(id) {
			const el = sectionFor(id);
			if(!el) return;
			spyEnabled = false;
			if(spyTimer) clearTimeout(spyTimer);
			markActive(id);
			el.scrollIntoView({ behavior: 'smooth', block: 'start' });
			const release = function() { spyEnabled = true; };
			if('onscrollend' in window) {
				window.addEventListener('scrollend', release, { once: true });
				spyTimer = setTimeout(release, 1200);
			} else {
				spyTimer = setTimeout(release, 700);
			}
		};

		const sb = new CerbUI.Sidebar(nav, {
			fullHeight: true,
			filter: true,
			collapseTo: 'icons',
			storageKey: 'surfacesNavCollapsed',
			filterPlaceholder: 'Filter surfaces…',
			onSelect: function(li) { goTo(li.dataset.target); return true; }
		});

		const footer = document.getElementById('footer');
		const fit = function() { nav.style.height = 'calc(100vh - ' + ((footer && footer.offsetHeight) || 0) + 'px)'; };
		fit();
		window.addEventListener('resize', fit);

		const railBody = nav.querySelector('.cerb-ui-sidebar--body');
		const keepVisible = function(li) {
			if(!railBody) return;
			const lr = li.getBoundingClientRect(), br = railBody.getBoundingClientRect();
			if(lr.top < br.top) railBody.scrollTop -= (br.top - lr.top) + 8;
			else if(lr.bottom > br.bottom) railBody.scrollTop += (lr.bottom - br.bottom) + 8;
		};

		const byId = new Map();
		nav.querySelectorAll('.cerb-ui-sidebar--item').forEach(function(li) { byId.set(li.dataset.target, li); });

		const markActive = function(id) {
			const li = byId.get(id);
			if(!li) return;
			sb.setActive(li);
			keepVisible(li);
		};

		const visible = new Set();
		const io = new IntersectionObserver(function(entries) {
			entries.forEach(function(e) {
				if(e.isIntersecting) visible.add(e.target); else visible.delete(e.target);
			});
			if(!spyEnabled) return;
			let top = null;
			visible.forEach(function(el) {
				if(!top || el.getBoundingClientRect().top < top.getBoundingClientRect().top) top = el;
			});
			if(top && top.id) markActive(top.id);
		}, { rootMargin: '0px 0px -75% 0px' });

		byId.forEach(function(li, id) { const el = sectionFor(id); if(el) io.observe(el); });
	})();
});
</script>
