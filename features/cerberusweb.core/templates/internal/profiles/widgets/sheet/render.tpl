<div id="widget{$widget->id}">
	{if 'fieldsets' == $layout.style}
		{include file="devblocks:cerberusweb.core::ui/sheets/render_fieldsets.tpl"}
	{elseif in_array($layout.style, ['columns','grid'])}
		{include file="devblocks:cerberusweb.core::ui/sheets/render_grid.tpl"}
	{else}
		{include file="devblocks:cerberusweb.core::ui/sheets/render.tpl"}
	{/if}

	{if $widget->extension_params.toolbar_kata}
		<div data-cerb-toolbar style="margin-top:0.5em;">
			{$widget_ext->renderToolbar($widget, $profile_context, $profile_context_id, [], $rows_visible)}
		</div>
	{/if}
</div>

<script type="text/javascript">
$(function() {
	var $widget = $('#widget{$widget->id}');
    var $profile_toolbar = $('#profileToolbar').find('[data-cerb-toolbar]');
	var $sheet = $widget.find('.cerb-sheet, .cerb-data-sheet, .cerb-sheet-grid, .cerb-sheet-columns');
	var $sheet_toolbar = $widget.find('[data-cerb-toolbar]');
	var $tab = $widget.closest('.cerb-profile-layout');
	var $parent = $widget.closest('.cerb-profile-widget').off('.widget{$widget->id}');
    
    let rows_selected = [];
    let rows_visible = {$rows_visible|default:[]|json_encode nofilter};

	$sheet.on('cerb-sheet--refresh', function(e) {
		e.stopPropagation();
		$tab.triggerHandler($.Event('cerb-widget-refresh', { widget_id: {$widget->id} }));
	});
    
    let doneFunc = function(e) {
        e.stopPropagation();

        if(!e.hasOwnProperty('trigger'))
            return;

        if(e.eventData.exit === 'return') {
            Devblocks.interactionWorkerPostActions(e.eventData);
        }

        let $target = e.trigger;
        let done_params = new URLSearchParams($target.attr('data-interaction-done'));

        if(done_params.has('refresh_toolbar')) {
            let refresh = done_params.get('refresh_toolbar');

            if(!refresh || '0' === refresh)
                return;
            
			$profile_toolbar.trigger($.Event('cerb-toolbar--refresh'));
        }
        
        let done_actions = Devblocks.toolbarAfterActions(done_params, {
            'widgets': $tab.find('.cerb-profile-widget'),
            'default_widget_ids': [parseInt('{$widget->id}')],
        });

        if(done_actions.hasOwnProperty('refresh_widget_ids')) {
            $tab.triggerHandler($.Event('cerb-widgets-refresh', {
                widget_ids: done_actions['refresh_widget_ids'],
                refresh_options: { }
            }));
        }
    }

	$sheet.on('cerb-sheet--interaction-done', doneFunc);

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

        rows_visible = [];
        rows_selected = [];

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

	// Toolbars

	$sheet_toolbar.cerbToolbar({
		caller: {
			name: 'cerb.toolbar.profileWidget.sheet',
			params: {
				record_type: '{$profile_context}',
				record_id: '{$profile_context_id}',
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
			name: 'cerb.toolbar.profileWidget.sheet.column',
			params: {
				record_type: '{$profile_context}',
				record_id: '{$profile_context_id}',
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

	// Keyboard shortcuts

	let $responders = $widget.find('[data-interaction-keyboard]');

	$responders.each(function() {
		let $this = $(this);
		$parent.on(
			'keydown.widget{$widget->id}',
			null,
			$this.attr('data-interaction-keyboard'),
			function(e) {
				e.preventDefault();
				e.stopPropagation();
				$this.click();
			}
		);
	});
});
</script>