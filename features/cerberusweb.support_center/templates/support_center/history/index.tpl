<div id="history">
	
<div class="header"><h1>{$translate->_('portal.sc.public.history.ticket_history')}</h1></div>
<div class="search">
	<form action="{devblocks_url}c=history&a=search{/devblocks_url}" method="POST">
		<input class="query" type="text" name="q" value=""><button type="submit">{'common.search'|devblocks_translate|lower}</button>
	</form>
</div>

{if !empty($open_view)}
<div class="header"><h1>{$translate->_('portal.sc.public.history.my_open_conversations')}</h1></div>
<div id="view{$open_view->id}">
{$open_view->render()}
</div>
{/if}

{if !empty($closed_view)}
<div class="header"><h1>{$translate->_('portal.sc.public.history.my_closed_conversations')}</h1></div>
<div id="view{$closed_view->id}">
{$closed_view->render()}
</div>
{/if}

</div><!--#history-->