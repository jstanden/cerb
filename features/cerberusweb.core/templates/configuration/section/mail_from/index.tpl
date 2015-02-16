<h2>Reply-To Addresses</h2>

<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmSetupMailFrom" onsubmit="return false;">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="mail_from">
<input type="hidden" name="action" value="saveJson">

<div style="margin-bottom:10px;">
	<button type="button" onclick="genericAjaxPopup('peek','c=config&a=handleSectionAction&section=mail_from&action=peek&id=0',null,false,'550');"><span class="cerb-sprite2 sprite-plus-circle"></span> {'common.add'|devblocks_translate|capitalize}</button>
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
				{$address->getReplyPersonal($active_worker)}  
				&lt;{$address->email}&gt;
			</td>
		</tr>
		
		<tr>
			<td valign="top" style="min-width:75px;">
				<b>{'mail.transport'|devblocks_translate|capitalize}:</b>
			</td>
			<td>
				{$transport = $address->getReplyMailTransport()}
				{if $transport}
					<a href="{devblocks_url}c=profiles&a=mail_transport&id={$transport->id}-{$transport->name|devblocks_permalink}{/devblocks_url}">{$transport->name}</a>
				{else}
					({'common.default'|devblocks_translate|lower})
				{/if}  
			</td>
		</tr>
		
		<tr>
			<td valign="top" style="min-width:75px;">
				<b>HTML Template:</b>
			</td>
			<td>
				{$html_template = $address->getReplyHtmlTemplate()}
				{if $html_template}
				<a href="{devblocks_url}c=profiles&a=html_template&id={$html_template->id}-{$html_template->name|devblocks_permalink}{/devblocks_url}">{$html_template->name}</a>
				{/if}
			</td>
		</tr>
		
		<tr>
			<td valign="top">
				<b>Signature:</b>
			</td>
			<td>
				<div style="display:inline-block;padding:10px;border:1px {if empty($address->reply_signature)}dashed{else}solid{/if} rgb(200,200,200);background-color:rgb(245,245,245);">
				{$address->getReplySignature($active_worker)|escape:'html'|devblocks_hyperlinks|nl2br nofilter}
				</div>
			</td>
		</tr>
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