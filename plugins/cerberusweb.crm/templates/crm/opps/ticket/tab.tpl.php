<form action="{devblocks_url}{/devblocks_url}" method="post" style="padding-bottom:5px;">
	<button type="button" onclick="genericAjaxPanel('c=crm&a=showOppPanel&id=0&email={$address->email}&view_id={$view->id}',this,false,'500px',function(o){literal}{{/literal} ajax.cbEmailSinglePeek(); {literal}}{/literal});"><img src="{devblocks_url}c=resource&p=cerberusweb.crm&f=images/money.gif{/devblocks_url}" align="top"> Add Opportunity</button>
</form>

{if !empty($view)}
<div id="view{$view->id}">
	{$view->render()}
</div>
{/if}