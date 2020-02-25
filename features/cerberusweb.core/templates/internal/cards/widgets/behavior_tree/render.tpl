{if $is_writeable}
<div id="cardWidget{$widget->getUniqueId($behavior->id)}" class="cerb-code-editor-toolbar">
    <button type="button" class="cerb-code-editor-toolbar-button" data-cerb-button="simulate"><span class="glyphicons glyphicons-play"></span> {'common.simulator'|devblocks_translate|capitalize}</button>
    <button type="button" class="cerb-code-editor-toolbar-button" data-cerb-button="export"><span class="glyphicons glyphicons-file-export"></span> {'common.export'|devblocks_translate|capitalize}</button>
</div>
{/if}

{include file="devblocks:cerberusweb.core::internal/bot/behavior/tab.tpl"}

<script type="text/javascript">
$(function() {
   var $widget = $('#cardWidget{$widget->getUniqueId($behavior->id)}');

   $widget.find('button[data-cerb-button=simulate]').on('click', function() {
       genericAjaxPopup('simulate_behavior', 'c=profiles&a=invoke&module=behavior&action=renderSimulatorPopup&trigger_id={$behavior->id}',null,false,'50%');
   });

   $widget.find('button[data-cerb-button=export]').on('click', function() {
       genericAjaxPopup('export_behavior','c=profiles&a=invoke&module=behavior&action=renderExportPopup&trigger_id={$behavior->id}',null,false,'50%');
   });
});
</script>