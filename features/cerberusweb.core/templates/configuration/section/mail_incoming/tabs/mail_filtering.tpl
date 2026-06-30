<div id="frmSetupMailFiltering" class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div class="cerb-ui-header--title-sm">{'common.automations'|devblocks_translate|capitalize}</div>
	</div>

	<div>
		<button type="button" class="cerb-ui-button cerb-peek-trigger" data-context="{$context_automation_event}" data-context-id="mail.filter" data-edit="true">
			<span class="cerb-icons cerb-icon-gear"></span> {'common.configure'|devblocks_translate|capitalize}
		</button>
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	const $frm = $('#frmSetupMailFiltering');
	$frm.find('.cerb-peek-trigger').cerbPeekTrigger();
});
</script>
