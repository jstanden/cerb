{foreach from=$login_extensions_enabled item=login_extension key=login_extension_id}
{if $login_extension_id != $login_extension_active->id}
<div>
	{if $login_extension->params['switcher_icon']}
		{$icon_url = $login_extension->params['switcher_icon']}
		<img src="{devblocks_url}c=resource&p={$login_extension->plugin_id}&f={$icon_url}{/devblocks_url}" align="absmiddle"> 
	{/if}
	{if $login_extension->params['switcher_label']}
		{$label = $login_extension->params['switcher_label']}
	{else}
		{$label = $login_extension->name}
	{/if}
	<a href="{devblocks_url}c=login&a=provider&ext={$login_extension_id}{/devblocks_url}">{$label}</a>
</div>
{/if}
{/foreach}
