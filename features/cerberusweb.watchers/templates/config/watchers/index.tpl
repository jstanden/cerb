<form action="{devblocks_url}{/devblocks_url}" style="margin-bottom:5px;">
	<button type="button" onclick="genericAjaxPanel('c=preferences&a=handleTabAction&tab=core.pref.notifications&action=showWatcherPanel&id=0&view_id={$view->id}',null,false,'550');"><span class="cerb-sprite sprite-funnel"></span> Add Watcher Filter</button>
</form>

<table cellpadding="0" cellspacing="0" border="0">
	<tr>
		<td valign="top" width="0%" nowrap="nowrap">
			{include file="file:$core_tplpath/internal/views/criteria_list.tpl" divName="searchCriteriaDialog"}
			<div id="searchCriteriaDialog" style="visibility:visible;"></div>
		</td>
		<td valign="top" width="0%" nowrap="nowrap" style="padding-right:5px;"></td>
		<td valign="top" width="100%">
			<div id="view{$view->id}">{$view->render()}</div>
		</td>
	</tr>
</table>

