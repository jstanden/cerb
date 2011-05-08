{if $active_worker->hasPriv('core.snippets.actions.create')}
<form action="{devblocks_url}{/devblocks_url}" method="post" style="padding-bottom:5px;">
	<input type="hidden" name="c" value="tickets">
	<input type="hidden" name="a" value="">
	<select name="context">
		<option value="">Plaintext</option>
		<option value="cerberusweb.contexts.ticket">Ticket</option>
		<option value="cerberusweb.contexts.worker">Worker</option>
	</select><!--
	--><button type="button" onclick="genericAjaxPopup('peek','c=tickets&a=showSnippetsPeek&id=0&view_id={$view->id}&context='+selectValue(this.form.context),null,false,'550');"><span class="cerb-sprite2 sprite-plus-circle-frame"></span> Add Snippet</button>
</form>
{/if}

{include file="devblocks:cerberusweb.core::internal/views/search_and_view.tpl" view=$view}
