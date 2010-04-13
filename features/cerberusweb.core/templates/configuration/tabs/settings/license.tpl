<div class="block">
<h2>License Info</h2>
<br>

{if empty($license.key)}
	<span style="color:rgb(200,0,0);">No License (Free Mode)</span><br>
	<ul style="margin-top:0px;">
		<li>Limited to 3 workers</li>
		<li>Cerberus Helpdesk tagline on outgoing e-mail</li>
		<li>Worker itemized permissions are disabled</li>
		<li><a href="http://www.cerberusweb.com/buy" target="_blank">Purchase a Cerberus Helpdesk license</a></li>
	</ul> 
{else}
	Licensed to: {$license.company}<br>
	Order e-mail: {$license.email}<br>
	<br>
	## BEGIN CERB5 LICENSE<br>
	Key: {$license.key}<br>
	Created: {$license.created|devblocks_date:'Y-m-d':true}<br>
	Updated: {$license.updated|devblocks_date:'Y-m-d':true}<br>
	Expires: {$license.expires|devblocks_date:'Y-m-d':true}<br>
	Workers: {if 100==$license.workers}100+{else}{$license.workers}{/if}<br>
	## END<br>
	<br>
	<a href="javascript:;" onclick="$(this).fadeOut();$('#frmLicense').fadeIn();">add new license</a>
{/if} 

<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmLicense" style="{if !empty($license)}display:none;{/if}">
<h2>Enter License</h2>
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="saveLicense">
<input type="hidden" name="do_delete" value="0">

<b>Enter your company name <u>exactly</u> as it appears on your order:</b></br>
<input type="text" name="company" size="64" value=""><br>
<br>

<b>Enter your e-mail address <u>exactly</u> as it appears on your order:</b></br>
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
{if !empty($license)}<button type="button" onclick="if(confirm('Are you sure you want to remove your license?')) { this.form.do_delete.value='1'; genericAjaxPost('frmLicense','divLicenseInfo'); } "><span class="cerb-sprite sprite-forbidden"></span> Remove License</button>{/if}
</form>

</div>


