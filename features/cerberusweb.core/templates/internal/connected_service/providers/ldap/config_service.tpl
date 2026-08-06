<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-form">
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">Host</label>
			<input type="text" name="params[host]" value="{$params.host}" spellcheck="false" placeholder="ldap.example.com">
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">Port</label>
			<input type="text" name="params[port]" value="{$params.port}" spellcheck="false" placeholder="389">
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">Encryption</label>
			<input type="hidden" name="params[encryption]" value="{$params.encryption}">
			<div class="cerb-ui-switcher" data-cerb-ldap-encryption>
				<button type="button" data-value="" {if !$params.encryption}class="cerb-ui-switcher--active"{/if}>{'common.automatic'|devblocks_translate|capitalize}</button>
				<button type="button" data-value="tls" {if 'tls' == $params.encryption}class="cerb-ui-switcher--active"{/if}>TLS</button>
				<button type="button" data-value="ssl" {if 'ssl' == $params.encryption}class="cerb-ui-switcher--active"{/if}>SSL</button>
				<button type="button" data-value="disabled" {if 'disabled' == $params.encryption}class="cerb-ui-switcher--active"{/if}>{'common.disabled'|devblocks_translate|capitalize}</button>
			</div>
		</div>

		<div data-cerb-ldap-tls-overrides{if 'disabled' == $params.encryption} style="display:none;"{/if}>
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">Certificate</label>
				<input type="hidden" name="params[cert_verify]" value="{$params.cert_verify}">
				<div class="cerb-ui-switcher" data-cerb-ldap-certverify>
					<button type="button" data-value="" {if !$params.cert_verify}class="cerb-ui-switcher--active"{/if}>Verify</button>
					<button type="button" data-value="allow" {if 'allow' == $params.cert_verify}class="cerb-ui-switcher--active"{/if}>Allow self-signed</button>
					<button type="button" data-value="never" {if 'never' == $params.cert_verify}class="cerb-ui-switcher--active"{/if}>Skip</button>
				</div>
			</div>

			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">CA certificate <span class="cerb-ui-form--hint">{'common.optional'|devblocks_translate|lower}</span></label>
				<textarea name="params[ca_cert]" spellcheck="false" rows="4" placeholder="-----BEGIN CERTIFICATE-----">{$params.ca_cert}</textarea>
				<div class="cerb-ui-form--help">Paste a server's self-signed or CA certificate (PEM) to trust it while keeping verification on.</div>
			</div>
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">Bind DN</label>
			<input type="text" name="params[bind_dn]" value="{$params.bind_dn}" spellcheck="false" placeholder="cn=read-only-admin,dc=example,dc=com">
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">Bind Password</label>
			<input type="password" name="params[bind_password]" value="{$params.bind_password}" autocomplete="off" spellcheck="false">
		</div>
	</div>
</div>

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-form">
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">Search context</label>
			<input type="text" name="params[context_search]" value="{$params.context_search}">
			<div class="cerb-ui-form--help">example: OU=customers,DC=example,DC=com</div>
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">Email field</label>
			<input type="text" name="params[field_email]" value="{$params.field_email}">
			<div class="cerb-ui-form--help">example: mail</div>
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">First name (given name) field <span class="cerb-ui-form--hint">{'common.optional'|devblocks_translate|lower}</span></label>
			<input type="text" name="params[field_firstname]" value="{$params.field_firstname}">
			<div class="cerb-ui-form--help">example: givenName</div>
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">Last name (surname) field <span class="cerb-ui-form--hint">{'common.optional'|devblocks_translate|lower}</span></label>
			<input type="text" name="params[field_lastname]" value="{$params.field_lastname}">
			<div class="cerb-ui-form--help">example: sn</div>
		</div>
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}">
{literal}
(function() {
	if(!window.CerbUI || !CerbUI.Switcher)
		return;

	// Encryption switcher → hidden input; reveals the TLS overrides unless "disabled"
	document.querySelectorAll('[data-cerb-ldap-encryption]').forEach(function(sw) {
		const field = sw.closest('.cerb-ui-form--field');
		const input = field ? field.querySelector('input[type="hidden"]') : null;
		const form = sw.closest('.cerb-ui-form') || document;
		const overrides = form.querySelector('[data-cerb-ldap-tls-overrides]');

		new CerbUI.Switcher(sw, {
			value: input ? input.value : null,
			onSelect: function(value) {
				if(input) input.value = value;
				if(overrides) overrides.style.display = (value === 'disabled') ? 'none' : '';
			}
		});
	});

	// Certificate-verification switcher → hidden input
	document.querySelectorAll('[data-cerb-ldap-certverify]').forEach(function(sw) {
		const field = sw.closest('.cerb-ui-form--field');
		const input = field ? field.querySelector('input[type="hidden"]') : null;

		new CerbUI.Switcher(sw, {
			value: input ? input.value : null,
			onSelect: function(value) { if(input) input.value = value; }
		});
	});
})();
{/literal}
</script>
