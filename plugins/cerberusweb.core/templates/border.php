{include file="$tpl_path/header.tpl.php"}

{if !empty($prebody_renderers)}
	{foreach from=$prebody_renderers item=renderer}
		{if !empty($renderer)}{$renderer->render()}{/if}
	{/foreach}
{/if}

{if !empty($tour_enabled)}{include file="tour.tpl.php"}{/if}
<table cellspacing="0" cellpadding="2" border="0" width="100%">
	<tr>
		<td align="left" valign="bottom">
			{assign var=logo_url value=$settings->get('helpdesk_logo_url','')}
			{if empty($logo_url)}
			<img src="{devblocks_url}images/logo.png{/devblocks_url}?v={$smarty.const.APP_BUILD}">
			{else}
			<img src="{$logo_url}">
			{/if}
		</td>
		<td align="right" valign="bottom" style="line-height:150%;">
		{if empty($visit)}
			{$translate->_('header.not_signed_in')} <a href="{devblocks_url}c=login{/devblocks_url}">{$translate->_('header.signon')|lower}</a>
		{else}
			{assign var=worker_name value=''|cat:'<b>'|cat:$active_worker->getName()|cat:'</b>'}
			{'header.signed_in'|devblocks_translate:$worker_name}
			<a href="{devblocks_url}c=preferences{/devblocks_url}">{$translate->_('header.preferences')|lower}</a> 
			 | <a href="{devblocks_url}c=login&a=signout{/devblocks_url}">{$translate->_('header.signoff')|lower}</a> 
			<br> 
			 <a href="javascript:;" onclick="genericAjaxPanel('c=display&a=showFnrPanel',this,false,'550px');">{$translate->_('common.fnr')|lower|escape}</a>  
			{if !empty($active_worker_memberships)} | <a href="{devblocks_url}c=groups{/devblocks_url}">{$translate->_('header.group_setup')|lower}</a>{/if} 
			{if $active_worker->is_superuser} | <a href="{devblocks_url}c=config{/devblocks_url}">{$translate->_('header.config')|lower}</a>{/if} 
			<br> 
		{/if}
		</td>
	</tr>
</table>

{include file="$tpl_path/menu.tpl.php"}

{if !empty($page) && $page->isVisible()}
	{$page->render()}
{else}
	{$translate->_('header.no_page')}
{/if}

{include file="$tpl_path/footer.tpl.php"}
