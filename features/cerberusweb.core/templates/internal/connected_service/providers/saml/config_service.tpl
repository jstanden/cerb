{$fieldset_id = uniqid()}
<div class="cerb-ui-panel cerb-ui-panel--spaced" id="{$fieldset_id}">
	<div class="cerb-ui-form">
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">Entity ID</label>
			<input type="text" name="params[entity_id]" value="{$params.entity_id}" spellcheck="false">
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">SSO URL</label>
			<input type="text" name="params[url_sso]" value="{$params.url_sso}" spellcheck="false">
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">SLO URL <span class="cerb-ui-form--hint">{'common.optional'|devblocks_translate|lower}</span></label>
			<input type="text" name="params[url_slo]" value="{$params.url_slo}" spellcheck="false">
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">X.509 Certificate</label>
			<textarea name="params[cert]" style="height:10em;" spellcheck="false">{$params.cert}</textarea>
		</div>
	</div>
</div>