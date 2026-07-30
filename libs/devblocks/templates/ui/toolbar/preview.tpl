<div class="cerb-ui-panel cerb-ui-panel--spaced" data-cerb-toolbar-preview style="margin-top:10px;">
	<div class="cerb-ui-header cerb-ui-header--tight cerb-ui-header--center">
		<div class="cerb-ui-header--title-sm">{'common.preview'|devblocks_translate|capitalize}</div>
		<div class="cerb-ui-header--right">
			<button type="button" class="cerb-ui-button cerb-ui-button--transparent" data-cerb-preview-remove title="{'common.remove'|devblocks_translate|capitalize}"><span class="cerb-icons cerb-icon-circle-remove"></span></button>
		</div>
	</div>

	<div>
		{if !$toolbar}
			<span class="cerb-u-text-muted">No interactions are available.</span>
		{else}
			{DevblocksPlatform::services()->ui()->toolbar()->render($toolbar)}
		{/if}
	</div>
</div>

{$script_uid = uniqid('script')}
<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript" id="{$script_uid}">
$(function() {
	let $script = $('#{$script_uid}');
	let $panel = $script.prev('[data-cerb-toolbar-preview]');

	// Remove
	$panel.find('[data-cerb-preview-remove]').on('click', function(e) {
		e.stopPropagation();
		$(this).closest('[data-cerb-toolbar-preview]').remove();
	});

	// Toolbar preview (menus only — no interactions are fired from the preview)
	let toolbar_ul = $panel.find('ul.cerb-ui-toolbar')[0];
	if(toolbar_ul && window.CerbUI && CerbUI.Toolbar)
		new CerbUI.Toolbar(toolbar_ul);
});
</script>
