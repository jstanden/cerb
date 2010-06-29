<form style="margin-bottom:10px;">
	<select name="to_context" onchange="if($(this).val().length==0)return;genericAjaxPopup('chooser','c=internal&a=contextLinkAddPeek&from_context={$context}&from_context_id={$context_id}&to_context='+encodeURIComponent($(this).val())+'&return_uri={$return_uri|escape:'url'}',null,true,'750');$(this).val('');">
		<option value="">-- manage links --</option>
		<option value="cerberusweb.contexts.address">Address</option>
		<option value="cerberusweb.contexts.opportunity">Opportunity</option>
		<option value="cerberusweb.contexts.org">Organization</option>
		<option value="cerberusweb.contexts.task">Task</option>
		<option value="cerberusweb.contexts.ticket">Ticket</option>
		<option value="cerberusweb.contexts.worker">Worker</option>
	</select>
</form>

{if is_array($views)}
{foreach from=$views item=view}
<div id="view{$view->id}">
	{$view->render()}
</div>
{/foreach}
{/if}
