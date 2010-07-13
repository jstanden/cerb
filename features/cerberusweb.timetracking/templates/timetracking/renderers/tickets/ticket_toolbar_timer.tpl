{if $active_worker->hasPriv('timetracking.actions.create')}
<button type="button" onclick="timeTrackingTimer.play('cerberusweb.contexts.ticket','{$ticket->id}');"><span class="cerb-sprite sprite-stopwatch"></span> {$translate->_('timetracking.ui.button.track')|capitalize}</button>
{/if}