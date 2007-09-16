{include file="$tpl_path/header.tpl.php"}
<!--- 
<table cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color:rgb(240,240,240);padding:2px;">
<tr>
	<td align="left">
		<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/wgm/usermeet_powered.gif{/devblocks_url}">
	</td>
	<td align="right">
	</td>
</tr>
</table>
--->
{if !empty($tour_enabled)}{include file="tour.tpl.php"}{/if}
<table cellspacing="0" cellpadding="2" border="0" width="100%">
	<tr>
		<td align="left" valign="bottom">
			{assign var=logo_url value=$settings->get('helpdesk_logo_url','')}
			{if empty($logo_url)}
			<img src="{devblocks_url}images/logo.jpg{/devblocks_url}">
			{else}
			<img src="{$logo_url}">
			{/if}
		</td>
		<td align="right" valign="bottom" style="line-height:150%;">
		{if empty($visit)}
			{$translate->_('header.not_signed_in')} [<a href="{devblocks_url}c=login{/devblocks_url}">{$translate->_('header.signon')|lower}</a>]
		{else}
			{$common_translated.header_signed_in}
			[ <a href="{devblocks_url}c=login&a=signout{/devblocks_url}">{$translate->_('header.signoff')|lower}</a> ]
			<br> 
			[ <a href="javascript:;" onclick="genericAjaxPanel('c=display&a=showFnrPanel',this,false,'550px');">{$translate->_('header.fnr')|lower}</a> ] 
			[ <a href="{devblocks_url}c=preferences{/devblocks_url}">{$translate->_('header.preferences')|lower}</a> ]
			{if $active_worker->is_superuser}[ <a href="{devblocks_url}c=config{/devblocks_url}">{$translate->_('header.config')|lower}</a> ]{/if} 
			<br> 
			
			{* [ <a href="javascript:;" onclick="genericAjaxPanel('c=tickets&a=showHistoryPanel',this);">{$translate->_('header.history')|lower}</a> ] *}
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
