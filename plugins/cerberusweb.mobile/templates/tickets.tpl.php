<h2 style="color: rgb(102,102,102);">Tickets</h2>

{foreach from=$views item=view name=views}
	<div id="view{$view->id}">
		{$view->render()}
	</div>
{/foreach}

