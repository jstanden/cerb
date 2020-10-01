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
	var $sheet_toolbar = $widget.find('[data-cerb-toolbar]');
	var $tab = $widget.closest('.cerb-profile-layout');

	$sheet.on('cerb-sheet--refresh', function(e) {
		e.stopPropagation();
		$tab.triggerHandler($.Event('cerb-widget-refresh', { widget_id: {$widget->id} }));
	});

	// [TODO] selection
	{if $widget->extension_params.toolbar_kata}
	$sheet.on('cerb-sheet--selections-changed', function(e) {
		e.stopPropagation();

		// Update the toolbar
		var formData = new FormData();
		formData.set('c', 'profiles');
		formData.set('a', 'invokeWidget');
		formData.set('widget_id', '{$widget->id}');
		formData.set('action', 'renderToolbar');
		formData.set('profile_context', '{$profile_context}');
		formData.set('profile_context_id', '{$profile_context_id}');

		if(e.hasOwnProperty('row_selections'))
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