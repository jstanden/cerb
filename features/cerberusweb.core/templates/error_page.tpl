<html lang="en">
<head>
    <title>{$settings->get('cerberusweb.core','helpdesk_title')}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <style>
        HTML {
            font-family: "Helvetica Neue", "Helvetica", "Roboto", sans-serif;
            color: rgb(25,25,25);
        }
        A {
            color: rgb(0,0,0);
            font-weight: bold;
        }
        H1, H2 {
            color: rgb(150,0,0);
        }
        .cell {
            margin: auto;
            width: 90vw;
            padding: 1em;
            text-align: center;
        }
        .logo {
            width: 500px;
            max-width: 80vw;
            height: auto;
        }
    </style>
</head>
<body>
<div class="cell">
    {assign var=logo_url value=$settings->get('cerberusweb.core','helpdesk_logo_url','')}
    {if empty($logo_url)}
        <img class="logo" src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/wgm/cerb_logo.svg{/devblocks_url}?v={$smarty.const.APP_BUILD}" alt="Logo">
    {else}
        <img class="logo" src="{$logo_url}" alt="Logo">
    {/if}

    {include file="devblocks:cerberusweb.core::internal/error_pages/{$error_template}.tpl"}
</div>
</body>
</html>

{*
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
    
    {include file="devblocks:cerberusweb.core::internal/error_pages/403.tpl"}
</body>

</html>
*}