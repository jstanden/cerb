	<div class="cerb-uiref-component" id="spinner">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-spinner"></span>Spinner</div>

		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<svg class="cerb-ui-spinner cerb-u-mx-3" viewBox="0 0 100 100"><circle cx="50" cy="50" r="45"></circle></svg>
				<svg class="cerb-ui-spinner cerb-ui-spinner--arc cerb-u-mx-3" viewBox="0 0 100 100"><circle cx="50" cy="50" r="45"></circle></svg>
				<svg class="cerb-ui-spinner cerb-ui-spinner--dots cerb-u-mx-3" viewBox="0 0 120 40"><circle cx="20" cy="20" r="12"></circle><circle cx="60" cy="20" r="12"></circle><circle cx="100" cy="20" r="12"></circle></svg>
				<svg class="cerb-ui-spinner cerb-ui-spinner--spark cerb-u-mx-3" viewBox="0 0 24 24"><line x1="12" y1="2" x2="12" y2="6"></line><line x1="12" y1="18" x2="12" y2="22"></line><line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line><line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line><line x1="2" y1="12" x2="6" y2="12"></line><line x1="18" y1="12" x2="22" y2="12"></line><line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line><line x1="16.24" y1="7.76" x2="19.07" y2="4.93"></line></svg>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;!-- author the markup (CSS animates it) --&gt;
&lt;svg class="cerb-ui-spinner" viewBox="0 0 100 100"&gt;&lt;circle cx="50" cy="50" r="45"&gt;&lt;/circle&gt;&lt;/svg&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;!-- arc: a 90° arc spinning around its center --&gt;
&lt;svg class="cerb-ui-spinner cerb-ui-spinner--arc" viewBox="0 0 100 100"&gt;&lt;circle cx="50" cy="50" r="45"&gt;&lt;/circle&gt;&lt;/svg&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;!-- dots: a typing indicator (three dots pulse left→right) --&gt;
&lt;svg class="cerb-ui-spinner cerb-ui-spinner--dots" viewBox="0 0 120 40"&gt;&lt;circle cx="20" cy="20" r="12"&gt;&lt;/circle&gt;&lt;circle cx="60" cy="20" r="12"&gt;&lt;/circle&gt;&lt;circle cx="100" cy="20" r="12"&gt;&lt;/circle&gt;&lt;/svg&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;!-- spark: the spinner icon's 8 radial hashes with a rotating tail --&gt;
&lt;svg class="cerb-ui-spinner cerb-ui-spinner--spark" viewBox="0 0 24 24"&gt;&lt;line x1="12" y1="2" x2="12" y2="6"/&gt;&lt;line x1="12" y1="18" x2="12" y2="22"/&gt;&lt;line x1="4.93" y1="4.93" x2="7.76" y2="7.76"/&gt;&lt;line x1="16.24" y1="16.24" x2="19.07" y2="19.07"/&gt;&lt;line x1="2" y1="12" x2="6" y2="12"/&gt;&lt;line x1="18" y1="12" x2="22" y2="12"/&gt;&lt;line x1="4.93" y1="19.07" x2="7.76" y2="16.24"/&gt;&lt;line x1="16.24" y1="7.76" x2="19.07" y2="4.93"/&gt;&lt;/svg&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>// or build it in JS (pass a variant: 'arc', 'dots', or 'spark')
el.appendChild(CerbUI.Spinner.create());         // default ring
el.appendChild(CerbUI.Spinner.create('arc'));    // arc
el.appendChild(CerbUI.Spinner.create('dots'));   // dots
el.appendChild(CerbUI.Spinner.create('spark'));  // spark
const s = new CerbUI.Spinner('spark'); el.appendChild(s.el);</pre>
			</div>
		</div>
	</div>
