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
	var $this = $('#{$script_id}');
	var $div = $this.prev('div');
	var $widget_content = $this.closest('.cerb-profile-widget--content');
	var $widget = $widget_content.closest('.cerb-profile-widget').off('.widget{$widget->id}');
	var $profile_tab = $widget.closest('.cerb-profile-layout');

	// Refresh when done
	var doneFunc = function(e) {
		e.stopPropagation();

		var $target = e.trigger;

		if(!$target.is('.cerb-bot-trigger'))
			return;

		if(e.eventData)
			Devblocks.interactionWorkerPostActions(e.eventData);

		var done_params = new URLSearchParams($target.attr('data-interaction-done'));

		// Refresh this widget by default
		if(!done_params.has('refresh_widgets[]')) {
			done_params.set('refresh_widgets[]', '{$widget->name}');
		}
		
		var refresh = done_params.getAll('refresh_widgets[]');

		var widget_ids = [];

		if(-1 !== $.inArray('all', refresh)) {
			// Everything
		} else {
			$profile_tab.find('.cerb-profile-widget')
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

		$profile_tab.triggerHandler(evt);
	};

	var resetFunc = function(e) {
		e.stopPropagation();

		var evt = $.Event('cerb-widget-refresh');
		evt.widget_id = $widget.attr('data-widget-id');
		evt.refresh_options = { };

		$profile_tab.triggerHandler(evt);
	};

	var errorFunc = function(e) {
		e.stopPropagation();
	};

	const toolbarOptions = {
		caller: {
			name: 'cerb.toolbar.profileWidget.interactions',
			params: {
				record_type: '{$dict->record__context}',
				record_id: '{$dict->record_id}',
				widget_id: '{$widget->id}'
			}
		},
		start: function(formData) {
		},
		{if !$widget->extension_params.is_popup}target: $widget_content,{/if}
		done: doneFunc,
		reset: resetFunc,
		error: errorFunc
	};

	$div.cerbToolbar(toolbarOptions);

	// Keyboard shortcuts

	let $responders = $widget.find('[data-interaction-keyboard]');

	$responders.each(function() {
		let $this = $(this);
		$widget.on(
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