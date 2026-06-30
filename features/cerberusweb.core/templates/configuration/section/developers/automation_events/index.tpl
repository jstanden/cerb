<style nonce="{DevblocksPlatform::getRequestNonce()}">
.cerb-flow-section--name { color:var(--cerb-color-text); }

/* Graph host: a framed canvas the read-only CerbUI.NodeGraph fills */
.cerb-flow-graph { height:85vh; min-height:60vh; max-height:90vh; margin-top:1em; border:1px solid var(--cerb-color-background-contrast-225); border-radius:0.75em; overflow:hidden; }

/* NodeGraph chrome (.cerb-ui-node-graph*) + .cerb-ui-node--clickable now live in core cerb-ui SCSS. */
</style>

<div class="cerb-ui-page">
	<div class="cerb-ui-header">
		<div>
			<div class="cerb-ui-header--title">Automation Events</div>
			<div class="cerb-ui-header--subtitle">Events, listeners, and automations grouped into execution-order flows</div>
		</div>
	</div>

	<div class="cerb-ui-sidebar-layout cerb-u-mt-3 cerb-u-items-start">
		<aside class="cerb-ui-sidebar" id="flowsNav" style="--cerb-ui-sidebar-width:260px;">
			<div class="cerb-ui-sidebar--body">
				<div class="cerb-ui-sidebar--section">
					<div class="cerb-ui-sidebar--label">Flows</div>
					<ul>
						{foreach from=$flows item=flow}
							<li data-target="{$flow.slug}" data-icon="{$flow.icon}">{$flow.label}</li>
						{/foreach}
					</ul>
				</div>
			</div>
		</aside>

		<div class="cerb-ui-sidebar-layout--content cerb-flows-content">
			{foreach from=$flows item=flow}
				<div class="cerb-flow-section cerb-ui-section-card cerb-u-bgg-3" id="{$flow.slug}">
					<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2 cerb-u-flex-wrap">
						<span class="cerb-icons cerb-icon-{$flow.icon} cerb-u-fs-2x"></span>
						<span class="cerb-flow-section--name cerb-u-fs-2x">{$flow.label}</span>
					</div>
					{if $flow.description}<div class="cerb-flow-section--desc cerb-u-text-muted cerb-u-mt-1">{$flow.description}</div>{/if}
					<div class="cerb-flow-graph" data-graph="{$flow.graph_json nofilter}"></div>
				</div>
			{/foreach}
		</div><!-- /.cerb-flows-content -->
	</div><!-- /.cerb-ui-sidebar-layout -->
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}">
{literal}
$(function() {
	const NODE_TYPES = [
		{ id:'start',        label:'Start',        icon:'flag',        headerColor:'#48bb78', category:'Flow',         ports:'center', start:true },
		{ id:'event',        label:'Event',        icon:'zap',         headerColor:'#3182ce', category:'Event',        ports:'center' },
		{ id:'listener',     label:'Listener',     icon:'list',        headerColor:'#0987a0', category:'Listener',     ports:'center', container:true },
		{ id:'automation',   label:'Automation',   icon:'bot',         headerColor:'#805ad5', category:'Automation',   terminal:true },
		{ id:'legacy_event', label:'Legacy event', icon:'zap',         headerColor:'#dd6b20', category:'Legacy event', ports:'center', container:true },
		{ id:'behavior',     label:'Behavior',     icon:'bot-message', headerColor:'#b7791f', category:'Behavior',     terminal:true },
		{ id:'trigger',      label:'Trigger',      icon:'gear',        headerColor:'#3182ce', category:'Trigger',      ports:'center', container:true },
		{ id:'structural',   label:'Step',         icon:'cube',        headerColor:'#718096', category:'Pipeline',     ports:'center' }
	];

	// Double-click a record node → open its profile peek (mirrors cerbPeekTrigger, but on dblclick not click).
	const openPeek = function(nodeData) {
		const d = (nodeData && nodeData.data) || {};
		if(!d.context || !d.contextId || typeof genericAjaxPopup !== 'function') return;
		const url = 'c=internal&a=invoke&module=records&action=showPeekPopup'
			+ '&context=' + encodeURIComponent(d.context) + '&context_id=' + encodeURIComponent(d.contextId);
		genericAjaxPopup('peek_' + d.context + '_' + d.contextId, url, null, false, '50%');
	};

	// A toolbar button (one or more cerb-icons); a 'flag' icon is tinted green.
	const toolBtn = function(bar, icons, title, onClick) {
		const b = document.createElement('button');
		b.type = 'button';
		b.className = 'cerb-ui-button cerb-ui-node-graph--tool' + (icons.length > 1 ? ' cerb-ui-node-graph--lanebtn' : '');
		b.title = title;
		icons.forEach(function(ic) {
			const s = document.createElement('span');
			s.className = 'cerb-icons cerb-icon-' + ic;
			if(ic === 'flag') s.style.color = 'var(--cerb-color-tag-green)';
			b.appendChild(s);
		});
		b.addEventListener('click', onClick);
		bar.appendChild(b);
		return b;
	};

	// Build the graph's toolbar (NodeGraph is chrome-less — the page owns the toolbar via its API), inserted above
	// the canvas inside the framed host. Fit / zoom, plus a single green-flag button that cycles through the flow's
	// lanes (focusing each at 100% zoom), with an "i/n" count when there's more than one.
	const buildGraphToolbar = function(host, g) {
		const bar = document.createElement('div');
		bar.className = 'cerb-ui-node-graph--toolbar';

		toolBtn(bar, ['move'], 'Fit', function() { g.fit(); });
		toolBtn(bar, ['zoom-in'], 'Zoom in', function() { if(g.canvas) g.canvas.zoomIn(); });
		toolBtn(bar, ['zoom-out'], 'Zoom out', function() { if(g.canvas) g.canvas.zoomOut(); });

		const laneCount = g.getLaneCount();
		if(laneCount >= 1) {
			const sep = document.createElement('span');
			sep.className = 'cerb-ui-node-graph--toolsep';
			bar.appendChild(sep);

			const btn = document.createElement('button');
			btn.type = 'button';
			btn.className = 'cerb-ui-button cerb-ui-node-graph--tool cerb-ui-node-graph--laneflag';
			btn.title = laneCount > 1 ? 'Next flow' : 'Focus flow';
			const flagIcon = document.createElement('span');
			flagIcon.className = 'cerb-icons cerb-icon-flag';
			flagIcon.style.color = 'var(--cerb-color-tag-green)';
			btn.appendChild(flagIcon);
			let label = null;
			if(laneCount > 1) {
				label = document.createElement('span');
				label.className = 'cerb-ui-node-graph--lanecount';
				btn.appendChild(label);
			}
			bar.appendChild(btn);

			let laneIndex = 0;
			const focus = function() {
				g.focusLane(laneIndex);
				if(label) label.textContent = (laneIndex + 1) + '/' + laneCount;
			};
			// Endlessly cycle to the next lane (single lane just re-focuses).
			btn.addEventListener('click', function() { laneIndex = (laneIndex + 1) % laneCount; focus(); });
			focus();   // initial frame: lane 1 at 100%
		}

		host.insertBefore(bar, host.firstChild);
	};

	const inited = new WeakSet();
	const initGraph = function(host) {
		if(inited.has(host) || !(window.CerbUI && CerbUI.NodeGraph)) return;
		inited.add(host);
		let graph;
		try { graph = JSON.parse(host.getAttribute('data-graph') || '{}'); }
		catch(e) { return; }
		const g = new CerbUI.NodeGraph(host, { nodeTypes: NODE_TYPES, minimap: true, onNodeOpen: function(n) { openPeek(n); } });
		g.loadJSON(graph);
		buildGraphToolbar(host, g);
	};

	// Lazy-init each flow graph as it nears the viewport — eagerly building all 20 canvases would be heavy.
	const hosts = Array.prototype.slice.call(document.querySelectorAll('.cerb-flow-graph'));
	if('IntersectionObserver' in window) {
		const lazyIo = new IntersectionObserver(function(entries) {
			entries.forEach(function(e) { if(e.isIntersecting) { initGraph(e.target); lazyIo.unobserve(e.target); } });
		}, { rootMargin: '300px 0px' });
		hosts.forEach(function(h) { lazyIo.observe(h); });
	} else {
		hosts.forEach(initGraph);
	}

	// Sidebar: type-to-filter rail + scrollspy (mirrors the Records setup page)
	(function() {
		const nav = document.getElementById('flowsNav');
		const content = document.querySelector('.cerb-flows-content');
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
			storageKey: 'automationFlowsNavCollapsed',
			filterPlaceholder: 'Filter flows…',
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
{/literal}
</script>
