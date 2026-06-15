	<div class="cerb-uiref-component" id="kbd">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-keyboard"></span>Kbd</div>

		{* Example: key caps — single keys, modifiers, and a shortcut cluster *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Keyboard key caps &mdash; inline-flex, fixed-width font, a subtle bottom edge for a &ldquo;physical key&rdquo; look; sit several in a row for a shortcut. CSS-only</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div style="display:flex; align-items:center; gap:1.5em; flex-wrap:wrap;">
					<span><kbd class="cerb-ui-kbd">A</kbd> <kbd class="cerb-ui-kbd">Z</kbd> <kbd class="cerb-ui-kbd">?</kbd></span>
					<span><kbd class="cerb-ui-kbd">&#8984;</kbd> <kbd class="cerb-ui-kbd">K</kbd></span>
					<span><kbd class="cerb-ui-kbd">Esc</kbd></span>
					<span><kbd class="cerb-ui-kbd">&uarr;</kbd> <kbd class="cerb-ui-kbd">&darr;</kbd></span>
					<span><kbd class="cerb-ui-kbd">Shift</kbd> + <kbd class="cerb-ui-kbd">Enter</kbd></span>
				</div>
				<div class="cerb-uiref-result">Used by the global command bar's trailing shortcut keys (and active <code>CerbUI.Menu</code> rows).</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;!-- one cap per key; sit several in a row for a combo (any inline element works; &lt;kbd&gt; is semantic) --&gt;
&lt;kbd class="cerb-ui-kbd"&gt;&#8984;&lt;/kbd&gt; &lt;kbd class="cerb-ui-kbd"&gt;K&lt;/kbd&gt;
&lt;kbd class="cerb-ui-kbd"&gt;Esc&lt;/kbd&gt;
&lt;kbd class="cerb-ui-kbd"&gt;Shift&lt;/kbd&gt; + &lt;kbd class="cerb-ui-kbd"&gt;Enter&lt;/kbd&gt;</pre>
			</div>
		</div>
	</div>
