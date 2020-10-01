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
	var $sheet_toolbar = $widget.find('[data-cerb-toolbar]');

	$sheet.on('cerb-sheet--refresh', function(e) {
		e.stopPropagation();

		// Reload sheet via event
		$tab.triggerHandler($.Event('cerb-widget-refresh', { widget_id: {$widget->id} }));
	});

	{if $widget->params.toolbar_kata}
	$sheet.on('cerb-sheet--selections-changed', function(e) {
		e.stopPropagation();

		// Update the toolbar
		var formData = new FormData();
		formData.set('c', 'pages');
		formData.set('a', 'invokeWidget');
		formData.set('widget_id', '{$widget->id}');
		formData.set('action', 'renderToolbar');

		for(var i in e.row_selections) {
			formData.append('row_selections[]', e.row_selections[i]);
		}

		$sheet_toolbar.html(Devblocks.getSpinner().css('max-width', '16px'));

		genericAjaxPost(formData, null, null, function(html) {
			$sheet_toolbar.html(html);
		});
	});
	{/if}

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