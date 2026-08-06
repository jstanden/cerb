{$div_id = uniqid()}
<div class="cerb-ui-panel cerb-ui-panel--spaced" id="{$div_id}">
	<button type="button" class="cerb-ui-button oauth-button">
		{if $params.access_token || $params.oauth_token}
		Linked to {$params.label|default:$service->name}
		{else}
		Link to {$service->name}
		{/if}
	</button>

	<div class="oauth-params"></div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	const $container = $('#{$div_id}');

	$container.find('.oauth-button').click(function(e) {
		e.stopPropagation();
		window.open('{devblocks_url}ajax.php?c=profiles&a=invoke&module=connected_account&action=auth&id={$account->id}&service_id={$service->id}{/devblocks_url}&form_id={$div_id}', 'auth', 'width=1024,height=768');
	});

	$container.on('oauth-saved', function(e) {
		e.stopPropagation();

		$container.find('.oauth-button').text('Linked to ' + e.label);

		const $hidden = $('<input/>')
			.attr('type', 'hidden')
			.attr('name', 'params[params_json]')
			.val(e.params)
		;

		$container.find('.oauth-params').html('').append($hidden);
	});
});
</script>
