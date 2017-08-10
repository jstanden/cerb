{if $active_worker->hasPriv('contexts.cerberusweb.contexts.timetracking.create')}
<button type="button" onclick="timeTrackingTimer.play('cerberusweb.contexts.ticket','{$message->ticket_id}');" title="{'timetracking.ui.button.track'|devblocks_translate|capitalize}"><span class="glyphicons glyphicons-stopwatch"></span></button>
{/if}