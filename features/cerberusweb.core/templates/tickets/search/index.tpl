<table cellpadding="0" cellspacing="0" border="0">
	<tr>
		<td valign="top" width="0%" nowrap="nowrap">
			<div id="tourDashboardSearchCriteria"></div>
			{include file="file:$core_tpl/internal/views/criteria_list.tpl" divName="searchCriteriaDialog"}
			<div id="searchCriteriaDialog" style="visibility:visible;"></div>
		</td>
		<td valign="top" width="0%" nowrap="nowrap" style="padding-right:5px;"></td>
		<td valign="top" width="100%">
			<div id="view{$view->id}">{$view->render()}</div>
		</td>
	</tr>
</table>