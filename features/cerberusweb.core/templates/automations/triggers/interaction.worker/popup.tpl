<div id="{$layer}" data-cerb-interaction-popup>
	{include file="devblocks:cerberusweb.core::automations/triggers/interaction.worker/panel.tpl"}
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFind('#{$layer}');

	$popup.one('popup_open',function() {
		// Title is owned by the opener (the launcher's label); await:form:title still overrides per step.
		$popup.closest('.cerb-ui-dialog').find('.cerb-ui-dialog--btn[aria-label="Close"]')
			.attr('tabindex', '-1')
			;
	});
});
</script>
