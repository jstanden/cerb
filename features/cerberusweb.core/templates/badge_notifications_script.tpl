{if $active_worker_notify_count}
<script type="text/javascript">
$().ready(function() {
	$('#badgeNotifications a').html("<span class='glyphicons glyphicons-bell' style='font-size:14px;'></span> {'header.notifications.unread'|devblocks_translate:$active_worker_notify_count}").parent().fadeIn('slow');
});
</script>
{/if}
