{if !empty($view)}
<form action="#" method="POST" id="filter{$view->id}">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="">
<input type="hidden" name="id" value="{$view->id}">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<div id="viewCustomFilters{$view->id}">
{include file="devblocks:cerberusweb.core::internal/views/customize_view_criteria.tpl"}
</div>
</form>

<div id="view{$view->id}" data-context="{$view->getContext()}">{$view->render()}</div>

<script type="text/javascript">
$(function() {
	$('#viewCustomFilters{$view->id}').bind('view_refresh', function(event) {
		if(event.target == event.currentTarget)
			genericAjaxGet('view{$view->id}','c=internal&a=invoke&module=worklists&action=refresh&id={$view->id}');
	});
});
</script>
{/if}