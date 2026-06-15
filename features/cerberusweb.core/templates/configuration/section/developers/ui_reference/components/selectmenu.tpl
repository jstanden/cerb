	<div class="cerb-uiref-component" id="selectmenu">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-chevron-down"></span>SelectMenu</div>

		{* Example: a searchable <select> — open and type to filter (e.g. "Los") *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Searchable select &mdash; enhances a native <code>&lt;select&gt;</code>; open and type to filter (try <code>Los</code>); reuses CerbUI.Menu</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<select id="uiref-selectmenu-tz">
					<option value="">Select a timezone&hellip;</option>
					<option value="America/Anchorage">America/Anchorage</option>
					<option value="America/Chicago">America/Chicago</option>
					<option value="America/Denver">America/Denver</option>
					<option value="America/Los_Angeles">America/Los_Angeles</option>
					<option value="America/New_York">America/New_York</option>
					<option value="America/Phoenix">America/Phoenix</option>
					<option value="America/Sao_Paulo">America/Sao_Paulo</option>
					<option value="Asia/Kolkata">Asia/Kolkata</option>
					<option value="Asia/Shanghai">Asia/Shanghai</option>
					<option value="Asia/Tokyo">Asia/Tokyo</option>
					<option value="Australia/Sydney">Australia/Sydney</option>
					<option value="Europe/Berlin">Europe/Berlin</option>
					<option value="Europe/London">Europe/London</option>
					<option value="Europe/Paris">Europe/Paris</option>
					<option value="Pacific/Auckland">Pacific/Auckland</option>
					<option value="UTC">UTC</option>
				</select>
				<span class="cerb-uiref-result" style="margin-left:0.7em;">Value: <b id="uiref-selectmenu-tz-result">&mdash;</b></span>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;!-- a normal &lt;select&gt; — it stays in the DOM (hidden) and still submits --&gt;
&lt;select id="tz"&gt;
    &lt;option value=""&gt;Select a timezone…&lt;/option&gt;
    &lt;option value="America/Los_Angeles"&gt;America/Los_Angeles&lt;/option&gt;
    &lt;option value="UTC"&gt;UTC&lt;/option&gt;
&lt;/select&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>const sm = new CerbUI.SelectMenu(el, {
	placeholder: 'Select a timezone…', // shown when the empty option is selected
	filter:      true,                 // type-to-filter (default true; forwarded to CerbUI.Menu)
	onSelect:  function(value, text, option) { /* fired on choose; the &lt;select&gt; also gets a change event */ },
	// onRender: function(li, option) { /* advanced: build custom item markup */ },
});

// Long lists (timezones, etc.) virtualize automatically via CerbUI.Menu.
// API: sm.getValue(); sm.setValue('UTC'); sm.open(); sm.close(); sm.destroy();</pre>
			</div>
		</div>

		{* Example: per-option icons via data-cerb-ui-icon *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Per-option icons (<code>data-cerb-ui-icon</code> on each <code>&lt;option&gt;</code>)</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<select id="uiref-selectmenu-icons">
					<option value="open" data-cerb-ui-icon="inbox" selected>Open</option>
					<option value="waiting" data-cerb-ui-icon="clock">Waiting</option>
					<option value="closed" data-cerb-ui-icon="check">Closed</option>
					<option value="deleted" data-cerb-ui-icon="trash" disabled>Deleted</option>
				</select>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;!-- data-cerb-ui-icon="&lt;name&gt;" renders a leading cerb-icons glyph --&gt;
&lt;select id="status"&gt;
    &lt;option value="open" data-cerb-ui-icon="inbox"&gt;Open&lt;/option&gt;
    &lt;option value="closed" data-cerb-ui-icon="check"&gt;Closed&lt;/option&gt;
    &lt;option value="deleted" data-cerb-ui-icon="trash" disabled&gt;Deleted&lt;/option&gt;
&lt;/select&gt;

&lt;script&gt;new CerbUI.SelectMenu(document.getElementById('status'));&lt;/script&gt;</pre>
			</div>
		</div>

		{* Example: custom renderer — a CerbUI.Pip presence dot before each label (shown in the trigger too) *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Custom renderer (<code>onRender</code>) &mdash; a <a href="#pip">Pip</a> presence dot per option; the selected one shows in the trigger</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<select id="uiref-selectmenu-presence">
					<option value="available" selected>Available</option>
					<option value="busy">Busy</option>
					<option value="dnd">Do Not Disturb</option>
					<option value="invisible">Invisible</option>
				</select>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}// onRender runs for each menu item AND for the selected option in the trigger.
// `el` already contains the label — prepend your adornment before el.firstChild.
const COLORS = { available: 'green', busy: 'orange', dnd: 'red', invisible: 'gray' };

new CerbUI.SelectMenu(el, {
	onRender: function(el, option) {
		const pip = document.createElement('span');
		pip.className = 'cerb-ui-pip';
		pip.style.color = 'var(--cerb-color-tag-' + (COLORS[option.value] || 'gray') + ')';
		pip.style.marginRight = '0.5em';
		el.insertBefore(pip, el.firstChild);
	},
});{/literal}</pre>
			</div>
		</div>
	</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}">
(function() {
	// SelectMenu: searchable timezone select, reporting the chosen value
	(function() {
		const el = document.getElementById('uiref-selectmenu-tz');
		const out = document.getElementById('uiref-selectmenu-tz-result');
		if(el && window.CerbUI && CerbUI.SelectMenu) {
			new CerbUI.SelectMenu(el, {
				placeholder: 'Select a timezone…',
				onSelect: function(value) { if(out) out.textContent = value; },
			});
		}
	})();

	// SelectMenu: per-option icons via data-cerb-ui-icon
	(function() {
		const el = document.getElementById('uiref-selectmenu-icons');
		if(el && window.CerbUI && CerbUI.SelectMenu) {
			new CerbUI.SelectMenu(el);
		}
	})();

	// SelectMenu: custom renderer — a Pip presence dot per option (and in the trigger)
	(function() {
		const el = document.getElementById('uiref-selectmenu-presence');
		if(el && window.CerbUI && CerbUI.SelectMenu) {
			const COLORS = { available: 'green', busy: 'orange', dnd: 'red', invisible: 'gray' };
			new CerbUI.SelectMenu(el, {
				onRender: function(target, option) {
					const pip = document.createElement('span');
					pip.className = 'cerb-ui-pip';
					pip.style.color = 'var(--cerb-color-tag-' + (COLORS[option.value] || 'gray') + ')';
					pip.style.marginRight = '0.5em';
					target.insertBefore(pip, target.firstChild);
				},
			});
		}
	})();
})();
</script>
