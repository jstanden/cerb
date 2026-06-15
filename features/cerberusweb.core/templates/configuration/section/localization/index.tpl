<div class="cerb-ui-header">
	<div>
		<div class="cerb-ui-header--title">{'common.localization'|devblocks_translate|capitalize}</div>
		<div class="cerb-ui-header--subtitle">Configure the default timezone and format</div>
	</div>
</div>

<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmSetupLocalization" class="cerb-ui-form">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="localization">
<input type="hidden" name="action" value="saveJson">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div class="cerb-ui-header--title-sm">Date &amp; Time</div>
	</div>

	<div class="cerb-ui-form--row">
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'preferences.account.timezone'|devblocks_translate|capitalize}</label>
			<select name="timezone">
				<option value="">({'common.default'|devblocks_translate|lower})</option>
				{foreach from=$timezones item=tz}
					<option value="{$tz}" {if $tz==$setting_timezone}selected{/if}>{$tz}</option>
				{/foreach}
			</select>
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'preferences.account.timeformat'|devblocks_translate|capitalize}</label>
			<select name="time_format">
				{$timeformats = ['D, d M Y h:i a', 'D, d M Y H:i']}
				{foreach from=$timeformats item=timeformat}
					<option value="{$timeformat}" {if $setting_time_format==$timeformat}selected{/if}>{$smarty.now|devblocks_date:$timeformat}</option>
				{/foreach}
			</select>
		</div>
	</div>
</div>

<div>
	<button type="button" id="btnSaveLocalization" class="cerb-ui-button cerb-u-anim-group"><span class="cerb-icons cerb-icon-circle-ok cerb-u-anim-pulse-hover"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
</div>
</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	const $frm = $('#frmSetupLocalization');

	Devblocks.formDisableSubmit($frm);

	if(window.CerbUI && CerbUI.SelectMenu) {
		$frm.find('select[name=timezone], select[name=time_format]').each(function() {
			new CerbUI.SelectMenu(this);
		});
	}

	$frm.find('#btnSaveLocalization')
		.click(function(e) {
			e.stopPropagation();
			Devblocks.saveAjaxForm($frm);
		})
	;
});
</script>
