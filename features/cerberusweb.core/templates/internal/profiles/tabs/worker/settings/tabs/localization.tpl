{$form_id = uniqid()}
<form id="{$form_id}" class="cerb-ui-form" action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invokeTab">
<input type="hidden" name="tab_id" value="{$tab->id}">
<input type="hidden" name="section" value="worker">
<input type="hidden" name="action" value="saveSettingsSectionTabJson">
<input type="hidden" name="worker_id" value="{$worker->id}">
<input type="hidden" name="tab" value="localization">

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight"><div class="cerb-ui-header--title-sm">{'common.localization'|devblocks_translate|capitalize}</div></div>

	<div class="cerb-ui-form">
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'preferences.account.timezone'|devblocks_translate|capitalize}{if !empty($server_timezone)} <span class="cerb-ui-form--hint">{'preferences.account.current'|devblocks_translate} {$server_timezone}</span>{/if}</label>
			<select name="timezone">
				{foreach from=$timezones item=tz}
					<option value="{$tz}" {if $tz==$server_timezone}selected{/if}>{$tz}</option>
				{/foreach}
			</select>
		</div>

		<div class="cerb-ui-form--row">
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'preferences.account.timeformat'|devblocks_translate|capitalize}</label>
				<select name="time_format">
					{$timeformats = ['D, d M Y h:i a', 'D, d M Y H:i']}
					{foreach from=$timeformats item=timeformat}
						<option value="{$timeformat}" {if $prefs.time_format==$timeformat}selected{/if}>{$smarty.now|devblocks_date:$timeformat}</option>
					{/foreach}
				</select>
			</div>

			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'preferences.account.language'|devblocks_translate|capitalize}{if !empty($selected_language) && isset($langs.$selected_language)} <span class="cerb-ui-form--hint">{'preferences.account.current'|devblocks_translate} {$langs.$selected_language}</span>{/if}</label>
				<select name="lang_code">
					{foreach from=$langs key=lang_code item=lang_name}
						<option value="{$lang_code}" {if $lang_code==$selected_language}selected{/if}>{$lang_name}</option>
					{/foreach}
				</select>
			</div>
		</div>
	</div>
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

	Devblocks.formDisableSubmit($frm);

	if(window.CerbUI && CerbUI.SelectMenu)
		$frm.find('select').each(function() { new CerbUI.SelectMenu(this); });

	$frm.find('#btnSave_{$form_id}').on('click', function(e) {
		e.stopPropagation();
		Devblocks.saveAjaxTabForm($frm);
	});
});
</script>
