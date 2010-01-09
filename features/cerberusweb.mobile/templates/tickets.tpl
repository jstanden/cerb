
<br><br><a href="{devblocks_url}c=mobile&a=tickets&a2=sidebar{/devblocks_url}">&lt;&lt; Change List</a><br>
<h2 style="color: rgb(102,102,102);">Mail : {$title}</h2>
{foreach from=$views item=view name=views}
	<div id="view{$view->id}">
		{$view->render()}
	</div>
{/foreach}

