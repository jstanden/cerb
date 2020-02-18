{$fieldset_id = uniqid()}
<fieldset class="peek black" id="{$fieldset_id}">
	<b>Client ID:</b><br>
	<input type="text" name="params[client_id]" value="{$params.client_id}" style="width:100%;" size="50" spellcheck="false"><br>
	<br>
	
	<b>Client Secret:</b><br>
	<input type="text" name="params[client_secret]" value="{$params.client_secret}" style="width:100%;" size="50" spellcheck="false"><br>
	<br>
	
	<b>Authorize Scope:</b><br>
	<input type="text" name="params[scope]" value="{$params.scope|default:'openid profile'}" style="width:100%;" size="50" spellcheck="false"><br>
	<br>
	
	<b>Issuer:</b><br>
	<input type="text" name="params[issuer]" value="{$params.issuer}" style="width:100%;" size="50" spellcheck="false"><br>
	<br>
	
	<button type="button" class="cerb-oidc-discovery-button">Run Discovery</button>
	<br>
	<br>
	
	<div class="cerb-oidc-discovery-status"></div>
	
	<b>Authorization URL:</b><br>
	<input type="text" name="params[authorization_url]" value="{$params.authorization_url}" style="width:100%;" size="50" spellcheck="false"><br>
	<br>
	
	<b>Access Token URL:</b><br>
	<input type="text" name="params[access_token_url]" value="{$params.access_token_url}" style="width:100%;" size="50" spellcheck="false"><br>
	<br>
	
	<b>Userinfo URL:</b><br>
	<input type="text" name="params[userinfo_url]" value="{$params.userinfo_url}" style="width:100%;" size="50" spellcheck="false"><br>
	<br>
	
	<b>JWKS URL:</b><br>
	<input type="text" name="params[jwks_url]" value="{$params.jwks_url}" style="width:100%;" size="50" spellcheck="false"><br>
	<br>
</fieldset>

<script type="text/javascript">
$(function() {
	var $fieldset = $('#{$fieldset_id}');
	var $button_discovery = $fieldset.find('.cerb-oidc-discovery-button');
	var $status_discovery = $fieldset.find('.cerb-oidc-discovery-status');
	
	$button_discovery.on('click', function(e) {
		var $issuer = $fieldset.find('input:text[name="params[issuer]"]');
		var issuer = $issuer.val();
		
		$status_discovery.html('');

		var formData = new FormData();
		formData.set('c', 'profiles');
		formData.set('a', 'handleSectionAction');
		formData.set('section', 'connected_service');
		formData.set('action', 'ajax');
		formData.set('ajax', 'runDiscovery');
		formData.set('id', '{$service->extension_id}');
		formData.set('issuer', issuer);

		genericAjaxPost(formData, '', '', function(json) {
			var $input_authorization_url = $fieldset.find('input:text[name="params[authorization_url]"]');
			var $input_access_token_url = $fieldset.find('input:text[name="params[access_token_url]"]');
			var $input_userinfo_url = $fieldset.find('input:text[name="params[userinfo_url]"]');
			var $input_jwks_url = $fieldset.find('input:text[name="params[jwks_url]"]');
			
			if(null == json || null == json.issuer) {
				if(json.error) {
					Devblocks.showError($status_discovery, json.error);
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