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
		[ <a href="{devblocks_url}c=preferences{/devblocks_url}">{$translate->_('header.preferences')|lower}</a> ] 
		{if $active_worker->is_superuser}[ <a href="{devblocks_url}c=config{/devblocks_url}">{$translate->_('header.config')|lower}</a> ]{/if} 
		[ <a href="{devblocks_url}c=login&a=signout{/devblocks_url}">{$translate->_('header.signoff')|lower}</a> ]
		{*
		<span id="tourHeaderMyTasks"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/hand_paper.gif{/devblocks_url}" align="bottom" border="0"> <a href="{devblocks_url}c=tickets&a=workspaces&i=my{/devblocks_url}" title="{$translate->_('header.my_flagged_tickets')|capitalize}">my tasks</a></span>
		<span id="tourHeaderTeamLoads"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/businessmen.gif{/devblocks_url}"> <a href="javascript:;" onclick="genericAjaxPanel('c=tickets&a=showTeamPanel',this);">{$translate->_('teamwork.my_team_loads')|lower}</a></span> 
		<span id="tourHeaderGetTickets"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/document_into.gif{/devblocks_url}"> <a href="javascript:;" onclick="genericAjaxPanel('c=tickets&a=showAssignPanel',this,true);">{$translate->_('teamwork.assign_work')|lower}</a></span>
		*} 
		<br> 
		
		{* [ <a href="javascript:;" onclick="genericAjaxPanel('c=tickets&a=showHistoryPanel',this);">{$translate->_('header.history')|lower}</a> ] *}
		
		{* 
		<form action="{devblocks_url}{/devblocks_url}" method="post">
		<input type="hidden" name="c" value="tickets">
		<input type="hidden" name="a" value="doQuickSearch">
		<span id="tourHeaderQuickLookup"><b>Quick Ticket Lookup:</b></span> <select name="type">
			<option value="mask">Ticket ID</option>
			<option value="req">Requester</option>
			<option value="subject">Subject</option>
			<option value="content">Content</option>
		</select><input type="text" name="query" size="24"><input type="submit" value="Search">
		</form>
		 *}
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
