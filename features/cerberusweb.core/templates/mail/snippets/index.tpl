<form action="{devblocks_url}{/devblocks_url}" method="post" style="padding-bottom:5px;">
	<input type="hidden" name="c" value="tickets">
	<input type="hidden" name="a" value="">
	<select name="context">
		<option value="cerberusweb.contexts.plaintext">Plaintext</option>
		<option value="cerberusweb.contexts.ticket">Ticket</option>
		<option value="cerberusweb.contexts.worker">Worker</option>
	</select><!--
	-->{if 1||$active_worker->hasPriv('crm.opp.actions.create')}<button type="button" onclick="genericAjaxPanel('c=tickets&a=showSnippetsPeek&id=0&view_id={$view->id}&context='+selectValue(this.form.context),null,false,'550');"><span class="cerb-sprite sprite-add"></span> Add Snippet</button>{/if}
</form>

<div id="view{$view->id}">
  	{$view->render()}
</div>
