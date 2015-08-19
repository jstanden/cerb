<div id="history">

<form action="#" method="POST" id="filters_{$view->id}">
<input type="hidden" name="_csrf_token" value="{$session->csrf_token}">
{include file="devblocks:cerberusweb.support_center:portal_{$portal_code}:support_center/internal/view/view_filters.tpl" view=$view}
</form>

{if !empty($view)}
	<div class="header">
		<b>Ticket History</b>
	</div>
	<div id="view{$view->id}">
	{$view->render()}
	</div>
{/if}

</div><!--#history-->