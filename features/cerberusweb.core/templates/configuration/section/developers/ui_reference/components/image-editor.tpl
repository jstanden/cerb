	<div class="cerb-uiref-component" id="image-editor">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-picture"></span>ImageEditor</div>

		{* An editable image well (a CerbUI.Avatar). Hover it (or call open()) to launch a CerbUI.Dialog with a
		   canvas: pan/zoom an image under a fixed-aspect crop, fill a background color, erase, and pull image
		   bytes from the record.profile.image.editor toolbar (text/emoji, URL, or a Cerb icon). Non-destructive
		   — it only rasterizes to a PNG data URL on Save. Replaces the legacy ajax.chooserAvatar. *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Editable avatar well &mdash; hover for the edit button (or an external trigger / <code>open()</code>); the dialog has pan/zoom + a fixed-aspect crop, background color, erase, and toolbar image sources. Saves a PNG data URL (or <code>data:null</code>) into a hidden input</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-3">
					<span id="uiref-imageeditor"
						class="cerb-ui-avatar" style="width:64px;height:64px;font-size:27px;"
						data-cerb-image-editor data-image-width="256" data-image-height="256" data-name="uiref_avatar"
						data-avatar="Jane Doe" data-avatar-seed="worker:1"
						data-avatar-image="{devblocks_url}c=resource&p=cerberusweb.core&f=images/avatars/person1.png{/devblocks_url}"></span>
					<input type="hidden" name="uiref_avatar" value="">
					<span class="cerb-uiref-result">Saved: <b id="uiref-imageeditor-result">&mdash;</b></span>
				</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;!-- the well is a CerbUI.Avatar marked editable; the hidden input posts the result --&gt;
&lt;span class="cerb-ui-avatar" data-cerb-image-editor
      data-context="cerberusweb.contexts.worker" data-context-id="5"
      data-image-width="256" data-image-height="256" data-name="avatar_image"
      data-avatar="Jane Doe" data-avatar-seed="worker:5"
      data-avatar-image="…c=avatars&amp;context=worker&amp;context_id=5"&gt;&lt;/span&gt;
&lt;input type="hidden" name="avatar_image" value=""&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>new CerbUI.ImageEditor(el, {
	context:  'cerberusweb.contexts.worker',  // omit for standalone (no toolbar; seed via value)
	contextId: 5,
	width:     256, height: 256,              // output px; crop aspect = width:height
	name:      'avatar_image',                // hidden &lt;input&gt; that posts the PNG data URL (or data:null)
	onSave:    function(dataUrl) { /* dataUrl is null when cleared */ },
});

// API: ed.open(); ed.close(); ed.getValue(); ed.destroy();
// Image sources come from the record.profile.image.editor toolbar interactions, returning
// { image: { text } } (emoji/monogram), { image: { url } } (upload/remote), or { image: { icon } } (a Cerb icon).
// The bytes-&rarr;data-URL exchange and DAO_ContextAvatar::upsertWithImage save path are unchanged.</pre>
			</div>
		</div>

		{* Crop shapes *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Crop overlay shapes &mdash; the overlay shows exactly what will be cropped. <strong>rect</strong> uses the <code>width:height</code> aspect (a <strong>square</strong> when 1:1); <strong>circle</strong> draws a round guide (1:1) for avatars displayed in a circle. Square outputs default to <code>circle</code>; set <code>data-crop-shape="rect"</code> (or the <code>cropShape</code> option) to override. The saved PNG is still the square &mdash; the circle just previews the round display</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-3">
					<span id="uiref-imageeditor-rect"
						class="cerb-ui-avatar" style="width:64px;height:64px;font-size:27px;"
						data-cerb-image-editor data-image-width="512" data-image-height="288" data-crop-shape="rect" data-name="uiref_banner"
						data-avatar="Banner" data-avatar-seed="banner:1"
						data-avatar-image="{devblocks_url}c=resource&p=cerberusweb.core&f=images/avatars/building1.png{/devblocks_url}"></span>
					<input type="hidden" name="uiref_banner" value="">
					<span class="cerb-uiref-result">16:9 <code>rect</code> crop &mdash; the square demo above defaults to <code>circle</code></span>
				</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>// Crop overlay shape: 'rect' (aspect = width:height) or 'circle' (a 1:1 round guide)
new CerbUI.ImageEditor(el, { width: 256, height: 256, cropShape: 'circle' }); // round avatar (square default)
new CerbUI.ImageEditor(el, { width: 256, height: 256, cropShape: 'rect' });   // square
new CerbUI.ImageEditor(el, { width: 512, height: 288, cropShape: 'rect' });   // 16:9 banner

// …or on the element: data-crop-shape="rect|circle". A 1:1 output defaults to 'circle'.</pre>
			</div>
		</div>
	</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}">
(function() {
	const el = document.getElementById('uiref-imageeditor');
	const out = document.getElementById('uiref-imageeditor-result');
	if(el && window.CerbUI && CerbUI.ImageEditor) {
		// Standalone (no data-context) so the gallery demo needs no record: it seeds from the avatar image and
		// skips the server toolbar. Real usage passes a context + id and gets the configured image toolbar.
		new CerbUI.ImageEditor(el, {
			width: 256, height: 256,
			value: el.getAttribute('data-avatar-image'),
			onSave: function(dataUrl) { if(out) out.textContent = dataUrl ? (dataUrl.slice(0, 24) + '…') : '(cleared)'; },
		});
	}

	const rectEl = document.getElementById('uiref-imageeditor-rect');
	if(rectEl && window.CerbUI && CerbUI.ImageEditor) {
		new CerbUI.ImageEditor(rectEl, {
			width: 512, height: 288, cropShape: 'rect',
			value: rectEl.getAttribute('data-avatar-image'),
		});
	}
})();
</script>
