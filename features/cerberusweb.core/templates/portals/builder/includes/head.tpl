<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta http-equiv="Cache-Control" content="no-cache">
<meta name="_csrf_token" content="{$session.csrf_token}">

{* [TODO] portal + page title *}
<title></title>

<!-- Mobile -->
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
<meta name="apple-mobile-web-app-title" content="Cerb">
<meta name="apple-mobile-web-app-status-bar-style" content="black">
<link rel="apple-touch-startup-image" href="{devblocks_url full=true}c=resource&p=cerberusweb.core&f=images/mobile/cerby-splash-640x920.png{/devblocks_url}?v={$smarty.const.APP_BUILD}">
<link href="{devblocks_url full=true}c=resource&p=cerberusweb.core&f=images/mobile/cerby-144x144.png{/devblocks_url}?v={$smarty.const.APP_BUILD}" sizes="144x144" rel="apple-touch-icon-precomposed">

<!-- Platform -->
<script type="text/javascript" src="{devblocks_url}c=_resource&f=builder.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>

<!-- Application -->
<link type="text/css" rel="stylesheet" href="{devblocks_url}c=resource&p=cerberusweb.core&f=portals/builder/css/style.css{/devblocks_url}?v={$smarty.const.APP_BUILD}">

<!-- User-defined styles -->
{*
<link type="text/css" rel="stylesheet" href="{devblocks_url}c=resource&p=cerberusweb.core&f=css/user.css{/devblocks_url}?v={$user_stylesheet_timestamp}">
*}