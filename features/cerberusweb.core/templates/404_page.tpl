<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html {if $pref_dark_mode}class="dark"{/if}>
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
    <link type="text/css" rel="stylesheet" href="{devblocks_url}c=resource&p=cerberusweb.core&f=css/cerb.css{/devblocks_url}?v={$smarty.const.APP_BUILD}">
</head>

<body>
    <div>
        {assign var=logo_url value=$settings->get('cerberusweb.core','helpdesk_logo_url','')}
        {if empty($logo_url)}
            <img src="{devblocks_url}c=resource&p=cerberusweb.core&f=css/logo{if $pref_dark_mode}-dark{/if}{/devblocks_url}?v={$smarty.const.APP_BUILD}" width="281" height="80">
        {else}
            <img src="{$logo_url}">
        {/if}
    </div>
    
    {include file="devblocks:cerberusweb.core::404.tpl"}
</body>

</html>
