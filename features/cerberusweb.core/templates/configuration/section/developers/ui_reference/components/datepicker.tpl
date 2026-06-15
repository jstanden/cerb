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
})();
</script>
