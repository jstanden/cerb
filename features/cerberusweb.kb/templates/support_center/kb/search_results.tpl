<div id="kb">

<div class="header"><h1>{$translate->_('common.knowledgebase')|capitalize}</h1></div>

<div class="search">
	<form action="{devblocks_url}c=kb&a=search{/devblocks_url}" method="POST">
		<input class="query" type="text" name="q" value="{$q|escape}"><button type="submit">search</button>
	</form>
</div>
<br>

<div class="header"><h1>Search results{if !empty($q)} for '{$q|escape}'{/if}</h1></div>

<div id="view{$view->id}">
{$view->render()}
</div>

</div>