<form style="margin-bottom:10px;">
	<button type="button" onclick="genericAjaxPanel('c=internal&a=contextLinkAddPeek&context=cerberusweb.contexts.task&context_id={$task_id}&return_uri={"tasks/display/{$task_id}/links"|escape:'url'}',null,false,'510');"><span class="cerb-sprite sprite-gear"></span> Manage Links</button>
</form>

{if is_array($views)}
{foreach from=$views item=view}
<div id="view{$view->id}">
	{$view->render()}
</div>
{/foreach}
{/if}
