<!DOCTYPE html>
<html lang="en">
    <head>
        {include file="devblocks:cerberusweb.core::portals/builder/includes/head.tpl"}
    </head>
    
    <body>
        {if is_a($renderer, 'Extension_PortalPageRenderer')}
        {$renderer->render()}
        {/if}
    </body>
</html>