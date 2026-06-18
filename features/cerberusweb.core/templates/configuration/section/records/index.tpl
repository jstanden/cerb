<style nonce="{DevblocksPlatform::getRequestNonce()}">
#recordsNav { --cerb-ui-sidebar-width: 240px; }
.cerb-records-content { padding-left: 1.75em; }

.cerb-records-add { display:inline-flex; align-items:center; justify-content:center; padding:0.1em 0.35em; border:0; border-radius:5px; background:transparent; color:var(--cerb-color-background-contrast-150); cursor:pointer; }
.cerb-records-add:hover { background:var(--cerb-color-background-contrast-220); color:var(--cerb-color-link); }

.cerb-records-section { border-radius:1em; margin-bottom: 2em; padding: 1.25em 1.5em 1.5em; }

.cerb-records-group { margin-top:1.1em; }
.cerb-records-group--label { text-transform:uppercase; letter-spacing:0.04em; border-bottom:1px solid var(--cerb-color-background-contrast-220); padding-bottom:0.3em; margin-bottom:0.5em; }

/* Two adjacent key tables (records API + quick search), collapsing to one column when narrow */
.cerb-records-two { gap:1em 2em; }
.cerb-records-two--col { flex:1 1 280px; min-width:0; }
.cerb-records-req { color:var(--cerb-color-tag-red); }
.cerb-records-keynote { margin-top:0.15em; line-height:1.35; }

/* Records API table: separator row introducing custom fields + collapsible fieldset tbodies */
.cerb-records-apitable tbody + tbody td { border-top:0; }
.cerb-records-sepcell { padding:1em 0.6em 0.3em !important; border-bottom:0 !important; }
.cerb-records-sepcell .cerb-ui-separator, .cerb-records-fssep { font-size:1em; }
.cerb-records-fsrow td { padding:0 !important; border-bottom:0 !important; }
.cerb-records-fstoggle { display:block; width:100%; padding:0.5em 0.6em; border:0; background:transparent; cursor:pointer; font:inherit; color:inherit; }
.cerb-records-fschevron { transition:transform 0.12s ease; }
.cerb-records-fs.is-open .cerb-records-fschevron { transform:rotate(90deg); }
/* Fieldset field rows start collapsed; the chevron toggles them */
.cerb-records-fs .cerb-records-table--row { display:none; }
.cerb-records-fs.is-open .cerb-records-table--row { display:table-row; }

.cerb-records-table { width:100%; border-collapse:collapse; font-size:0.85em; }
.cerb-records-table td { padding:0.4em 0.6em; border-bottom:1px solid var(--cerb-color-background-contrast-230); vertical-align:middle; }
.cerb-records-table tr:last-child td { border-bottom:0; }
.cerb-records-table--icon { width:1.4em; text-align:center; }
.cerb-records-table--label { color:var(--cerb-color-text); }
.cerb-records-table--link:hover td { background:var(--cerb-color-background-contrast-220); }
.cerb-records-table--link:hover .cerb-records-table--label { color:var(--cerb-color-link); }

.cerb-records-fieldset-acc { margin-top:0.75em; }
</style>

