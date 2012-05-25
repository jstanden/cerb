{if $active_worker->hasPriv('core.snippets.actions.create')}
<form action="#" method="post" style="padding-bottom:5px;">
	<button type="button" onclick="genericAjaxPopup('peek','c=internal&a=showSnippetsPeek&id=0&owner_context={$owner_context}&owner_context_id={$owner_context_id}&view_id={$view->id}',null,false,'550');"><span class="cerb-sprite2 sprite-plus-circle"></span> Add Snippet</button>
</form>
{/if}

{include file="devblocks:cerberusweb.core::internal/views/search_and_view.tpl" view=$view}
