{$is_writeable = !$is_locked && CerberusContexts::isWriteableByActor(CerberusContexts::CONTEXT_WORKSPACE_PAGE, $page, $active_worker)}

<div style="margin-bottom:5px;">
	{if !$is_locked}
	<div style="display:flex;flex-flow:row wrap;align-items:center;gap:10px;margin-bottom:5px;">
		{if $is_writeable}
		<div class="cerb-ui-toolbar-strip cerb-no-print">
			{if $active_worker->hasPriv("contexts.{CerberusContexts::CONTEXT_WORKSPACE_WIDGET}.create")}<button id="btnWorkspaceTabAddWidget{$model->id}" type="button" class="cerb-peek-trigger cerb-ui-toolbar-button" data-context="{CerberusContexts::CONTEXT_WORKSPACE_WIDGET}" data-context-id="0" data-edit="tab:{$model->id}" data-width="75%"><span class="cerb-icons cerb-icon-circle-plus"></span> {'common.widget.add'|devblocks_translate|capitalize}</button>{/if}
			<button id="btnWorkspaceTabEditDashboard{$model->id}" type="button" class="cerb-ui-toolbar-button"><span class="cerb-icons cerb-icon-edit"></span> {'common.dashboard.edit'|devblocks_translate|capitalize}</button>
			<button id="btnWorkspaceTabToggleWidgets{$model->id}" type="button" class="cerb-ui-toolbar-button" style="display:none;" aria-pressed="false" title="Hidden widgets"><span class="cerb-icons cerb-icon-eye-close"></span> Hidden Widgets <span class="cerb-ui-toolbar--badge cerb-ui-toolbar--badge-neutral badge-count">0</span></button>
		</div>
		{/if}

		<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2 cerb-no-print" style="margin-left:auto;">
			<div id="workspaceTabRefreshRing{$model->id}" style="display:none;"></div>
			<span class="cerb-ui-header--label">Auto-refresh</span>
			<label class="cerb-ui-toggle"><input type="checkbox" id="btnWorkspaceTabAutoRefresh{$model->id}"><span class="cerb-ui-toggle--slider"></span></label>
			<ul id="workspaceTabRefreshMenu{$model->id}" hidden>
				<li data-ms="60000">1 min</li>
				<li data-ms="300000">5 min</li>
				<li data-ms="900000">15 min</li>
			</ul>
		</div>
	</div>
	{/if}

	{include file="devblocks:cerberusweb.core::internal/dashboards/prompts/render.tpl" prompts=$prompts}
</div>

