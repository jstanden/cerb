<h2>Localization</h2>

<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmSetupLocalization" onsubmit="return false;">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="localization">
<input type="hidden" name="action" value="saveJson">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<fieldset>
	<legend>Date &amp; Time</legend>
	
	{$setting_timezone = $settings->get('cerberusweb.core', CerberusSettings::TIMEZONE, '')}

	<div style="margin-bottom:5px;">
		<b>{'preferences.account.timezone'|devblocks_translate|capitalize}</b><br>
		<select name="timezone">
			<option value="">({'common.default'|devblocks_translate|lower})</option>
			{foreach from=$timezones item=tz}
				<option value="{$tz}" {if $tz==$setting_timezone}selected{/if}>{$tz}</option>
			{/foreach}
		</select>
	</div>
	
	{$setting_time_format = $settings->get('cerberusweb.core',CerberusSettings::TIME_FORMAT,CerberusSettingsDefaults::TIME_FORMAT)}
	
	<b>{'preferences.account.timeformat'|devblocks_translate|capitalize}</b><br>
	<select name="time_format">
		{$timeformats = ['D, d M Y h:i a', 'D, d M Y H:i']}
		{foreach from=$timeformats item=timeformat}
			<option value="{$timeformat}" {if $setting_time_format==$timeformat}selected{/if}>{time()|devblocks_date:$timeformat}</option>
		{/foreach}
	</select><br>
	<br>

	<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
</fieldset>
</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#frmSetupLocalization');
	
	$frm.find('BUTTON.submit')
		.click(function(e) {
			Devblocks.saveAjaxForm($frm);
		})
	;
});
</script>
