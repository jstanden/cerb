{$unit_width = $model->extension_params.column_width|default:500}
{if $active_worker->is_superuser}
<button id="btnProfileTabAddWidget{$model->id}" type="button" class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_PROFILE_WIDGET}" data-context-id="0" data-edit="tab:{$model->id}"><span class="glyphicons glyphicons-circle-plus"></span> {'common.add'|devblocks_translate|capitalize}</button>
{/if}

<div id="profileTab{$model->id}" style="vertical-align:top;display:flex;flex-flow:row wrap;">
	{foreach from=$widgets item=widget name=widgets}
	{include file="devblocks:cerberusweb.core::internal/profiles/widgets/render.tpl" widget=$widget unit_width=$unit_width}
	{/foreach}
</div>

<script type="text/javascript">
$(function() {
	var $container = $('#profileTab{$model->id}');
	var $add_button = $('#btnProfileTabAddWidget{$model->id}');
	
	// Drag
	{if $active_worker->is_superuser}
	$container
		.sortable({
			tolerance: 'pointer',
			items: '.cerb-profile-widget',
			helper: 'clone',
			forceHelperSize: true,
			forcePlaceholderSize: true,
			handle: '.cerb-profile-widget--header .glyphicons-menu-hamburger',
			opacity: 0.7,
			update: function(event, ui) {
				$container.trigger('cerb-reorder');
			}
		})
		;
	{/if}
	
	$container.on('cerb-reorder', function(e) {
		var tab_ids = $container.find('> .cerb-profile-widget').map(function(d) { return $(this).attr('data-widget-id'); });
				
		genericAjaxGet('', 'c=profiles&a=handleProfileTabAction&tab_id={$model->id}&action=reorderWidgets' 
			+ '&' + $.param({ 'widget_ids': $.makeArray(tab_ids) })
		);
	})
	
	var addEvents = function($target) {
		var $menu = $target.find('.cerb-profile-widget--menu');
		
		$menu
			.menu({
				select: function(event, ui) {
					var $li = $(ui.item);
					$li.closest('ul').hide();
					
					if($li.is('.cerb-profile-widget-menu--refresh')) {
						var $widget = $li.closest('.cerb-profile-widget');
						
						async.series([ async.apply(loadWidgetFunc, $widget.attr('data-widget-id'), false) ], function(err, json) {
							// Done
						});
					}
				}
			})
			;
		
		$target.find('.cerb-profile-widget--link').on('click', function(e) {
			e.stopPropagation();
			$(this).closest('div').find('.cerb-profile-widget--menu').toggle();
		});
		
		$menu.find('.cerb-peek-trigger')
			.cerbPeekTrigger()
			.on('cerb-peek-saved', function(e) {
				async.series([ async.apply(loadWidgetFunc, e.id, true) ], function(err, json) {
					// Done
				});
			})
			.on('cerb-peek-deleted', function(e) {
				$('#profileWidget' + e.id).closest('.cerb-profile-widget').remove();
				$container.trigger('cerb-reorder');
			})
			;
		
		return $target;
	}
	
	addEvents($container);
	
	var jobs = [];
	
	{if $active_worker->is_superuser}
	$add_button
		.cerbPeekTrigger()
		.on('cerb-peek-saved', function(e) {
			var $placeholder = $('<div class="cerb-profile-widget"/>').hide().prependTo($container);
			var $widget = $('<div/>').attr('id', 'profileWidget' + e.id).appendTo($placeholder);
			
			async.series([ async.apply(loadWidgetFunc, e.id, true) ], function(err, json) {
				$container.trigger('cerb-reorder');
			});
		})
		;
	{/if}
	
	var loadWidgetFunc = function(widget_id, is_full, callback) {
		var $widget = $('#profileWidget' + widget_id).empty();
		var $spinner = $('<span class="cerb-ajax-spinner"/>').appendTo($widget);
		
		genericAjaxGet('', 'c=profiles&a=handleProfileTabAction&tab_id={$model->id}&action=renderWidget&context={$context}&context_id={$context_id}&id=' + encodeURIComponent(widget_id) + '&full=' + encodeURIComponent(is_full ? 1 : 0), function(html) {
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
	
	{foreach from=$widgets item=widget}
	jobs.push(
		async.apply(loadWidgetFunc, {$widget->id|default:0}, false)
	);
	{/foreach}
	
	async.parallelLimit(jobs, 2, function(err, json) {
		
	});
});
</script>