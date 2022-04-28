<article id="portalPage{$page_meta.key}">
    <div class="cerb-portal-wrapper">
        {if $page_meta.label}
            <h1>{$page_meta.label}</h1>
        {/if}
        
        <div class="cerb-interaction-panel">
            {include file="devblocks:cerberusweb.core::portals/builder/pages/interaction/container.tpl" interaction_label=null continuation_token=$continuation_token}
        </div>
    </div>
</article>

<script type="text/javascript">
    var $page = document.querySelector('#portalPage{$page_meta.key}');
    var $container = $page.querySelector('div.cerb-interaction-panel');

    $$.interactionBind($container);
    $$.interactionContinue($container, false);
</script>