<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-form">
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">Base URL</label>
			<input type="text" name="params[base_url]" value="{$params.base_url}" spellcheck="false">
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">Also allow these URL prefixes</label>
			<textarea name="params[url_whitelist]" rows="5" style="height:7em;">{$params.url_whitelist}</textarea>
			<div class="cerb-ui-form--help">one per line</div>
		</div>
	</div>
</div>
