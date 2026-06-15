	<div class="cerb-uiref-component" id="confirm">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-checked"></span>Confirm</div>

		{* CerbUI.Confirm — a forced-modal confirmation (the confirmPopup replacement) *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label"><code>CerbUI.Confirm</code> &mdash; a forced-modal confirmation built on <a href="#dialog">Dialog</a>: centered in the viewport, no ×/minimize/resize/drag, <b>Esc</b> ignored, always on top of other dialogs. Cancel/OK only; labels are customizable; title &amp; body default. The design-system replacement for the legacy <code>confirmPopup()</code></div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<button type="button" class="cerb-ui-button" id="uiref-dialog-confirm-btn">Ask to confirm</button>
				<span class="cerb-uiref-result" style="margin-left:0.7em;">Last: <b id="uiref-dialog-confirm-result">&mdash;</b></span>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}CerbUI.Confirm.open({
	title:       'Delete snippet',    // default 'Confirm'
	body:        'This can\'t be undone.', // default 'Are you sure?' (string or DOM node)
	confirmText: 'Delete',            // default 'OK'
	cancelText:  'Keep',              // default 'Cancel'
	onConfirm:   function() { /* proceed */ },
	onCancel:    function() { /* optional */ },
});{/literal}</pre>
			</div>
		</div>
	</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}">
(function() {
	// CerbUI.Confirm: a forced-modal confirmation with custom button labels
	const confirmBtn = document.getElementById('uiref-dialog-confirm-btn');
	const confirmOut = document.getElementById('uiref-dialog-confirm-result');
	if(confirmBtn && window.CerbUI && CerbUI.Confirm) confirmBtn.addEventListener('click', function() {
		CerbUI.Confirm.open({
			title: 'Delete snippet',
			body: "This can't be undone.",
			confirmText: 'Delete',
			cancelText: 'Keep',
			onConfirm: function() { if(confirmOut) confirmOut.textContent = 'confirmed'; },
			onCancel: function() { if(confirmOut) confirmOut.textContent = 'cancelled'; },
		});
	});
})();
</script>
