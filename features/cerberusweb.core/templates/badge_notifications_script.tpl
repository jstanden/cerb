{if $active_worker && $active_worker_notify_count}
<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$().ready(function() {
	const $btn = $('#badgeNotifications');
	const $icon = $('<span class="cerb-icons cerb-icon-bell"/>');
	const $count = $('<span style="margin-left:0.2em;vertical-align:0.2em;"/>').text("{$active_worker_notify_count} ");
	$btn.html('').append($icon).append($count);
	$btn.show();

	$btn.attr('type', 'button');
	$btn.attr('data-context', '{CerberusContexts::CONTEXT_NOTIFICATION}');
	$btn.attr('data-layer', 'notifications_me');
	$btn.attr('data-query', 'isRead:n');
	$btn.attr('data-query-required', 'worker.id:{$active_worker->id}');
	$btn.cerbSearchTrigger();
});
</script>
{/if}
