<form action="{devblocks_url}{/devblocks_url}" style="margin-bottom:5px;">
	<button type="button" onclick="genericAjaxPanel('c=feedback&a=showEntry&id=0&view_id={$view->id}',null,false,'500px',function(o){literal}{{/literal} genericAjaxPostAfterSubmitEvent.subscribe(function(type,args){literal}{{/literal}genericAjaxGet('view{$view->id}','c=internal&a=viewRefresh&id={$view->id}');{literal}}{/literal}); {literal}}{/literal} );"><img src="{devblocks_url}c=resource&p=cerberusweb.feedback&f=images/question_and_answer.png{/devblocks_url}" align="top"> {$translate->_('feedback.button.capture')|capitalize}</button>
</form>

<table cellpadding="0" cellspacing="0" width="100%">

<tr>
	<td width="0%" nowrap="nowrap" valign="top">
		<div style="width:220px;">
			{include file="file:$core_path/internal/views/criteria_list.tpl.php" divName="feedbackSearchFilters"}
			<div id="feedbackSearchFilters" style="visibility:visible;"></div>
		</div>
	</td>
	
	<td nowrap="nowrap" width="0%"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/spacer.gif{/devblocks_url}" width="5" height="1"></td>
	
	<td width="100%" valign="top">
		<div id="view{$view->id}">{$view->render()}</div>
	</td>
	
</tr>

</table>
