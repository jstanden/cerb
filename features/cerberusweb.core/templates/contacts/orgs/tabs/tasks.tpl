{if $active_worker->hasPriv('core.tasks.actions.create')}
<form action="{devblocks_url}{/devblocks_url}" style="margin-bottom:5px;">
	<button type="button" onclick="genericAjaxPanel('c=tasks&a=showTaskPeek&id=0&view_id={$view->id}&link_namespace=cerberusweb.tasks.org&link_object_id={$contact->id}',null,false,'500');"><span class="cerb-sprite sprite-add"></span> {'tasks.add'|devblocks_translate}</button>
</form>
{/if}

<div id="vieworg_tasks">{$view->render()}</div>
