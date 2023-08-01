<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html lang="en" {if $pref_dark_mode}class="dark"{/if}>
<head>
    <title>{$settings->get('cerberusweb.core','helpdesk_title')}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <style type="text/css">
        :root {
            --cerb-error-link-color: rgb(0,0,0);
            --cerb-error-text-color: rgb(25,25,25);
            --cerb-error-background-color: rgb(255,255,255);
            --cerb-error-heading-color: rgb(150,0,0);
        }

        .dark {
            --cerb-error-link-color: rgb(240,240,240);
            --cerb-error-text-color: rgb(210,210,210);
            --cerb-error-background-color: rgb(32,32,32);
            --cerb-error-heading-color: rgb(128,180,70);
        }

        HTML {
            font-family: "Helvetica Neue", "Helvetica", "Roboto", sans-serif;
            color: var(--cerb-error-text-color);
            background-color: var(--cerb-error-background-color);
        }
        A {
            color: var(--cerb-error-link-color);
            font-weight: bold;
        }
        H1, H2 {
            color: var(--cerb-error-heading-color);
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
        <img class="logo" src="{devblocks_url}c=resource&p=cerberusweb.core&f=css/logo{if $pref_dark_mode}-dark{/if}{/devblocks_url}?v={$smarty.const.APP_BUILD}">
    {else}
        <img class="logo" src="{$logo_url}" alt="Logo">
    {/if}

    {include file="devblocks:cerberusweb.core::internal/error_pages/{$error_template}.tpl"}
</div>
</body>
</html>