{if 'sidebar_left' == $layout}
	<div id="workspaceTab{$model->id}" class="cerb-workspace-layout cerb-workspace-layout--sidebar-left" style="vertical-align:top;display:flex;flex-flow:row wrap;">
		<div data-layout-zone="sidebar" class="cerb-workspace-layout-zone" style="flex:1 1 33%;min-width:345px;overflow-x:clip;">
			<div class="cerb-workspace-layout-zone--widgets" style="padding:2px;vertical-align:top;display:flex;flex-flow:row wrap;min-height:100px;">
			{foreach from=$zones.sidebar item=widget name=widgets}
				{include file="devblocks:cerberusweb.core::internal/workspaces/widgets/render.tpl" widget=$widget}
			{/foreach}
			</div>
		</div>
		
		<div data-layout-zone="content" class="cerb-workspace-layout-zone" style="flex:2 2 66%;min-width:345px;overflow-x:clip;">
			<div class="cerb-workspace-layout-zone--widgets" style="padding:2px;vertical-align:top;display:flex;flex-flow:row wrap;min-height:100px;">
			{foreach from=$zones.content item=widget name=widgets}
				{include file="devblocks:cerberusweb.core::internal/workspaces/widgets/render.tpl" widget=$widget}
			{/foreach}
			</div>
		</div>
	</div>
{elseif 'sidebar_right' == $layout}
	<div id="workspaceTab{$model->id}" class="cerb-workspace-layout cerb-workspace-layout--sidebar-right" style="vertical-align:top;display:flex;flex-flow:row wrap;">
		<div data-layout-zone="content" class="cerb-workspace-layout-zone cerb-workspace-layout-zone--content" style="flex:2 2 66%;min-width:345px;overflow-x:clip;">
			<div class="cerb-workspace-layout-zone--widgets" style="padding:2px;vertical-align:top;display:flex;flex-flow:row wrap;min-height:100px;">
			{foreach from=$zones.content item=widget name=widgets}
				{include file="devblocks:cerberusweb.core::internal/workspaces/widgets/render.tpl" widget=$widget}
			{/foreach}
			</div>
		</div>
		
		<div data-layout-zone="sidebar" class="cerb-workspace-layout-zone cerb-workspace-layout-zone--sidebar" style="flex:1 1 33%;min-width:345px;overflow-x:clip;">
			<div class="cerb-workspace-layout-zone--widgets" style="padding:2px;vertical-align:top;display:flex;flex-flow:row wrap;min-height:100px;">
			{foreach from=$zones.sidebar item=widget name=widgets}
				{include file="devblocks:cerberusweb.core::internal/workspaces/widgets/render.tpl" widget=$widget}
			{/foreach}
			</div>
		</div>
	</div>
{elseif 'halves' == $layout}
	<div id="workspaceTab{$model->id}" class="cerb-workspace-layout cerb-workspace-layout--halves" style="vertical-align:top;display:flex;flex-flow:row wrap;">
		<div data-layout-zone="left" class="cerb-workspace-layout-zone cerb-workspace-layout-zone--left" style="flex:1 1 50%;min-width:345px;overflow-x:clip;">
			<div class="cerb-workspace-layout-zone--widgets" style="padding:2px;vertical-align:top;display:flex;flex-flow:row wrap;min-height:100px;">
				{foreach from=$zones.left item=widget name=widgets}
					{include file="devblocks:cerberusweb.core::internal/workspaces/widgets/render.tpl" widget=$widget}
				{/foreach}
			</div>
		</div>

		<div data-layout-zone="right" class="cerb-workspace-layout-zone cerb-workspace-layout-zone--right" style="flex:1 1 50%;min-width:345px;overflow-x:clip;">
			<div class="cerb-workspace-layout-zone--widgets" style="padding:2px;vertical-align:top;display:flex;flex-flow:row wrap;min-height:100px;">
				{foreach from=$zones.right item=widget name=widgets}
					{include file="devblocks:cerberusweb.core::internal/workspaces/widgets/render.tpl" widget=$widget}
				{/foreach}
			</div>
		</div>
	</div>
{elseif 'thirds' == $layout}
	<div id="workspaceTab{$model->id}" class="cerb-workspace-layout cerb-workspace-layout--thirds" style="vertical-align:top;display:flex;flex-flow:row wrap;">
		<div data-layout-zone="left" class="cerb-workspace-layout-zone cerb-workspace-layout-zone--left" style="flex:1 1 33%;min-width:345px;overflow-x:clip;">
			<div class="cerb-workspace-layout-zone--widgets" style="padding:2px;vertical-align:top;display:flex;flex-flow:row wrap;min-height:100px;">
			{foreach from=$zones.left item=widget name=widgets}
				{include file="devblocks:cerberusweb.core::internal/workspaces/widgets/render.tpl" widget=$widget}
			{/foreach}
			</div>
		</div>
		
		<div data-layout-zone="center" class="cerb-workspace-layout-zone cerb-workspace-layout-zone--center" style="flex:1 1 33%;min-width:345px;overflow-x:clip;">
			<div class="cerb-workspace-layout-zone--widgets" style="padding:2px;vertical-align:top;display:flex;flex-flow:row wrap;min-height:100px;">
			{foreach from=$zones.center item=widget name=widgets}
				{include file="devblocks:cerberusweb.core::internal/workspaces/widgets/render.tpl" widget=$widget}
			{/foreach}
			</div>
		</div>
		
		<div data-layout-zone="right" class="cerb-workspace-layout-zone cerb-workspace-layout-zone--right" style="flex:1 1 33%;min-width:345px;overflow-x:clip;">
			<div class="cerb-workspace-layout-zone--widgets" style="padding:2px;vertical-align:top;display:flex;flex-flow:row wrap;min-height:100px;">
			{foreach from=$zones.right item=widget name=widgets}
				{include file="devblocks:cerberusweb.core::internal/workspaces/widgets/render.tpl" widget=$widget}
			{/foreach}
			</div>
		</div>
	</div>
{else}
	<div id="workspaceTab{$model->id}" class="cerb-workspace-layout cerb-workspace-layout--content" style="vertical-align:top;display:flex;flex-flow:row wrap;">
		<div data-layout-zone="content" class="cerb-workspace-layout-zone" style="flex:1 1 100%;overflow-x:clip;">
			<div class="cerb-workspace-layout-zone--widgets" style="padding:2px;vertical-align:top;display:flex;flex-flow:row wrap;min-height:100px;">
			{foreach from=$zones.content item=widget name=widgets}
				{include file="devblocks:cerberusweb.core::internal/workspaces/widgets/render.tpl" widget=$widget}
			{/foreach}
			</div>
		</div>
	</div>
{/if}

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var $container = $('#workspaceTab{$model->id}');
	var $add_button = $('#btnWorkspaceTabAddWidget{$model->id}');
	var $edit_button = $('#btnWorkspaceTabEditDashboard{$model->id}');
	let $toggle_widgets_button = $('#btnWorkspaceTabToggleWidgets{$model->id}');
	
	// Drag
	{if $is_writeable}
	$container.find('.cerb-workspace-layout-zone--widgets').each(function() {
		if(window.CerbUI && CerbUI.Sortable)
			new CerbUI.Sortable(this, {
				tolerance: 'pointer',
				items: '.cerb-workspace-widget',
				handle: '.cerb-workspace-widget--header .cerb-icon-menu-hamburger',
				connectWith: '.cerb-workspace-layout-zone--widgets',
				helper: 'clone',
				onStart: function() {
					// Faint outline marks the drop areas (zones already carry a min-height so empty ones are
					// droppable; the moving slot placeholder shows the actual drop spot).
					$container.find('.cerb-workspace-layout-zone--widgets')
						.css('outline', '1px dashed var(--cerb-color-background-contrast-200)')
						.css('outline-offset', '-2px')
						;
				},
				onEnd: function() {
					// Clears on commit AND cancel (snap-back)
					$container.find('.cerb-workspace-layout-zone--widgets')
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
		formData.set('a', 'invoke');
		formData.set('module', 'workspace_widget');
		formData.set('action', 'reorderWidgets');
		formData.set('tab_id', '{$model->id}');

		// Zones
		$container.find('> .cerb-workspace-layout-zone')
			.each(function(d) {
				var $cell = $(this);
				var zone = $cell.attr('data-layout-zone');
				var ids = $cell.find('.cerb-workspace-widget').map(function(d) { return $(this).attr('data-widget-id'); });

				formData.append('zones[' + zone + ']', $.makeArray(ids));
			})
			;

		genericAjaxPost(formData);
	});
	
	$container.on('cerb-widget-refresh', function(e) {
		var widget_id = e.widget_id;
		var refresh_options = (e.refresh_options && typeof e.refresh_options == 'object') ? e.refresh_options : {};

		CerbUI.utils.series([ CerbUI.utils.apply(loadWidgetFunc, widget_id, false, refresh_options) ], function(err, json) {
			// Done
		});
	});

	$container.on('cerb-widgets-refresh', function(e) {
		var widget_ids = (e.widget_ids && $.isArray(e.widget_ids)) ? e.widget_ids : [];
		var refresh_options = (e.refresh_options && typeof e.refresh_options == 'object') ? e.refresh_options : { };

		var jobs = [];

		$container.find('.cerb-workspace-widget').each(function() {
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
		var menuEl = $target.find('.cerb-workspace-widget--menu')[0];
		var $menu_link = $target.find('.cerb-workspace-widget--link');
		var $handle = $target.find('.cerb-workspace-widget--header .cerb-icon-menu-hamburger');

		{if $is_writeable}
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
					
					var $widget = $li.closest('.cerb-workspace-widget');
					var widget_id = $widget.attr('data-widget-id');
					
					if($li.is('.cerb-workspace-widget-menu--edit')) {
						$li.clone()
							.cerbPeekTrigger()
							.on('cerb-peek-saved', function(e) {
								// [TODO] Check the event type
								CerbUI.utils.series([ CerbUI.utils.apply(loadWidgetFunc, e.id, true, {}) ], function(err, json) {
									// Done
								});
							})
							.on('cerb-peek-deleted', function(e) {
								$('#workspaceWidget' + e.id).closest('.cerb-workspace-widget').remove();
								$container.trigger('cerb-reorder');
							})
							.click()
							;
						
					} else if($li.is('.cerb-workspace-widget-menu--refresh')) {
						CerbUI.utils.series([ CerbUI.utils.apply(loadWidgetFunc, widget_id, false, {}) ], function(err, json) {
							// Done
						});
						
					} else if($li.is('.cerb-workspace-widget-menu--export-data')) {
						let url = 'c=profiles&a=invoke&module=workspace_widget&action=exportWidgetData&id=' + widget_id;
						let $cal = $('#workspaceWidget' + widget_id).find('form[data-cerb-calendar-month]').first();
						if($cal.length) {
							url += '&month=' + encodeURIComponent($cal.attr('data-cerb-calendar-month'))
								+  '&year='  + encodeURIComponent($cal.attr('data-cerb-calendar-year'));
						}
						genericAjaxPopup('export_data', url, null, false);

					} else if($li.is('.cerb-workspace-widget-menu--export-widget')) {
						genericAjaxPopup('export_widget', 'c=profiles&a=invoke&module=workspace_widget&action=exportWidget&id=' + widget_id, null, false);
						
					}
				}
			}) : null;
		
		return $target;
	}

	$container.find('.cerb-workspace-widget').each(function() {
		addEvents($(this));
	});

	{if $is_writeable}
	$add_button
		.cerbPeekTrigger()
		.on('cerb-peek-saved', function(e) {
			var $zone = $container.find('> .cerb-workspace-layout-zone:first > .cerb-workspace-layout-zone--widgets:first');
			var $placeholder = $('<div class="cerb-workspace-widget"/>').hide().prependTo($zone);
			$('<div/>').attr('id', 'workspaceWidget' + e.id).appendTo($placeholder);
			
			CerbUI.utils.series([ CerbUI.utils.apply(loadWidgetFunc, e.id, true, {}) ], function(err, json) {
				$container.trigger('cerb-reorder');
			});
		})
		;
	
	$edit_button
		.on('click', function(e) {
			e.stopPropagation();
			var $workspace = $('#frmWorkspacePage{$model->workspace_page_id}');
			$workspace.find('a.edit-tab').attr('data-context-id', '{$model->id}').click();
		})
		;

	$toggle_widgets_button
		.on('click', function(e) {
			e.stopPropagation();

			let $btn = $(this);
			let show = 'true' !== $btn.attr('aria-pressed');

			$btn.attr('aria-pressed', show ? 'true' : 'false')
				.toggleClass('cerb-ui-toolbar-button--active', show);

			let $hidden = $container.find('.cerb-workspace-widget--hidden');

			if(show) {
				$hidden.show();

				// The initial refresh skips non-visible widgets, so load content for any revealed for the first time
				let load_ids = [];
				$hidden.each(function() {
					let $content = $(this).find('.cerb-workspace-widget--content');
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

	let hidden_widget_count = $container.find('.cerb-workspace-widget--hidden').length;

	if(hidden_widget_count > 0) {
		$toggle_widgets_button.find('.badge-count').text(hidden_widget_count);
		$toggle_widgets_button.show();
	}
	{/if}

	var loadWidgetFunc = function(widget_id, is_full, refresh_options, callback) {
		var $widget = $('#workspaceWidget' + widget_id).fadeTo('fast', 0.3);

		if(!is_full && !$widget.closest('.cerb-workspace-widget').is(':visible')) {
			callback();
			return;
		}

		if(is_full) {
			Devblocks.getSpinner().prependTo($widget);
		} else {
			Devblocks.getSpinner(true).prependTo($widget);
		}

		var formData;

		if(refresh_options instanceof FormData) {
			formData = refresh_options;
		} else {
			formData = new FormData();
		}

		formData.set('c', 'profiles');
		formData.set('a', 'invoke');
		formData.set('module', 'workspace_widget');
		formData.set('action', 'renderWidget');
		formData.set('id', widget_id);
		formData.set('full', is_full ? '1' : '0');

		if(refresh_options instanceof Object) {
			Devblocks.objectToFormData(refresh_options, formData);
		}

		let hookSuccess = function(html) {
			if('string' !== typeof html || 0 === html.length) {
				$widget.empty();

				$('<div/>')
					.text('Error: Widget failed to load.')
					.css('margin-bottom', '25px')
					.appendTo($widget)
				;

				if(is_full) {
					var $parent = $widget.closest('.cerb-workspace-widget');
					var $clone = $parent.clone();

					addEvents($clone).insertBefore(
						$widget.closest('.cerb-workspace-widget').hide()
					);

					$widget.closest('.cerb-workspace-widget').remove();
				}

			} else {
				try {
					if(is_full) {
						addEvents($(html)).insertBefore(
							$widget.attr('id',null).closest('.cerb-workspace-widget').hide()
						);

						$widget.closest('.cerb-workspace-widget').remove();
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
		};

		let hookError = function(err) {
			$widget.empty();

			if('object' == typeof err && err.hasOwnProperty('responseText')) {
				$widget.text('Error: ' + err.responseText + ' (' + err.status + ')');
			}

			callback();
		}

		genericAjaxPost(formData, '', '', hookSuccess, {
			'error': hookError
		});
	};
	
	clearInterval(window.dashboardTimer{$model->id});
	
	var tick = function() {
		var $dashboard = $('#workspaceTab{$model->id}');
		
		if($dashboard.length === 0 || !$dashboard.is(':visible')) {
			clearInterval(window.dashboardTimer{$model->id});
			delete window.dashboardTimer{$model->id};
			return;
		}
		
		$dashboard.find('.cerb-workspace-widget').each(function() {
			$(this).triggerHandler('cerb-dashboard-heartbeat');
		});
	};
	
	window.dashboardTimer{$model->id} = setInterval(tick, 1000);

	// Auto-refresh: a viewer toggle (projectors/wall displays) that reloads the tab's widgets on a fixed
	// cadence — the widgets, not the page. A CerbUI.TimeRing counts down; click it for the interval menu.
	let autoRefresh = {
		INTERVAL_MS: 5 * 60 * 1000, // 5 min default (ring menu offers 1 / 5 / 15)
		on: false,
		cycleStart: 0,
		pausedAt: 0,
		ringEl: document.getElementById('workspaceTabRefreshRing{$model->id}'),
		ring: null,
		intervalLabel: function(ms) { return Math.round(ms / 60000) + 'm'; },
		applyInterval: function(ms) {
			this.INTERVAL_MS = ms;
			this.cycleStart = Date.now();
			if(this.ring) this.ring.setKey(this.intervalLabel(ms));
		},
		start: function() {
			if(this.on) return;
			this.on = true;
			if(this.ringEl) this.ringEl.style.display = '';
			if(this.ring) this.ring.setKey(this.intervalLabel(this.INTERVAL_MS));
			this.cycleStart = Date.now();
			this.pausedAt = 0;
		},
		stop: function() {
			this.on = false;
			if(this.ringEl) this.ringEl.style.display = 'none';
			if(this.ring) this.ring.setFraction(0);
		},
	};

	if(autoRefresh.ringEl && window.CerbUI && CerbUI.TimeRing)
		autoRefresh.ring = new CerbUI.TimeRing(autoRefresh.ringEl, { size: 34, key: autoRefresh.intervalLabel(autoRefresh.INTERVAL_MS) });

	// The ring doubles as the interval picker: click it for a 1 / 5 / 15-min menu
	let autoRefreshMenuUl = document.getElementById('workspaceTabRefreshMenu{$model->id}');
	if(autoRefresh.ringEl && autoRefreshMenuUl && window.CerbUI && CerbUI.Menu) {
		autoRefresh.ringEl.style.cursor = 'pointer';
		autoRefresh.ringEl.setAttribute('title', 'Change refresh interval');
		new CerbUI.Menu(autoRefreshMenuUl, {
			clickTrigger: autoRefresh.ringEl,
			onSelect: function(li, src) {
				let ms = parseInt($(src).attr('data-ms'), 10);
				if(ms > 0) autoRefresh.applyInterval(ms);
			}
		});
	}

	let autoRefreshToggle = document.getElementById('btnWorkspaceTabAutoRefresh{$model->id}');
	if(autoRefreshToggle && window.CerbUI && CerbUI.Toggle)
		new CerbUI.Toggle(autoRefreshToggle, { onChange: function(checked) { checked ? autoRefresh.start() : autoRefresh.stop(); } });

	// True while a peek/dialog is open ON SCREEN (minimized/docked ones don't count, so a parked popup doesn't
	// freeze auto-refresh). Peeks (genericAjaxPopup) are CerbUI.Dialogs tracked in _openDialogs.
	let autoRefreshPopupOpen = function() {
		if(window.CerbUI && CerbUI.Dialog && CerbUI.Dialog._openDialogs) {
			for(const d of CerbUI.Dialog._openDialogs) {
				if(d && d._open && !d.minimized) return true;
			}
		}
		if(window.jQuery && $('.ui-dialog:visible').length) return true; // legacy jQuery-UI popups
		return false;
	};

	let autoRefreshTimer = setInterval(function() {
		// Self-clear once this tab's container leaves the document (page/tab navigation)
		if(!$container[0] || !document.body.contains($container[0])) { clearInterval(autoRefreshTimer); return; }
		if(!autoRefresh.on) return;

		// Freeze the countdown (hold, don't drain, don't fire) while the tab is hidden, a popup is open in front
		// of the user, OR the ring itself is scrolled out of view — the timer only runs while you can see it, so
		// a refresh only ever happens at the top-of-tab glance view and never yanks a mid-read reader upward.
		let paused = !$container.is(':visible') || autoRefreshPopupOpen();
		if(!paused && autoRefresh.ringEl) {
			let r = autoRefresh.ringEl.getBoundingClientRect();
			if(r.bottom <= 0 || r.top >= (window.innerHeight || document.documentElement.clientHeight)) paused = true;
		}
		if(paused) {
			if(!autoRefresh.pausedAt) autoRefresh.pausedAt = Date.now();
			return;
		}

		// Resuming: shift the cycle forward by however long we were paused so the countdown held where it was,
		// then guarantee a short grace (≥10s) so it never fires the instant they return or dismiss a dialog.
		if(autoRefresh.pausedAt) {
			autoRefresh.cycleStart += Date.now() - autoRefresh.pausedAt;
			autoRefresh.pausedAt = 0;
			if(autoRefresh.INTERVAL_MS - (Date.now() - autoRefresh.cycleStart) < 10000)
				autoRefresh.cycleStart = Date.now() - (autoRefresh.INTERVAL_MS - 10000);
		}

		let elapsed = Date.now() - autoRefresh.cycleStart;

		if(elapsed >= autoRefresh.INTERVAL_MS) {
			autoRefresh.cycleStart = Date.now();
			$container.triggerHandler('cerb-widgets-refresh');
			return;
		}

		if(autoRefresh.ring) {
			autoRefresh.ring.setFraction(Math.min(1, elapsed / autoRefresh.INTERVAL_MS));
			autoRefresh.ring.setValue(CerbUI.date.remain(Math.max(0, Math.round((autoRefresh.INTERVAL_MS - elapsed) / 1000))));
		}
	}, 1000);

	$container.triggerHandler('cerb-widgets-refresh');
});
</script>