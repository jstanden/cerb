	<div class="cerb-uiref-component" id="dialog">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-window-top"></span>Dialog</div>

		{* Example: classic Cerb title bar — draggable, resizable, Esc closes *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Classic title bar (<code>header:'bar'</code>) &mdash; drag the bar, resize from the edges, <code>Esc</code> closes the topmost</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<button type="button" class="cerb-ui-button" id="uiref-dialog-classic-btn">Open ticket dialog</button>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;!-- the content element; CerbUI.Dialog moves it into a floating shell --&gt;
&lt;div id="dlg"&gt;…your content…&lt;/div&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>const dlg = new CerbUI.Dialog(el, {
	title:      'Ticket',     // shown in the bar (header:'bar')
	header:     'bar',        // 'bar' | 'floating' | 'none'  (default 'bar')
	draggable:  true,         // default true
	resizable:  true,         // default true
	closable:   true,         // show the × button (default true)
	minimizable: true,        // show the minimize caret (default: true only for 'bar')
	modal:      false,        // dim the page behind a backdrop (default false)
	width:      480,          // px (default 400); minWidth 200, minHeight 80
	// position: { x: 100, y: 80 },  // explicit; else centered (or namespace-inherited)
	namespace:  'ticket',     // siblings share position + close each other (default: per-instance)
	fixed:      false,        // position:fixed instead of absolute (default false)
	closeOnEscape: true,      // topmost dialog only (default true)
	closeWarnOnUnsavedChanges: false, // warn before closing once a control is actually changed (default false)
	// dragHandle: '[data-cerb-ui-dialog-drag]', // drag region for header:'floating'|'none'
	onOpen:    function() {},
	onClose:   function() { /* return false to veto the close */ },
	onMinimize: function(min) {},
	onDragged:  function(x, y) {},
	onResized:  function(w, h) {},
});
dlg.open();   // also: dlg.close(); dlg.isOpen(); dlg.setTitle('…'); dlg.isDirty(); dlg.markClean(); dlg.destroy();

// also fires DOM events on the content element:
el.addEventListener('cerb-ui-dialog:open',  () =&gt; {});
el.addEventListener('cerb-ui-dialog:close', () =&gt; {});
// look an instance up later: CerbUI.Dialog.from(el)</pre>
			</div>
		</div>

		{* Example: chromeless / floating — content owns its header (cerb-ui-header), only a floating × *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Chromeless (<code>header:'floating'</code>) &mdash; no blue bar; the content supplies its own <a href="#header">cerb-ui-header</a>; a × floats top-right</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<button type="button" class="cerb-ui-button" id="uiref-dialog-floating-btn">Open record dialog</button>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;!-- mark a region with data-cerb-ui-dialog-drag to keep it draggable --&gt;
&lt;div id="dlg"&gt;
	&lt;div class="cerb-ui-header" data-cerb-ui-dialog-drag&gt;
		&lt;div class="cerb-ui-header--title-sm"&gt;Helio Inc&lt;/div&gt;
		&lt;div class="cerb-ui-header--right"&gt;&lt;button class="cerb-ui-button"&gt;Edit&lt;/button&gt;&lt;/div&gt;
	&lt;/div&gt;
	…body…
&lt;/div&gt;

new CerbUI.Dialog(el, { header: 'floating', title: 'Helio Inc' }); // no titlebar, but title labels it in the minimize tray</pre>
			</div>
		</div>

		{* Example: modal with a footer button bar (cerb-ui-header--right) *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Modal + footer button bar (<code>modal:true</code>) &mdash; a backdrop dims the page; actions use <code>cerb-ui-header--right</code></div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<button type="button" class="cerb-ui-button" id="uiref-dialog-modal-btn">Open modal form</button>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>new CerbUI.Dialog(el, { title: 'Edit snippet', modal: true, width: 460 });

&lt;!-- a Cancel button can close its own dialog --&gt;
&lt;button class="cerb-ui-button" onclick="CerbUI.Dialog.from(el).close()"&gt;Cancel&lt;/button&gt;</pre>
			</div>
		</div>

		{* Example: minimize / drag / resize, reporting callbacks *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Minimize, drag &amp; resize &mdash; the caret docks the window into the top-right tray (a window icon + count); click the tray to restore, or pick <b>Close all</b> when several are docked. Open a few and minimize them; <code>onMinimize</code>/<code>onResized</code> fire</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<button type="button" class="cerb-ui-button" id="uiref-dialog-resize-btn">Open resizable dialog</button>
				<span class="cerb-uiref-result" style="margin-left:0.7em;">Last: <b id="uiref-dialog-resize-result">&mdash;</b></span>
			</div>
		</div>

		{* Example: namespace / position reuse *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Namespace &amp; position reuse (<code>namespace</code>) &mdash; reopening reuses the last position; opening a sibling closes the other</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<button type="button" class="cerb-ui-button" id="uiref-dialog-ns-a-btn">Open “A”</button>
				<button type="button" class="cerb-ui-button" id="uiref-dialog-ns-b-btn">Open “B”</button>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>// both share a namespace -> moving one and reopening reuses its position;
// opening the sibling closes the first (one open per namespace)
new CerbUI.Dialog(elA, { title: 'A', namespace: 'demo' });
new CerbUI.Dialog(elB, { title: 'B', namespace: 'demo' });</pre>
			</div>
		</div>

		{* Example: simple alert — not draggable/resizable *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Simple alert (<code>draggable:false, resizable:false</code>)</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<button type="button" class="cerb-ui-button" id="uiref-dialog-alert-btn">Show alert</button>
			</div>
		</div>

		{* Example: unsaved-changes guard — warn before closing once a control is actually changed *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Unsaved-changes guard (<code>closeWarnOnUnsavedChanges</code>) &mdash; warns before closing <b>only after</b> a tracked control actually changes (typing, a toggle, a menu); merely having inputs never nags. Fires on every close path: the ×, <b>Esc</b>, and the tray's <b>Close all</b>. Add <code>data-cerb-ui-dialog-no-dirty</code> to a control (or any ancestor) to exclude it. Left dirty &amp; minimized, it also guards a page reload / back-forward / close (native browser prompt)</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<button type="button" class="cerb-ui-button" id="uiref-dialog-dirty-btn">Open edit form</button>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}new CerbUI.Dialog(el, { title: 'Edit ticket', closeWarnOnUnsavedChanges: true });

&lt;!-- exclude an already-persisted control (or a whole region) from dirty-tracking --&gt;
&lt;input type="search" data-cerb-ui-dialog-no-dirty&gt;

// a successful save should clear the flag so closing doesn't re-warn:
function onSaved(el) {
	const dlg = CerbUI.Dialog.from(el);
	dlg.markClean();   // also: dlg.isDirty()
	dlg.close();
}{/literal}</pre>
			</div>
		</div>

		{* Example: fromAjax — fetch HTML into a popup (the genericAjaxPopup replacement) *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">AJAX content (<code>CerbUI.Dialog.fromAjax</code>) &mdash; spinner while loading, then the fetched HTML; response <code>&lt;script&gt;</code> runs under the nonce. A tall dialog grows and the <b>page</b> scrolls to it; pass <code>scrollBody:true</code> to cap it to the viewport and scroll the <b>body</b> instead</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<button type="button" class="cerb-ui-button" id="uiref-dialog-ajax-btn">Load help popup (page scroll)</button>
				<button type="button" class="cerb-ui-button" id="uiref-dialog-ajax-scroll-btn">Load help popup (scrollBody)</button>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}// string ⇒ GET (ajax args); FormData ⇒ POST. opts are the constructor options + onLoad.
CerbUI.Dialog.fromAjax('c=profiles&amp;a=invoke&amp;module=snippet&amp;action=helpPopup', {
	title: 'Snippet help', // titlebar text
	// width: 600,         // omit → 75% of the viewport, capped at 1100 (mobile: always 95%)
	// scrollBody: true,   // cap to the viewport + scroll the body (default: grow + page scroll)
	// namespace: 'peek',  // siblings share position + auto-close each other (supersedes the old layer/reuse)
	// onLoad: function(content, html) { /* runs after the HTML is injected */ },
});

// POST a form's data instead of GET args:
// CerbUI.Dialog.fromAjax(new FormData(document.getElementById('myForm')), { title: 'Edit' });

// loaded content finds its own dialog from any element inside it (replaces genericAjaxPopupFind):
// CerbUI.Dialog.from(thisFormEl).close();{/literal}</pre>
			</div>
		</div>

		{* Hidden templates — CerbUI.Dialog relocates each into a floating shell (and restores here on destroy) *}
		<div id="uiref-dialog-templates" style="display:none;">
			<div id="uiref-dialog-classic-content" style="line-height:1.5;">
				<p>This is a classic Cerb dialog with the accent title bar &mdash; the look you know.</p>
				<p>Drag it by the bar, resize from any edge or corner, minimize with the caret (it docks to the top-right tray), or press <b>Esc</b> to close.</p>
			</div>

			<div id="uiref-dialog-floating-content" style="line-height:1.5;">
				<div class="cerb-ui-header cerb-ui-header--center" data-cerb-ui-dialog-drag style="cursor:move;">
					<div class="cerb-ui-header--title-sm"><span class="cerb-icons cerb-icon-building-apartments"></span>Helio Inc</div>
					<div class="cerb-ui-header--right">
						<span class="cerb-ui-chip">Growth</span>
						<button type="button" class="cerb-ui-button">Edit</button>
					</div>
				</div>
				<p>No blue bar here &mdash; the content provides its own header via <code>cerb-ui-header</code>, and only a small × floats in the top-right corner. Drag from the header (marked <code>data-cerb-ui-dialog-drag</code>).</p>
			</div>

			<div id="uiref-dialog-modal-content" style="line-height:1.5;">
				<p style="margin-top:0;">Editing this record is blocked behind a modal backdrop until you act.</p>
				<input type="text" value="My snippet" style="width:100%; box-sizing:border-box; margin-bottom:1em;">
				<div class="cerb-ui-header cerb-ui-header--tight" style="margin-bottom:0;">
					<div></div>
					<div class="cerb-ui-header--right">
						<button type="button" class="cerb-ui-button" id="uiref-dialog-modal-cancel">Cancel</button>
						<button type="button" class="cerb-ui-button">Save</button>
					</div>
				</div>
			</div>

			<div id="uiref-dialog-resize-content" style="line-height:1.5;">
				<p>Drag the title bar to move me. Grab an edge or corner to resize (I won't shrink below the minimums). Use the caret to minimize me to the top-right tray, then click the tray to restore me.</p>
			</div>

			<div id="uiref-dialog-ns-a-content" style="line-height:1.5;">
				<p>Dialog <b>A</b>. Move me somewhere, close me, and reopen &mdash; I'll come back where you left me. Open <b>B</b> and I'll step aside.</p>
			</div>
			<div id="uiref-dialog-ns-b-content" style="line-height:1.5;">
				<p>Dialog <b>B</b>, sharing A's namespace. Only one of us is open at a time, and we share a position.</p>
			</div>

			<div id="uiref-dialog-alert-content" style="line-height:1.5;">
				<p style="margin-top:0;">Your changes have been saved.</p>
				<div class="cerb-ui-header cerb-ui-header--tight" style="margin-bottom:0;">
					<div></div>
					<div class="cerb-ui-header--right">
						<button type="button" class="cerb-ui-button" id="uiref-dialog-alert-ok">OK</button>
					</div>
				</div>
			</div>

			<div id="uiref-dialog-dirty-content" style="line-height:1.5;">
				<p style="margin-top:0;">Change any field, then try to close (the ×, <b>Esc</b>, or minimize then <b>Close all</b>) &mdash; you'll be asked to confirm. Close it untouched and it just closes.</p>
				<form class="cerb-ui-form" style="max-width:360px;">
					<div class="cerb-ui-form--field">
						<label class="cerb-ui-form--label">Subject</label>
						<input type="text" placeholder="Type to make me dirty…">
					</div>
					<div class="cerb-ui-form--field">
						<label class="cerb-ui-form--label">Status</label>
						<select id="uiref-dialog-dirty-select">
							<option>Open</option>
							<option>Waiting</option>
							<option>Closed</option>
						</select>
					</div>
					<div class="cerb-ui-form--field">
						<label class="cerb-ui-form--label">Notify subscribers</label>
						<label class="cerb-ui-toggle" id="uiref-dialog-dirty-toggle"><input type="checkbox"><span class="cerb-ui-toggle--slider"></span></label>
					</div>
					<div class="cerb-ui-form--field">
						<label class="cerb-ui-form--label">Search <span class="cerb-ui-form--hint">excluded via data-cerb-ui-dialog-no-dirty</span></label>
						<input type="search" data-cerb-ui-dialog-no-dirty placeholder="Already persisted — won't warn">
					</div>
				</form>
				<div class="cerb-ui-header cerb-ui-header--tight" style="margin-bottom:0;">
					<div></div>
					<div class="cerb-ui-header--right">
						<button type="button" class="cerb-ui-button" id="uiref-dialog-dirty-save">Save</button>
					</div>
				</div>
			</div>
		</div>
	</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}">
(function() {
	// Dialog: build each example once and open it from its trigger button
	(function() {
		if(!(window.CerbUI && CerbUI.Dialog)) return;

		const wire = function(btnId, contentId, opts) {
			const btn = document.getElementById(btnId);
			const content = document.getElementById(contentId);
			if(!btn || !content) return null;
			const dlg = new CerbUI.Dialog(content, opts);
			btn.addEventListener('click', function() { dlg.open(); });
			return dlg;
		};

		wire('uiref-dialog-classic-btn', 'uiref-dialog-classic-content', { title: 'Ticket', width: 480 });
		wire('uiref-dialog-floating-btn', 'uiref-dialog-floating-content', { header: 'floating', title: 'Helio Inc' }); // default width; title = tray label
		wire('uiref-dialog-modal-btn', 'uiref-dialog-modal-content', { title: 'Edit snippet', modal: true, width: 460 });

		const resizeOut = document.getElementById('uiref-dialog-resize-result');
		wire('uiref-dialog-resize-btn', 'uiref-dialog-resize-content', {
			title: 'Resizable',
			width: 420,
			onMinimize: function(min) { if(resizeOut) resizeOut.textContent = min ? 'minimized' : 'restored'; },
			onResized: function(w, h) { if(resizeOut) resizeOut.textContent = 'resized to ' + Math.round(w) + '×' + (h ? Math.round(h) : 'auto'); },
		});

		wire('uiref-dialog-ns-a-btn', 'uiref-dialog-ns-a-content', { title: 'A', namespace: 'uiref-dlg-ns', width: 360 });
		wire('uiref-dialog-ns-b-btn', 'uiref-dialog-ns-b-content', { title: 'B', namespace: 'uiref-dlg-ns', width: 360 });

		wire('uiref-dialog-alert-btn', 'uiref-dialog-alert-content', { title: 'Heads up', draggable: false, resizable: false, width: 360 });

		// Unsaved-changes guard: a form dialog that warns once a tracked control is actually changed
		const dirtyDlg = wire('uiref-dialog-dirty-btn', 'uiref-dialog-dirty-content', { title: 'Edit ticket', width: 420, closeWarnOnUnsavedChanges: true });
		if(dirtyDlg) {
			const dirtySelect = document.getElementById('uiref-dialog-dirty-select');
			if(dirtySelect && CerbUI.SelectMenu) new CerbUI.SelectMenu(dirtySelect);
			const dirtyToggle = document.getElementById('uiref-dialog-dirty-toggle');
			if(dirtyToggle && CerbUI.Toggle) new CerbUI.Toggle(dirtyToggle);
			// Save clears the dirty flag before closing, so a successful save never trips the warning
			const dirtySave = document.getElementById('uiref-dialog-dirty-save');
			if(dirtySave) dirtySave.addEventListener('click', function() { dirtyDlg.markClean(); dirtyDlg.close(); });
		}

		// CerbUI.Confirm: a forced-modal confirmation with custom button labels
		const confirmBtn = document.getElementById('uiref-dialog-confirm-btn');
		const confirmOut = document.getElementById('uiref-dialog-confirm-result');
		if(confirmBtn && CerbUI.Confirm) confirmBtn.addEventListener('click', function() {
			CerbUI.Confirm.open({
				title: 'Delete snippet',
				body: "This can't be undone.",
				confirmText: 'Delete',
				cancelText: 'Keep',
				onConfirm: function() { if(confirmOut) confirmOut.textContent = 'confirmed'; },
				onCancel: function() { if(confirmOut) confirmOut.textContent = 'cancelled'; },
			});
		});

		// Footer buttons that close their own dialog
		['uiref-dialog-modal-cancel', 'uiref-dialog-alert-ok'].forEach(function(id) {
			const b = document.getElementById(id);
			if(b) b.addEventListener('click', function() {
				const content = b.closest('[id$="-content"]');
				const dlg = content && CerbUI.Dialog.from(content);
				if(dlg) dlg.close();
			});
		});

		// fromAjax: build the dialog procedurally and load real HTML (the snippet help popup) into it
		const ajaxBtn = document.getElementById('uiref-dialog-ajax-btn');
		if(ajaxBtn) ajaxBtn.addEventListener('click', function() {
			CerbUI.Dialog.fromAjax('c=profiles&a=invoke&module=snippet&action=helpPopup', { title: 'Snippet help' }); // default width
		});
		// same content, but capped to the viewport with an internally-scrolling body
		const ajaxScrollBtn = document.getElementById('uiref-dialog-ajax-scroll-btn');
		if(ajaxScrollBtn) ajaxScrollBtn.addEventListener('click', function() {
			CerbUI.Dialog.fromAjax('c=profiles&a=invoke&module=snippet&action=helpPopup', { title: 'Snippet help', scrollBody: true }); // default width
		});
	})();
})();
</script>
