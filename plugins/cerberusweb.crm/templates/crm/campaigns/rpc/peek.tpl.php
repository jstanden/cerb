<table cellpadding="0" cellspacing="0" border="0" width="98%">
	<tr>
		<td align="left" width="0%" nowrap="nowrap" style="padding-right:5px;"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/folder.gif{/devblocks_url}" align="absmiddle"></td>
		<td align="left" width="100%" nowrap="nowrap"><h1>Campaign</h1></td>
	</tr>
</table>

<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formCampaignPeek" name="formCampaignPeek" onsubmit="return false;">
<input type="hidden" name="c" value="crm">
<input type="hidden" name="a" value="saveCampaignPanel">
<input type="hidden" name="campaign_id" value="{$campaign->id}">
<input type="hidden" name="view_id" value="{$view_id}">

<table cellpadding="0" cellspacing="2" border="0" width="98%">
	<tr>
		<td width="0%" nowrap="nowrap" align="right" valign="top">Name: </td>
		<td width="100%">
			<input type="text" name="name" style="width:98%;border:1px solid rgb(180,180,180);padding:2px;" value="{$campaign->name|escape}" autocomplete="off">
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" align="right" valign="top">Buckets: </td>
		<td width="100%">
			<div style="height:200px;overflow:auto;width:100%;">
			{if !empty($buckets)}
				<table cellpadding="0" cellspacing="0" border="0">
				<tr>
					<td><b>Name</b></td>	
					<td><b>Remove</b></td>	
				</tr>
				{foreach from=$buckets item=bucket key=bucket_id}
					<tr>
						<td>
							<input type="hidden" name="bucket_ids[]" value="{$bucket->id}">
							<input type="text" name="bucket_names[]" style="width:250px;border:1px solid rgb(180,180,180);padding:2px;" value="{$bucket->name|escape}">
						</td>
						<td align="center">
							<input type="checkbox" name="bucket_dels[]" value="{$bucket->id}">
						</td>
					</tr>
				{/foreach}
				</table>
			{/if}
			<b>Add Buckets:</b> (comma separated)<br>
			<input type="text" name="add_buckets_csv" style="width:98%;border:1px solid rgb(180,180,180);padding:2px;" value=""><br>
			</div>
		</td>
	</tr>
</table>

<button type="button" onclick="genericPanel.hide();genericAjaxPost('formCampaignPeek', 'view{$view_id}')"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')}</button>
<button type="button" onclick="genericPanel.hide();"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete.gif{/devblocks_url}" align="top"> {$translate->_('common.close')|capitalize}</button>
<br>
</form>
