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
	
	{if !$is_mfa_enabled}
	<div class="block cerb-mfa-enable" style="padding:10px;margin:5px 0;display:none;">
		<input type="hidden" name="mfa_params[seed]" value="{$seed}">
	
		<h3>Step 1: Scan this QR code with your app (e.g. 1Password, Authy, Google Authenticator):</h3>
		
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

<button type="button" class="submit" style="margin-top:10px;"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#{$form_id}');
	
	{if !$worker->is_mfa_required}
	var $input_mfa_enable = $frm.find('input[name="mfa_params[state]"]');
	var $div_mfa_enable = $frm.find('div.cerb-mfa-enable');
	
	var options = { width:192, height:192, text:"otpauth://totp/Cerb:{$seed_name}?secret={$seed}" };
	var hasCanvasSupport = !!window.CanvasRenderingContext2D;

	// If no <canvas> tag, use <table> instead
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
});
</script>