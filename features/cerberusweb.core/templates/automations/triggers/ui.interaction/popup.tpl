<div id="{$layer}">
	{include file="devblocks:cerberusweb.core::automations/triggers/ui.interaction/panel.tpl"}
</div>

<script type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFind('#{$layer}');

	$popup.one('popup_open',function() {
		$popup.dialog('option','title', "{$popup_title|escape:'javascript' nofilter}");
		
		$popup.closest('.ui-dialog').find('.ui-dialog-titlebar-close')
			.attr('tabindex', '-1')
			;
	});
});
</script>
