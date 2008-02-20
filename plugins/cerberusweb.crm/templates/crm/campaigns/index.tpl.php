{include file="$path/crm/submenu.tpl.php"}

<table cellspacing="0" cellpadding="0" border="0" width="100%" style="padding-bottom:5px;">
<tr>
	<td width="1%" nowrap="nowrap" valign="top" style="padding-right:5px;">
		<h1>Campaigns</h1>
	</td>
	<td width="99%" valign="middle">
	</td>
</tr>
</table>

<form action="{devblocks_url}{/devblocks_url}" method="post" style="padding-bottom:5px;">
	<input type="hidden" name="c" value="crm">
	<input type="hidden" name="a" value="">
	<button type="button" onclick="genericAjaxPanel('c=crm&a=showCampaignPanel&id=0&view_id=',this,false,'500px');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/folder_add.gif{/devblocks_url}" align="top"> Add Campaign</button>
</form>

{foreach from=$campaigns item=campaign key=campaign_id}
	<a href="javascript:;" onclick="genericAjaxPanel('c=crm&a=showCampaignPanel&id={$campaign_id}&view_id=',this,false,'500px');">{$campaign->name}</a><br>
{/foreach}
<br>
