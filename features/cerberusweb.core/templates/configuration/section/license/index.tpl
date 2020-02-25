<h2>License</h2>

{$we_trust_you=CerberusLicense::getInstance()}
<fieldset>
	<legend>Active License</legend>

	{if !$we_trust_you->key}
		<span style="color:rgb(200,0,0);">No License (Evaluation Edition)</span><br>
		<ul style="margin-top:0px;">
			<li>Limited to 1 simultaneous worker.</li>
			<li><a href="https://cerb.ai/pricing" target="_blank" rel="noopener">Purchase a Cerb license</a></li>
		</ul> 
	{else}
		<b>Serial #:</b> {$we_trust_you->key}<br>
		<b>Licensed To:</b> {$we_trust_you->company}<br>
		<b>Simultaneous Workers:</b> {if 100==$we_trust_you->seats}100+{else}{$we_trust_you->seats}{/if}<br>
		<b>Software Updates Expire:</b> {$we_trust_you->upgrades|devblocks_date:'F d, Y':true}<br>
		
		<div style="margin-top:5px;">
			<button type="button" onclick="$(this).parent().fadeOut();$('#frmLicense').fadeIn().find('input:text:first').focus();"><span class="glyphicons glyphicons-cogwheel"></span> Update License</button>
		</div>
	{/if}
</fieldset>

<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmLicense" style="{if $we_trust_you->key && empty($error)}display:none;{/if}" onsubmit="return false;">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="license">
<input type="hidden" name="action" value="saveJson">
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">
	
<fieldset>
	<legend>Update License</legend>
	
	<b>Enter your company name <u>exactly</u> as it appears on your order:</b><br>
	<input type="text" name="company" size="64" value=""><br>
	<br>
	
	<b>Enter your e-mail address <u>exactly</u> as it appears on your order:</b><br>
	<input type="text" name="email" size="64" value=""><br>
	<br>
	
	<b>Paste the license information you received with your order:</b><br>
	<textarea rows="8" cols="80" name="key"></textarea><br>

	<div class="delete_confirm" style="display:none;">
		<div class="ui-widget">
			<div class="ui-state-highlight ui-corner-all" style="margin:10px;padding:5px;display:inline-block;">
				Are you sure you want to remove your license?<br>
				<button type="button" onclick="$frm=$(this.form);$frm.find('input:hidden[name=do_delete]').val('1');$frm.find('BUTTON.submit').click();"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> Yes</button>
				<button type="button" onclick="$(this).closest('div.delete_confirm').hide();"><span class="glyphicons glyphicons-circle-minus" style="color:rgb(200,0,0);"></span> No</button>
			</div>
		</div>
	</div>
	
	<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	{if $we_trust_you->key}<button type="button" class="delete"><span class="glyphicons glyphicons-circle-minus" style="color:rgb(200,0,0);"></span> Remove License</button>{/if}

</fieldset>
</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#frmLicense');
	
	$frm.find('BUTTON.submit')
		.click(function(e) {
			Devblocks.saveAjaxForm($frm, {
				success: function(json) {
					document.location.href = '{devblocks_url}c=config&a=license{/devblocks_url}';
				}
			});
		})
	;
	$frm.find('BUTTON.delete')
		.click(function(e) {
			$frm.find('div.delete_confirm').fadeIn();
		})
	;
});
</script>
