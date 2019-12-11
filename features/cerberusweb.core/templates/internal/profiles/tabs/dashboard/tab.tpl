{if $active_worker->is_superuser}
<div style="margin-bottom:5px;" class="cerb-no-print">
	<button id="btnProfileTabAddWidget{$model->id}" type="button" class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_PROFILE_WIDGET}" data-context-id="0" data-edit="tab:{$model->id}" data-width="75%"><span class="glyphicons glyphicons-circle-plus"></span> {'common.add'|devblocks_translate|capitalize}</button>
</div>
{/if}

{if 'sidebar_left' == $layout}
	<div id="profileTab{$model->id}" class="cerb-profile-layout cerb-profile-layout--sidebar-left" style="vertical-align:top;display:flex;flex-flow:row wrap;">
		<div data-layout-zone="sidebar" class="cerb-profile-layout-zone" style="flex:1 1 33%;min-width:345px;overflow-x:hidden;">
			<div class="cerb-profile-layout-zone--widgets" style="padding:2px;vertical-align:top;display:flex;flex-flow:row wrap;min-height:100px;">
			{foreach from=$zones.sidebar item=widget name=widgets}
				{include file="devblocks:cerberusweb.core::internal/profiles/widgets/render.tpl" widget=$widget}
			{/foreach}
			</div>
		</div>
		
		<div data-layout-zone="content" class="cerb-profile-layout-zone" style="flex:2 2 66%;min-width:345px;overflow-x:hidden;">
			<div class="cerb-profile-layout-zone--widgets" style="padding:2px;vertical-align:top;display:flex;flex-flow:row wrap;min-height:100px;">
			{foreach from=$zones.content item=widget name=widgets}
				{include file="devblocks:cerberusweb.core::internal/profiles/widgets/render.tpl" widget=$widget}
			{/foreach}
			</div>
		</div>
	</div>
{elseif 'sidebar_right' == $layout}
	<div id="profileTab{$model->id}" class="cerb-profile-layout cerb-profile-layout--sidebar-right" style="vertical-align:top;display:flex;flex-flow:row wrap;">
		<div data-layout-zone="content" class="cerb-profile-layout-zone cerb-profile-layout-zone--content" style="flex:2 2 66%;min-width:345px;overflow-x:hidden;">
			<div class="cerb-profile-layout-zone--widgets" style="padding:2px;vertical-align:top;display:flex;flex-flow:row wrap;min-height:100px;">
			{foreach from=$zones.content item=widget name=widgets}
				{include file="devblocks:cerberusweb.core::internal/profiles/widgets/render.tpl" widget=$widget}
			{/foreach}
			</div>
		</div>
		
		<div data-layout-zone="sidebar" class="cerb-profile-layout-zone cerb-profile-layout-zone--sidebar" style="flex:1 1 33%;min-width:345px;overflow-x:hidden;">
			<div class="cerb-profile-layout-zone--widgets" style="padding:2px;vertical-align:top;display:flex;flex-flow:row wrap;min-height:100px;">
			{foreach from=$zones.sidebar item=widget name=widgets}
				{include file="devblocks:cerberusweb.core::internal/profiles/widgets/render.tpl" widget=$widget}
			{/foreach}
			</div>
		</div>
	</div>
{else}
	<div id="profileTab{$model->id}" class="cerb-profile-layout cerb-profile-layout--content" style="vertical-align:top;display:flex;flex-flow:row wrap;">
		<div data-layout-zone="content" class="cerb-profile-layout-zone" style="flex:1 1 100%;overflow-x:hidden;">
			<div class="cerb-profile-layout-zone--widgets" style="padding:2px;vertical-align:top;display:flex;flex-flow:row wrap;min-height:100px;">
			{foreach from=$zones.content item=widget name=widgets}
				{include file="devblocks:cerberusweb.core::internal/profiles/widgets/render.tpl" widget=$widget}
			{/foreach}
			</div>
		</div>
	</div>
{/if}

