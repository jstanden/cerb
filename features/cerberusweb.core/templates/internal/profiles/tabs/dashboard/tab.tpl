{if $active_worker->is_superuser}
<div class="cerb-ui-toolbar-strip cerb-no-print" style="margin-bottom:5px;">
	<button id="btnProfileTabAddWidget{$model->id}" type="button" class="cerb-peek-trigger cerb-ui-toolbar-button" data-context="{CerberusContexts::CONTEXT_PROFILE_WIDGET}" data-context-id="0" data-edit="tab:{$model->id}" data-width="75%"><span class="cerb-icons cerb-icon-circle-plus"></span> {'common.add.widget'|devblocks_translate|capitalize}</button>
	<button id="btnProfileTabEdit{$model->id}" type="button" class="cerb-peek-trigger cerb-ui-toolbar-button" data-context="{CerberusContexts::CONTEXT_PROFILE_TAB}" data-context-id="{$model->id}" data-edit="true" data-width="75%"><span class="cerb-icons cerb-icon-edit"></span> Edit Tab</button>
	<button id="btnProfileTabToggleWidgets{$model->id}" type="button" class="cerb-ui-toolbar-button" style="display:none;" aria-pressed="false" title="Hidden widgets"><span class="cerb-icons cerb-icon-eye-close"></span> Hidden Widgets <span class="cerb-ui-toolbar--badge cerb-ui-toolbar--badge-neutral badge-count">0</span></button>
</div>
{/if}

