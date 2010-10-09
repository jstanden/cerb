{if !empty($visit)}
<div id="tourHeaderMenu"></div>

<ul class="navmenu">
	{foreach from=$page_manifests item=m}
		{if !empty($m->params.menutitle)}
			<li class="{if $page->id==$m->id || ($page->id=='core.page.display'&&$m->id=='core.page.tickets')}selected{/if}">
				<a href="{devblocks_url}c={$m->params.uri}{/devblocks_url}">{$translate->_($m->params.menutitle)|lower}</a>				
			</li>
		{/if}
	{/foreach}
</ul>
<div style="clear:both;"></div>
{/if}