<script type="text/javascript">
$(function() {
	var $container = $('#profileTab{$model->id}');
	var $add_button = $('#btnProfileTabAddWidget{$model->id}');

	// Drag
	{if $active_worker->is_superuser}
	$container.find('.cerb-profile-layout-zone--widgets')
		.sortable({
			tolerance: 'pointer',
			items: '.cerb-profile-widget',
			helper: 'clone',
			placeholder: 'ui-state-highlight',
			forceHelperSize: true,
			forcePlaceholderSize: true,
			handle: '.cerb-profile-widget--header .glyphicons-menu-hamburger',
			connectWith: '.cerb-profile-layout-zone--widgets',
			opacity: 0.7,
			start: function(event, ui) {
				$container.find('.cerb-profile-layout-zone--widgets')
					.css('border', '2px dashed orange')
					.css('background-color', 'rgb(250,250,250)')
					.css('min-height', '100vh')
					;
			},
			stop: function(event, ui) {
				$container.find('.cerb-profile-layout-zone--widgets')
					.css('border', '')
					.css('background-color', '')
					.css('min-height', 'initial')
					;
			},
			update: function(event, ui) {
				$container.trigger('cerb-reorder');
			}
		})
		;
	{/if}
	
	$container.on('cerb-reorder', function(e) {
		var formData = new FormData();
		formData.append('c', 'profiles');
		formData.append('a', 'handleProfileTabAction');
		formData.append('tab_id', '{$model->id}');
		formData.append('action', 'reorderWidgets');

		// Zones
		$container.find('> .cerb-profile-layout-zone')
			.each(function(d) {
				var $cell = $(this);
				var zone = $cell.attr('data-layout-zone');
				var ids = $cell.find('.cerb-profile-widget').map(function(d) { return $(this).attr('data-widget-id'); });

				formData.append('zones[' + zone + ']', $.makeArray(ids));
			})
			;
		
		genericAjaxPost(formData);
	});
	
	$container.on('cerb-widget-refresh', function(e) {
		var widget_id = e.widget_id;
		var refresh_options = (e.refresh_options && typeof e.refresh_options == 'object') ? e.refresh_options : {};

		async.series([ async.apply(loadWidgetFunc, widget_id, false, refresh_options) ], function(err, json) {
			// Done
		});
	});
	
	var addEvents = function($target) {
		var $menu = $target.find('.cerb-profile-widget--menu');
		var $menu_link = $target.find('.cerb-profile-widget--link');
		var $handle = $target.find('.glyphicons-menu-hamburger');

		{if $active_worker->is_superuser}
		$target.hoverIntent({
			interval: 50,
			timeout: 250,
			over: function (e) {
				$handle.show();
			},
			out: function (e) {
				$handle.hide();
			}
		});
		{/if}

		$menu
			.menu({
				select: function(event, ui) {
					var $li = $(ui.item);
					$li.closest('ul').hide();
					
					var $widget = $li.closest('.cerb-profile-widget');
					var widget_id = $widget.attr('data-widget-id');
					
					if($li.is('.cerb-profile-widget-menu--edit')) {
						$li.clone()
							.cerbPeekTrigger()
							.on('cerb-peek-saved', function(e) {
								// [TODO] Check the event type
								async.series([ async.apply(loadWidgetFunc, e.id, true, {}) ], function(err, json) {
									// Done
								});
							})
							.on('cerb-peek-deleted', function(e) {
								$('#profileWidget' + e.id).closest('.cerb-profile-widget').remove();
								$container.trigger('cerb-reorder');
							})
							.click()
							;
						
					} else if($li.is('.cerb-profile-widget-menu--refresh')) {
						async.series([ async.apply(loadWidgetFunc, widget_id, false, {}) ], function(err, json) {
							// Done
						});
					} else if($li.is('.cerb-profile-widget-menu--export-widget')) {
						genericAjaxPopup('export_widget', 'c=profiles&a=handleSectionAction&section=profile_widget&action=exportWidget&id=' + widget_id, null, false);
					}
				}
			})
			;
		
		$menu_link.on('click', function(e) {
			e.stopPropagation();
			$(this).closest('.cerb-profile-widget').find('.cerb-profile-widget--menu').toggle();
		});
		
		return $target;
	}
	
	$container.find('.cerb-profile-widget').each(function() {
		addEvents($(this));
	});

	var jobs = [];
	
	{if $active_worker->is_superuser}
	$add_button
		.cerbPeekTrigger()
		.on('cerb-peek-saved', function(e) {
			var $zone = $container.find('> .cerb-profile-layout-zone:first > .cerb-profile-layout-zone--widgets:first');
			var $placeholder = $('<div class="cerb-profile-widget"/>').hide().prependTo($zone);
			var $widget = $('<div/>').attr('id', 'profileWidget' + e.id).appendTo($placeholder);
			
			async.series([ async.apply(loadWidgetFunc, e.id, true, {}) ], function(err, json) {
				$container.trigger('cerb-reorder');
			});
		})
		;
	{/if}
	
	var loadWidgetFunc = function(widget_id, is_full, refresh_options, callback) {
		var $widget = $('#profileWidget' + widget_id).empty();
		var $spinner = $('<span class="cerb-ajax-spinner"/>').appendTo($widget);
		
		var request_url = 'c=profiles&a=handleProfileTabAction&tab_id={$model->id}&action=renderWidget&context={$context}&context_id={$context_id}&id=' 
			+ encodeURIComponent(widget_id) 
			+ '&full=' + encodeURIComponent(is_full ? 1 : 0)
			;
		
		if(typeof refresh_options == 'object')
			request_url += '&' + $.param(refresh_options);
		
		genericAjaxGet('', request_url, function(html) {
			if(0 == html.length) {
				$widget.empty();
				
			} else {
				try {
					if(is_full) {
						addEvents($(html)).insertBefore(
							$widget.attr('id',null).closest('.cerb-profile-widget').hide()
						);
						
						$widget.closest('.cerb-profile-widget').remove();
					} else {
						$widget.html(html);
					}
				} catch(e) {
					if(console)
						console.error(e);
				}
			}
			callback();
		});
	};
	
	{foreach from=$zones item=zone}
	{foreach from=$zone item=widget}
	jobs.push(
		async.apply(loadWidgetFunc, {$widget->id|default:0}, false, {})
	);
	{/foreach}
	{/foreach}
	
	async.parallelLimit(jobs, 2, function(err, json) {
		
	});
});
</script>