<div class="cerb-ui-page">
	<div class="cerb-ui-header">
		<div>
			<div class="cerb-ui-header--title">{'common.records'|devblocks_translate|capitalize}</div>
			<div class="cerb-ui-header--subtitle">Fields, custom fields, and fieldsets across every record type</div>
		</div>
	</div>

	<div class="cerb-ui-sidebar-layout cerb-u-mt-3 cerb-u-items-start">
		<aside class="cerb-ui-sidebar" id="recordsNav">
			<div class="cerb-ui-sidebar--body">
				<div class="cerb-ui-sidebar--section">
					<div class="cerb-ui-sidebar--label cerb-u-flex cerb-u-items-center cerb-u-justify-between cerb-u-gap-2">
						<span>Record types</span>
						<button type="button" id="btnAddCustomRecord" class="cerb-records-add cerb-peek-trigger" title="Add a custom record type" data-context="{$context_custom_record}" data-context-id="0" data-edit="true"><span class="cerb-icons cerb-icon-circle-plus"></span></button>
					</div>
					<ul>
						{foreach from=$record_types item=rt}
							<li data-target="{$rt.slug}" data-icon="{$rt.icon}">{$rt.name}</li>
						{/foreach}
					</ul>
				</div>
			</div>
		</aside>

		<div class="cerb-ui-sidebar-layout--content cerb-records-content">
			{foreach from=$record_types item=rt}
				{include file="devblocks:cerberusweb.core::configuration/section/records/_record.tpl" rt=$rt}
			{/foreach}
		</div><!-- /.cerb-records-content -->
	</div><!-- /.cerb-ui-sidebar-layout -->
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}">
$(function() {
	const CTX_FIELD = {$context_custom_field|json_encode nofilter};
	const CTX_FIELDSET = {$context_custom_fieldset|json_encode nofilter};
	const $content = $('.cerb-records-content');
	const cssEsc = function(s) { return (window.CSS && CSS.escape) ? CSS.escape(s) : s; };

	const initAccordions = function($scope) {
		if(!(window.CerbUI && CerbUI.Accordion)) return;
		$scope.find('.cerb-records-fieldset-acc').each(function() {
			new CerbUI.Accordion(this, { active: -1, collapsible: true });
		});
	};

	// (Re)bind the peeks + accordions within a section's freshly-rendered body
	const bindSection = function($section) {
		$section.find('.cerb-peek-trigger').cerbPeekTrigger();
		initAccordions($section);
	};

	// Re-render just one record type's body in place — no full page reload
	const refreshSection = function(contextId) {
		const $section = $('.cerb-records-section[data-context="' + cssEsc(contextId) + '"]');
		if(!$section.length) return;
		genericAjaxGet($section, 'c=config&a=invoke&module=records&action=renderSectionBody&context=' + encodeURIComponent(contextId), function() {
			bindSection($section);
		});
	};

	// Initial bind: inline sections + the sidebar add-record button
	bindSection($content);
	$('#recordsNav').find('.cerb-peek-trigger').cerbPeekTrigger();

	// A new custom record type changes the rail too, so it warrants a full reload
	$('#btnAddCustomRecord').on('cerb-peek-saved', function(e) {
		e.stopPropagation();
		document.location.reload();
	});

	// Editing a custom record type (name/icon/uri) changes the rail too — reload, and stop the
	// section-refresh handler below from also firing
	$content.on('cerb-peek-saved', '.cerb-records-edit-type', function(e) {
		e.stopPropagation();
		document.location.reload();
	});

	// Editing/deleting an existing field: cerbPeekTrigger rebroadcasts cerb-peek-* up through its section
	$content.on('cerb-peek-saved cerb-peek-deleted', '.cerb-records-section', function() {
		refreshSection(this.getAttribute('data-context'));
	});

	// Open a create peek, preset its record-type context, and refresh that section on save
	const openCreatePeek = function(peekContext, forContext) {
		const args = 'c=internal&a=invoke&module=records&action=showPeekPopup'
			+ '&context=' + encodeURIComponent(peekContext) + '&context_id=0&edit=1';
		const $popup = genericAjaxPopup('peek', args, null, false, '60%');
		$popup.one('popup_open', function() {
			$(this).find('select[name=context]').val(forContext).trigger('change');
		});
		$popup.on('peek_saved peek_deleted', function() { refreshSection(forContext); });
	};

	// Collapsible fieldset tbodies in the Records API table (chevron toggles the field rows)
	$content.on('click', '.cerb-records-fstoggle', function(e) {
		e.stopPropagation();
		const tbody = this.closest('tbody');
		if(tbody) tbody.classList.toggle('is-open');
	});

	$content.on('click', '[data-cerb-add-field]', function(e) {
		e.stopPropagation();
		openCreatePeek(CTX_FIELD, this.getAttribute('data-context'));
	});

	$content.on('click', '[data-cerb-add-fieldset]', function(e) {
		e.stopPropagation();
		openCreatePeek(CTX_FIELDSET, this.getAttribute('data-context'));
	});

	// Sidebar: type-to-filter rail + scrollspy (mirrors the UI Reference page)
	(function() {
		const nav = document.getElementById('recordsNav');
		const content = document.querySelector('.cerb-records-content');
		if(!nav || !content || !(window.CerbUI && CerbUI.Sidebar)) return;

		const sectionFor = function(id) {
			return id ? content.querySelector('#' + ((window.CSS && CSS.escape) ? CSS.escape(id) : id)) : null;
		};
		const goTo = function(id, push) {
			const el = sectionFor(id);
			if(!el) return;
			el.scrollIntoView({ behavior: 'smooth', block: 'start' });
			if(push) history.replaceState(null, '', '#' + id);
		};

		const sb = new CerbUI.Sidebar(nav, {
			fullHeight: true,
			filter: true,
			collapseTo: 'icons',
			storageKey: 'recordsNavCollapsed',
			filterPlaceholder: 'Filter record types…',
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
