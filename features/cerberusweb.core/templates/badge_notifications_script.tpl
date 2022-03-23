{if $active_worker && $active_worker_notify_count}
<script type="text/javascript">
$().ready(function() {
	var $badge = $('#badgeNotifications');
	var $a = $badge.find('> a');
	var $count = $('<span/>').text("{$active_worker_notify_count} ");
	{if $active_worker_notify_count == 1}
	var $txt = $('<span/>').text("{'common.notification'|devblocks_translate|lower}");
	{else}
	var $txt = $('<span/>').text("{'common.notifications'|devblocks_translate|lower}");
	{/if}
	$a.html('').append($count).append($txt);
	$badge.fadeIn('slow');
	
	$a.attr('data-context', '{CerberusContexts::CONTEXT_NOTIFICATION}');
	$a.attr('data-layer', 'notifications_me');
	$a.attr('data-query', 'isRead:n');
	$a.attr('data-query-required', 'worker.id:{$active_worker->id}');
	$a.cerbSearchTrigger();
});
</script>
{/if}
