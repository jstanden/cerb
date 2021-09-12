<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset={$smarty.const.LANG_CHARSET_CODE}">
	<meta http-equiv="Cache-Control" content="no-cache">
	<meta name="robots" content="noindex">
	<meta name="googlebot" content="noindex">
	<meta name="_csrf_token" content="{$session.csrf_token}">
	
	<title>{$settings->get('cerberusweb.core','helpdesk_title')}</title>
	{$favicon_url = $settings->get('cerberusweb.core','helpdesk_favicon_url','')}
	{if empty($favicon_url)}
	<link rel="icon" type="image/png" sizes="32x32" href="{devblocks_url}c=resource&p=cerberusweb.core&f=images/favicon-32x32.png{/devblocks_url}">
	<link rel="icon" type="image/png" sizes="96x96" href="{devblocks_url}c=resource&p=cerberusweb.core&f=images/favicon-96x96.png{/devblocks_url}">
	<link rel="icon" type="image/png" sizes="16x16" href="{devblocks_url}c=resource&p=cerberusweb.core&f=images/favicon-16x16.png{/devblocks_url}">
	{else}
	<link type="image/x-icon" rel="shortcut icon" href="{$favicon_url}">
	{/if}
	
	<script type="text/javascript">
		var DevblocksAppPath = '{$smarty.const.DEVBLOCKS_WEBPATH}';
		var DevblocksWebPath = '{devblocks_url}{/devblocks_url}';
		var CerbSchemaRecordsVersion = {intval(DevblocksPlatform::services()->cache()->getTagVersion("schema_records"))};
	</script>
	
	<style type="text/css">
		#cerb-logo {
			display: inline-block;
			max-width: 100vw;
			background: url({devblocks_url}c=resource&p=cerberusweb.core&f=css/logo{/devblocks_url}?v={$settings->get('cerberusweb.core','ui_user_logo_updated_at',0)}) no-repeat;
			background-size: contain;
			width: 281px;
			height: 80px;
		}
	</style>
	
	<!-- Mobile -->
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
	<meta name="apple-mobile-web-app-title" content="Cerb">
	<meta name="apple-mobile-web-app-status-bar-style" content="black">
	<link rel="apple-touch-startup-image" href="{devblocks_url full=true}c=resource&p=cerberusweb.core&f=images/mobile/cerby-splash-640x920.png{/devblocks_url}?v={$smarty.const.APP_BUILD}">
	<link href="{devblocks_url full=true}c=resource&p=cerberusweb.core&f=images/mobile/cerby-144x144.png{/devblocks_url}?v={$smarty.const.APP_BUILD}" sizes="144x144" rel="apple-touch-icon-precomposed">
	
	<!-- Platform -->
	<script type="text/javascript" src="{devblocks_url}c=resource&p=devblocks.core&f=js/async-min.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>
	<link type="text/css" rel="stylesheet" href="{devblocks_url}c=resource&p=devblocks.core&f=css/jquery-ui.css{/devblocks_url}?v={$smarty.const.APP_BUILD}">
	<script type="text/javascript" src="{devblocks_url}c=resource&p=devblocks.core&f=js/jquery/jquery.combined.min.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>
	<script type="text/javascript" src="{devblocks_url}c=resource&p=devblocks.core&f=js/devblocks.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>

	<!-- Application -->
	<link type="text/css" rel="stylesheet" href="{devblocks_url}c=resource&p=cerberusweb.core&f=css/cerb.css{/devblocks_url}?v={$smarty.const.APP_BUILD}">
	<script type="text/javascript" src="{devblocks_url}c=resource&p=cerberusweb.core&f=js/cerberus.js{/devblocks_url}?v={$smarty.const.APP_BUILD}&pl=3"></script>
	
	<!-- User-defined styles -->
	{$user_stylesheet_timestamp = $settings->get('cerberusweb.core',CerberusSettings::UI_USER_STYLESHEET_UPDATED_AT,0)}
	{if $user_stylesheet_timestamp}
	<link type="text/css" rel="stylesheet" href="{devblocks_url}c=resource&p=cerberusweb.core&f=css/user.css{/devblocks_url}?v={$user_stylesheet_timestamp}">
	{/if}
</head>

<body>
<div id="cerb-alerts"></div>