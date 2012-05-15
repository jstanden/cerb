{if $active_worker->hasPriv('crm.opp.actions.create')}
<form action="{devblocks_url}{/devblocks_url}" method="post" style="padding-bottom:5px;">
	<button type="button" onclick="genericAjaxPopup('peek','c=internal&a=showPeekPopup&context={CerberusContexts::CONTEXT_OPPORTUNITY}&context_id=0&view_id={$view->id}&link_context={CerberusContexts::CONTEXT_TICKET}&link_context_id={$ticket_id}',null,false,'500');"><span class="cerb-sprite2 sprite-plus-circle"></span> {'crm.opp.add'|devblocks_translate|capitalize}</button>
</form>
{/if}

{if !empty($view)}
<div id="view{$view->id}">
	{$view->render()}
</div>
{/if}