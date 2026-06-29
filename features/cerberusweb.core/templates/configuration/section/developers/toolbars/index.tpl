<style nonce="{DevblocksPlatform::getRequestNonce()}">
#toolbarsNav { --cerb-ui-sidebar-width: 260px; }
/* Collapsed strip = icons only; hide the 'Toolbars' label (id beats the base collapsed rule) */
#toolbarsNav.cerb-ui-sidebar--collapsed .cerb-ui-sidebar--label { display:none; }
.cerb-toolbars-content { padding-left: 1.75em; }

.cerb-toolbars-section { border-radius:1em; margin-bottom: 2em; padding: 1.25em 1.5em 1.5em; }
.cerb-toolbars-section--name { color:var(--cerb-color-text); }
.cerb-toolbars-section--desc { color:var(--cerb-color-background-contrast-150); margin-top:0.35em; }

.cerb-toolbars-sub { margin-top:1.1em; padding-top:0.75em; border-top:1px solid var(--cerb-color-background-contrast-220); }
.cerb-toolbars-sub:first-of-type { border-top:0; }
.cerb-toolbars-sub--head { display:flex; align-items:center; gap:0.6em; flex-wrap:wrap; }
.cerb-toolbars-sub--name { font-weight:bold; }
.cerb-toolbars-prio { color:var(--cerb-color-background-contrast-150); font-size:0.8em; }

