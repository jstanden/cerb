{if $active_worker->hasPriv('timetracking.actions.create')}

<script type="text/javascript">
	$subpage = $('BODY > DIV.cerb-subpage');
	$toolbar = $subpage.find('form.toolbar');
	
	$new_button = $('<button type="button" title="{'timetracking.ui.button.track'|devblocks_translate|capitalize}">&nbsp;<span class="cerb-sprite sprite-stopwatch"></span>&nbsp;</button>');
	$new_button.click(function(e) {
		timeTrackingTimer.play('{$page_context}','{$page_context_id}');
	});
	
	$new_button.appendTo($toolbar);
</script>
	
{/if}