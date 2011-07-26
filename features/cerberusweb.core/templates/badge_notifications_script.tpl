{if $active_worker_notify_count}
<script type="text/javascript">
$().ready(function() {
	$('#badgeNotifications a').html("{'header.notifications.unread'|devblocks_translate:$active_worker_notify_count}").parent().fadeIn('slow');
});
</script>
{/if}
