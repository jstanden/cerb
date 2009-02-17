{if $active_worker->hasPriv('core.tasks.actions.create')}
<form action="{devblocks_url}{/devblocks_url}" style="margin-bottom:5px;">
	<button type="button" onclick="genericAjaxPanel('c=tasks&a=showTaskPeek&id=0&view_id={$view->id}&link_namespace=cerberusweb.tasks.ticket&link_object_id={$ticket->id}',this,false,'500px',{literal}function(o){document.getElementById('formTaskPeek').title.focus();}{/literal});"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/gear_add.gif{/devblocks_url}" align="top"> Add Task</button>
</form>
{/if}

<div id="viewticket_tasks">{$view->render()}</div>
