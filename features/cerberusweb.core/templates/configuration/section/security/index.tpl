<div class="cerb-ui-header">
	<div>
		<div class="cerb-ui-header--title">{{'common.security'|devblocks_translate|capitalize}}</div>
		<div class="cerb-ui-header--subtitle"></div>
	</div>
</div>

<form id="frmSetupSecurity" action="{devblocks_url}{/devblocks_url}" method="post" class="cerb-ui-form">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="security">
<input type="hidden" name="action" value="saveJson">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div class="cerb-ui-header--title-sm">Session Cookies</div>
	</div>

	<div class="cerb-ui-form--field">
		<label class="cerb-ui-form--label">Expire sessions after this period of inactivity</label>
		{$opts = [
		["15 minutes",900],
		["30 minutes",1800],
		["1 hour",3600],
		["2 hours",7200],
		["4 hours",14400],
		["6 hours",21600],
		["8 hours",28800],
		["12 hours",43200],
		["1 day",86400],
		["3 days",259200],
		["1 week",604800],
		["2 weeks",1209600],
		["1 month",2592000]
		]}
		<select name="session_lifespan">
			{foreach from=$opts item=opt}
				<option value="{$opt[1]}" {if $opt[1]==$session_lifespan}selected="selected"{/if}>{$opt[0]}</option>
			{/foreach}
		</select>
	</div>
</div>

<div class="cerb-ui-panel cerb-ui-panel--spaced cerb-ui-panel--warn">
	<div class="cerb-ui-header">
		<div class="cerb-ui-callout">
			<span class="cerb-icons cerb-icon-alert cerb-ui-callout--icon"></span>
			<div>
				<div class="cerb-ui-header--title-sm">Deprecation Warning</div>
				<div class="cerb-ui-header--subtitle">IP-based authentication is deprecated. Use <a href="{devblocks_url}c=config&a=service_tokens{/devblocks_url}">Service Tokens</a> instead.</div>
			</div>
		</div>
	</div>
</div>

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div class="cerb-ui-header--title-sm">Administration Endpoints (/cron, /debug, /update)</div>
	</div>

	<div class="cerb-ui-form--field">
		<label class="cerb-ui-form--label">Allow access from these IPs <span class="cerb-ui-form--hint">(one IP per line)</span></label>
		<textarea name="authorized_ips" rows="5">{$authorized_ips}</textarea>
		<div class="cerb-ui-form--help">Partial IP matches OK &mdash; e.g. 192.168.1.</div>
	</div>
</div>

<div>
	<button type="button" id="btnSaveSecurity" class="cerb-ui-button cerb-u-anim-group"><span class="cerb-icons cerb-icon-circle-ok cerb-u-anim-pulse-hover"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
</div>

</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	const $frm = $('#frmSetupSecurity');

	Devblocks.formDisableSubmit($frm);

	if(window.CerbUI && CerbUI.SelectMenu) {
		$frm.find('select[name=session_lifespan]').each(function() {
			new CerbUI.SelectMenu(this);
		});
	}

	$frm.find('#btnSaveSecurity').on('click', function(e) {
		e.stopPropagation();
		Devblocks.clearAlerts();
		Devblocks.saveAjaxForm($frm);
	});
});
</script>
