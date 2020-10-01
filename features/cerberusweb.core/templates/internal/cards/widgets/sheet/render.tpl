<div id="cardWidget{$widget->getUniqueId($card_context_id)}">
	{include file="devblocks:cerberusweb.core::ui/sheets/render.tpl"}

	{if $widget->extension_params.toolbar_kata}
		<div style="margin-top:5px;" data-cerb-toolbar>
			{include file="devblocks:cerberusweb.core::internal/cards/widgets/sheet/toolbar.tpl" row_selections=[]}
		</div>
	{/if}
</div>

<script type="text/javascript">
$(function() {
	var $widget = $('#cardWidget{$widget->getUniqueId($card_context_id)}');
	var $sheet = $widget.find('.cerb-sheet');
	var $popup = genericAjaxPopupFind($widget);

	$sheet.find('.cerb-peek-trigger')
		.cerbPeekTrigger()
		.on('cerb-peek-saved cerb-peek-deleted', function(e) {
			e.stopPropagation();
			$popup.triggerHandler($.Event('cerb-widget-refresh', { widget_id: {$widget->id} }));
		})
	;

	$sheet.on('cerb-sheet--page-changed', function(e) {
		e.stopPropagation();

		var evt = $.Event('cerb-widget-refresh');
		evt.widget_id = {$widget->id};
		evt.refresh_options = {
			'page': e.page
		};

		$popup.triggerHandler(evt);
	});
});
</script>