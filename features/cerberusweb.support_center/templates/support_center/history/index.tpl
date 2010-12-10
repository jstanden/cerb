<div id="history">

<form action="#" method="POST" id="filters_{$view->id}">
{include file="devblocks:cerberusweb.support_center:portal_{$portal_code}:support_center/internal/view/view_filters.tpl" view=$view}
</form>

{if !empty($view)}
	<div class="header">
		<h1>Ticket History</h1>
	</div>
	<div id="view{$view->id}">
	{$view->render()}
	</div>
{/if}

</div><!--#history-->