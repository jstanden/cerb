	<div class="cerb-uiref-component" id="form">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-form"></span>Form</div>

		{* cerb-ui-form on a <form> (or any wrapper) auto-styles the controls inside. For anything beyond a
		   couple of fields, group them into --section panels: the form's gap spaces the sections, and each
		   --section-body re-establishes the field gap *inside* a section. --field = label-above + full-width
		   control; --row = responsive columns; --control = leading icon. *}

		{* Minimal — a bare form whose own gap spaces the fields (no section needed for a small form). *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Minimal — a few fields, no sections</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<form class="cerb-ui-form" style="max-width:420px;">
					<div class="cerb-ui-form--field">
						<label class="cerb-ui-form--label">Name <span class="cerb-ui-form--required">*</span></label>
						<input type="text" value="Kim Li">
					</div>
					<div class="cerb-ui-form--field">
						<label class="cerb-ui-form--label">Email <span class="cerb-ui-form--hint">optional</span></label>
						<input type="email" placeholder="name@example.com">
					</div>
				</form>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;form class="cerb-ui-form"&gt;
	&lt;div class="cerb-ui-form--field"&gt;
		&lt;label class="cerb-ui-form--label"&gt;Name &lt;span class="cerb-ui-form--required"&gt;*&lt;/span&gt;&lt;/label&gt;
		&lt;input type="text" name="name"&gt;
	&lt;/div&gt;
	&lt;div class="cerb-ui-form--field"&gt;
		&lt;label class="cerb-ui-form--label"&gt;Email &lt;span class="cerb-ui-form--hint"&gt;optional&lt;/span&gt;&lt;/label&gt;
		&lt;input type="email" name="email"&gt;
	&lt;/div&gt;
&lt;/form&gt;</pre>
			</div>
		</div>

		{* Full — multiple --section panels in one form (gap between them), with rows, leading-icon inputs,
		   a SelectMenu, a Toggle, and help text. *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Full — titled sections, rows, icons, SelectMenu, Toggle</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<form class="cerb-ui-form" style="max-width:560px;">
					<div class="cerb-ui-form--section">
						<div class="cerb-ui-form--section-head">Identity</div>
						<div class="cerb-ui-form--section-body">
							<div class="cerb-ui-form--row">
								<div class="cerb-ui-form--field">
									<label class="cerb-ui-form--label">First name <span class="cerb-ui-form--required">*</span></label>
									<input type="text" value="Kim">
								</div>
								<div class="cerb-ui-form--field">
									<label class="cerb-ui-form--label">Last name</label>
									<input type="text" value="Li">
								</div>
							</div>
							<div class="cerb-ui-form--field">
								<label class="cerb-ui-form--label">Location</label>
								<label class="cerb-ui-form--control">
									<span class="cerb-ui-form--control-icon cerb-icons cerb-icon-location"></span>
									<input type="text" value="San Francisco, CA">
								</label>
							</div>
						</div>
					</div>

					<div class="cerb-ui-form--section">
						<div class="cerb-ui-form--section-head">Contact details</div>
						<div class="cerb-ui-form--section-body">
							<div class="cerb-ui-form--field">
								<label class="cerb-ui-form--label">Primary email <span class="cerb-ui-form--required">*</span></label>
								<label class="cerb-ui-form--control">
									<span class="cerb-ui-form--control-icon cerb-icons cerb-icon-mail"></span>
									<input type="email" value="kim.li@acme.example">
								</label>
							</div>
							<div class="cerb-ui-form--field">
								<label class="cerb-ui-form--label">Secondary email <span class="cerb-ui-form--hint">optional</span></label>
								<input type="email" placeholder="alias@company.com">
							</div>
							<div class="cerb-ui-form--row">
								<div class="cerb-ui-form--field">
									<label class="cerb-ui-form--label">Phone</label>
									<label class="cerb-ui-form--control">
										<span class="cerb-ui-form--control-icon cerb-icons cerb-icon-phone-handset"></span>
										<input type="tel" value="+1 (415) 555-0182">
									</label>
								</div>
								<div class="cerb-ui-form--field">
									<label class="cerb-ui-form--label">Mobile <span class="cerb-ui-form--hint">optional</span></label>
									<input type="tel" placeholder="+1 (555) 000-0000">
								</div>
							</div>
						</div>
					</div>

					<div class="cerb-ui-form--section">
						<div class="cerb-ui-form--section-head">Localization</div>
						<div class="cerb-ui-form--section-body">
							<div class="cerb-ui-form--row">
								<div class="cerb-ui-form--field">
									<label class="cerb-ui-form--label">Owner</label>
									<select id="uiref-form-owner">
										<option value="">Unassigned</option>
										<option value="ada" selected>Ada Greene</option>
										<option value="kim">Kim Li</option>
										<option value="sam">Sam Patel</option>
									</select>
								</div>
								<div class="cerb-ui-form--field">
									<label class="cerb-ui-form--label">Timezone <span class="cerb-ui-form--hint">native &lt;select&gt;</span></label>
									<select>
										<option>Pacific (PT) &mdash; US/Canada</option>
										<option>Eastern (ET) &mdash; US/Canada</option>
									</select>
								</div>
							</div>
							<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2">
								<label class="cerb-ui-toggle"><input type="checkbox" checked><span class="cerb-ui-toggle--slider"></span></label>
								<span class="cerb-ui-form--label">Notify watchers on changes</span>
							</div>
						</div>
					</div>

					<div class="cerb-ui-form--section">
						<div class="cerb-ui-form--section-head">Internal notes <span class="cerb-icons cerb-icon-chevron-down"></span></div>
						<div class="cerb-ui-form--section-body">
							<div class="cerb-ui-form--field">
								<textarea rows="3">Primary technical contact. Escalate P0s directly.</textarea>
								<div class="cerb-ui-form--help">Only visible to team members. Not included in replies.</div>
							</div>
						</div>
					</div>
				</form>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;form class="cerb-ui-form"&gt;
	&lt;div class="cerb-ui-form--section"&gt;
		&lt;div class="cerb-ui-form--section-head"&gt;Identity&lt;/div&gt;
		&lt;div class="cerb-ui-form--section-body"&gt;
			&lt;!-- --row: side-by-side, collapses to one column when narrow --&gt;
			&lt;div class="cerb-ui-form--row"&gt;
				&lt;div class="cerb-ui-form--field"&gt;
					&lt;label class="cerb-ui-form--label"&gt;First name &lt;span class="cerb-ui-form--required"&gt;*&lt;/span&gt;&lt;/label&gt;
					&lt;input type="text" name="first_name"&gt;
				&lt;/div&gt;
				&lt;div class="cerb-ui-form--field"&gt;
					&lt;label class="cerb-ui-form--label"&gt;Last name&lt;/label&gt;
					&lt;input type="text" name="last_name"&gt;
				&lt;/div&gt;
			&lt;/div&gt;
			&lt;!-- --control: a leading icon inside the input --&gt;
			&lt;div class="cerb-ui-form--field"&gt;
				&lt;label class="cerb-ui-form--label"&gt;Location&lt;/label&gt;
				&lt;label class="cerb-ui-form--control"&gt;
					&lt;span class="cerb-ui-form--control-icon cerb-icons cerb-icon-location"&gt;&lt;/span&gt;
					&lt;input type="text" name="location"&gt;
				&lt;/label&gt;
			&lt;/div&gt;
		&lt;/div&gt;
	&lt;/div&gt;

	&lt;div class="cerb-ui-form--section"&gt;
		&lt;div class="cerb-ui-form--section-head"&gt;Contact details&lt;/div&gt;
		&lt;div class="cerb-ui-form--section-body"&gt;
			&lt;div class="cerb-ui-form--field"&gt;
				&lt;label class="cerb-ui-form--label"&gt;Primary email &lt;span class="cerb-ui-form--required"&gt;*&lt;/span&gt;&lt;/label&gt;
				&lt;label class="cerb-ui-form--control"&gt;
					&lt;span class="cerb-ui-form--control-icon cerb-icons cerb-icon-mail"&gt;&lt;/span&gt;
					&lt;input type="email" name="email"&gt;
				&lt;/label&gt;
			&lt;/div&gt;
			&lt;!-- a SelectMenu enhances the native select (wired in JS below) --&gt;
			&lt;div class="cerb-ui-form--field"&gt;
				&lt;label class="cerb-ui-form--label"&gt;Owner&lt;/label&gt;
				&lt;select id="owner"&gt;
					&lt;option value="ada"&gt;Ada Greene&lt;/option&gt;
				&lt;/select&gt;
			&lt;/div&gt;
			&lt;!-- a Toggle works bare (CSS only) --&gt;
			&lt;div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2"&gt;
				&lt;label class="cerb-ui-toggle"&gt;&lt;input type="checkbox" name="notify" checked&gt;&lt;span class="cerb-ui-toggle--slider"&gt;&lt;/span&gt;&lt;/label&gt;
				&lt;span class="cerb-ui-form--label"&gt;Notify watchers on changes&lt;/span&gt;
			&lt;/div&gt;
			&lt;div class="cerb-ui-form--field"&gt;
				&lt;label class="cerb-ui-form--label"&gt;Internal notes&lt;/label&gt;
				&lt;textarea name="notes" rows="3"&gt;&lt;/textarea&gt;
				&lt;div class="cerb-ui-form--help"&gt;Only visible to team members.&lt;/div&gt;
			&lt;/div&gt;
		&lt;/div&gt;
	&lt;/div&gt;
&lt;/form&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>// SelectMenu enhances the native &lt;select&gt;; full-width inside a --field. Toggle is optional (CSS works bare).
new CerbUI.SelectMenu(document.getElementById('owner'));</pre>
			</div>
		</div>
	</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}">
(function() {
	// Form: SelectMenu enhancing the Owner select inside the form example
	(function() {
		const el = document.getElementById('uiref-form-owner');
		if(el && window.CerbUI && CerbUI.SelectMenu)
			new CerbUI.SelectMenu(el);
	})();
})();
</script>
