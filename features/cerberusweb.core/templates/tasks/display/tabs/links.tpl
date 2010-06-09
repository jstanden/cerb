<form style="margin-bottom:10px;">
	<select name="to_context" onchange="if($(this).val().length==0)return;genericAjaxPanel('c=internal&a=contextLinkAddPeek&from_context=cerberusweb.contexts.task&from_context_id={$task_id}&to_context='+encodeURIComponent($(this).val())+'&return_uri={"tasks/display/{$task_id}/links"|escape:'url'}',null,false,'750');$(this).val('');">
		<option value="">-- manage links --</option>
		<option value="cerberusweb.contexts.address">Address</option>
		<option value="cerberusweb.contexts.org">Organization</option>
	</select>
</form>

{if is_array($views)}
{foreach from=$views item=view}
<div id="view{$view->id}">
	{$view->render()}
</div>
{/foreach}
{/if}
