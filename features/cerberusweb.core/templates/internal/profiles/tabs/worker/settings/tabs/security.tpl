{$form_id = uniqid()}
<form id="{$form_id}" class="cerb-ui-form" action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invokeTab">
<input type="hidden" name="tab_id" value="{$tab->id}">
<input type="hidden" name="section" value="worker">
<input type="hidden" name="action" value="saveSettingsSectionTabJson">
<input type="hidden" name="worker_id" value="{$worker->id}">
<input type="hidden" name="tab" value="security">

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight"><div class="cerb-ui-header--title-sm">{'common.auth.2fa'|devblocks_translate|capitalize}</div></div>

	<p class="cerb-u-text-muted">
		Two-factor authentication adds extra protection to your account during logins and account recovery by requiring a security code from a device in your possession in addition to your password.
	</p>

	{if !$worker->is_mfa_required}
	<div class="cerb-ui-form--field">
		<div>
			<input type="hidden" name="mfa_params[state]" id="mfa_state_{$form_id}" value="{if $is_mfa_enabled}1{else}0{/if}">
			<div class="cerb-ui-switcher" data-cerb-input="mfa_state_{$form_id}">
				<button type="button" data-value="1"{if $is_mfa_enabled} class="cerb-ui-switcher--active"{/if}>{'common.enabled'|devblocks_translate|capitalize}</button>
				<button type="button" data-value="0"{if !$is_mfa_enabled} class="cerb-ui-switcher--active"{/if}>{'common.disabled'|devblocks_translate|capitalize}</button>
			</div>
		</div>
	</div>

	{if $is_mfa_enabled}
	<div class="cerb-mfa-backup-codes cerb-u-my-2">
		<div class="cerb-u-mb-1">
			<button type="button" class="cerb-ui-button cerb-ui-button--subtle cerb-mfa-regenerate-backup-codes"><span class="cerb-icons cerb-icon-refresh"></span> Generate new backup codes ({$mfa_backup_code_count} remaining)</button>
		</div>

		{if $mfa_backup_code_count > 2}
		{elseif $mfa_backup_code_count > 0}
			<div class="cerb-ui-panel cerb-ui-panel--spaced cerb-ui-panel--warn">
				<div class="cerb-ui-header cerb-ui-header--center">
					<div class="cerb-ui-callout">
						<span class="cerb-icons cerb-icon-alert cerb-ui-callout--icon"></span>
						<div>
							<div class="cerb-ui-header--subtitle">Only {$mfa_backup_code_count} backup codes remaining</div>
						</div>
					</div>
				</div>
			</div>
		{else}
			<div class="cerb-ui-panel cerb-ui-panel--spaced cerb-ui-panel--alert">
				<div class="cerb-ui-header cerb-ui-header--center">
					<div class="cerb-ui-callout">
						<span class="cerb-icons cerb-icon-alert cerb-ui-callout--icon"></span>
						<div>
							<div class="cerb-ui-header--subtitle">No backup codes remaining</div>
						</div>
					</div>
				</div>
			</div>
		{/if}
	</div>
	{/if}

	{if !$is_mfa_enabled}
	<div class="cerb-ui-panel cerb-ui-panel--spaced cerb-mfa-enable" style="display:none;">
		<input type="hidden" name="mfa_params[seed]" value="{$seed}">

		<div class="cerb-u-fw-700 cerb-u-mb-1">Step 1: Scan this QR code with your app (e.g. Apple Keychain, 1Password, Google Authenticator):</div>

		<div class="qrcode"></div>

		<p class="cerb-u-mt-1">
			or type this code manually: <b>{$seed}</b>
		</p>

		<p class="cerb-u-mt-1 cerb-u-text-muted">
			Need help? See: <a href="https://cerb.ai/guides/security/two-factor-auth/" target="_blank" rel="noopener noreferrer" tabindex="-1">Configure two-factor authentication</a>
		</p>

		<div class="cerb-u-fw-700 cerb-u-mt-2 cerb-u-mb-1">Step 2: Type the current access code from your two-factor app:</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--control">
				<span class="cerb-ui-form--control-icon cerb-icons cerb-icon-key"></span>
				<input type="text" name="mfa_params[otp]" value="" placeholder="e.g. 123456" autocomplete="off">
			</label>
		</div>
	</div>
	{/if}
	{else}
	<p>
		<b class="cerb-u-fs-1"><span class="cerb-icons cerb-icon-checked"></span> {'common.required'|devblocks_translate|capitalize}</b>
	</p>
	{/if}
