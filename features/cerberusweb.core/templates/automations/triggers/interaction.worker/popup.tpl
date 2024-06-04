<div id="{$layer}" data-cerb-interaction-popup>
	{include file="devblocks:cerberusweb.core::automations/triggers/interaction.worker/panel.tpl"}
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFind('#{$layer}');

	$popup.one('popup_open',function() {
		$popup.dialog('option','title', "{'common.interaction'|devblocks_translate|capitalize}");
		
		$popup.closest('.ui-dialog').find('.ui-dialog-titlebar-close')
			.attr('tabindex', '-1')
			;
	});
});
</script>
