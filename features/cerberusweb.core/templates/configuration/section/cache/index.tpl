<div class="cerb-ui-header">
	<div>
		<div class="cerb-ui-header--title">{'common.cache'|devblocks_translate|capitalize}</div>
		<div class="cerb-ui-header--subtitle">Configure distributed memory-based cache engines for high performance</div>
	</div>
</div>

<div class="cerb-ui-panel cerb-ui-panel--spaced" id="setupConfigCache">
	<div class="cerb-ui-header cerb-ui-header--tight cerb-ui-header--center">
		<div class="cerb-ui-header--title-sm"><span class="cerb-icons cerb-icon-database"></span> {$cacher->manifest->name}</div>
		{if !$smarty.const.DEVBLOCKS_CACHE_ENGINE_PREVENT_CHANGE}
		<div class="cerb-ui-header--right">
			<button type="button" class="cerb-ui-button" data-cerb-edit-cache title="{'common.edit'|devblocks_translate|capitalize}"><span class="cerb-icons cerb-icon-edit"></span></button>
		</div>
		{/if}
	</div>

	<div>
		{$cacher->renderStatus()}
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	$('#setupConfigCache').find('[data-cerb-edit-cache]').on('click', function(e) {
		e.stopPropagation();
		genericAjaxPopup('peek','c=config&a=invoke&module=cache&action=showCachePeek', null, false);
	});
});
</script>
