<div>
    {if 'fieldsets' == $layout.style}
        {include file="devblocks:cerberusweb.core::ui/sheets/render_fieldsets.tpl"}
    {elseif in_array($layout.style, ['columns','grid'])}
        {include file="devblocks:cerberusweb.core::ui/sheets/render_grid.tpl"}
    {else}
		{include file="devblocks:cerberusweb.core::ui/sheets/render.tpl"}
    {/if}

	{if $widget->params.toolbar_kata}
		<div data-cerb-toolbar style="margin-top:0.5em;">
			{$widget_ext->renderToolbar($widget)}
		</div>
	{/if}
</div>

<script type="text/javascript">
$(function() {
	var $widget = $('#workspaceWidget{$widget->id}');
	var $sheet = $widget.find('.cerb-sheet, .cerb-data-sheet, .cerb-sheet-grid, .cerb-sheet-columns');
	var $tab = $widget.closest('.cerb-workspace-layout');
	var $sheet_toolbar = $widget.find('[data-cerb-toolbar]');

	$sheet.on('cerb-sheet--refresh', function(e) {
		e.stopPropagation();

		// Reload sheet via event
		$tab.triggerHandler($.Event('cerb-widget-refresh', { widget_id: {$widget->id} }));
	});

    let doneFunc = function(e) {
        e.stopPropagation();

        if(!e.hasOwnProperty('trigger'))
            return;

        if(e.hasOwnProperty('eventData') && e.eventData.exit === 'return') {
            Devblocks.interactionWorkerPostActions(e.eventData);
        }

        let $target = e.trigger;
        let done_params = new URLSearchParams($target.attr('data-interaction-done'));

        let done_actions = Devblocks.toolbarAfterActions(done_params, {
            'widgets': $tab.find('.cerb-workspace-widget'),
            'default_widget_ids': [parseInt('{$widget->id}')],
        });
		
        // Refresh card widgets
        if(done_actions.hasOwnProperty('refresh_widget_ids')) {
            $tab.triggerHandler($.Event('cerb-widgets-refresh', {
                widget_ids: done_actions['refresh_widget_ids'],
                refresh_options: { }
            }));
        }
    }

    $sheet.on('cerb-sheet--interaction-done', doneFunc);
	
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