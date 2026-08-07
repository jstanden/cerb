{$uniqid = uniqid()}
{$account = ($model && $model->params.connected_account_id) ? DAO_ConnectedAccount::get($model->params.connected_account_id) : null}

<div class="cerb-ui-panel cerb-ui-panel--spaced" id="{$uniqid}">
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div class="cerb-ui-header--title-sm">SMTP</div>
		<div class="cerb-ui-header--subtitle">This mail transport delivers mail to an <a href="https://en.wikipedia.org/wiki/Simple_Mail_Transfer_Protocol" target="_blank" rel="noopener noreferrer">SMTP</a> server.</div>
	</div>

	<div class="cerb-ui-form">
		<div class="cerb-ui-form--row">
			<div class="cerb-ui-form--field cerb-u-flex-2">
				<label class="cerb-ui-form--label">Host</label>
				<input type="text" name="params[{$extension->id}][host]" value="{$model->params.host|default:'localhost'}" placeholder="e.g. localhost" spellcheck="false">
			</div>

			<div class="cerb-ui-form--field cerb-u-flex-1">
				<label class="cerb-ui-form--label">Port</label>
				<input type="text" name="params[{$extension->id}][port]" value="{$model->params.port|default:25}" spellcheck="false">
				<div class="cerb-ui-form--help">465/587 for TLS/SSL; 25 for legacy</div>
			</div>
		</div>

		<div class="cerb-ui-form--row">
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">Encryption</label>
				<div>
					<input type="hidden" name="params[{$extension->id}][encryption]" id="encryption_{$uniqid}" value="{$model->params.encryption|default:'None'}">
					<div class="cerb-ui-switcher" data-cerb-input="encryption_{$uniqid}">
						<button type="button" data-value="None"{if empty($model->params.encryption) || $model->params.encryption == 'None'} class="cerb-ui-switcher--active"{/if}>None</button>
						<button type="button" data-value="TLS"{if $model->params.encryption == 'TLS'} class="cerb-ui-switcher--active"{/if}>TLS</button>
						<button type="button" data-value="SSL"{if $model->params.encryption == 'SSL'} class="cerb-ui-switcher--active"{/if}>SSL</button>
					</div>
				</div>
			</div>

			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">SSL Validation</label>
				<div>
					<input type="hidden" name="params[{$extension->id}][ssl_disable_validation]" id="sslValidation_{$uniqid}" value="{$model->params.ssl_disable_validation|default:0}">
					<div class="cerb-ui-switcher" data-cerb-input="sslValidation_{$uniqid}">
						<button type="button" data-value="0"{if empty($model->params.ssl_disable_validation)} class="cerb-ui-switcher--active"{/if}>Strict (recommended)</button>
						<button type="button" data-value="1"{if $model->params.ssl_disable_validation == 1} class="cerb-ui-switcher--active"{/if}>{'common.disabled'|devblocks_translate|capitalize}</button>
					</div>
				</div>
			</div>
		</div>

		<div class="cerb-ui-form--field">
			<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2">
				<label class="cerb-ui-toggle">
					<input type="checkbox" name="params[{$extension->id}][auth_enabled]" id="authEnabled_{$uniqid}" value="1" class="peek-smtp-auth" {if $model->params.auth_enabled}checked{/if}>
					<span class="cerb-ui-toggle--slider"></span>
				</label>
				<label for="authEnabled_{$uniqid}" class="cerb-ui-form--label" style="margin:0;">Authentication <span class="cerb-ui-form--hint">{'common.optional'|devblocks_translate|lower}</span></label>
			</div>

			<div class="peek-smtp-encryption cerb-u-mt-2" style="display:{if $model->params.auth_enabled}block{else}none{/if};">
				<div class="cerb-ui-form">
					<div class="cerb-ui-form--row">
						<div class="cerb-ui-form--field">
							<label class="cerb-ui-form--label">Username</label>
							<input type="text" name="params[{$extension->id}][auth_user]" value="{$model->params.auth_user}" spellcheck="false">
						</div>
						<div class="cerb-ui-form--field">
							<label class="cerb-ui-form--label">Password <span class="cerb-ui-form--hint">{'common.optional'|devblocks_translate|lower}</span></label>
							<input type="password" name="params[{$extension->id}][auth_pass]" value="{$model->params.auth_pass}" autocomplete="off" spellcheck="false" placeholder="••••••••">
						</div>
					</div>

					<div class="cerb-ui-form--field">
						<label class="cerb-ui-form--label">XOAuth2 <span class="cerb-ui-form--hint">{'common.optional'|devblocks_translate|lower}</span></label>
						<div class="cerb-ui-record-chooser" id="accountChooser_{$uniqid}">
							{if $account}
								<li data-context-id="{$account->id}" data-label="{$account->name}"></li>
							{/if}
						</div>
					</div>
				</div>
			</div>
		</div>

		<div class="cerb-ui-form--row">
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">Timeout</label>
				<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-1">
					<input type="text" name="params[{$extension->id}][timeout]" value="{$model->params.timeout|default:30}" style="width:5em;flex:0 0 auto;">
					<span class="cerb-u-text-muted">seconds</span>
				</div>
			</div>

			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">Max deliveries per connection</label>
				<input type="text" name="params[{$extension->id}][max_sends]" value="{$model->params.max_sends|default:20}" style="width:7em;">
				<div class="cerb-ui-form--help">depends on your mail server; default is 20</div>
			</div>
		</div>
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var $fieldset = $('#{$uniqid}');

	// Switchers (encryption, SSL validation)
	if(window.CerbUI && CerbUI.Switcher) {
		$fieldset.find('.cerb-ui-switcher[data-cerb-input]').each(function() {
			var input = document.getElementById(this.getAttribute('data-cerb-input'));
			new CerbUI.Switcher(this, {
				value: input ? input.value : null,
				onSelect: function(value) { if(input) input.value = value; }
			});
		});
	}

	// XOAuth2 connected account chooser
	if(window.CerbUI && CerbUI.RecordChooser) {
		var accountEl = $fieldset.find('#accountChooser_{$uniqid}')[0];
		if(accountEl)
			new CerbUI.RecordChooser(accountEl, {
				context: '{CerberusContexts::CONTEXT_CONNECTED_ACCOUNT}',
				name: 'params[{$extension->id}][connected_account_id]',
				emptyIcon: 'key',
				query: 'service:(type:oauth2)',
				searchPlaceholder: 'XOAuth2 account'
			});
	}

	// Reveal the auth block
	$fieldset.find('input:checkbox.peek-smtp-auth').click(function() {
		if($(this).is(':checked')) {
			$fieldset.find('div.peek-smtp-encryption').fadeIn();
		} else {
			$fieldset.find('div.peek-smtp-encryption')
				.fadeOut()
				.find('input:text')
				.val('')
				;
		}
	});
});
</script>