{$uniqid = uniqid()}
<div id="{$uniqid}">
	<div class="cerb-ui-panel cerb-ui-panel--spaced cerb-facebook-account">
		<div class="cerb-ui-header cerb-ui-header--tight">
			<div class="cerb-ui-header--title-sm">Facebook Account</div>
		</div>

		<div class="cerb-ui-form">
			<div class="cerb-ui-form--field">
				<div class="cerb-ui-record-chooser" id="accountChooser_{$uniqid}">
					{if $connected_account}
						<li data-context-id="{$connected_account->id}" data-label="{$connected_account->name}"></li>
					{/if}
				</div>
			</div>
		</div>
	</div>

	<div class="cerb-ui-panel cerb-ui-panel--spaced">
		<div class="cerb-ui-header cerb-ui-header--tight">
			<div class="cerb-ui-header--title-sm">Page</div>
		</div>

		<div class="cerb-facebook-pages">
			{if $params.page.name}
			Linked to <b>{$params.page.name}</b>
			{/if}
		</div>
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	const $container = $('#{$uniqid}');
	const $pages = $container.find('div.cerb-facebook-pages');
	const accountEl = $container.find('#accountChooser_{$uniqid}')[0];

	const refreshPages = function() {
		const value = chooser ? chooser.getValue() : null;

		if(!value) {
			$pages.hide().html('');
			return;
		}

		const formData = new FormData();
		formData.set('c', 'profiles');
		formData.set('a', 'invoke');
		formData.set('module', 'connected_service');
		formData.set('action', 'ajax');
		formData.set('ajax', 'getPagesFromAccount');
		formData.set('id', '{$service->extension_id}');
		formData.set('connected_account_id', value.id);

		genericAjaxPost(formData, $pages, null, function() {
			$pages.fadeIn();
		});
	};

	let chooser = null;

	if(accountEl && window.CerbUI && CerbUI.RecordChooser) {
		chooser = new CerbUI.RecordChooser(accountEl, {
			context: '{CerberusContexts::CONTEXT_CONNECTED_ACCOUNT}',
			name: 'params[connected_account_id]',
			emptyIcon: 'key',
			query: 'facebook',
			searchPlaceholder: 'Facebook account',
			onSelect: refreshPages
		});

		// The chooser's clear button removes the value without a callback and stops propagation, so listen in
		// the capture phase (runs before the button's stopPropagation) and refresh on the next frame.
		accountEl.addEventListener('click', function(e) {
			if(e.target.closest('.cerb-ui-record-chooser--clear'))
				requestAnimationFrame(refreshPages);
		}, true);
	}
});
</script>
