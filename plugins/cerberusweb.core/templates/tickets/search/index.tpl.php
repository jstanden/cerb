{include file="file:$core_tpl/tickets/submenu.tpl.php"}

<table cellspacing="0" cellpadding="0" border="0" width="100%" style="padding-bottom:5px;">
<tr>
	<td width="1%" nowrap="nowrap" valign="top" style="padding-right:5px;">
		<h1>{$translate->_('common.search')|capitalize}</h1>
	</td>
	<td width="98%" valign="middle">
		
	</td>
	<td width="1%" valign="middle" nowrap="nowrap">
		{include file="file:$core_tpl/tickets/quick_search_box.tpl.php"}
	</td>
</tr>
</table>

<table cellpadding="0" cellspacing="0" border="0">
	<tr>
		<td valign="top" width="0%" nowrap="nowrap">
			<div id="tourDashboardSearchCriteria"></div>
			{include file="file:$core_tpl/internal/views/criteria_list.tpl.php" divName="searchCriteriaDialog"}
			<div id="searchCriteriaDialog" style="visibility:visible;"></div>
		</td>
		<td valign="top" width="0%" nowrap="nowrap"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/spacer.gif{/devblocks_url}" width="5" height="1"></td>
		<td valign="top" width="100%">
			<div id="view{$view->id}">{$view->render()}</div>
		</td>
	</tr>
</table>