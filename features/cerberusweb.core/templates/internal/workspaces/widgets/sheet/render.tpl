<div>
	{include file="devblocks:cerberusweb.core::ui/sheets/render.tpl"}

	{if $widget->params.toolbar_kata}
		<div style="margin-top:5px;" data-cerb-toolbar>
			{include file="devblocks:cerberusweb.core::internal/workspaces/widgets/sheet/toolbar.tpl" row_selections=[]}
		</div>
	{/if}
</div>

<script type="text/javascript">
$(function() {
	var $widget = $('#workspaceWidget{$widget->id}');
	var $sheet = $widget.find('.cerb-sheet');
	var $tab = $widget.closest('.cerb-workspace-layout');

	$sheet.on('cerb-sheet--refresh', function(e) {
		e.stopPropagation();

		// Reload sheet via event
		$tab.triggerHandler($.Event('cerb-widget-refresh', { widget_id: {$widget->id} }));
	});

	$sheet.on('cerb-sheet--page-changed', function(e) {
		e.stopPropagation();

		var evt = $.Event('cerb-widget-refresh');
		evt.widget_id = {$widget->id};
		evt.refresh_options = {
			'page': e.page
		};

		$tab.triggerHandler(evt);
	});
});
</script>