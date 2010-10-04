{if $active_worker->hasPriv('timetracking.actions.create')}
<form action="{devblocks_url}{/devblocks_url}" method="post" style="padding-bottom:5px;">
<button type="button" onclick="timeTrackingTimer.play('','');"><span class="cerb-sprite sprite-stopwatch"></span> {$translate->_('timetracking.ui.button.track')|capitalize}</button>
</form>
{/if}

{include file="devblocks:cerberusweb.core::internal/views/search_and_view.tpl" view=$view}