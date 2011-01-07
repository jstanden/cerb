{include file="devblocks:cerberusweb.core::header.tpl"}
{include file="devblocks:cerberusweb.core::internal/renderers/prebody_autoreload.tpl"}

{if !empty($prebody_renderers)}
	{foreach from=$prebody_renderers item=renderer}
		{if !empty($renderer)}{$renderer->render()}{/if}
	{/foreach}
{/if}

{if !empty($tour_enabled)}{include file="devblocks:cerberusweb.core::internal/tour/banner.tpl"}{/if}
<table cellspacing="0" cellpadding="2" border="0" width="100%">
	<tr>
		<td align="left" valign="bottom">
			{assign var=logo_url value=$settings->get('cerberusweb.core','helpdesk_logo_url','')}
			{if empty($logo_url)}
			<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/wgm/logo.gif{/devblocks_url}?v={$smarty.const.APP_BUILD}">
			{else}
			<img src="{$logo_url}">
			{/if}
		</td>
		<td align="right" valign="bottom" style="line-height:150%;">
		{if empty($visit)}
			{$translate->_('header.not_signed_in')} <a href="{devblocks_url}c=login{/devblocks_url}">{$translate->_('header.signon')|lower}</a>
		{elseif !empty($active_worker)}
			{if $active_worker_notify_count}
			<span style="padding:3px 5px 3px 5px;background-color:rgb(200,0,0);"><a href="{devblocks_url}c=home&a=events{/devblocks_url}" style="color:rgb(255,255,255);text-decoration:none;font-weight:bold;">{'header.notifications.unread'|devblocks_translate:$active_worker_notify_count}</a></span>
			{/if}
			
			{assign var=worker_name value=''|cat:'<b>'|cat:$active_worker->getName()|cat:'</b>'}
			{'header.signed_in'|devblocks_translate:$worker_name nofilter}
			(<a href="{devblocks_url}c=login&a=signout{/devblocks_url}">{$translate->_('header.signoff')|lower}</a>)
		{/if}
		</td>
	</tr>
</table>

{include file="devblocks:cerberusweb.core::menu.tpl"}

{if !empty($page) && $page->isVisible()}
	{$page->render()}
{else}
	{$translate->_('header.no_page')}
{/if}

{if !empty($postbody_renderers)}
	{foreach from=$postbody_renderers item=renderer}
		{if !empty($renderer)}{$renderer->render()}{/if}
	{/foreach}
{/if}

{include file="devblocks:cerberusweb.core::footer.tpl"}
