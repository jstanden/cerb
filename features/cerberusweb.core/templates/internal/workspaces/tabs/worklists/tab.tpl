{$is_writeable = CerberusContexts::isWriteableByActor(CerberusContexts::CONTEXT_WORKSPACE_PAGE, $page, $active_worker)}

{if empty($worklists)}
<form action="#">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">
<div class="cerb-ui-panel cerb-ui-panel--spaced cerb-ui-panel--note">
	<div class="cerb-ui-header cerb-ui-header--center">
		<div class="cerb-ui-callout">
			<span class="cerb-icons cerb-icon-circle-info cerb-ui-callout--icon"></span>
			<div>
				<div class="cerb-ui-header--title-sm">Let's put this workspace to good use</div>
				<div class="cerb-ui-header--subtitle">
					You now have a blank worklists tab.  You can click the
					<button type="button" data-cerb-button="edit_menu"><span class="cerb-icons cerb-icon-gear"></span></button>
					button and select <b>Edit Tab</b> from the menu to display any number of worklists right here in a single place.
				</div>
			</div>
		</div>
	</div>
</div>
</form>
{/if}

{if !empty($worklists) && !$is_locked}
<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2 cerb-no-print cerb-u-mb-2">
	{if $is_writeable}
	<div class="cerb-ui-toolbar-strip">
		<button id="btnWorklistsTabEdit{$tab->id}" type="button" class="cerb-ui-toolbar-button"><span class="cerb-icons cerb-icon-edit"></span> Edit Worklists</button>
	</div>
	{/if}

	<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2" style="margin-left:auto;">
		<div id="worklistsTabRefreshRing{$tab->id}" style="display:none;"></div>
		<span class="cerb-ui-header--label">Auto-refresh</span>
		<label class="cerb-ui-toggle"><input type="checkbox" id="btnWorklistsTabAutoRefresh{$tab->id}"><span class="cerb-ui-toggle--slider"></span></label>
		<ul id="worklistsTabRefreshMenu{$tab->id}" hidden>
			<li data-ms="60000">1 min</li>
			<li data-ms="300000">5 min</li>
			<li data-ms="900000">15 min</li>
		</ul>
	</div>
</div>
{/if}

<div id="divWorklistsTab{$tab->id}">
{foreach from=$worklists item=worklist key=worklist_id}
	<div id="worklistPlaceholder{$worklist_id}" style="margin-bottom:10px;">
		<div style="font-size:18px;font-weight:bold;text-align:center;padding:10px;margin:10px;">
			Loading: {$worklist->name}<br>
			{include file="devblocks:cerberusweb.core::ui/spinner.tpl"}
		</div>
	</div>
{/foreach}
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	// Page title
	document.title = "{$tab->name|escape:'javascript' nofilter} - {$page->name|escape:'javascript' nofilter} - {$settings->get('cerberusweb.core','helpdesk_title')|escape:'javascript' nofilter}";

	// Help
	$('#divWorklistsTab{$tab->id}').prev('form').find('[data-cerb-button=edit_menu]').on('click', function(e) {
		e.stopPropagation();

		let $btn = $('#frmWorkspacePage{$page->id} button.config-page');
		let $el = $(this);

		if(window.CerbUI && CerbUI.effects) {
			CerbUI.effects.transfer($el[0], $btn[0], { onEnd: function() {
				CerbUI.effects.pulse($btn[0], { times: 3, onEnd: function() { $btn.click(); } });
			}});
		} else {
			$btn.click();
		}
	});
	
	// Edit Worklists — a shortcut for this tab's "Edit Tab" (same as the workspace page menu, more obvious)
	$('#btnWorklistsTabEdit{$tab->id}').on('click', function(e) {
		e.stopPropagation();
		$('#frmWorkspacePage{$page->id}').find('a.edit-tab').attr('data-context-id', '{$tab->id}').click();
	});

	// Worklist loader

	var async_tasks = [];
	
	var cerbLoadWorklist = function(worklist_id, callback) {
		var $div = $('#worklistPlaceholder' + worklist_id);
	
		$div.fadeTo("fast", 0.2);

		var formData = new FormData();
		formData.set('c', 'pages');
		formData.set('a', 'renderWorklist');
		formData.set('list_id', worklist_id);

		genericAjaxPost(formData, '', '', function(html) {
			var $div = $('#worklistPlaceholder' + worklist_id);
			
			if(null != $div) {
				$div.fadeOut();
				
				$('<div style="margin-bottom:10px;"></div>')
					.fadeTo("fast", 0.2)
					.html(html)
					.insertAfter($div)
					.fadeTo("fast", 1.0)
					;
				
				$div.remove();
			}
			
			callback(null);
		});
	}
	
	{foreach from=$worklists item=worklist key=worklist_id}
	async_tasks.push(CerbUI.utils.apply(cerbLoadWorklist, '{$worklist_id}'));
	{/foreach}

	CerbUI.utils.series(async_tasks, function(err, data) {
		// Done!
	});

	// Auto-refresh: a viewer toggle (projectors/wall displays) that reloads the tab's worklists on a fixed
	// cadence — the rows, not the page. A CerbUI.TimeRing counts down; click it for the interval menu.
	let $refreshContainer = $('#divWorklistsTab{$tab->id}');

	let autoRefresh = {
		INTERVAL_MS: 5 * 60 * 1000, // 5 min default (ring menu offers 1 / 5 / 15)
		on: false,
		cycleStart: 0,
		pausedAt: 0,
		ringEl: document.getElementById('worklistsTabRefreshRing{$tab->id}'),
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
		refresh: function() {
			// Fire each worklist view's own refresh event (re-renders rows in place, no page reload)
			$refreshContainer.find('div[id^="viewCustomFilters"]').trigger('view_refresh');
		},
	};

	if(autoRefresh.ringEl && window.CerbUI && CerbUI.TimeRing)
		autoRefresh.ring = new CerbUI.TimeRing(autoRefresh.ringEl, { size: 34, key: autoRefresh.intervalLabel(autoRefresh.INTERVAL_MS) });

	// The ring doubles as the interval picker: click it for a 1 / 5 / 15-min menu
	let autoRefreshMenuUl = document.getElementById('worklistsTabRefreshMenu{$tab->id}');
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

	let autoRefreshToggle = document.getElementById('btnWorklistsTabAutoRefresh{$tab->id}');
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
		if(!$refreshContainer[0] || !document.body.contains($refreshContainer[0])) { clearInterval(autoRefreshTimer); return; }
		if(!autoRefresh.on) return;

		// Freeze the countdown (hold, don't drain, don't fire) while the tab is hidden, a popup is open in front
		// of the user, OR the ring itself is scrolled out of view — the timer only runs while you can see it, so
		// a refresh only ever happens at the top-of-tab glance view and never yanks a mid-read reader upward.
		let paused = !$refreshContainer.is(':visible') || autoRefreshPopupOpen();
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
			autoRefresh.refresh();
			return;
		}

		if(autoRefresh.ring) {
			autoRefresh.ring.setFraction(Math.min(1, elapsed / autoRefresh.INTERVAL_MS));
			autoRefresh.ring.setValue(CerbUI.date.remain(Math.max(0, Math.round((autoRefresh.INTERVAL_MS - elapsed) / 1000))));
		}
	}, 1000);
});
</script>