{if 'sidebar_left' == $layout}
	<div id="profileTab{$model->id}" class="cerb-profile-layout cerb-profile-layout--sidebar-left" style="vertical-align:top;display:flex;flex-flow:row wrap;">
		<div data-layout-zone="sidebar" class="cerb-profile-layout-zone" style="flex:1 1 33%;min-width:345px;overflow-x:clip;">
			<div class="cerb-profile-layout-zone--widgets" style="padding:2px;vertical-align:top;display:flex;flex-flow:row wrap;min-height:100px;">
			{foreach from=$zones.sidebar item=widget name=widgets}
				{include file="devblocks:cerberusweb.core::internal/profiles/widgets/render.tpl" widget=$widget}
			{/foreach}
			</div>
		</div>
		
		<div data-layout-zone="content" class="cerb-profile-layout-zone" style="flex:2 2 66%;min-width:345px;overflow-x:clip;">
			<div class="cerb-profile-layout-zone--widgets" style="padding:2px;vertical-align:top;display:flex;flex-flow:row wrap;min-height:100px;">
			{foreach from=$zones.content item=widget name=widgets}
				{include file="devblocks:cerberusweb.core::internal/profiles/widgets/render.tpl" widget=$widget}
			{/foreach}
			</div>
		</div>
	</div>
{elseif 'sidebar_right' == $layout}
	<div id="profileTab{$model->id}" class="cerb-profile-layout cerb-profile-layout--sidebar-right" style="vertical-align:top;display:flex;flex-flow:row wrap;">
		<div data-layout-zone="content" class="cerb-profile-layout-zone cerb-profile-layout-zone--content" style="flex:2 2 66%;min-width:345px;overflow-x:clip;">
			<div class="cerb-profile-layout-zone--widgets" style="padding:2px;vertical-align:top;display:flex;flex-flow:row wrap;min-height:100px;">
			{foreach from=$zones.content item=widget name=widgets}
				{include file="devblocks:cerberusweb.core::internal/profiles/widgets/render.tpl" widget=$widget}
			{/foreach}
			</div>
		</div>
		
		<div data-layout-zone="sidebar" class="cerb-profile-layout-zone cerb-profile-layout-zone--sidebar" style="flex:1 1 33%;min-width:345px;overflow-x:clip;">
			<div class="cerb-profile-layout-zone--widgets" style="padding:2px;vertical-align:top;display:flex;flex-flow:row wrap;min-height:100px;">
			{foreach from=$zones.sidebar item=widget name=widgets}
				{include file="devblocks:cerberusweb.core::internal/profiles/widgets/render.tpl" widget=$widget}
			{/foreach}
			</div>
		</div>
	</div>
{elseif 'halves' == $layout}
	<div id="profileTab{$model->id}" class="cerb-workspace-layout cerb-profile-layout--halves" style="vertical-align:top;display:flex;flex-flow:row wrap;">
		<div data-layout-zone="left" class="cerb-profile-layout-zone cerb-profile-layout-zone--left" style="flex:1 1 50%;min-width:345px;overflow-x:clip;">
			<div class="cerb-profile-layout-zone--widgets" style="padding:2px;vertical-align:top;display:flex;flex-flow:row wrap;min-height:100px;">
				{foreach from=$zones.left item=widget name=widgets}
					{include file="devblocks:cerberusweb.core::internal/profiles/widgets/render.tpl" widget=$widget}
				{/foreach}
			</div>
		</div>

		<div data-layout-zone="right" class="cerb-profile-layout-zone cerb-profile-layout-zone--right" style="flex:1 1 50%;min-width:345px;overflow-x:clip;">
			<div class="cerb-profile-layout-zone--widgets" style="padding:2px;vertical-align:top;display:flex;flex-flow:row wrap;min-height:100px;">
				{foreach from=$zones.right item=widget name=widgets}
					{include file="devblocks:cerberusweb.core::internal/profiles/widgets/render.tpl" widget=$widget}
				{/foreach}
			</div>
		</div>
	</div>
{elseif 'thirds' == $layout}
	<div id="profileTab{$model->id}" class="cerb-profile-layout cerb-profile-layout--thirds" style="vertical-align:top;display:flex;flex-flow:row wrap;">
		<div data-layout-zone="left" class="cerb-profile-layout-zone cerb-profile-layout-zone--left" style="flex:1 1 33%;min-width:345px;overflow-x:clip;">
			<div class="cerb-profile-layout-zone--widgets" style="padding:2px;vertical-align:top;display:flex;flex-flow:row wrap;min-height:100px;">
				{foreach from=$zones.left item=widget name=widgets}
					{include file="devblocks:cerberusweb.core::internal/profiles/widgets/render.tpl" widget=$widget}
				{/foreach}
			</div>
		</div>

		<div data-layout-zone="center" class="cerb-profile-layout-zone cerb-profile-layout-zone--center" style="flex:1 1 33%;min-width:345px;overflow-x:clip;">
			<div class="cerb-profile-layout-zone--widgets" style="padding:2px;vertical-align:top;display:flex;flex-flow:row wrap;min-height:100px;">
				{foreach from=$zones.center item=widget name=widgets}
					{include file="devblocks:cerberusweb.core::internal/profiles/widgets/render.tpl" widget=$widget}
				{/foreach}
			</div>
		</div>

		<div data-layout-zone="right" class="cerb-profile-layout-zone cerb-profile-layout-zone--right" style="flex:1 1 33%;min-width:345px;overflow-x:clip;">
			<div class="cerb-profile-layout-zone--widgets" style="padding:2px;vertical-align:top;display:flex;flex-flow:row wrap;min-height:100px;">
				{foreach from=$zones.right item=widget name=widgets}
					{include file="devblocks:cerberusweb.core::internal/profiles/widgets/render.tpl" widget=$widget}
				{/foreach}
			</div>
		</div>
	</div>
{else}
	<div id="profileTab{$model->id}" class="cerb-profile-layout cerb-profile-layout--content" style="vertical-align:top;display:flex;flex-flow:row wrap;">
		<div data-layout-zone="content" class="cerb-profile-layout-zone" style="flex:1 1 100%;overflow-x:clip;">
			<div class="cerb-profile-layout-zone--widgets" style="padding:2px;vertical-align:top;display:flex;flex-flow:row wrap;min-height:100px;">
			{foreach from=$zones.content item=widget name=widgets}
				{include file="devblocks:cerberusweb.core::internal/profiles/widgets/render.tpl" widget=$widget}
			{/foreach}
			</div>
		</div>
	</div>
{/if}

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var $container = $('#profileTab{$model->id}');
	let cerbTabs = window.CerbUI?.Tabs?.fromPanel($container[0]);
	var $add_button = $('#btnProfileTabAddWidget{$model->id}');
	var $edit_button = $('#btnProfileTabEdit{$model->id}');
	var $toggle_widgets_button = $('#btnProfileTabToggleWidgets{$model->id}');

	// Drag
	{if $active_worker->is_superuser}
	$container.find('.cerb-profile-layout-zone--widgets').each(function() {
		if(window.CerbUI && CerbUI.Sortable)
			new CerbUI.Sortable(this, {
				tolerance: 'pointer',
				items: '.cerb-profile-widget',
				handle: '.cerb-profile-widget--header .cerb-icon-menu-hamburger',
				connectWith: '.cerb-profile-layout-zone--widgets',
				helper: 'clone',
				onStart: function() {
					// Faint outline marks the drop areas (zones already carry a min-height so empty ones are
					// droppable; the moving slot placeholder shows the actual drop spot).
					$container.find('.cerb-profile-layout-zone--widgets')
						.css('outline', '1px dashed var(--cerb-color-background-contrast-200)')
						.css('outline-offset', '-2px')
						;
				},
				onEnd: function() {
					// Clears on commit AND cancel (snap-back)
					$container.find('.cerb-profile-layout-zone--widgets')
						.css('outline', '')
						.css('outline-offset', '')
						;
				}
			});
	});

	// reorderWidgets posts the full zone→widget map; the bubbling sorted event fires once per drop
	$container[0].addEventListener('cerb-ui-sortable:sorted', function() {
		$container.trigger('cerb-reorder');
	});
	{/if}
	
	$container.on('cerb-reorder', function(e) {
		var formData = new FormData();
		formData.set('c', 'profiles');
		formData.set('a', 'invokeTab');
		formData.set('section', 'profile_widget');
		formData.set('tab_id', '{$model->id}');
		formData.set('action', 'reorderWidgets');

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
		var refresh_options = (e.refresh_options && typeof e.refresh_options == 'object') ? e.refresh_options : { };

		CerbUI.utils.series([ CerbUI.utils.apply(loadWidgetFunc, widget_id, false, refresh_options) ], function(err, json) {
			// Done
		});
	});

	$container.on('cerb-widgets-refresh', function(e) {
		var widget_ids = (e.widget_ids && $.isArray(e.widget_ids)) ? e.widget_ids : [];
		var refresh_options = (e.refresh_options && typeof e.refresh_options == 'object') ? e.refresh_options : { };

		var jobs = [];

		$container.find('.cerb-profile-widget').each(function() {
			var $widget = $(this);
			var widget_id = parseInt($widget.attr('data-widget-id'));

			// If we're refreshing this widget or all widgets
			if(widget_id && (0 === widget_ids.length || -1 !== $.inArray(widget_id, widget_ids))) {
				jobs.push(
					CerbUI.utils.apply(loadWidgetFunc, widget_id, false, refresh_options)
				);
			}
		});

		CerbUI.utils.parallelLimit(jobs, 2, function(err, json) {
			// Done
		});
	});

	var addEvents = function($target) {
		var menuEl = $target.find('.cerb-profile-widget--menu')[0];
		var $menu_link = $target.find('.cerb-profile-widget--link');
		var $handle = $target.find('.cerb-profile-widget--header .cerb-icon-menu-hamburger');

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

		var menu = (menuEl && window.CerbUI && CerbUI.Menu) ? new CerbUI.Menu(menuEl, {
				clickTrigger: $menu_link[0],
				onSelect: function(li, src) {
					var $li = $(src);
					
					var $widget = $li.closest('.cerb-profile-widget');
					var widget_id = $widget.attr('data-widget-id');
					
					if($li.is('.cerb-profile-widget-menu--edit')) {
						$li.clone()
							.cerbPeekTrigger()
							.on('cerb-peek-saved', function(e) {
								// [TODO] Check the event type
								CerbUI.utils.series([ CerbUI.utils.apply(loadWidgetFunc, e.id, true, {}) ], function(err, json) {
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
						CerbUI.utils.series([ CerbUI.utils.apply(loadWidgetFunc, widget_id, false, {}) ], function(err, json) {
							// Done
						});
					} else if($li.is('.cerb-profile-widget-menu--export-widget')) {
						genericAjaxPopup('export_widget', 'c=profiles&a=invoke&module=profile_widget&action=exportWidget&id=' + widget_id, null, false);
					}
				}
			}) : null;
		
		return $target;
	}
	
	$container.find('.cerb-profile-widget').each(function() {
		addEvents($(this));
	});

	{if $active_worker->is_superuser}
	$add_button
		.cerbPeekTrigger()
		.on('cerb-peek-saved', function(e) {
			var $zone = $container.find('> .cerb-profile-layout-zone:first > .cerb-profile-layout-zone--widgets:first');
			var $placeholder = $('<div class="cerb-profile-widget"/>').hide().prependTo($zone);
			$('<div/>').attr('id', 'profileWidget' + e.id).appendTo($placeholder);
			
			CerbUI.utils.series([ CerbUI.utils.apply(loadWidgetFunc, e.id, true, {}) ], function(err, json) {
				$container.trigger('cerb-reorder');
			});
		})
	;
	
	$edit_button
		.cerbPeekTrigger()
		.on('cerb-peek-saved', function(e) {
			if(e.hasOwnProperty('label') && cerbTabs && cerbTabs.activeTab) {
				$(cerbTabs.activeTab.li).find('a').text(e.label);
			}

			if(cerbTabs) cerbTabs.refresh();
		})
		.on('cerb-peek-deleted', function(e) {
			if(cerbTabs && cerbTabs.activeTab) {
				$(cerbTabs.activeTab.li).remove();
				cerbTabs.sync();
			}
		})
	;
	
	$toggle_widgets_button
		.on('click', function(e) {
			e.stopPropagation();

			let $btn = $(this);
			let show = 'true' !== $btn.attr('aria-pressed');

			$btn.attr('aria-pressed', show ? 'true' : 'false')
				.toggleClass('cerb-ui-toolbar-button--active', show);

			let $hidden = $container.find('.cerb-profile-widget--hidden');

			if(show) {
				$hidden.show();

				// The initial refresh skips non-visible widgets, so load content for any revealed for the first time
				let load_ids = [];
				$hidden.each(function() {
					let $content = $(this).find('.cerb-profile-widget--content');
					if($content.length && 0 === $content.children().length)
						load_ids.push(parseInt($(this).attr('data-widget-id')));
				});

				if(load_ids.length)
					$container.trigger({ type: 'cerb-widgets-refresh', widget_ids: load_ids });
			} else {
				$hidden.hide();
			}
		})
	;

	let hidden_profile_widgets = $container.find('.cerb-profile-widget--hidden').length;

	if(hidden_profile_widgets > 0) {
		$toggle_widgets_button.find('.badge-count').text(hidden_profile_widgets);
		$toggle_widgets_button.show();
	}
	{/if}
	
	var loadWidgetFunc = function(widget_id, is_full, refresh_options, callback) {
		var $widget = $('#profileWidget' + widget_id).fadeTo('fast', 0.3);
		
		if(!is_full && !$widget.closest('.cerb-profile-widget').is(':visible')) {
			callback();
			return;
		}

		Devblocks.getSpinner(true).prependTo($widget);

		var formData;

		if(refresh_options instanceof FormData) {
			formData = refresh_options;
		} else {
			formData = new FormData();
		}

		formData.set('c', 'profiles');
		formData.set('a', 'invokeTab');
		formData.set('tab_id', '{$model->id}');
		formData.set('action', 'renderWidget');
		formData.set('context', '{$context}');
		formData.set('context_id', '{$context_id}');
		formData.set('id', widget_id);
		formData.set('full', is_full ? '1' : '0');

		if(refresh_options instanceof Object) {
			Devblocks.objectToFormData(refresh_options, formData);
		}

		let hookSuccess = function(html) {
			if(0 === html.length) {
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

			$widget.fadeTo('fast', 1.0);
			callback();
		}

		let hookError = function() {
			$widget.empty();
			callback();
		}

		genericAjaxPost(formData, '', '', hookSuccess, {
			'error': hookError
		});
	};

	$container.triggerHandler($.Event('cerb-widgets-refresh'));
});
</script>