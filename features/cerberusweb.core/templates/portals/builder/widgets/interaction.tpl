{$interaction_uniqid = uniqid('interaction')}
<div class="cerb-interaction-panel" id="{$interaction_uniqid}">
    {include file="devblocks:cerberusweb.core::portals/builder/pages/interaction/container.tpl" continuation_token=$continuation_token}
</div>

<script type="text/javascript">
    var $container = document.querySelector('#{$interaction_uniqid}');
    
    $$.interactionBind($container);
    $$.interactionContinue($container, false);
</script>