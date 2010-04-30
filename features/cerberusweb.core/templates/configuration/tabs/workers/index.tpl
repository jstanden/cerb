<form>
	<button type="button" onclick="genericAjaxPanel('c=config&a=showWorkerPeek&id=0&view_id={$view->id|escape:'url'}',null,false,'500');"><span class="cerb-sprite sprite-add"></span> Add Worker</button>
</form>

<table cellpadding="0" cellspacing="0" border="0">
	<tr>
		<td valign="top" width="0%" nowrap="nowrap">
			{include file="file:$core_tpl/internal/views/criteria_list.tpl" divName="workersCriteriaDialog"}
			<div id="workersCriteriaDialog" style="visibility:visible;"></div>
		</td>
		<td valign="top" width="0%" nowrap="nowrap" style="padding-right:5px;"></td>
		<td valign="top" width="100%">
			<div id="view{$view->id}">{$view->render()}</div>
		</td>
	</tr>
</table>
