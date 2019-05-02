{include file="devblocks:cerberusweb.core::portals/builder/includes/header.tpl"}

{$user_stylesheet = $portal->getParam('user_stylesheet')}
<style type="text/css">
{$user_stylesheet nofilter}
</style>

{if $renderer instanceof Extension_PortalPageRenderer}
{$renderer->render()}
{/if}

{include file="devblocks:cerberusweb.core::portals/builder/includes/footer.tpl"}