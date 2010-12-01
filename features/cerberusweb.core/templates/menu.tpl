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
	
	{if $active_worker->is_superuser}
	<li class="{if $page->id=='core.page.configuration'}selected{/if}" style="float:right;">
		<a href="{devblocks_url}c=config{/devblocks_url}">{$translate->_('header.config')|lower}</a>				
	</li>
	{/if}
	
	{if !empty($active_worker_memberships)}
	<li class="{if $page->id=='core.page.groups'}selected{/if}" style="float:right;">
		<a href="{devblocks_url}c=groups{/devblocks_url}">{$translate->_('common.groups')|lower}</a>				
	</li>
	{/if}
	
	<li class="{if $page->id=='core.page.preferences'}selected{/if}" style="float:right;">
		<a href="{devblocks_url}c=preferences{/devblocks_url}">{$translate->_('header.preferences')|lower}</a>				
	</li>
</ul>
<div style="clear:both;background-color:rgb(134,169,227);height:5px;"></div>
{/if}
