	<div class="cerb-uiref-component" id="calendar">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-calendar"></span>Calendar</div>

		{* Example: multi-source calendar with day/week/month/year views, spanning strips, legend toggle *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Multi-source calendar &mdash; switch <code>Day/Week/Month/Year</code>, toggle a source in the legend, click a day number (month) or month name (year) to drill in, click empty time to "create"</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div id="uiref-calendar" style="width:100%;"></div>
				<div class="cerb-uiref-result" style="margin-top:0.7em;">Last action: <b id="uiref-calendar-result">&mdash;</b></div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>const cal = new CerbUI.Calendar(el, {
	defaultView: 'month',          // 'day' | 'week' | 'month' | 'year'
	startOfWeek: 'mon',            // 'mon' | 'sun'
	calendarId:  123,              // used by the default click-to-create peek
	sources: [
		{ id: 'team', label: 'Team', color: '#4a90d9', events: [
			// epoch SECONDS; allDay events render as colored pills, multi-day ones as continuous strips
			{ label: 'Conference', start: START, end: END, allDay: true, icon: 'calendar' },
			{ label: 'Standup', start: START, end: END },   // timed → swatch + start time
		] },
		{ id: 'oncall', label: 'On-call', color: '#e67e22', fetch: function(startSec, endSec) {
			// return a flat array of events for the range (or set serverShape:true for the raw
			// day-keyed Model_Calendar::getEvents() payload — it is auto de-duped into spanning events)
			return fetchEventsForRange(startSec, endSec);
		} },
	],
	onEventClick: function(ev, domEvt) { /* return false to suppress the default record peek */ },
	onCellClick:  function(ctx) { /* ctx = start, end, allDay, view; return false to suppress default create */ },
	// onCreate: function(ctx) { /* overrides the default calendar_event create peek entirely */ },
});

// DOM events on el (bubbles): cerb-ui-calendar:viewchange / :datechange / :rangechange /
//   :eventclick / :cellclick / :create / :sourcetoggle
// API: cal.setView(v); cal.setDate(d); cal.next()/prev()/today();
//      cal.addSource(s); cal.removeSource(id); cal.toggleSource(id); cal.refresh(); cal.destroy();</pre>
			</div>
		</div>
	</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}">
{literal}
(function() {
	const el = document.getElementById('uiref-calendar');
	const out = document.getElementById('uiref-calendar-result');
	if(!el || !window.CerbUI || !CerbUI.Calendar) return;

	// Build demo events relative to the current month so they always land in view.
	const now = new Date();
	const y = now.getFullYear(), m = now.getMonth();
	const at = function(day, h, mi) { return Math.floor(new Date(y, m, day, h || 0, mi || 0).getTime() / 1000); };
	const allDay = function(day) { const s = at(day); return { start: s, end: s + 86399, allDay: true }; };

	const cal = new CerbUI.Calendar(el, {
		defaultView: 'month',
		startOfWeek: 'mon',
		sources: [
			{ id: 'team', label: 'Team', color: '#4a90d9', events: [
				// A ~2-week block → ONE continuous strip wrapping across week rows (not repeated per day).
				{ label: 'Conference', start: at(8), end: at(19) + 86399, allDay: true, icon: 'calendar' },
				Object.assign({ label: 'Holiday' }, allDay(4)),
				{ label: 'Standup', start: at(10, 9, 0), end: at(10, 9, 30) },
				{ label: 'Design review', start: at(10, 14, 0), end: at(10, 15, 30) },
				{ label: 'Sprint demo', start: at(24, 11, 0), end: at(24, 12, 0) },
			] },
			{ id: 'oncall', label: 'On-call', color: '#e67e22', events: [
				{ label: 'On-call: Ana', start: at(1), end: at(7) + 86399, allDay: true, icon: 'bell' },
				{ label: 'On-call: Bo', start: at(15), end: at(21) + 86399, allDay: true, icon: 'bell' },
				{ label: 'Deploy window', start: at(12, 16, 0), end: at(12, 17, 30) },
			] },
			{ id: 'personal', label: 'Personal', color: '#8e44ad', events: [
				{ label: 'Lunch', start: at(10, 12, 0), end: at(10, 13, 0) },
				Object.assign({ label: 'PTO' }, allDay(22)),
			] },
		],
		onEventClick: function(ev) {
			if(out) out.textContent = 'Clicked event: ' + ev.label;
			return false; // demo has no real records to peek
		},
		onCellClick: function(ctx) {
			const when = new Date(ctx.start * 1000);
			if(out) out.textContent = 'Create ' + (ctx.allDay ? 'all-day' : 'timed') + ' event @ ' + when.toLocaleString();
			return false; // demo has no real calendar to attach to
		},
	});

	el.addEventListener('cerb-ui-calendar:viewchange', function(e) {
		if(out) out.textContent = 'View → ' + e.detail.view;
	});
})();
{/literal}
</script>
