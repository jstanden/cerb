{$fieldset_id = uniqid()}
<div class="cerb-ui-panel cerb-ui-panel--spaced" id="{$fieldset_id}">
	<div class="cerb-ui-form">
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">Client ID</label>
			<input type="text" name="params[client_id]" value="{$params.client_id}" spellcheck="false">
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">Client Secret</label>
			<input type="text" name="params[client_secret]" value="{$params.client_secret}" spellcheck="false">
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">Request Token URL</label>
			<input type="text" name="params[request_token_url]" value="{$params.request_token_url}" spellcheck="false">
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">Authentication URL</label>
			<input type="text" name="params[authentication_url]" value="{$params.authentication_url}" spellcheck="false">
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">Access Token URL</label>
			<input type="text" name="params[access_token_url]" value="{$params.access_token_url}" spellcheck="false">
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">Signature Method</label>
			{$methods = ['HMAC-SHA1','PLAINTEXT']}
			<select name="params[signature_method]">
				{foreach from=$methods item=method}
				<option value="{$method}" {if $method==$params.signature_method}selected="selected"{/if}>{$method}</option>
				{/foreach}
			</select>
		</div>
	</div>
</div>
