<h2>Localization</h2>

<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmSetupLocalization" onsubmit="return false;">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="localization">
<input type="hidden" name="action" value="saveJson">

<fieldset>
	<legend>Date &amp; Time</legend>
	
	{$setting_time_format = $settings->get('cerberusweb.core',CerberusSettings::TIME_FORMAT,CerberusSettingsDefaults::TIME_FORMAT)}
	
	<b>{'preferences.account.timeformat'|devblocks_translate|capitalize}</b><br>
	<select name="time_format">
		{$timeformats = ['D, d M Y h:i a', 'D, d M Y H:i']}
		{foreach from=$timeformats item=timeformat}
			<option value="{$timeformat}" {if $setting_time_format==$timeformat}selected{/if}>{time()|devblocks_date:$timeformat}</option>
		{/foreach}
	</select><br>
	<br>

	<div class="status"></div>
	
	<button type="button" class="submit"><span class="cerb-sprite2 sprite-tick-circle"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
</fieldset>
</form>

<script type="text/javascript">
	$('#frmSetupLocalization BUTTON.submit')
		.click(function(e) {
			genericAjaxPost('frmSetupLocalization','',null,function(json) {
				$o = $.parseJSON(json);
				if(false == $o || false == $o.status) {
					Devblocks.showError('#frmSetupLocalization div.status',$o.error);
				} else {
					Devblocks.showSuccess('#frmSetupLocalization div.status','Your changes have been saved.');
				}
			});
		})
	;
</script>
