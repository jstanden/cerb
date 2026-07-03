{* Shared CerbUI.Calendar host for the calendar widgets (profile / workspace / card).
   Config (dom id, calendar id, tz offset, week start, source color) is assigned as JSON by
   Model_Calendar::displayWidget(). The calendar fetches its events from c=ui&a=calendarEventsJson. *}
<div id="{$cerb_ui_calendar_dom_id}" class="cerb-ui-calendar-widget" style="width:100%;"></div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
{literal}
(function() {
	const cfg = {/literal}{$cerb_ui_calendar_config nofilter}{literal};
	const el = document.getElementById(cfg.domId);

	if(!el || !window.CerbUI || !CerbUI.Calendar)
		return;

	const calId = cfg.calendarId;

	new CerbUI.Calendar(el, {
		defaultView: cfg.defaultView,
		startOfWeek: cfg.startOfWeek,
		tz: cfg.tz,
		calendarId: calId,
		sources: [{
			id: 'cal' + calId,
			label: cfg.label,
			color: cfg.color,
			serverShape: true,
			fetch: function(startSec, endSec) {
				return new Promise(function(resolve) {
					genericAjaxGet('', 'c=ui&a=calendarEventsJson&calendar_id=' + calId + '&from=' + startSec + '&to=' + endSec, function(json) {
						resolve((json && json.events) ? json.events : {});
					});
				});
			}
		}]
	});
})();
{/literal}
</script>
