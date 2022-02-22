<div>
	{if !$interactions}
	No interactions are available.
	{else}
	{DevblocksPlatform::services()->ui()->toolbar()->render($interactions)}
	{/if}
</div>

{$script_id = uniqid('script')}
<script type="text/javascript" id="{$script_id}">
$(function() {
	var $this = $('#{$script_id}')
	var $div = $this.prev('div');
	var $widget_content = $this.closest('.cerb-workspace-widget--content');
	var $widget = $widget_content.closest('.cerb-workspace-widget');
	var $workspace_tab = $widget.closest('.cerb-workspace-layout');

	var resetFunc = function(e) {
		e.stopPropagation();

		var evt = $.Event('cerb-widget-refresh');
		evt.widget_id = $widget.attr('data-widget-id');
		evt.refresh_options = { };

		$workspace_tab.triggerHandler(evt);
	};

	// Refresh when done
	var doneFunc = function(e) {
		e.stopPropagation();

		var $target = e.trigger;

		if(!$target.is('.cerb-bot-trigger'))
			return;

		var done_params = new URLSearchParams($target.attr('data-interaction-done'));

		// Refresh this widget by default
		if(!done_params.has('refresh_widgets[]')) {
			done_params.set('refresh_widgets[]', '{$widget->label}');
		}

		var refresh = done_params.getAll('refresh_widgets[]');

		var widget_ids = [];

		if(-1 !== $.inArray('all', refresh)) {
			// Everything
		} else {
			$workspace_tab.find('.cerb-workspace-widget')
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

		$workspace_tab.triggerHandler(evt);
	};

	var errorFunc = function(e) {
		e.stopPropagation();
	};

	$div.cerbToolbar({
		caller: {
			name: 'cerb.toolbar.workspaceWidget.interactions',
			params: {
				widget_id: '{$widget->id}',
				page_id: '{$dict->widget_tab_page_id}'
			}
		},
		start: function(formData) {
		},
		target: $widget_content,
		done: doneFunc,
		reset: resetFunc,
		error: errorFunc
	});
});
</script>