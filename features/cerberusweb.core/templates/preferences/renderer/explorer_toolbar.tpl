<form action="{devblocks_url}{/devblocks_url}" method="post">
	<button type="button" onclick="$(this).fadeOut();genericAjaxGet('','c=preferences&a=explorerEventMarkRead&id={$item->params.id}');"><span class="cerb-sprite2 sprite-tick-circle-frame"></span> {$translate->_('home.my_notifications.button.mark_read')}</button>
</form>