</div>

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight"><div class="cerb-ui-header--title-sm">Secret Questions</div></div>

	<p class="cerb-u-text-muted">
		When recovering your account's login information without two-factor authentication, you'll be asked one or more of the following secret questions to verify your identity.
	</p>
	<p class="cerb-u-text-muted">
		You should pick questions that don't have answers that could be easily obtained from social networks or a Google search.  Your answers shouldn't come from a small set of choices that could be guessed in a few attempts, such as "How old were you when...".
	</p>

	{$q_placeholder = ["e.g. Where do you wish you met your spouse?","e.g. What is your favorite sentence in your favorite book?","e.g. What did you turn into gold during a lucid dream?"]}
	{$a_placeholder = ["astronaut training","\"Did I say sharks?\" I exclaimed hastily. \"I meant 150 pearls. Sharks wouldn't make sense.\"","a rubber duck"]}

	{section start=0 loop=3 name=secrets}
	{$section_idx = $smarty.section.secrets.index}
	<div class="cerb-u-mt-2">
		<div class="cerb-u-fw-700 cerb-u-mb-1">Secret Question #{$smarty.section.secrets.iteration}</div>
		<div class="cerb-ui-form">
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">Question</label>
				<input type="text" name="sq_q[]" value="{$secret_questions.$section_idx.q}" placeholder="{$q_placeholder.$section_idx}" autocomplete="off">
			</div>
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">Hint</label>
				<input type="text" name="sq_h[]" value="{$secret_questions.$section_idx.h}" placeholder="" autocomplete="off">
			</div>
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">Answer</label>
				<input type="text" name="sq_a[]" value="{$secret_questions.$section_idx.a}" placeholder="{$a_placeholder.$section_idx}" autocomplete="off">
			</div>
		</div>
	</div>
	{/section}
</div>

<div>
	<div class="cerb-ui-toolbar-strip">
		<button type="button" id="btnSave_{$form_id}" class="cerb-ui-toolbar-button cerb-u-anim-group"><span class="cerb-icons cerb-icon-circle-ok cerb-u-anim-pulse-hover"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	</div>
</div>
</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#{$form_id}');

	if(window.CerbUI && CerbUI.Switcher) {
		$frm.find('.cerb-ui-switcher[data-cerb-input]').each(function() {
			let input = document.getElementById(this.getAttribute('data-cerb-input'));
			new CerbUI.Switcher(this, {
				value: input ? input.value : null,
				onSelect: function(value) { if(input) { input.value = value; input.dispatchEvent(new Event('change')); } }
			});
		});
	}

	function cerbMfaOpenBackupCodesPopup() {
		let formData = new FormData();
		formData.set('c', 'profiles');
		formData.set('a', 'invokeTab');
		formData.set('tab_id', '{$tab->id}');
		formData.set('action', 'renderMfaBackupCodesPopup');
		formData.set('worker_id', '{$worker->id}');

		let $popup = genericAjaxPopup(
			'mfa_backup_codes',
			formData,
			null,
			false,
			'50%'
		);

		$popup.one('popup_close', function() {
			window.CerbUI?.Tabs?.fromPanel($frm[0])?.refresh();
		});
	}

	{if !$worker->is_mfa_required}
	let $input_mfa_enable = $frm.find('input[name="mfa_params[state]"]');
	let $div_mfa_enable = $frm.find('div.cerb-mfa-enable');

	if(window.CerbUI && CerbUI.QrCode)
		new CerbUI.QrCode($frm.find('.qrcode')[0], { size:192, text:"otpauth://totp/Cerb:{$seed_name|escape:'url'}?secret={$seed}&issuer=Cerb" });

	$input_mfa_enable.on('change', function(e) {
		if($(this).val() == '1') {
			{if !$is_mfa_enabled}
			$div_mfa_enable.fadeIn();
			$div_mfa_enable.find('input:text').focus();
			{/if}
		} else {
			$div_mfa_enable.hide();
		}
	});
	{/if}

	$frm.find('#btnSave_{$form_id}').on('click', function(e) {
		e.stopPropagation();
		Devblocks.saveAjaxTabForm($frm);
	});

	{if $is_mfa_enabled}
	$frm.find('button.cerb-mfa-regenerate-backup-codes').on('click', function(e) {
		e.stopPropagation();
		{if $mfa_backup_code_count > 0}
		CerbUI.Confirm.open({
			title: 'Generate new backup codes',
			body: 'This will invalidate {$mfa_backup_code_count} existing backup code{if $mfa_backup_code_count != 1}s{/if}. Continue?',
			confirmText: '{'common.continue'|devblocks_translate|capitalize}',
			onConfirm: function() { cerbMfaOpenBackupCodesPopup(); }
		});
		{else}
		cerbMfaOpenBackupCodesPopup();
		{/if}
	});
	{/if}
});
</script>
