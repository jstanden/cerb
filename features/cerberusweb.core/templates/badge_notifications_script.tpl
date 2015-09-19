{if $active_worker_notify_count}
<script type="text/javascript">
$().ready(function() {
	var $badge = $('#badgeNotifications');
	var $a = $badge.find('> a');
	var $count = $('<span></span>').text("{$active_worker_notify_count} ");
	{if $active_worker_notify_count == 1}
	var $txt = $('<span></span>').text("{'common.notification'|devblocks_translate|lower}");
	{else}
	var $txt = $('<span></span>').text("{'common.notifications'|devblocks_translate|lower}");
	{/if}
	$a.html('').append($count).append($txt);
	$badge.fadeIn('slow');
	
	$a.click(function() {
		var $window = genericAjaxPopup('notifications','c=internal&a=openNotificationsPopup', null, false, '90%');
	});
});
</script>
{/if}
