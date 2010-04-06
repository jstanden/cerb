<form action="{devblocks_url}{/devblocks_url}" method="post" style="padding-bottom:5px;">
	<input type="hidden" name="c" value="forums"> 
	<input type="hidden" name="a" value="import">
	<button id="btnSynchronize" type="submit"><span class="cerb-sprite sprite-refresh"></span> {$translate->_('common.synchronize')|capitalize}</button><br>
</form>

<table cellpadding="0" cellspacing="0" width="100%">

<tr>
	<td width="0%" nowrap="nowrap" valign="top">
		<div style="width:220px;">
			{include file="file:$core_tpl/internal/views/criteria_list.tpl" divName="feedbackSearchFilters"}
			<div id="feedbackSearchFilters" style="visibility:visible;"></div>
		</div>
	</td>
	
	<td nowrap="nowrap" width="0%" style="padding-right:5px;"></td>
	
	<td width="100%" valign="top">
		<div id="view{$view->id}">{$view->render()}</div>
	</td>
	
</tr>

</table>
