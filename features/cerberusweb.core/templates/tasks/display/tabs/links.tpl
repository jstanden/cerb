<form style="margin-bottom:10px;">
	<select name="to_context">
		<option value="cerberusweb.contexts.address">Address</option>
		<option value="cerberusweb.contexts.org">Organization</option>
	</select>
	<button type="button" onclick="genericAjaxPanel('c=internal&a=contextLinkAddPeek&from_context=cerberusweb.contexts.task&from_context_id={$task_id}&to_context='+encodeURIComponent($(this).prev('select[name=to_context]').val())+'&return_uri={"tasks/display/{$task_id}/links"|escape:'url'}',null,false,'510');"><span class="cerb-sprite sprite-gear"></span> Edit Links</button>
</form>

{if is_array($views)}
{foreach from=$views item=view}
<div id="view{$view->id}">
	{$view->render()}
</div>
{/foreach}
{/if}