.cerb-toolbars-chip { display:inline-flex; align-items:center; gap:0.25em; padding:0.05em 0.5em; border-radius:1em; font-size:0.7em; text-transform:uppercase; letter-spacing:0.04em; background:var(--cerb-color-background-contrast-220); color:var(--cerb-color-background-contrast-125); }
.cerb-toolbars-chip--disabled { background:var(--cerb-color-tag-red); color:#fff; }
.cerb-toolbars-chip--workflow { background:var(--cerb-color-tag-purple); color:#fff; }

.cerb-toolbars-empty { color:var(--cerb-color-background-contrast-160); font-style:italic; margin-top:0.5em; }
.cerb-toolbars-missing { display:flex; align-items:center; gap:0.4em; margin-top:0.6em; color:var(--cerb-color-tag-red); font-size:0.85em; }
.cerb-toolbars-missing code { font-size:0.95em; }
</style>

{* Inert menu tree: a nested <ul class="cerb-ui-toolbar"> the CerbUI.Toolbar component enhances (icons +
   cascading menus). No data-interaction-* attributes are emitted, so nothing fires. Resolved interaction
   items wrap their label in a peek-trigger anchor to the bound automation profile. *}
{function toolbar_menu items=[]}
	{foreach from=$items item=item}
		{if $item.type == 'divider'}
			<li></li>
		{elseif $item.type == 'menu'}
			<li{if $item.icon} data-icon="{$item.icon}"{/if}{if $item.tooltip} title="{$item.tooltip}"{/if}>{$item.label}{if $item.children}<ul>{toolbar_menu items=$item.children}</ul>{/if}</li>
		{else}
			<li{if $item.icon} data-icon="{$item.icon}"{/if}{if $item.tooltip} title="{$item.tooltip}"{/if}{if $item.keyboard} data-keyboard="{$item.keyboard}"{/if}>{if $item.url}<a href="{$item.url}" class="cerb-toolbars-peek no-underline" data-context="{$item.context}" data-context-id="{$item.id}">{$item.label}</a>{else}{$item.label}{/if}</li>
		{/if}
	{/foreach}
{/function}

<div class="cerb-ui-page">
	<div class="cerb-ui-header">
		<div>
			<div class="cerb-ui-header--title">Toolbars</div>
			<div class="cerb-ui-header--subtitle">Toolbars, their sections, and the interactions each section contributes</div>
		</div>
	</div>

	<div class="cerb-ui-sidebar-layout cerb-u-mt-3 cerb-u-items-start">
		<aside class="cerb-ui-sidebar" id="toolbarsNav">
			<div class="cerb-ui-sidebar--body">
				<div class="cerb-ui-sidebar--section">
					<div class="cerb-ui-sidebar--label">Toolbars</div>
					<ul>
						{foreach from=$toolbars item=toolbar}
							<li data-target="{$toolbar.slug}" data-icon="{$toolbar.icon}">{$toolbar.name}</li>
						{/foreach}
					</ul>
				</div>
			</div>
		</aside>

		<div class="cerb-ui-sidebar-layout--content cerb-toolbars-content">
			{foreach from=$toolbars item=toolbar}
				<div class="cerb-toolbars-section cerb-u-bgg-3" id="{$toolbar.slug}">
					<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2 cerb-u-flex-wrap">
						<span class="cerb-icons cerb-icon-{$toolbar.icon} cerb-u-fs-2x"></span>
						<span class="cerb-toolbars-section--name cerb-u-fs-2x">{$toolbar.name}</span>
						<button type="button" class="cerb-ui-button cerb-ui-button--transparent cerb-peek-trigger cerb-u-ml-auto" title="Add section" data-context="{CerberusContexts::CONTEXT_TOOLBAR_SECTION}" data-context-id="0" data-edit="toolbar:{$toolbar.name}" data-width="80%"><span class="cerb-icons cerb-icon-circle-plus"></span></button>
					</div>
					{if $toolbar.description}<div class="cerb-toolbars-section--desc">{$toolbar.description}</div>{/if}

					{foreach from=$toolbar.sections item=section}
						<div class="cerb-toolbars-sub">
							<div class="cerb-toolbars-sub--head">
								<a href="{$section.url}" class="cerb-toolbars-sub--name cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_TOOLBAR_SECTION}" data-context-id="{$section.id}" data-edit="true" data-width="80%">{$section.name}</a>
								<span class="cerb-toolbars-prio">priority {$section.priority}</span>
								{if $section.is_disabled}<span class="cerb-toolbars-chip cerb-toolbars-chip--disabled">disabled</span>{/if}
								{if $section.workflow_id}<a href="{$section.workflow_url}" class="cerb-toolbars-chip cerb-toolbars-chip--workflow cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_WORKFLOW}" data-context-id="{$section.workflow_id}" title="Managed by workflow: {$section.workflow_name}"><span class="cerb-icons cerb-icon-nodes"></span> workflow</a>{/if}
								<button type="button" class="cerb-ui-button cerb-ui-button--transparent cerb-peek-trigger cerb-u-ml-auto" title="Edit section" data-context="{CerberusContexts::CONTEXT_TOOLBAR_SECTION}" data-context-id="{$section.id}" data-edit="true" data-width="80%"><span class="cerb-icons cerb-icon-edit"></span></button>
							</div>

							{if $section.items}
								<div class="cerb-u-bgg-1 cerb-u-rounded-3 cerb-u-p-4 cerb-u-mt-2{if $section.is_disabled} cerb-u-opacity-50{/if}">
									<ul class="cerb-ui-toolbar cerb-toolbars-menu">{toolbar_menu items=$section.items}</ul>
								</div>
							{else}
								<div class="cerb-toolbars-empty">No items.</div>
							{/if}

							{if $section.missing_items}
								<div class="cerb-toolbars-missing">
									<span class="cerb-icons cerb-icon-alert"></span>
									<span>Unresolved {if count($section.missing_items) == 1}interaction{else}interactions{/if}: {foreach from=$section.missing_items item=miss name=miss}<code>{$miss.label}</code>{if !$smarty.foreach.miss.last}, {/if}{/foreach}</span>
								</div>
							{/if}
						</div>
					{foreachelse}
						<div class="cerb-toolbars-empty">No sections.</div>
					{/foreach}
				</div>
			{/foreach}
		</div><!-- /.cerb-toolbars-content -->
	</div><!-- /.cerb-ui-sidebar-layout -->
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}">
$(function() {
	const $content = $('.cerb-toolbars-content');

	// Inline peeks for sections + resolved automations. Adding/editing a section changes the rendered menus,
	// so reload the page once the peek saves or deletes.
	$content.find('.cerb-peek-trigger, a.cerb-toolbars-peek')
		.cerbPeekTrigger()
		.on('cerb-peek-saved cerb-peek-deleted', function() { window.location.reload(); });

	// Enhance each section's KATA into a cascading menu. Items carry no data-interaction-uri, so CerbUI.Toolbar
	// never wires cerbBotTrigger and a click is a no-op — except a resolved interaction, whose source <li> holds
	// a peek-trigger anchor we click through to open the automation profile.
	if(window.CerbUI && CerbUI.Toolbar) {
		$content.find('ul.cerb-toolbars-menu').each(function() {
			new CerbUI.Toolbar(this, {
				onSelect: function(item, sourceLi) {
					const a = sourceLi && sourceLi.querySelector('a.cerb-toolbars-peek');
					if(a) a.click();
				}
			});
		});
	}

	// Sidebar: type-to-filter rail + scrollspy (mirrors the Automation Events setup page)
	(function() {
		const nav = document.getElementById('toolbarsNav');
		const content = document.querySelector('.cerb-toolbars-content');
		if(!nav || !content || !(window.CerbUI && CerbUI.Sidebar)) return;

		const sectionFor = function(id) {
			return id ? content.querySelector('#' + ((window.CSS && CSS.escape) ? CSS.escape(id) : id)) : null;
		};
		// While true, scrollspy may update the active item. An explicit sidebar click disables it until the
		// smooth-scroll settles, so passing sections don't yank the selection off the clicked target.
		let spyEnabled = true;
		let spyTimer = null;

		const goTo = function(id, push) {
			const el = sectionFor(id);
			if(!el) return;
			spyEnabled = false;
			if(spyTimer) clearTimeout(spyTimer);
			markActive(id);
			el.scrollIntoView({ behavior: 'smooth', block: 'start' });
			if(push) history.replaceState(null, '', '#' + id);
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
			storageKey: 'toolbarsNavCollapsed',
			filterPlaceholder: 'Filter toolbars…',
			onSelect: function(li) { goTo(li.dataset.target, true); return true; }
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

		const hash = (location.hash || '').replace(/^#/, '');
		if(hash && byId.has(hash)) { goTo(hash, false); markActive(hash); }
	})();
});
</script>
