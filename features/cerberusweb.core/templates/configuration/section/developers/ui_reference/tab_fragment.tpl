{* Demo-only fragment for the UI Reference Tabs (dynamic/AJAX) example. Loaded by CerbUI.Tabs via Cerb's
   genericAjaxGet on first activation. The inline <script> carries the request nonce; genericAjaxGet injects
   the fragment with jQuery, so the script runs under CSP — proving script execution in AJAX-loaded panels. *}
<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header"><div class="cerb-ui-header--label">Dynamic panel {$which}</div></div>
	<p>This HTML was loaded with <code>genericAjaxGet</code> on first activation, then cached. Tab: <b>{$which}</b>.</p>
	<p>Inline script status: <b id="uiref-tabs-proof-{$which}">did NOT run &#10007;</b></p>
</div>
<script nonce="{DevblocksPlatform::getRequestNonce()}">
{literal}(function() {
	var el = document.getElementById('uiref-tabs-proof-{/literal}{$which}{literal}');
	if(el) {
		var t = new Date();
		el.textContent = 'script executed at ' + t.toLocaleTimeString() + ' ✓';
	}
})();{/literal}
</script>
