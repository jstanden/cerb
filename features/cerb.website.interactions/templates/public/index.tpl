<html lang="en">
    <head>
        <title></title>

        {*
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
        *}
        
        <style>
            BODY {
                background-image: linear-gradient(white 20%, gray);
            }
        </style>
    </head>
    
    <body>
        {if $page_interaction}
        <div data-cerb-interaction="{$page_interaction}" data-cerb-interaction-params="{$page_interaction_params}" data-cerb-interaction-style="full" data-cerb-interaction-autostart></div>
        {/if}

        <script id="cerb-interactions" data-cerb-badge-interaction="menu" src="{devblocks_url full=true}c=assets&f=cerb.js{/devblocks_url}?v={$smarty.const.APP_BUILD}" type="text/javascript" crossorigin="anonymous" defer></script>
    </body>
</html>