{$div_id = uniqid('error')}
<div id="{$div_id}" class="cerb-ui-panel cerb-ui-panel--spaced cerb-ui-panel--alert cerb-u-mt-2">
	<div class="cerb-ui-header cerb-ui-header--center">
		<div class="cerb-ui-callout">
			<span class="cerb-icons cerb-icon-alert cerb-ui-callout--icon"></span>
			<div>
				{if $error_title}
				<div class="cerb-ui-header--title-sm">{$error_title}</div>
				{/if}
				<div class="cerb-ui-header--subtitle">{$error_message}</div>
			</div>
		</div>
		<div class="cerb-ui-header--right">
			<button type="button" class="cerb-ui-button"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.ok'|devblocks_translate}</button>
		</div>
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $div = $('#{$div_id}');

	$div.find('button').on('click', function(e) {
		e.stopPropagation();
		$(this).closest('div.cerb-ui-panel').remove();
	});
});
</script>
