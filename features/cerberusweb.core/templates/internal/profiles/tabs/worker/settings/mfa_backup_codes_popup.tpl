<div style="padding:10px;">
	<div class="help-box">
		<p>Each code can be used once if you lose access to your authenticator app.</p>
		<p>Save these backup codes. They will not be shown again.</p>
	</div>

	<div class="cerb-code-editor-toolbar">
		<button type="button" class="cerb-code-editor-toolbar-button cerb-mfa-copy-codes" title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
		<button type="button" class="cerb-code-editor-toolbar-button cerb-mfa-download-codes" title="Download"><span class="cerb-icons cerb-icon-cloud-download"></span></button>
		<button type="button" class="cerb-code-editor-toolbar-button cerb-mfa-print-codes" title="Print"><span class="cerb-icons cerb-icon-print"></span></button>
	</div>

	<div style="margin:10px 0;">
		{if 'fieldsets' == $layout.style}
			{include file="devblocks:cerberusweb.core::ui/sheets/render_fieldsets.tpl"}
		{elseif in_array($layout.style, ['columns','grid'])}
			{include file="devblocks:cerberusweb.core::ui/sheets/render_grid.tpl"}
		{else}
			{include file="devblocks:cerberusweb.core::ui/sheets/render.tpl"}
		{/if}
	</div>

	<p style="margin-top:15px;">
		<label><input type="checkbox" class="cerb-mfa-ack-checkbox"> I have saved these backup codes in a safe place.</label>
	</p>

	<button type="button" class="cerb-mfa-ack-done cerb-hidden" style="margin-top:5px;"><span class="cerb-icons cerb-icon-circle-ok"></span> Done</button>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $popup = genericAjaxPopupFetch('mfa_backup_codes');
	let codes = {$backup_codes|json_encode nofilter};

	$popup.one('popup_open', function() {
		$(this).dialog('option', 'title', 'MFA Backup Codes');

		// Copy to clipboard button
		$popup.find('.cerb-mfa-copy-codes').on('click', function() {
			navigator.clipboard.writeText(codes.join('\n'));
			Devblocks.createAlert('Copied to clipboard!', 'note');
		});

		// Download button
		let blob = new Blob([codes.join('\n')], { type: 'text/plain' });
		let downloadUrl = URL.createObjectURL(blob);
		$popup.find('.cerb-mfa-download-codes').on('click', function() {
			let a = document.createElement('a');
			a.href = downloadUrl;
			a.download = 'cerb-backup-codes.txt';
			a.click();
		});

		// Print button
		$popup.find('.cerb-mfa-print-codes').on('click', function() {
			let w = window.open('', '_blank');
			w.document.write('<html><body><pre style="font-size:14px;">' + codes.join('\n') + '</pre></body></html>');
			w.print();
			w.close();
		});

		// Ack checkbox reveals Done button
		$popup.find('.cerb-mfa-ack-checkbox').on('change', function() {
			$popup.find('.cerb-mfa-ack-done').toggleClass('cerb-hidden', !$(this).is(':checked'));
		});

		$popup.find('.cerb-mfa-ack-done').on('click', function() {
			$popup.dialog('close');
		});
	});
});
</script>
