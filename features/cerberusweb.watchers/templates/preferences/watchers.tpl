<div id="tourMyAcctWatchers"></div>
<form action="{devblocks_url}{/devblocks_url}" style="margin-bottom:5px;">
	<button type="button" onclick="genericAjaxPanel('c=preferences&a=handleTabAction&tab=core.pref.notifications&action=showWatcherPanel&id=0&view_id={$view->id}',null,false,'550');"><span class="cerb-sprite sprite-funnel"></span> Add Watcher Filter</button>
</form>

<form action="#" method="POST" id="filter{$view->id}">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="">
<input type="hidden" name="id" value="{$view->id}">

<div id="viewCustomFilters{$view->id}" style="margin:10px;">
{include file="$core_tpl/internal/views/customize_view_criteria.tpl"}
</div>
</form>

<div id="view{$view->id}">{$view->render()}</div>

<script>
	$('#viewCustomFilters{$view->id}').bind('devblocks.refresh', function(event) {
		if(event.target == event.currentTarget)
			genericAjaxGet('view{$view->id}','c=internal&a=viewRefresh&id={$view->id|escape}');
	} );
</script>
