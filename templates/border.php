{include file="header.tpl.php"}

<table cellspacing="0" cellpadding="2" border="0" width="100%">
	<tr>
		<td align="left" valign="bottom"><img src="images/logo.jpg"></td>
		<td align="right" valign="bottom">
		{if empty($visit)}
		Not signed in [<a href="?c=core.module.signin&a=show">sign in</a>]
		{else}
		Signed in as <b>{$visit->login}</b> 
		(
		<a href="#" title="My Flagged Tickets"><img src="images/flag_red.gif" align="absmiddle" title="My Flagged Tickets" border="0"></a>
		<a href="#" title="My Flagged Tickets">5</a> 
		<a href="#" title="My Suggested Tickets"><img src="images/hand_paper.gif" align="absmiddle" title="My Suggested Tickets" border="0"></a>
		<a href="#" title="My Suggested Tickets">9</a>
		)
		[ <a href="?c=core.module.signin&a=signout">sign off</a> ]<br>
		[ last viewed: <a href="?c=core.module.dashboard&a=viewticket&id=1">pricing for LiveHelp</a> ]
		{/if}
		</td>
	</tr>
</table>

{include file="menu.tpl.php"}

{if !empty($module)}
	{$module->render()}
{else}
	No module selected.
{/if}

{include file="footer.tpl.php"}
