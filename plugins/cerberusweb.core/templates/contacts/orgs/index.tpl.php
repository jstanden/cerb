<table cellspacing="0" cellpadding="0" border="0" width="100%" style="padding-bottom:5px;">
<tr>
	<td width="1%" nowrap="nowrap" valign="top" style="padding-right:5px;">
		<h1>Organizations</h1>
	</td>
	<td width="98%" valign="middle">
		{include file="file:$path/contacts/menu.tpl.php"}
	</td>
	<td width="1%" nowrap="nowrap" valign="middle" align="right">
		<form action="{devblocks_url}{/devblocks_url}" method="post">
		<input type="hidden" name="c" value="contacts">
		<input type="hidden" name="a" value="doOrgQuickSearch">
		<span><b>Quick Search:</b></span> <select name="type">
			<option value="name">Name</option>
			<option value="phone">Phone</option>
		</select><input type="text" name="query" size="24"><input type="submit" value="go!">
		</form>
	</td>
</tr>
</table>

<table cellpadding="0" cellspacing="0" border="0">
	<tr>
		<td valign="top" width="0%" nowrap="nowrap">
			{include file="file:$path/internal/views/criteria_list.tpl.php" divName="searchCriteriaDialog"}
			<div id="searchCriteriaDialog" style="visibility:visible;"></div>
			
			<form action="{devblocks_url}{/devblocks_url}" style="margin-top:5px;">
				<button type="button" onclick="genericAjaxPanel('c=contacts&a=showOrgPeek&id=0&view_id={$view->id}',this,false,'500px',ajax.cbAddressPeek);"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/office-building.gif{/devblocks_url}" align="top"> Add Org</button>
			</form>
		</td>
		<td valign="top" width="0%" nowrap="nowrap"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/spacer.gif{/devblocks_url}" width="5" height="1"></td>
		<td valign="top" width="100%">
			<div id="view{$view->id}">{$view->render()}</div>
		</td>
	</tr>
</table>