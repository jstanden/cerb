<!DOCTYPE html PUBLIC "-//WAPFORUM//DTD XHTML Mobile 1.0//EN" "http://www.wapforum.org/DTD/xhtml-mobile10.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head><title>Cerberus Helpdesk</title></head>
<body style="font-size:small;font-weight:normal;">

<img alt="powered by cerberus helpdesk" src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/wgm/logo_small.gif{/devblocks_url}" border="0">

{if !empty($page) && $page->isVisible()}
	{$page->render()}
{else}
	{$translate->_('header.no_page')}
{/if}

<br>
<br>

<div>
[ <a href="{devblocks_url}c=mobile&a=tickets&a2=overview{/devblocks_url}">Mail</a> ]
[ <a href="{devblocks_url}c=mobile&a=tickets&a2=search{/devblocks_url}">Search</a> ]

</div>
<br>
<table cellspacing="0" cellpadding="2" border="0" width="100%">
	<tr>
		<td align="right" valign="bottom" style="line-height:150%;">
		{if empty($visit)}
			{$translate->_('header.not_signed_in')} [<a href="{devblocks_url}c=login{/devblocks_url}">{$translate->_('header.signon')|lower}</a>]
		{else}
			{$common_translated.header_signed_in}
			[ <a href="{devblocks_url}c=login&a=signout{/devblocks_url}">{$translate->_('header.signoff')|lower}</a> ]
		{/if}
		</td>
	</tr>
</table>

</body>
</html>

