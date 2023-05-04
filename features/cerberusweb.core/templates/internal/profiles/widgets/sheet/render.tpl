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
			{$widget_ext->renderToolbar($widget, $profile_context, $profile_context_id)}
		</div>
	{/if}
</div>

<script type="text/javascript">
$(function() {
	var $widget = $('#widget{$widget->id}');
	var $sheet = $widget.find('.cerb-sheet, .cerb-data-sheet, .cerb-sheet-grid, .cerb-sheet-columns');
	var $sheet_toolbar = $widget.find('[data-cerb-toolbar]');
	var $tab = $widget.closest('.cerb-profile-layout');
	var $parent = $widget.closest('.cerb-profile-widget').off('.widget{$widget->id}');

	$sheet.on('cerb-sheet--refresh', function(e) {
		e.stopPropagation();
		$tab.triggerHandler($.Event('cerb-widget-refresh', { widget_id: {$widget->id} }));
	});

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

	// Toolbar

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
			$tab.find('.cerb-profile-widget')
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

			// If nothing to do, abort
			if(0 === widget_ids.length)
				widget_ids = [-1];
		}

		var evt = $.Event('cerb-widgets-refresh', {
			widget_ids: widget_ids,
			refresh_options: { }
		});

		$tab.triggerHandler(evt);
	};

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
		},
		done: doneFunc
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