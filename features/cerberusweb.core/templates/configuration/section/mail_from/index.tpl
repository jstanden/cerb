<h2>Reply-To Addresses</h2>

<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmSetupMailFrom" onsubmit="return false;">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="mail_from">
<input type="hidden" name="action" value="saveJson">

<div style="margin-bottom:10px;">
	<button type="button" onclick="genericAjaxPopup('peek','c=config&a=handleSectionAction&section=mail_from&action=peek&id=0',null,false,'550');"><span class="cerb-sprite sprite-add"></span> {'common.add'|devblocks_translate|capitalize}</button>
</div>

{foreach from=$addresses item=address key=address_id}
<fieldset style="border:0;">
	<legend {if $address->is_default}style="color:rgb(74,110,178);"{/if}>
		<a href="javascript:;" {if $address->is_default}style="color:rgb(74,110,178);"{/if} onclick="genericAjaxPopup('peek','c=config&a=handleSectionAction&section=mail_from&action=peek&id={$address_id}',null,false,'550');">{$address->email}</a>
		{if $address->is_default}
		(default)
		{/if}
	</legend>
	
	<table cellpadding="0" cellspacing="5" border="0">
		<tr>
			<td valign="top" style="min-width:75px;">
				<b>From:</b>
			</td>
			<td>
				{if !empty($address->reply_personal)}
				{$address->reply_personal} 
				{/if}
				&lt;{$address->email}&gt;
			</td>
		</tr>
		
		{if !empty($address->reply_signature)}
		<tr>
			<td valign="top">
				<b>Signature:</b>
			</td>
			<td>
				<div style="display:inline-block;padding:10px;border:1px solid rgb(200,200,200);background-color:rgb(245,245,245);">
				{$address->reply_signature|escape:'html'|devblocks_hyperlinks|nl2br nofilter}
				</div>
			</td>
		</tr>
		{/if}
	</table>
</fieldset>
{/foreach}

</form>

<script type="text/javascript">
	$('#frmSetupMailFrom BUTTON.submit')
		.click(function(e) {
			genericAjaxPost('frmSetupMailFrom','',null,function(json) {
				$o = $.parseJSON(json);
				if(false == $o || false == $o.status) {
					Devblocks.showError('#frmSetupMailFrom div.status',$o.error);
				} else {
					Devblocks.showSuccess('#frmSetupMailFrom div.status','Settings saved!');
				}
			});
		})
	;
</script>