{include file="file:$path/contacts/menu.tpl.php"}

<table cellpadding="0" cellspacing="0" border="0">
	<tr>
		<td valign="top" width="0%" nowrap="nowrap">
			{include file="file:$path/contacts/criteria_list.tpl.php" divName="searchCriteriaDialog"}
			<div id="searchCriteriaDialog" style="visibility:visible;"></div>
			<a href="{devblocks_url}c=contacts&a=orgs&id=0{/devblocks_url}">Add Organization</a>
		</td>
		<td valign="top" width="0%" nowrap="nowrap"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/spacer.gif{/devblocks_url}" width="5" height="1"></td>
		<td valign="top" width="100%">
			<div id="view{$view->id}">{include file="file:$path/contacts/orgs/contact_view.tpl.php"}</div>
		</td>
	</tr>
</table>