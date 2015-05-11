{if $active_worker->hasPriv('timetracking.actions.create')}

<script type="text/javascript">
var $subpage = $('BODY > DIV.cerb-subpage');
var $toolbar = $subpage.find('form.toolbar');

var $new_button = $('<button type="button" title="{'timetracking.ui.button.track'|devblocks_translate|capitalize}"><span class="glyphicons glyphicons-stopwatch"></span></button>');
$new_button.click(function(e) {
	timeTrackingTimer.play('{$page_context}','{$page_context_id}');
});

$new_button.appendTo($toolbar);
</script>
	
{/if}