{$fieldset_id = uniqid()}
<div class="cerb-ui-panel cerb-ui-panel--spaced" id="{$fieldset_id}">
	<div class="cerb-ui-form">
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">Grant Type</label>
			{$grant_types = ["authorization_code" => "Authorization Code", "authorization_code_pkce" => "Authorization Code with PKCE"]}
			<select name="params[grant_type]">
				{foreach from=$grant_types item=v key=k}
				<option value="{$k}" {if $k == $params.grant_type}selected="selected"{/if}>{$v}</option>
				{/foreach}
			</select>
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">Client ID</label>
			<input type="text" name="params[client_id]" value="{$params.client_id}" spellcheck="false">
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">Client Secret</label>
			<input type="text" name="params[client_secret]" value="{$params.client_secret}" spellcheck="false">
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
			<label class="cerb-ui-form--label">Resource Owner URL <span class="cerb-ui-form--hint">{'common.optional'|devblocks_translate|lower}</span></label>
			<input type="text" name="params[resource_owner_url]" value="{$params.resource_owner_url}" spellcheck="false">
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">Scope</label>
			<input type="text" name="params[scope]" value="{$params.scope}" spellcheck="false">
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">Approval Prompt <span class="cerb-ui-form--hint">{'common.optional'|devblocks_translate|lower}</span></label>
			<input type="text" name="params[approval_prompt]" value="{$params.approval_prompt}" spellcheck="false" placeholder="e.g. auto, force">
		</div>
	</div>
</div>
