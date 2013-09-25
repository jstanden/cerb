<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset={$smarty.const.LANG_CHARSET_CODE}">
	<meta http-equiv="Cache-Control" content="no-cache">
	<!--[if gte IE 9]>
	<meta http-equiv="X-UA-Compatible" content="IE=Edge"/>
	<![endif]-->
	
	<title>{$settings->get('cerberusweb.core','helpdesk_title')}</title>
	{assign var=favicon_url value=$settings->get('cerberusweb.core','helpdesk_favicon_url','')}
	{if empty($favicon_url)}
	<link type="image/x-icon" rel="shortcut icon" href="{devblocks_url}favicon.ico{/devblocks_url}">
	{else}
	<link type="image/x-icon" rel="shortcut icon" href="{$favicon_url}">
	{/if}
	<script type="text/javascript">
		var DevblocksAppPath = '{$smarty.const.DEVBLOCKS_WEBPATH}';
	</script>
	
	<!-- Platform -->
	<link type="text/css" rel="stylesheet" href="{devblocks_url}c=resource&p=devblocks.core&f=css/jquery-ui.css{/devblocks_url}?v={$smarty.const.APP_BUILD}">
	<script type="text/javascript" src="{devblocks_url}c=resource&p=devblocks.core&f=js/jquery/jquery.combined.min.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>
	<script type="text/javascript" src="{devblocks_url}c=resource&p=devblocks.core&f=js/devblocks.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>

	<!-- Application -->
	<link type="text/css" rel="stylesheet" href="{devblocks_url}c=resource&p=cerberusweb.core&f=css/cerberus.css{/devblocks_url}?v={$smarty.const.APP_BUILD}">
	<!--[if lt IE 9]>
	<link type="text/css" rel="stylesheet" href="{devblocks_url}c=resource&p=cerberusweb.core&f=css/cerberus-iehacks.css{/devblocks_url}?v={$smarty.const.APP_BUILD}">
	<![endif]-->
	<script type="text/javascript" src="{devblocks_url}c=resource&p=cerberusweb.core&f=js/markitup/jquery.markitup.pack.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>
	<script type="text/javascript" src="{devblocks_url}c=resource&p=cerberusweb.core&f=js/cerberus.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>
</head>

<body>
