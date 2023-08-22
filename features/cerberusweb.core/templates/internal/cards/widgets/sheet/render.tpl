<div id="cardWidget{$widget->getUniqueId($card_context_id)}">
    {if 'fieldsets' == $layout.style}
        {include file="devblocks:cerberusweb.core::ui/sheets/render_fieldsets.tpl"}
    {elseif in_array($layout.style, ['columns','grid'])}
        {include file="devblocks:cerberusweb.core::ui/sheets/render_grid.tpl"}
    {else}
        {include file="devblocks:cerberusweb.core::ui/sheets/render.tpl"}
    {/if}

	{if $widget->extension_params.toolbar_kata}
		<div data-cerb-toolbar style="margin-top:0.5em;">
			{$widget_ext->renderToolbar($widget, $card_context_id, [], $rows_visible)}
		</div>
	{/if}
</div>

<script type="text/javascript">
$(function() {
	var $widget = $('#cardWidget{$widget->getUniqueId($card_context_id)}');
	var $sheet = $widget.find('.cerb-sheet, .cerb-data-sheet, .cerb-sheet-grid, .cerb-sheet-columns');
	var $sheet_toolbar = $widget.find('[data-cerb-toolbar]');
	var $popup = genericAjaxPopupFind($widget);
    var $card_toolbar = $popup.find('[data-cerb-card-toolbar]').find('[data-cerb-toolbar]');

    let rows_selected = [];
    let rows_visible = {$rows_visible|default:[]|json_encode nofilter};
    
    $sheet.find('.cerb-peek-trigger')
		.cerbPeekTrigger()
		.on('cerb-peek-saved cerb-peek-deleted', function(e) {
			e.stopPropagation();
			$popup.triggerHandler($.Event('cerb-widget-refresh', { widget_id: {$widget->id} }));
		})
	;

    let doneFunc = function(e) {
        e.stopPropagation();

        if(!e.hasOwnProperty('trigger'))
            return;

        if(e.hasOwnProperty('eventData') && e.eventData.exit === 'return') {
            Devblocks.interactionWorkerPostActions(e.eventData);
        }

        let $target = e.trigger;
        let done_params = new URLSearchParams($target.attr('data-interaction-done'));

        if(done_params.has('refresh_toolbar')) {
            let refresh = done_params.get('refresh_toolbar');

            if(!refresh || '0' === refresh)
                return;

            $card_toolbar.trigger($.Event('cerb-toolbar--refresh'));
        }

        let done_actions = Devblocks.toolbarAfterActions(done_params, {
            'widgets': $popup.find('.cerb-card-widget'),
            'default_widget_ids': [parseInt('{$widget->id}')],
        });

        // Refresh card widgets
        if(done_actions.hasOwnProperty('refresh_widget_ids')) {
            $popup.triggerHandler($.Event('cerb-widgets-refresh', {
                widget_ids: done_actions['refresh_widget_ids'],
                refresh_options: { }
            }));
        }

        // Close the card popup
        if(done_params.has('close') && done_params.get('close')) {
            genericAjaxPopupClose($popup);
        }
    }
    
    $sheet.on('cerb-sheet--interaction-done', doneFunc);

	$sheet.on('cerb-sheet--selections-changed', function(e) {
		e.stopPropagation();

		// Update the toolbar
		var formData = new FormData();
		formData.set('c', 'profiles');
		formData.set('a', 'invoke');
		formData.set('module', 'card_widget');
		formData.set('action', 'invokeWidget');
		formData.set('widget_id', '{$widget->id}');
		formData.set('invoke_action', 'renderToolbar');
		formData.set('card_context_id', '{$card_context_id}');
        
        rows_selected = [];
        rows_visible = [];

        if(e.hasOwnProperty('rows_visible')) {
            rows_visible = e.rows_visible;
            for(let i in e.rows_visible) {
                formData.append('rows_visible[]', e.rows_visible[i]);
            }
        }

        if(e.hasOwnProperty('row_selections')) {
            rows_selected = e.row_selections;
            for(let i in e.row_selections) {
                formData.append('row_selections[]', e.row_selections[i]);
            }
        }

		$sheet_toolbar.html(Devblocks.getSpinner().css('max-width', '16px'));

		genericAjaxPost(formData, null, null, function(html) {
			$sheet_toolbar
				.html(html)
				.triggerHandler('cerb-toolbar--refreshed')
			;
		});
	});

	$sheet.on('cerb-sheet--page-changed', function(e) {
		e.stopPropagation();

		var evt = $.Event('cerb-widget-refresh');
		evt.widget_id = {$widget->id};
		evt.refresh_options = {
			'page': e.page
		};

		$popup.triggerHandler(evt);
	});

	// Toolbars

	$sheet_toolbar.cerbToolbar({
		caller: {
			name: 'cerb.toolbar.cardWidget.sheet',
			params: {
				record_type: '{$card_context}',
				record_id: '{$card_context_id}',
				widget_id: '{$widget->id}'
			}
		},
		start: function(formData) {
            for (const i in rows_visible) {
                formData.append('caller[params][rows_visible][]', rows_visible[i]);
            }
            for (const i in rows_selected) {
                formData.append('caller[params][rows_selected][]', rows_selected[i]);
            }
        },
		done: doneFunc
	});

	$sheet.find('[data-cerb-sheet-column-toolbar]').cerbToolbar({
		interaction_class: 'cerb-sheet-toolbar--interaction',
		caller: {
			name: 'cerb.toolbar.cardWidget.sheet.column',
			params: {
				record_type: '{$card_context}',
				record_id: '{$card_context_id}',
				widget_id: '{$widget->id}'
			}
		},
		start: function(formData) {
			for (const i in rows_visible) {
				formData.append('caller[params][rows_visible][]', rows_visible[i]);
			}
			for (const i in rows_selected) {
				formData.append('caller[params][rows_selected][]', rows_selected[i]);
			}
		},
		done: doneFunc,
	});
});
</script>