	<div class="cerb-uiref-component" id="file-upload">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-upload"></span>FileUpload</div>

		{* Inline uploads (drag/drop + click-to-browse), no popup. The bytes are exchanged for an attachment
		   file_id via the same chooserOpenFileAjaxUpload endpoint the legacy chooserFile used; those ids post
		   through hidden inputs. Chips render inside a well and open the attachment's card peek. Like
		   RecordChooser it can be defaulted from [data-file-id] seed markup. *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Inline file upload &mdash; drag/drop or click to browse; bytes are exchanged for an attachment id and posted via a hidden input (no popup). Chips open the attachment's card peek</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div id="uiref-fileupload"></div>
				<span class="cerb-uiref-result" style="margin-left:0.7em;">Files: <b id="uiref-fileupload-result">&mdash;</b></span>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;!-- any element becomes the well/drop zone; the component renders chips + hidden inputs into it --&gt;
&lt;div id="attachment"&gt;&lt;/div&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>const fu = new CerbUI.FileUpload(el, {
	name:      'file_id',            // hidden &lt;input&gt; (value = attachment id) so it posts
	multiple:  false,
	accept:    'image/*,.pdf',       // set on &lt;input accept&gt; AND validated client-side (advisory UX)
	maxSize:   10485760,             // bytes; 0 = no limit
	emptyIcon: 'paperclip',          // empty-state leading glyph (cerb-icons name)
	onChange:  function(values) { /* values = [ { id, name, size } ] */ },
	onError:   function(msg, file) { /* validation / upload failure */ },
});

// API: fu.getValue(); fu.setValue(v); fu.add([{ id, name, size }]); fu.clear(); fu.destroy();
// fu.add(...) appends already-uploaded attachments (e.g. an inline image pasted into an editor).
// Single: remove the chip to replace it. Bytes go to chooserOpenFileAjaxUpload (SHA-1 deduped).
// Each chip is a cerb-peek-trigger (context = attachment), so it opens the card peek — no download URL needed.</pre>
			</div>
		</div>

		{* Default the value(s) — seed markup the component enhances *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Default the value(s) &mdash; you usually have attachment id(s) from the record (e.g. a file custom field). Server-render the resolved name/size into a <code>[data-file-id]</code> child and the component enhances it (then clears the markup). No URL needed &mdash; the chip opens the card peek by id</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div id="uiref-fileupload-seed" class="cerb-ui-file-upload" data-multiple="1">
					<li data-file-id="0" data-file-name="quarterly-report.pdf" data-file-size="248192"></li>
					<li data-file-id="0" data-file-name="logo.png" data-file-size="18204"></li>
				</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;!-- Seed markup: one [data-file-id] child per value. Server-render the resolved name/size;
     the component reads these, builds the chips, then clears the markup. --&gt;
&lt;div class="cerb-ui-file-upload" data-multiple="1"&gt;
	&lt;li data-file-id="55"
	    data-file-name="quarterly-report.pdf"
	    data-file-size="248192"&gt;&lt;/li&gt;
&lt;/div&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>new CerbUI.FileUpload(el, { name: 'file_ids', multiple: true });  // no `value` → reads the seed markup</pre>
			</div>
		</div>
	</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}">
(function() {
	const el = document.getElementById('uiref-fileupload');
	const out = document.getElementById('uiref-fileupload-result');
	if(el && window.CerbUI && CerbUI.FileUpload) {
		new CerbUI.FileUpload(el, {
			name: 'file_id',
			onChange: function(values) {
				if(!out) return;
				const list = Array.isArray(values) ? values : (values ? [values] : []);
				out.textContent = list.length ? list.map(function(v) { return v.name; }).join(', ') : '—';
			},
		});
	}

	const elSeed = document.getElementById('uiref-fileupload-seed');
	if(elSeed && window.CerbUI && CerbUI.FileUpload) {
		new CerbUI.FileUpload(elSeed, { multiple: true });
	}
})();
</script>
