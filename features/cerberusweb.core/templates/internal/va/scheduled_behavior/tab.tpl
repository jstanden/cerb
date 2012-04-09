{if !empty($context) && !empty($context_id) && !empty($macros)}
<form action="javascript:;">
	<!-- Macros -->
	{include file="devblocks:cerberusweb.core::internal/macros/display/button.tpl" context=$context context_id=$context_id macros=$macros return_url=$return_url}		
</form>
{/if}

{include file="devblocks:cerberusweb.core::internal/views/search_and_view.tpl" view=$view}

<script type="text/javascript">
{include file="devblocks:cerberusweb.core::internal/macros/display/menu_script.tpl"}
</script>