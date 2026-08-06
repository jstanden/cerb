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
			<label class="cerb-ui-form--label">Authorize Scope</label>
			<input type="text" name="params[scope]" value="{$params.scope|default:'openid profile'}" spellcheck="false">
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">Issuer</label>
			<input type="text" name="params[issuer]" value="{$params.issuer}" spellcheck="false">
		</div>

		<div class="cerb-ui-form--field">
			<div>
				<button type="button" class="cerb-ui-button cerb-oidc-discovery-button">Run Discovery</button>
			</div>
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">Authorization URL</label>
			<input type="text" name="params[authorization_url]" value="{$params.authorization_url}" spellcheck="false">
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">Access Token URL</label>
			<input type="text" name="params[access_token_url]" value="{$params.access_token_url}" spellcheck="false">
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">Userinfo URL</label>
			<input type="text" name="params[userinfo_url]" value="{$params.userinfo_url}" spellcheck="false">
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">JWKS URL</label>
			<input type="text" name="params[jwks_url]" value="{$params.jwks_url}" spellcheck="false">
		</div>
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	const $fieldset = $('#{$fieldset_id}');
	const $button_discovery = $fieldset.find('.cerb-oidc-discovery-button');

	$button_discovery.on('click', function(e) {
		const $issuer = $fieldset.find('input:text[name="params[issuer]"]');
		const issuer = $issuer.val();

		Devblocks.clearAlerts();

		const formData = new FormData();
		formData.set('c', 'profiles');
		formData.set('a', 'invoke');
		formData.set('module', 'connected_service');
		formData.set('action', 'invoke');
		formData.set('service_action', 'runDiscovery');
		formData.set('id', '{$service->extension_id}');
		formData.set('issuer', issuer);

		genericAjaxPost(formData, '', '', function(json) {
			const $input_authorization_url = $fieldset.find('input:text[name="params[authorization_url]"]');
			const $input_access_token_url = $fieldset.find('input:text[name="params[access_token_url]"]');
			const $input_userinfo_url = $fieldset.find('input:text[name="params[userinfo_url]"]');
			const $input_jwks_url = $fieldset.find('input:text[name="params[jwks_url]"]');

			if(null == json || null == json.issuer) {
				if(json.error) {
					Devblocks.createAlertError(json.error);
				}
				return;
			}

			$input_authorization_url.val(json.authorization_endpoint);
			$input_access_token_url.val(json.token_endpoint);
			$input_userinfo_url.val(json.userinfo_endpoint);
			$input_jwks_url.val(json.jwks_uri);
		});
	});
});
</script>