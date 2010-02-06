{if $active_worker->hasPriv('crm.opp.actions.create')}
<form action="{devblocks_url}{/devblocks_url}" method="post" style="padding-bottom:5px;">
	<button type="button" onclick="genericAjaxPanel('c=crm&a=showOppPanel&id=0&view_id={$view->id}',null,false,'500');"><img src="{devblocks_url}c=resource&p=cerberusweb.crm&f=images/money.gif{/devblocks_url}" align="top"> {'crm.opp.add'|devblocks_translate}</button>
</form>
{/if}

{if !empty($view)}
<div id="view{$view->id}">
	{$view->render()}
</div>
{/if}