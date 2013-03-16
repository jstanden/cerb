<div id="server">
	{if !empty($view)}
		<div id="view{$view->id}">
			{$view->render()}
		</div>
	{else}
		<div class="message">{'portal.sc.public.server.empty'|devblocks_translate}</div>
	{/if}
</div>