<form action="{devblocks_url}{/devblocks_url}" style="margin-bottom:5px;">
	<button type="button" onclick="genericAjaxPanel('c=calls.ajax&a=showEntry&id=0&view_id={$view->id}',null,false,'500px',function(o){literal}{{/literal} ajax.cbAddressPeek(); ajax.cbEmailSinglePeek(); genericAjaxPostAfterSubmitEvent.subscribe(function(type,args){literal}{{/literal}genericAjaxGet('view{$view->id}','c=internal&a=viewRefresh&id={$view->id}');{literal}}{/literal}); {literal}}{/literal} );"><img src="{devblocks_url}c=resource&p=cerberusweb.calls&f=images/phone_call.png{/devblocks_url}" align="top"> {$translate->_('calls.ui.log_call')|capitalize}</button>
</form>

<table cellpadding="0" cellspacing="0" width="100%">

<tr>
	<td width="0%" nowrap="nowrap" valign="top">
		<div style="width:220px;">
			{include file="file:$core_path/internal/views/criteria_list.tpl.php" divName="callSearchFilters"}
			<div id="callSearchFilters" style="visibility:visible;"></div>
		</div>
	</td>
	
	<td nowrap="nowrap" width="0%"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/spacer.gif{/devblocks_url}" width="5" height="1"></td>
	
	<td width="100%" valign="top">
		<div id="view{$view->id}">{$view->render()}</div>
	</td>
	
</tr>

</table>
