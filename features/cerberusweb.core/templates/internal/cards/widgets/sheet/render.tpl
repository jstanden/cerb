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

	{*
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

		for(var i in e.row_selections) {
			formData.append('row_selections[]', e.row_selections[i]);
		}

		$sheet_toolbar.html(Devblocks.getSpinner().css('max-width', '16px'));

		genericAjaxPost(formData, null, null, function(html) {
			$sheet_toolbar.html(html);
		});
	});
	*}

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