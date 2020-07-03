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
	var $popup = genericAjaxPopupFind($this);
	var $widget_content = $this.closest('.cerb-card-widget--content');
	var $widget = $widget_content.closest('.cerb-card-widget');

	var resetFunc = function(e) {
		e.stopPropagation();

		var evt = $.Event('cerb-widget-refresh');
		evt.widget_id = $widget.attr('data-widget-id');
		evt.refresh_options = { };

		$popup.triggerHandler(evt);
	};

	// Refresh when done
	var doneFunc = function(e) {
		e.stopPropagation();

		var $target = e.trigger;

		if(!$target.is('.cerb-bot-trigger'))
			return;

		var done_params = new URLSearchParams($target.attr('data-interaction-done'));

		if(!done_params.has('refresh_widgets[]'))
			return;

		var refresh = done_params.getAll('refresh_widgets[]');

		var widget_ids = [];

		if(-1 !== $.inArray('all', refresh)) {
			// Everything
		} else {
			$popup.find('.cerb-card-widget')
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

		$popup.triggerHandler(evt);
	};

	$div.find('button.cerb-bot-trigger')
		.cerbBotTrigger({
			'container': $widget_content,
			'reset': resetFunc,
			'done': doneFunc
		})
	;

	// Menus
	$div
		.find('> button[data-cerb-toolbar-menu]')
		.on('click', function() {
			var $this = $(this);
			var $ul = $(this).next('ul').toggle();

			$ul.position({
				my: 'left top',
				at: 'left bottom',
				of: $this,
				collision: 'fit'
			});
		})
		.next('ul.cerb-float')
		.menu()
		.find('li.cerb-bot-trigger')
		.cerbBotTrigger({
			'container': $widget_content,
			'reset': resetFunc,
			'done': doneFunc
		})
		.on('click', function(e) {
			e.stopPropagation();
			$(this).closest('ul.cerb-float').hide();
		})
	;

});
</script>