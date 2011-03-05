<a href="javascript:;" class="menu">Example Menu <span>&#x25be;</span></a>
<ul class="cerb-popupmenu cerb-float" style="display:none;">
	{*<li><a href="{devblocks_url}c=config&a=example{/devblocks_url}">Hard-coded Menu Item</a></li>*}
	
	{$exts = Extension_PageMenuItem::getExtensions(true, 'core.page.configuration', $extension->manifest->id)}
	{*{if !empty($exts)}<li><hr></li>{/if}*}
	{foreach from=$exts item=menu_item}
		{if method_exists($menu_item,'render')}<li>{$menu_item->render()}</li>{/if}
	{/foreach}
</ul>
