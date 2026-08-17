	<div class="cerb-uiref-component" id="datepicker">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-calendar"></span>Datepicker</div>

		{* Example: auto trigger — calendar opens on focus/click; type to live-navigate; Enter confirms *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Auto trigger &mdash; opens on focus/click; type a date to navigate, <code>Enter</code> confirms, <code>Esc</code> closes</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<input type="text" id="uiref-datepicker-auto" placeholder="YYYY-MM-DD" size="20">
				<span class="cerb-uiref-result" style="margin-left:0.7em;">Selected: <b id="uiref-datepicker-auto-result">&mdash;</b></span>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;input type="text" id="when" placeholder="YYYY-MM-DD"&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>const picker = new CerbUI.DatePicker(el, {
	startOfWeek:  'mon',         // 'mon' | 'sun'  (default 'mon')
	outputFormat: 'YYYY-MM-DD',  // tokens: YYYY YY MMM MM M DDD DD D  (default 'YYYY-MM-DD')
	// parseFormat: 'MM/DD/YYYY', // format of a pre-existing input value (default: outputFormat)
	trigger:      'auto',        // 'auto' (focus/click) | 'button' (toggle button) (default 'auto')
	onSelect: function(date, formatted) { /* date = Date, formatted = string */ },
});

// also fires a DOM event on the input:
el.addEventListener('cerb-ui-datepicker:select', e =&gt; console.log(e.detail.formatted));

// public API: picker.setDate(dateOrString|null); picker.getDate(); picker.destroy();</pre>
			</div>
		</div>

		{* Example: button trigger + a custom output format *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Button trigger + custom format (<code>trigger:'button'</code>, <code>outputFormat:'MMM D, YYYY'</code>)</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<input type="text" id="uiref-datepicker-button" placeholder="Pick a date" size="20">
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>// a toggle button is inserted after the input; the calendar opens only via it
// (use this when the input has its own autocomplete to avoid conflicts)
new CerbUI.DatePicker(el, {
	trigger:      'button',
	outputFormat: 'MMM D, YYYY',   // e.g. "Jun 8, 2026"
});</pre>
			</div>
		</div>

		{* Example: element trigger (icon-only, no input) + lazy per-month indicator pips *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Element trigger + indicator pips (<code>trigger:'element'</code>, <code>loadIndicators</code>) &mdash; bind an existing button/icon; mark days with activity</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<button type="button" class="cerb-ui-button" id="uiref-datepicker-element">
					<span class="cerb-icons cerb-icon-calendar"></span> Jump to date
				</button>
				<span class="cerb-uiref-result" style="margin-left:0.7em;">Selected: <b id="uiref-datepicker-element-result">&mdash;</b></span>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>// Bind to an existing element (no text input is read/written); the popup anchors to it.
// loadIndicators is called per displayed month (cached) and paints .cerb-ui-pip dots:
//   done  = primary color, stash = orange; a day with both shows both side-by-side.
new CerbUI.DatePicker(buttonEl, {
	trigger: 'element',
	loadIndicators: function(year, month) {   // month is 0-based
		return fetch('...?year=' + year + '&amp;month=' + (month + 1))
			.then(r =&gt; r.json())
			.then(j =&gt; ({ done: j.done, stash: j.stash }));   // arrays of day-of-month numbers
	},
	onSelect: function(date, formatted) { /* … */ },
});

// picker.refreshIndicators();  // drop the cache + repaint after data changes</pre>
			</div>
		</div>

		{* Example: FormInput — the free-text natural-language date field (replaces legacy cerbDateInputHelper) *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">FormInput -- free-text date field: type <code>+2 hours</code>, <code>next monday 5pm America/New_York</code>, or an <code>@Calendar</code> token (autocompleted); blur or <code>Enter</code> resolves it server-side. Fires <code>cerb-date-changed</code></div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<input type="text" id="uiref-datepicker-forminput" size="40">
				<span class="cerb-uiref-result" style="margin-left:0.7em;">Resolved: <b id="uiref-datepicker-forminput-result">&mdash;</b></span>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>// Calendar button + calendar/timezone autocomplete + server-side natural-language parse.
new CerbUI.DatePicker.FormInput(el, {
	// submit: function() { /* run on Ctrl+Shift+Enter, after the date resolves */ },
});

// fires `cerb-date-changed` on the input after a successful parse:
el.addEventListener('cerb-date-changed', () =&gt; console.log(el.value));</pre>
			</div>
		</div>
	</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}">
(function() {
	// Datepicker: auto trigger, reporting the selected value
	(function() {
		const el = document.getElementById('uiref-datepicker-auto');
		const out = document.getElementById('uiref-datepicker-auto-result');
		if(el && window.CerbUI && CerbUI.DatePicker) {
			new CerbUI.DatePicker(el, {
				onSelect: function(date, formatted) { if(out) out.textContent = formatted; },
			});
		}
	})();

	// Datepicker: button trigger + custom output format
	(function() {
		const el = document.getElementById('uiref-datepicker-button');
		if(el && window.CerbUI && CerbUI.DatePicker) {
			new CerbUI.DatePicker(el, { trigger: 'button', outputFormat: 'MMM D, YYYY' });
		}
	})();

	// Datepicker: element trigger + lazy indicator pips (synthetic demo data per month)
	(function() {
		const el = document.getElementById('uiref-datepicker-element');
		const out = document.getElementById('uiref-datepicker-element-result');
		if(el && window.CerbUI && CerbUI.DatePicker) {
			new CerbUI.DatePicker(el, {
				trigger: 'element',
				loadIndicators: function(year, month) {
					// Demo: every 3rd day "done", every 5th "stash" (some days carry both).
					const done = [], stash = [];
					const days = new Date(year, month + 1, 0).getDate();
					for(let d = 1; d <= days; d++) {
						if(d % 3 === 0) done.push(d);
						if(d % 5 === 0) stash.push(d);
					}
					return Promise.resolve({ done: done, stash: stash });
				},
				onSelect: function(date, formatted) { if(out) out.textContent = formatted; },
			});
		}
	})();

	// DatePicker.FormInput: free-text natural-language date field
	(function() {
		const el = document.getElementById('uiref-datepicker-forminput');
		const out = document.getElementById('uiref-datepicker-forminput-result');
		if(el && window.CerbUI && CerbUI.DatePicker && CerbUI.DatePicker.FormInput) {
			new CerbUI.DatePicker.FormInput(el);
			el.addEventListener('cerb-date-changed', function() { if(out) out.textContent = el.value || '—'; });
		}
	})();
})();
</script>
