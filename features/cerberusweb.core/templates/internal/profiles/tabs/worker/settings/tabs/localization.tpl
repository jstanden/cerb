{$form_id = uniqid()}
<form id="{$form_id}" action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invokeTab">
<input type="hidden" name="tab_id" value="{$tab->id}">
<input type="hidden" name="section" value="worker">
<input type="hidden" name="action" value="saveSettingsSectionTabJson">
<input type="hidden" name="worker_id" value="{$worker->id}">
<input type="hidden" name="tab" value="localization">

<fieldset class="peek">
	<legend>{'common.localization'|devblocks_translate|capitalize}</legend>

	<div style="margin-bottom:5px;">
		<b>{'preferences.account.timezone'|devblocks_translate|capitalize}</b> {if !empty($server_timezone)}({'preferences.account.current'|devblocks_translate} {$server_timezone}){/if}<br>
		<select name="timezone">
			{foreach from=$timezones item=tz}
				<option value="{$tz}" {if $tz==$server_timezone}selected{/if}>{$tz}</option>
			{/foreach}
		</select>
	</div>
	
	<div style="margin-bottom:5px;">
		<b>{'preferences.account.timeformat'|devblocks_translate|capitalize}</b><br>
		<select name="time_format">
			{$timeformats = ['D, d M Y h:i a', 'D, d M Y H:i']}
			{foreach from=$timeformats item=timeformat}
				<option value="{$timeformat}" {if $prefs.time_format==$timeformat}selected{/if}>{time()|devblocks_date:$timeformat}</option>
			{/foreach}
		</select>
	</div>

	<div style="margin-bottom:5px;">
		<b>{'preferences.account.language'|devblocks_translate|capitalize}</b> {if !empty($selected_language) && isset($langs.$selected_language)}({'preferences.account.current'|devblocks_translate} {$langs.$selected_language}){/if}<br>
		<select name="lang_code">
			{foreach from=$langs key=lang_code item=lang_name}
				<option value="{$lang_code}" {if $lang_code==$selected_language}selected{/if}>{$lang_name}</option>
			{/foreach}
		</select>
	</div>
</fieldset>

<button type="button" class="submit" style="margin-top:10px;"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#{$form_id}');
	
	$frm.find('button.submit').on('click', function(e) {
		Devblocks.saveAjaxTabForm($frm);
	});
});
</script>