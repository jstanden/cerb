<!DOCTYPE html>
{$badge_interaction = $portal_schema->getBadgeInteraction()}
{$favicon = $portal_schema->getFaviconUri()}
{$logo = $portal_schema->getLogoImageUri()}
{$logo_text = $portal_schema->getLogoText()}
{$navbar = $portal_schema->getNavbar()}
{$title = $portal_schema->getTitle()}
{$version = $portal->updated_at|cat:"000"}

<html lang="en" class="cerb-portal">
    <head>
        <title>{$title|default:'Cerb'}</title>

        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
        
        {if $favicon}
        <link rel="icon" href="{devblocks_url full=true}c=assets&f=favicon{/devblocks_url}?v={$version}"/>
        {/if}
        
        <link rel="stylesheet" href="{devblocks_url full=true}c=assets&f=cerb.css{/devblocks_url}?v={$version}"/>
    </head>
    
    <body>
        <div class="cerb-header">
            {if $logo}
            <div class="cerb-logo">
                <img src="{devblocks_url}c=assets&f=logo{/devblocks_url}?v={$portal->updated_at}" alt="Logo" />
            </div>
            {/if}
            
            {if $logo_text}
            <div class="cerb-logo-text">
                {$logo_text}
            </div>
            {/if}
            
            {if $navbar}
            <ul class="cerb-links">
                {foreach from=$navbar item=link}
                <li class="{$link.class}"><a href="{$link.href}">{$link.label}</a></li>
                {/foreach}
            </ul>
            {/if}
        </div>
    
        {if $page_interaction}
        <div data-cerb-interaction="{$page_interaction}" data-cerb-interaction-params="{$page_interaction_params}" data-cerb-interaction-style="full" data-cerb-interaction-autostart></div>
        {/if}

        <script id="cerb-interactions" data-cerb-badge-interaction="{$badge_interaction}" data-cerb-badge-interaction-style="full" src="{devblocks_url full=true}c=assets&f=cerb.js{/devblocks_url}?v={$version}" type="text/javascript" nonce="{$session->nonce}" crossorigin="anonymous" defer></script>
    </body>
</html>