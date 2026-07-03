	<div class="cerb-uiref-component" id="qrcode">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-qr-code"></span>QR code</div>

		{* Example: basic — render a QR into a container; edit the text to re-encode live (scan it to verify) *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">A self-contained QR generator + <strong>SVG</strong> renderer (no jQuery, no canvas). Clean-room ISO/IEC 18004: byte mode, Reed&ndash;Solomon EC, mask selection. Renders dark-on-white regardless of theme so phone cameras can read it. Backs the two-factor setup screens. <strong>Edit the text and scan the code</strong> to verify.</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-u-flex cerb-u-flex-wrap cerb-u-items-center cerb-u-gap-3">
					<div id="uiref-qrcode-basic"></div>
					<label class="cerb-ui-form--field" style="flex:1 1 260px;">
						<span class="cerb-ui-form--label">Encoded text</span>
						<input type="text" id="uiref-qrcode-text" value="https://cerb.ai/" autocomplete="off">
					</label>
				</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;div id="qrcode"&gt;&lt;/div&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}// Render into a container (default: size 192, margin 4, level 'H', black-on-white)
const qr = new CerbUI.QrCode(document.getElementById('qrcode'), {
	// TOTP payload = the otpauth Key URI (include &issuer= so authenticator apps name the entry)
	text: 'otpauth://totp/Cerb:jane%40example.com?secret=JBSWY3DPEHPK3PXP&issuer=Cerb',
	size: 192,             // px (SVG scales crisply to any size)
	margin: 4,             // quiet-zone modules around the code
	correctLevel: 'H',     // 'L' | 'M' | 'Q' | 'H' error correction (default 'H')
	// foreground: '#000000', background: '#ffffff', // overridable (keep high contrast)
});

qr.setText('https://cerb.ai');   // re-encode + re-render

// Or build a detached &lt;svg&gt; with no container:
const svg = CerbUI.QrCode.create({ text: 'https://cerb.ai', size: 128 });{/literal}</pre>
			</div>
		</div>

		{* Example: sizes + EC levels *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Sizes scale the same SVG; higher error-correction levels (L&rarr;H) add redundancy (a denser code that survives more damage). The MFA screens use <code>H</code>.</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div id="uiref-qrcode-levels" class="cerb-u-flex cerb-u-flex-wrap cerb-u-items-center cerb-u-gap-3"></div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}CerbUI.QrCode.create({ text: 'https://cerb.ai', size: 96, correctLevel: 'L' });
CerbUI.QrCode.create({ text: 'https://cerb.ai', size: 128, correctLevel: 'H' });{/literal}</pre>
			</div>
		</div>
	</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}">
(function() {
	if(!(window.CerbUI && CerbUI.QrCode)) return;

	const basic = document.getElementById('uiref-qrcode-basic');
	const input = document.getElementById('uiref-qrcode-text');
	if(basic && input) {
		const qr = new CerbUI.QrCode(basic, { text: input.value, size: 176 });
		input.addEventListener('input', function() { qr.setText(input.value || ' '); });
	}

	const levels = document.getElementById('uiref-qrcode-levels');
	if(levels) {
		[['L', 96], ['M', 112], ['Q', 128], ['H', 144]].forEach(function(spec) {
			const cell = document.createElement('div');
			cell.className = 'cerb-u-flex cerb-u-flex-column cerb-u-items-center cerb-u-gap-1';
			cell.appendChild(CerbUI.QrCode.create({ text: 'https://cerb.ai', size: spec[1], correctLevel: spec[0] }));
			const cap = document.createElement('div');
			cap.className = 'cerb-u-text-muted cerb-u-fs-n1';
			cap.textContent = 'level ' + spec[0];
			cell.appendChild(cap);
			levels.appendChild(cell);
		});
	}
})();
</script>
