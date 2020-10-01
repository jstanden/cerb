<div id="widget{$widget->id}">
	{include file="devblocks:cerberusweb.core::ui/sheets/render.tpl"}

	{if $widget->extension_params.toolbar_kata}
		<div data-cerb-toolbar>
			{include file="devblocks:cerberusweb.core::internal/profiles/widgets/sheet/toolbar.tpl" row_selections=[]}
		</div>
	{/if}
</div>

<script type="text/javascript">
$(function() {
	var $widget = $('#widget{$widget->id}');
	var $sheet = $widget.find('.cerb-sheet');
	var $tab = $widget.closest('.cerb-profile-layout');

	$sheet.on('cerb-sheet--refresh', function(e) {
		e.stopPropagation();
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