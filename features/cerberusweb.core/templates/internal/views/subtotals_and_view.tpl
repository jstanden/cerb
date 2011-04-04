<table cellpadding="0" cellspacing="0" border="0" width="100%">
	<tr>
		{if empty($view->renderSubtotals) || '__'==substr($view->renderSubtotals,0,2)}
		<td valign="top" width="0%" nowrap="nowrap" id="view{$view->id}_sidebar"></td>
		{else}
		<td valign="top" width="0%" nowrap="nowrap" id="view{$view->id}_sidebar" style="padding-right:5px;">{$view->renderSubtotals()}</td>
		{/if}

		<td valign="top" width="100%">
			{include file=$view_template}
		</td>
	</tr>
</table>