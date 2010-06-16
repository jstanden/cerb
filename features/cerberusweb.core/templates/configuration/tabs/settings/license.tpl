{$we_trust_you=CerberusLicense::getInstance()}
<div class="block">
<h2>License Info</h2>
<br>

{if !$we_trust_you->key}
	<span style="color:rgb(200,0,0);">No License (Evaluation Edition)</span><br>
	<ul style="margin-top:0px;">
		<li>Limited to 1 simultaneous worker.</li>
		{*
		<li>Worker itemized permissions are disabled.</li>
		<li>Web-API is disabled.</li>
		*}
		<li><a href="http://www.cerberusweb.com/buy" target="_blank">Purchase a Cerberus Helpdesk license</a></li>
	</ul> 
{else}
	{if $smarty.const.ONDEMAND_MODE}
		<b>Licensed To:</b> {$we_trust_you->company}<br>
		<b>Simultaneous Workers:</b> {if 100==$we_trust_you->seats}100+{else}{$we_trust_you->seats}{/if}<br>
	{else}
		<b>Serial #:</b> {$we_trust_you->key}<br>
		<b>Licensed To:</b> {$we_trust_you->company}<br>
		<b>Simultaneous Workers:</b> {if 100==$we_trust_you->seats}100+{else}{$we_trust_you->seats}{/if}<br>
		<b>Software Updates Expire:</b> {$we_trust_you->upgrades|devblocks_date:'F d, Y':true}<br>
		<br>
		{if empty($error)}<a href="javascript:;" onclick="$(this).fadeOut();$('#frmLicense').fadeIn();">add new license</a>{/if}
	{/if}
{/if} 

{if !$smarty.const.ONDEMAND_MODE}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmLicense" style="{if $we_trust_you->key && empty($error)}display:none;{/if}">
<h2>Enter License</h2>
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="saveLicense">
<input type="hidden" name="do_delete" value="0">

<b>Enter your company name <u>exactly</u> as it appears on your order:</b><br>
<input type="text" name="company" size="64" value=""><br>
<br>

<b>Enter your e-mail address <u>exactly</u> as it appears on your order:</b><br>
<input type="text" name="email" size="64" value=""><br>
<br>

<b>Paste the license information you received with your order:</b><br>
<textarea rows="8" cols="80" name="key"></textarea><br>
<br>

{if $error}
	<div class="ui-widget">
		<div class="ui-state-error ui-corner-all" style="padding: 0 .7em; margin: 0.2em; "> 
			<p><span class="ui-icon ui-icon-alert" style="float: left; margin-right: .3em;"></span> 
			{$error}</p>
		</div>
	</div>
	<br>
{elseif $success}
	<div class="ui-widget">
		<div class="ui-state-highlight ui-corner-all" style="padding: 0 .7em; margin: 0.2em; "> 
			<p><span class="ui-icon ui-icon-info" style="float: left; margin-right: .3em;"></span> 
			<strong>Success:</strong> {$success}</p>
		</div>
	</div>
	<br>
{/if}

<button type="button" onclick="genericAjaxPost('frmLicense','divLicenseInfo');"><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')|capitalize}</button>
{if $we_trust_you->key}<button type="button" onclick="if(confirm('Are you sure you want to remove your license?')) { this.form.do_delete.value='1'; genericAjaxPost('frmLicense','divLicenseInfo'); } "><span class="cerb-sprite sprite-forbidden"></span> Remove License</button>{/if}
</form>
{/if}

</div>


