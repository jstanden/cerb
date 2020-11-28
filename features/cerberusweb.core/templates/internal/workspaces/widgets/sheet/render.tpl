<div>
	{include file="devblocks:cerberusweb.core::ui/sheets/render.tpl"}

	{if $widget->params.toolbar_kata}
		<div style="margin-top:5px;" data-cerb-toolbar>
			{$widget_ext->renderToolbar($widget)}
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
			$sheet_toolbar
				.html(html)
				.triggerHandler('cerb-toolbar--refreshed')
			;
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

	var doneFunc = function(e) {
		e.stopPropagation();

		var $target = e.trigger;

		var done_params = [];

		if($target.is('.cerb-bot-trigger')) {
			done_params = new URLSearchParams($target.attr('data-interaction-done'));
		} else {
			return;
		}

		if(!done_params.has('refresh_widgets[]'))
			return;

		var refresh = done_params.getAll('refresh_widgets[]');

		var widget_ids = [];

		if(-1 !== $.inArray('all', refresh)) {
			// Everything
		} else {
			$tab.find('.cerb-workspace-widget')
				.filter(function() {
					var $this = $(this);
					var name = $this.attr('data-widget-name');

					if(undefined === name)
						return false;

					return -1 !== $.inArray(name, refresh);
				})
				.each(function() {
					var $this = $(this);
					var widget_id = parseInt($this.attr('data-widget-id'));

					if(widget_id)
						widget_ids.push(widget_id);
				})
			;
		}

		var evt = $.Event('cerb-widgets-refresh', {
			widget_ids: widget_ids,
			refresh_options: { }
		});

		$tab.triggerHandler(evt);
	};

	$sheet_toolbar.cerbToolbar({
		caller: {
			name: 'cerb.toolbar.workspaceWidget.sheet',
			params: {
				page_id: '{$widget->getWorkspacePage()->id}',
				widget_id: '{$widget->id}'
			}
		},
		start: function(formData) {
		},
		done: doneFunc
	});
});
</script>