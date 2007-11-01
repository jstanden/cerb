<div style="position: relative; width:100%; height: 30;">
	<span style="position: absolute; left: 0; top:0;"><h1 style="display:inline;">Organizations</h1>&nbsp;
		{include file="file:$path/contacts/menu.tpl.php"}
	</span>
	<span style="position: absolute; right: 0; top:0;">
		<form action="{devblocks_url}{/devblocks_url}" method="post">
		<input type="hidden" name="c" value="contacts">
		<input type="hidden" name="a" value="doOrgQuickSearch">
		<span><b>Quick Search:</b></span> <select name="type">
			<option value="name">Name</option>
			<option value="phone">Phone</option>
		</select><input type="text" name="query" size="24"><input type="submit" value="go!">
		</form>
	</span>
</div>

<table cellpadding="0" cellspacing="0" border="0">
	<tr>
		<td valign="top" width="0%" nowrap="nowrap">
			{include file="file:$path/internal/views/criteria_list.tpl.php" divName="searchCriteriaDialog"}
			<div id="searchCriteriaDialog" style="visibility:visible;"></div>
			<a href="javascript:;" onclick="genericAjaxPanel('c=contacts&a=showOrgPeek&id=0&view_id={$view->id}',this,false,'500px');">Add Organization</a>
		</td>
		<td valign="top" width="0%" nowrap="nowrap"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/spacer.gif{/devblocks_url}" width="5" height="1"></td>
		<td valign="top" width="100%">
			<div id="view{$view->id}">{$view->render()}</div>
		</td>
	</tr>
</table>