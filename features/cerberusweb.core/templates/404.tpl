<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset={$smarty.const.LANG_CHARSET_CODE}">
	<meta http-equiv="Cache-Control" content="no-cache">
	
	<title>{$settings->get('cerberusweb.core','helpdesk_title')}</title>
	{assign var=favicon_url value=$settings->get('cerberusweb.core','helpdesk_favicon_url','')}
	{if empty($favicon_url)}
	<link type="image/x-icon" rel="shortcut icon" href="{devblocks_url}favicon.ico{/devblocks_url}">
	{else}
	<link type="image/x-icon" rel="shortcut icon" href="{$favicon_url}">
	{/if}
	<link type="text/css" rel="stylesheet" href="{devblocks_url}c=resource&p=cerberusweb.core&f=css/cerberus.css{/devblocks_url}?v={$smarty.const.APP_BUILD}">
</head>

<body>
	<div style="margin-bottom:10px;">
		{assign var=logo_url value=$settings->get('cerberusweb.core','helpdesk_logo_url','')}
		{if empty($logo_url)}
		<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/wgm/cerb6_logo.png{/devblocks_url}?v={$smarty.const.APP_BUILD}">
		{else}
		<img src="{$logo_url}">
		{/if}
	</div>
	
	<fieldset>
		<legend>Page Not Found</legend>
		
		Sorry! The page you requested could not be found. 
		<a href="{devblocks_url}{/devblocks_url}"><b>Click here</b></a> to return to the home page.
	</fieldset>
</body>

</html>
