<h2>License</h2>

{$we_trust_you=CerberusLicense::getInstance()}
<fieldset>
	<legend>Active License</legend>

	{if !$we_trust_you->key}
		<span style="color:rgb(200,0,0);">No License (Evaluation Edition)</span><br>
		<ul style="margin-top:0px;">
			<li>Limited to 1 simultaneous worker.</li>
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
			
			<div style="margin-top:5px;">
				<button type="button" onclick="$(this).parent().fadeOut();$('#frmLicense').fadeIn().find('input:text:first').focus();"><span class="cerb-sprite2 sprite-plus-circle"></span> Update License</button>
			</div>
		{/if}
	{/if}
</fieldset>

{if !$smarty.const.ONDEMAND_MODE}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmLicense" style="{if $we_trust_you->key && empty($error)}display:none;{/if}" onsubmit="return false;">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="license">
<input type="hidden" name="action" value="saveJson">
<input type="hidden" name="do_delete" value="0">
	
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

	<div class="status"></div>
	<div class="delete_confirm" style="display:none;">
		<div class="ui-widget">
			<div class="ui-state-highlight ui-corner-all" style="margin:10px;padding:5px;display:inline-block;">
				Are you sure you want to remove your license?<br>
				<button type="button" onclick="$frm=$(this.form);$frm.find('input:hidden[name=do_delete]').val('1');$frm.find('BUTTON.submit').click();"><span class="cerb-sprite2 sprite-tick-circle"></span> Yes</button>
				<button type="button" onclick="$(this).closest('div.delete_confirm').hide();"><span class="cerb-sprite2 sprite-minus-circle"></span> No</button>
			</div>
		</div>
	</div>
	
	<button type="button" class="submit"><span class="cerb-sprite2 sprite-tick-circle"></span> {$translate->_('common.save_changes')|capitalize}</button>
	{if $we_trust_you->key}<button type="button" class="delete"><span class="cerb-sprite2 sprite-minus-circle"></span> Remove License</button>{/if}

</fieldset>
</form>
{/if}

<script type="text/javascript">
	$('#frmLicense BUTTON.submit')
		.click(function(e) {
			genericAjaxPost('frmLicense','',null,function(json) {
				$o = $.parseJSON(json);
				if(false == $o || false == $o.status) {
					Devblocks.showError('#frmLicense div.status',$o.error);
				} else {
					//Devblocks.showSuccess('#frmLicense div.status','Settings saved!');
					document.location.href = '{devblocks_url}c=config&a=license{/devblocks_url}';
				}
			});
		})
	;
	$('#frmLicense BUTTON.delete')
		.click(function(e) {
			$frm = $('#frmLicense');
			$frm.find('div.status:visible').html('');
			$frm.find('div.delete_confirm').fadeIn();
		})
	;
</script>
