{$form_id = uniqid()}
<form id="{$form_id}" action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invokeTab">
<input type="hidden" name="tab_id" value="{$tab->id}">
<input type="hidden" name="section" value="worker">
<input type="hidden" name="action" value="saveSettingsSectionTabJson">
<input type="hidden" name="worker_id" value="{$worker->id}">
<input type="hidden" name="tab" value="security">

<fieldset class="peek">
	<legend>{'common.auth.2fa'|devblocks_translate|capitalize}</legend>

	<p>
		Two-factor authentication adds extra protection to your account during logins and account recovery by requiring a security code from a device in your possession in addition to your password.
	</p>

	{if !$worker->is_mfa_required}
	<p>
		<label><input type="radio" name="mfa_params[state]" value="1" {if $is_mfa_enabled}checked="checked"{/if}> {'common.enabled'|devblocks_translate|capitalize}</label>
		<label><input type="radio" name="mfa_params[state]" value="0" {if !$is_mfa_enabled}checked="checked"{/if}> {'common.disabled'|devblocks_translate|capitalize}</label>
	</p>

	{if $is_mfa_enabled}
	<div class="cerb-mfa-backup-codes" style="margin:10px 0;">
		<div style="margin-bottom:0.5em;">
			<button type="button" class="cerb-mfa-regenerate-backup-codes">Generate new backup codes ({$mfa_backup_code_count} remaining)</button>
		</div>

		{if $mfa_backup_code_count > 2}
		{elseif $mfa_backup_code_count > 0}
			<div class="help-box">
				<p><span class="glyphicons glyphicons-warning-sign" style="vertical-align:middle;"></span> Only {$mfa_backup_code_count} backup codes remaining</p>
			</div>
		{else}
			<div class="error-box">
				<p><span class="glyphicons glyphicons-warning-sign" style="vertical-align:middle;"></span> No backup codes remaining</p>
			</div>
		{/if}
	</div>
	{/if}

	{if !$is_mfa_enabled}
	<div class="block cerb-mfa-enable" style="padding:10px;margin:5px 0;display:none;">
		<input type="hidden" name="mfa_params[seed]" value="{$seed}">

		<h3>Step 1: Scan this QR code with your app (e.g. Apple Keychain, 1Password, Google Authenticator):</h3>

		<div class="qrcode"></div>

		<p style="margin-top:10px;">
			or type this code manually: <b>{$seed}</b>
		</p>

		<p style="margin-top:10px;">
			Need help? See: <a href="https://cerb.ai/guides/security/two-factor-auth/" target="_blank" rel="noopener noreferrer" tabindex="-1">Configure two-factor authentication</a>
		</p>

		<h3>Step 2: Type the current access code from your two-factor app:</h3>

		<div>
			<input type="text" name="mfa_params[otp]" size="45" value="" placeholder="e.g. 123456" style="width:100%;line-height:1.5em;height:24px;padding:0px 5px;border-radius:5px;box-sizing:border-box;">
		</div>
	</div>
	{/if}
	{else}
	<p>
		<b style="font-size:120%;"><span class="glyphicons glyphicons-check"></span> {'common.required'|devblocks_translate|capitalize}</b>
	</p>
	{/if}
</fieldset>

<fieldset class="peek">
	<legend>Secret Questions</legend>

	<p>
		When recovering your account's login information without two-factor authentication, you'll be asked one or more of the following secret questions to verify your identity.
	</p>
	<p>
		You should pick questions that don't have answers that could be easily obtained from social networks or a Google search.  Your answers shouldn't come from a small set of choices that could be guessed in a few attempts, such as "How old were you when...".
	</p>

	{$q_placeholder = ["e.g. Where do you wish you met your spouse?","e.g. What is your favorite sentence in your favorite book?","e.g. What did you turn into gold during a lucid dream?"]}
	{$a_placeholder = ["astronaut training","\"Did I say sharks?\" I exclaimed hastily. \"I meant 150 pearls. Sharks wouldn't make sense.\"","a rubber duck"]}

	{section start=0 loop=3 name=secrets}
	{$section_idx = $smarty.section.secrets.index}
	<h3 style="margin:5px 0;">Secret Question #{$smarty.section.secrets.iteration}</h3>

	<table cellspacing="1" cellpadding="0" border="0">
		<tr>
			<td>Question:</td>
			<td><input type="text" name="sq_q[]" value="{$secret_questions.$section_idx.q}" size="96" placeholder="{$q_placeholder.$section_idx}" autocomplete="off"></td>
		</tr>
		<tr>
			<td>Hint:</td>
			<td><input type="text" name="sq_h[]" value="{$secret_questions.$section_idx.h}" size="96" placeholder="" autocomplete="off"></td>
		</tr>
		<tr>
			<td>Answer:</td>
			<td><input type="text" name="sq_a[]" value="{$secret_questions.$section_idx.a}" size="96" placeholder="{$a_placeholder.$section_idx}" autocomplete="off"></td>
		</tr>
	</table>
	{/section}
</fieldset>

<button type="button" class="submit" style="margin-top:10px;"><span class="glyphicons glyphicons-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#{$form_id}');

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
			let $tabs = $frm.closest('.ui-tabs');
			let tabId = $tabs.tabs('option', 'active');
			$tabs.tabs('load', tabId);
		});
	}

	{if !$worker->is_mfa_required}
	let $input_mfa_enable = $frm.find('input[name="mfa_params[state]"]');
	let $div_mfa_enable = $frm.find('div.cerb-mfa-enable');

	let options = { width:192, height:192, text:"otpauth://totp/Cerb:{$seed_name}?secret={$seed}" };
	let hasCanvasSupport = !!window.CanvasRenderingContext2D;

	if(!hasCanvasSupport)
		options.render = 'table';

	$frm.find('.qrcode').qrcode(options);

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

	$frm.find('button.submit').on('click', function(e) {
		Devblocks.saveAjaxTabForm($frm);
	});

	{if $is_mfa_enabled}
	$frm.find('button.cerb-mfa-regenerate-backup-codes').on('click', function(e) {
		e.stopPropagation();
		{if $mfa_backup_code_count > 0}
		confirmPopup(
			'Generate new backup codes',
			'This will invalidate {$mfa_backup_code_count} existing backup code{if $mfa_backup_code_count != 1}s{/if}. Continue?',
			function() { cerbMfaOpenBackupCodesPopup(); }
		);
		{else}
		cerbMfaOpenBackupCodesPopup();
		{/if}
	});
	{/if}
});
</script>
