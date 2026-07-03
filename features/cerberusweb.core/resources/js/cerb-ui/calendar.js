/*
 * CerbUI.Calendar — a zero-dependency, inline calendar with day/week/month/year views and multiple event
 * sources (like chart series + a legend). Modeled on CerbUI.DatePicker; shares grid math + tz logic via
 * CerbUI.cal (calendar-core.js).
 *
 * Usage:
 *   new CerbUI.Calendar(el, {
 *     defaultView: 'month',           // 'day' | 'week' | 'month' | 'year'
 *     startOfWeek: 'mon',             // 'mon' | 'sun'
 *     calendarId:  123,               // used by the default click-to-create peek
 *     sources: [
 *       { id:'team', label:'Team', color:'#4a90d9', events:[ {label,color,start,end,allDay,context,contextId} ] },
 *       { id:'oncall', label:'On-call', color:'#e67e22', fetch: (startSec,endSec) => fetchEvents(...) },
 *     ],
 *     onEventClick: (ev, domEvt) => {},   // return false to suppress default (open the record peek)
 *     onCellClick:  (ctx) => {},          // {start,end,allDay,view}; return false to suppress default create
 *     onCreate:     (ctx) => {},          // overrides the default create-peek entirely
 *   });
 *
 * Events (on el, bubbles): cerb-ui-calendar:{viewchange,datechange,rangechange,eventclick,cellclick,create,
 *   sourcetoggle}. Data comes from each source's static `events[]` or async `fetch(startSec,endSec)`; set
 *   serverShape:true to accept the raw day-keyed Model_Calendar::getEvents() payload (auto de-duped).
 */

// Lucide-style inline SVGs — constant strings, never user data; stroke=currentColor themes them.
const _CAL_PREV_SVG =
	'<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"' +
	' stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
	'<polyline points="15 18 9 12 15 6"/></svg>';
const _CAL_NEXT_SVG =
	'<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"' +
	' stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
	'<polyline points="9 18 15 12 9 6"/></svg>';

const _CAL_VIEW_LABELS = { day: 'Day', week: 'Week', month: 'Month', year: 'Year' };

// Geometry lives in JS (deterministic), skin in CSS. Month slots + day/week vertical grid.
const _CAL_MONTH_SLOT_H = 20;   // px per strip lane / timed row in a month cell
const _CAL_MONTH_DAYNUM_H = 24; // px reserved for the day-number header in a month cell
const _CAL_MONTH_CELL_PAD = 2;  // px daycell top padding (must match _calendar.scss .--daycell padding-top)
const _CAL_MIN_EVENT_H = 16;    // px minimum height of a timed event block

// Small DOM builder.
function _calEl(tag, cls, text) {
	const e = document.createElement(tag);
	if(cls) e.className = cls;
	if(text != null) e.textContent = text;
	return e;
}

CerbUI.Calendar = class {
	static _uid = 0;
	static _instances = new WeakMap();
	static from(el) { return CerbUI.Calendar._instances.get(el); }

	constructor(el, opts = {}) {
		this.el = (typeof el === 'string') ? document.querySelector(el) : el;
		if(!this.el) return;
		this.uid = ++CerbUI.Calendar._uid;

		this.opts = Object.assign({
			views:        ['day', 'week', 'month', 'year'],
			defaultView:  'month',
			viewStorageKey: null,
			date:         null,
			startOfWeek:  'mon',
			dayStartHour: 0,
			dayEndHour:   24,
			hourHeight:   44,
			timeFormat:   '12h',
			tz:           null,   // IANA name (e.g. 'America/Los_Angeles') → DST-aware; null = browser-local
			tzOffsetMinutes: null, // legacy fixed offset (minutes east of UTC); NOT DST-aware — prefer tz
			showLegend:   true,
			maxPerDay:    4,
			calendarId:   null,
			onEventClick: null,
			onCellClick:  null,
			onCreate:     null,
			onDrillDown:  null,
			onRangeChange: null,
		}, opts);

		// Prefer an IANA zone name (DST-aware); fall back to the legacy fixed-offset number, else browser-local.
		this.tz = (this.opts.tz != null && this.opts.tz !== '') ? this.opts.tz : this.opts.tzOffsetMinutes;
		this.view = this.opts.views.includes(this.opts.defaultView) ? this.opts.defaultView : this.opts.views[0];
		this.viewDate = this._toDate(this.opts.date) || new Date();

		// Source model: Map<id, {descriptor, visible, events:Normalized[], loaded}>
		this.sources = new Map();
		(opts.sources || []).forEach((src) => this._addSourceModel(src));

		this._range = { start: 0, end: 0 };
		this._fetchSeq = 0;
		this._cache = new Map();
		this._tooltip = null;

		this._build();
		CerbUI.Calendar._instances.set(this.el, this);

		this._syncRange();
		this._fetchRange();
	}

	// ── Construction ──────────────────────────────────────────────────────────

	_build() {
		this.el.classList.add('cerb-ui-calendar');
		this.el.textContent = '';

		// Toolbar: nav + caption + view switcher
		const toolbar = _calEl('div', 'cerb-ui-calendar--toolbar');

		const navGroup = _calEl('div', 'cerb-ui-calendar--nav-group');
		this._prevBtn = this._navBtn('cerb-ui-calendar--nav', 'Previous', _CAL_PREV_SVG, () => this.prev());
		this._todayBtn = _calEl('button', 'cerb-ui-calendar--today', 'Today');
		this._todayBtn.type = 'button';
		this._todayBtn.addEventListener('click', () => this.today());
		this._nextBtn = this._navBtn('cerb-ui-calendar--nav', 'Next', _CAL_NEXT_SVG, () => this.next());
		navGroup.append(this._prevBtn, this._todayBtn, this._nextBtn);

		this._caption = _calEl('div', 'cerb-ui-calendar--caption');

		toolbar.append(navGroup, this._caption);

		// View switcher (only if more than one view)
		if(this.opts.views.length > 1) {
			this._switcherEl = _calEl('div', 'cerb-ui-switcher cerb-ui-calendar--views');
			this.opts.views.forEach((v) => {
				const b = _calEl('button', 'cerb-ui-calendar--view-btn', _CAL_VIEW_LABELS[v] || v);
				b.type = 'button';
				b.dataset.value = v;
				if(v === this.view) b.classList.add('cerb-ui-switcher--active');
				this._switcherEl.appendChild(b);
			});
			toolbar.appendChild(this._switcherEl);
			this._switcher = new CerbUI.Switcher(this._switcherEl, {
				value: this.view,
				storageKey: this.opts.viewStorageKey,
				onSelect: (v) => this.setView(v),
			});
			// storageKey may resolve a different initial view than defaultView — honor it.
			if(this._switcher.getValue() && this._switcher.getValue() !== this.view)
				this.view = this._switcher.getValue();
		}

		this.el.appendChild(toolbar);

		// Legend of sources (click-to-toggle)
		this._legendEl = _calEl('div', 'cerb-ui-calendar--legend');
		this.el.appendChild(this._legendEl);
		this._renderLegend();

		// Body (the swapped view region)
		this._body = _calEl('div', 'cerb-ui-calendar--body');
		this.el.appendChild(this._body);
	}

	_navBtn(cls, label, svg, handler) {
		const b = document.createElement('button');
		b.type = 'button';
		b.className = cls;
		b.setAttribute('aria-label', label);
		b.innerHTML = svg; // constant SVG, not user data
		b.addEventListener('click', handler);
		return b;
	}

	_renderLegend() {
		this._legendEl.textContent = '';
		if(!this.opts.showLegend || this.sources.size <= 0) {
			this._legendEl.style.display = 'none';
			return;
		}
		this._legendEl.style.display = '';
		this.sources.forEach((s, id) => {
			const item = _calEl('button', 'cerb-ui-calendar--legend-item');
			item.type = 'button';
			item.dataset.sourceId = id;
			if(!s.visible) item.classList.add('is-off');
			const swatch = _calEl('span', 'cerb-ui-calendar--legend-swatch');
			swatch.style.backgroundColor = s.descriptor.color || 'var(--cerb-color-background-contrast-150)';
			if(s.error) item.classList.add('cerb-ui-calendar--legend-item-error');
			const label = _calEl('span', 'cerb-ui-calendar--legend-label', s.descriptor.label || id);
			item.append(swatch, label);
			item.addEventListener('click', () => this.toggleSource(id));
			this._legendEl.appendChild(item);
		});
	}

	// ── Source model ──────────────────────────────────────────────────────────

	_addSourceModel(descriptor) {
		if(!descriptor || !descriptor.id) return;
		this.sources.set(descriptor.id, {
			descriptor: descriptor,
			visible: descriptor.visible !== false,
			events: [],
			loaded: false,
			error: false,
		});
	}

	// Normalize a source's raw payload (array of raw/normalized events, or a day-keyed server map) into the
	// internal event shape, resolving color/icon from the source.
	_normalizeSource(raw, source) {
		let rows = raw;
		if(source.descriptor.serverShape && raw && !Array.isArray(raw))
			rows = CerbUI.cal.dedupeServerEvents(raw); // day-keyed → flat de-duped
		if(!Array.isArray(rows)) rows = [];

		const color = source.descriptor.color || null;
		const icon = source.descriptor.icon || null;

		return rows.map((row) => {
			let start = (row.start != null) ? row.start
				: (row.ts_range_start != null ? row.ts_range_start : row.ts);
			let end = (row.end != null) ? row.end
				: (row.ts_range_end != null ? row.ts_range_end : row.ts_end);
			start = Number(start) || 0;
			end = (end != null) ? Number(end) : start;
			if(end < start) end = start;

			const contextId = (row.contextId != null) ? row.contextId
				: (row.context_id != null ? row.context_id : null);

			return {
				_id: source.descriptor.id + ':' + (contextId != null ? contextId : (row.label || '')) + ':' + start,
				sourceId: source.descriptor.id,
				label: row.label || '',
				icon: row.icon || icon,
				color: row.color || color || 'var(--cerb-color-background-contrast-150)',
				start: start,
				end: end,
				startMs: start * 1000,
				endMs: end * 1000,
				allDay: (row.allDay != null) ? !!row.allDay : CerbUI.cal.inferAllDay(start, end),
				url: row.url || (typeof row.link === 'string' && row.link.indexOf('ctx://') !== 0 ? row.link : null),
				context: row.context || null,
				contextId: contextId,
				spanDays: CerbUI.cal.spanDays(start, end, this.tz),
			};
		});
	}

	// ── Range + fetch ─────────────────────────────────────────────────────────

	// Compute the visible epoch-second range for the current view/date.
	_syncRange() {
		const cal = CerbUI.cal;
		const d = this.viewDate;
		let start, end;

		if(this.view === 'day') {
			start = cal.dayStartSec(d.getFullYear(), d.getMonth(), d.getDate(), this.tz);
			end = cal.dayStartSec(d.getFullYear(), d.getMonth(), d.getDate() + 1, this.tz);
		} else if(this.view === 'week') {
			const days = cal.weekDays(d, this.opts.startOfWeek);
			const a = days[0], b = days[6];
			start = cal.dayStartSec(a.getFullYear(), a.getMonth(), a.getDate(), this.tz);
			end = cal.dayStartSec(b.getFullYear(), b.getMonth(), b.getDate() + 1, this.tz);
		} else if(this.view === 'year') {
			start = cal.dayStartSec(d.getFullYear(), 0, 1, this.tz);
			end = cal.dayStartSec(d.getFullYear() + 1, 0, 1, this.tz);
		} else { // month — the full visible 42-cell grid
			const cells = cal.monthGridCells(d.getFullYear(), d.getMonth(), this.opts.startOfWeek);
			const first = cells[0], last = cells[41];
			start = cal.dayStartSec(first.year, first.month, first.day, this.tz);
			end = cal.dayStartSec(last.year, last.month, last.day + 1, this.tz);
		}
		this._range = { start: start, end: end };
	}

	_fetchRange() {
		const seq = ++this._fetchSeq;
		this._syncRange();
		this._dispatch('rangechange', { rangeStart: this._range.start, rangeEnd: this._range.end, view: this.view });
		if(typeof this.opts.onRangeChange === 'function')
			this.opts.onRangeChange(this._range.start, this._range.end, this.view);

		const rangeStart = this._range.start, rangeEnd = this._range.end;
		const jobs = [];

		this.sources.forEach((source) => {
			const cacheKey = source.descriptor.id + '|' + rangeStart + '|' + rangeEnd;
			if(this._cache.has(cacheKey)) {
				source.events = this._cache.get(cacheKey);
				source.loaded = true;
				return;
			}
			let p;
			if(typeof source.descriptor.fetch === 'function') {
				p = Promise.resolve()
					.then(() => source.descriptor.fetch(rangeStart, rangeEnd))
					.then((raw) => { source.error = false; return this._normalizeSource(raw, source); });
			} else {
				// Static events — normalize once (independent of range; render filters to range).
				p = Promise.resolve(this._normalizeSource(source.descriptor.events || [], source));
			}
			jobs.push(p.then((events) => {
				this._cache.set(cacheKey, events);
				source.events = events;
				source.loaded = true;
			}).catch(() => {
				source.error = true;
				source.events = [];
				source.loaded = true;
			}));
		});

		if(!jobs.length) { this.render(); return; }

		// Render once now (cached/static sources) and again after async settles.
		this.render();
		Promise.allSettled(jobs).then(() => {
			if(seq !== this._fetchSeq) return; // stale — user navigated away
			this._renderLegend(); // reflect any source error state
			this.render();
		});
	}

	// Flat normalized events from VISIBLE sources intersecting the current range.
	_visibleEvents() {
		const out = [];
		const rs = this._range.start, re = this._range.end;
		this.sources.forEach((s) => {
			if(!s.visible) return;
			s.events.forEach((ev) => {
				if(ev.end > rs && ev.start < re) out.push(ev);
			});
		});
		return out;
	}

	// ── Render dispatch ───────────────────────────────────────────────────────

	render() {
		if(!this._body) return;
		this._body.textContent = '';
		this._body.className = 'cerb-ui-calendar--body cerb-ui-calendar--body-' + this.view;
		this._updateCaption();

		if(this.view === 'day') this._renderDayLike([this.viewDate], false);
		else if(this.view === 'week') this._renderDayLike(CerbUI.cal.weekDays(this.viewDate, this.opts.startOfWeek), true);
		else if(this.view === 'year') this._renderYear();
		else this._renderMonth();
	}

	_updateCaption() {
		const cal = CerbUI.cal, d = this.viewDate;
		let text = '';
		if(this.view === 'day') {
			text = cal.DAY_ABBR[d.getDay()] + ', ' + cal.MONTH_ABBR[d.getMonth()] + ' ' + d.getDate() + ', ' + d.getFullYear();
		} else if(this.view === 'week') {
			const days = cal.weekDays(d, this.opts.startOfWeek);
			const a = days[0], b = days[6];
			const left = cal.MONTH_ABBR[a.getMonth()] + ' ' + a.getDate();
			const right = (a.getMonth() === b.getMonth())
				? String(b.getDate())
				: cal.MONTH_ABBR[b.getMonth()] + ' ' + b.getDate();
			text = left + ' – ' + right + ', ' + b.getFullYear();
		} else if(this.view === 'year') {
			text = String(d.getFullYear());
		} else {
			text = cal.MONTH_NAMES[d.getMonth()] + ' ' + d.getFullYear();
		}
		this._caption.textContent = text;
	}

	// ── Month view ────────────────────────────────────────────────────────────

	_renderMonth() {
		const cal = CerbUI.cal;
		const d = this.viewDate;
		const cells = cal.monthGridCells(d.getFullYear(), d.getMonth(), this.opts.startOfWeek);
		const events = this._visibleEvents();

		const strips = events.filter((ev) => ev.allDay || ev.spanDays > 1);
		const timed = events.filter((ev) => !ev.allDay && ev.spanDays <= 1);
		const lanes = cal.assignLanes(strips);

		const todayMid = cal.dayMidnightSec(Math.floor(Date.now() / 1000), this.tz);

		const wrap = _calEl('div', 'cerb-ui-calendar--month');

		// Weekday header
		const head = _calEl('div', 'cerb-ui-calendar--weekdays');
		const order = this.opts.startOfWeek === 'sun' ? [0, 1, 2, 3, 4, 5, 6] : [1, 2, 3, 4, 5, 6, 0];
		order.forEach((dow) => head.appendChild(_calEl('span', 'cerb-ui-calendar--weekday', cal.DAY_ABBR[dow])));
		wrap.appendChild(head);

		const weeksEl = _calEl('div', 'cerb-ui-calendar--weeks');

		for(let r = 0; r < 6; r++) {
			const weekCells = cells.slice(r * 7, r * 7 + 7);
			const colBounds = weekCells.map((c) => cal.dayBounds(c.year, c.month, c.day, this.tz));
			const weekStart = colBounds[0].startSec, weekEnd = colBounds[6].endSec;

			const stripsThisWeek = strips.filter((ev) => ev.end > weekStart && ev.start < weekEnd);
			let maxLane = -1;
			stripsThisWeek.forEach((ev) => { maxLane = Math.max(maxLane, Math.min(lanes.get(ev), this.opts.maxPerDay - 1)); });
			const bandLanes = Math.max(0, maxLane + 1);
			const bandH = bandLanes * _CAL_MONTH_SLOT_H;

			const weekEl = _calEl('div', 'cerb-ui-calendar--week');
			const cellsRow = _calEl('div', 'cerb-ui-calendar--daycells');

			// Per-day: header + reserved strip band + timed list + overflow
			weekCells.forEach((c, ci) => {
				const cellEl = _calEl('div', 'cerb-ui-calendar--daycell');
				if(!c.inMonth) cellEl.classList.add('cerb-ui-calendar--day-other');
				if(colBounds[ci].startSec === todayMid) cellEl.classList.add('cerb-ui-calendar--day-today');

				const num = _calEl('button', 'cerb-ui-calendar--daynum', String(c.day));
				num.type = 'button';
				num.style.height = _CAL_MONTH_DAYNUM_H + 'px';
				num.addEventListener('click', (e) => { e.stopPropagation(); this._drillTo('day', new Date(c.year, c.month, c.day)); });
				cellEl.appendChild(num);

				// Reserved band spacer so the absolutely-positioned strips overlay sits above the timed list.
				const band = _calEl('div', 'cerb-ui-calendar--strip-band');
				band.style.height = bandH + 'px';
				cellEl.appendChild(band);

				// Which lanes are consumed by strips over this day → free slots for timed events.
				const cellStart = colBounds[ci].startSec, cellEnd = colBounds[ci].endSec;
				const usedLanes = new Set();
				let hiddenStrips = 0;
				stripsThisWeek.forEach((ev) => {
					if(ev.end > cellStart && ev.start < cellEnd) {
						const lane = lanes.get(ev);
						if(lane < this.opts.maxPerDay) usedLanes.add(lane); else hiddenStrips++;
					}
				});

				const dayTimed = timed
					.filter((ev) => ev.end > cellStart && ev.start < cellEnd)
					.sort((a, b) => a.start - b.start);

				const freeSlots = Math.max(0, this.opts.maxPerDay - usedLanes.size);
				const showTimed = dayTimed.slice(0, freeSlots);
				const hiddenTimed = dayTimed.length - showTimed.length;

				const timedWrap = _calEl('div', 'cerb-ui-calendar--day-timed-list');
				showTimed.forEach((ev) => timedWrap.appendChild(this._monthTimedRow(ev)));
				cellEl.appendChild(timedWrap);

				const totalHidden = hiddenStrips + hiddenTimed;
				if(totalHidden > 0) {
					const more = _calEl('button', 'cerb-ui-calendar--more', '+' + totalHidden + ' more');
					more.type = 'button';
					more.addEventListener('click', (e) => { e.stopPropagation(); this._drillTo('day', new Date(c.year, c.month, c.day)); });
					cellEl.appendChild(more);
				}

				// Empty-cell click → create an all-day event on this day.
				cellEl.addEventListener('click', (e) => {
					if(e.target.closest('.cerb-ui-calendar--daynum, .cerb-ui-calendar--strip, .cerb-ui-calendar--day-timed, .cerb-ui-calendar--more')) return;
					this._cellClick(cellStart, colBounds[ci].endSec, true);
				});

				cellsRow.appendChild(cellEl);
			});

			weekEl.appendChild(cellsRow);

			// Strips overlay (absolute over the daycells, offset below the day-number header).
			const overlay = _calEl('div', 'cerb-ui-calendar--strips');
			overlay.style.top = (_CAL_MONTH_DAYNUM_H + _CAL_MONTH_CELL_PAD) + 'px';
			overlay.style.height = bandH + 'px';
			stripsThisWeek.forEach((ev) => {
				const lane = lanes.get(ev);
				if(lane >= this.opts.maxPerDay) return; // hidden → counted in "+N more"

				// First/last covered column within this week row.
				let colStart = -1, colEnd = -1;
				for(let ci = 0; ci < 7; ci++) {
					if(ev.end > colBounds[ci].startSec && ev.start < colBounds[ci].endSec) {
						if(colStart === -1) colStart = ci;
						colEnd = ci;
					}
				}
				if(colStart === -1) return;

				const startsHere = ev.start >= weekStart;
				const endsHere = ev.end <= weekEnd;

				const strip = _calEl('div', 'cerb-ui-calendar--strip');
				if(ev.allDay) strip.classList.add('cerb-ui-calendar--strip-allday');
				if(!startsHere) strip.classList.add('cerb-ui-calendar--continues-left');
				if(!endsHere) strip.classList.add('cerb-ui-calendar--continues-right');
				strip.style.left = (colStart / 7 * 100) + '%';
				strip.style.width = ((colEnd - colStart + 1) / 7 * 100) + '%';
				strip.style.top = (lane * _CAL_MONTH_SLOT_H) + 'px';
				strip.style.backgroundColor = ev.color;
				const stripTextColor = CerbUI.color.idealTextColor(ev.color);
				if(stripTextColor) strip.style.color = stripTextColor;
				strip.title = ev.label;

				// Show the label only in the row where the event truly starts.
				if(startsHere) {
					if(ev.icon) {
						const ic = _calEl('span', 'cerb-icons cerb-icon-' + ev.icon + ' cerb-ui-calendar--strip-icon');
						strip.appendChild(ic);
					}
					strip.appendChild(_calEl('span', 'cerb-ui-calendar--strip-label', ev.label));
				}
				strip.addEventListener('click', (e) => { e.stopPropagation(); this._eventClick(ev, e); });
				overlay.appendChild(strip);
			});
			weekEl.appendChild(overlay);

			weeksEl.appendChild(weekEl);
		}

		wrap.appendChild(weeksEl);
		this._body.appendChild(wrap);
	}

	// A single-day timed event as a compact month row: color swatch left, start time right.
	_monthTimedRow(ev) {
		const row = _calEl('button', 'cerb-ui-calendar--day-timed');
		row.type = 'button';
		row.title = ev.label + (ev.allDay ? '' : ' · ' + this._fmtTime(ev.start));
		const swatch = _calEl('span', 'cerb-ui-calendar--swatch');
		swatch.style.backgroundColor = ev.color;
		const label = _calEl('span', 'cerb-ui-calendar--day-timed-label', ev.label);
		const time = _calEl('span', 'cerb-ui-calendar--day-timed-time', this._fmtTime(ev.start));
		row.append(swatch, label, time);
		row.addEventListener('click', (e) => { e.stopPropagation(); this._eventClick(ev, e); });
		return row;
	}

	// ── Day / Week views (shared vertical 24h grid) ───────────────────────────

	_renderDayLike(dayDates, isWeek) {
		const cal = CerbUI.cal;
		const startHour = this.opts.dayStartHour, endHour = this.opts.dayEndHour;
		const hours = endHour - startHour;
		const HOUR_H = this.opts.hourHeight;
		const canvasH = hours * HOUR_H;
		const events = this._visibleEvents();
		const nowSec = Math.floor(Date.now() / 1000);
		const todayMid = cal.dayMidnightSec(nowSec, this.tz);

		const wrap = _calEl('div', isWeek ? 'cerb-ui-calendar--week-view' : 'cerb-ui-calendar--day-view');

		const dayBounds = dayDates.map((dd) => cal.dayBounds(dd.getFullYear(), dd.getMonth(), dd.getDate(), this.tz));

		// All-day / multi-day band (continuous strips across day columns).
		const allDayEvents = events.filter((ev) => ev.allDay || ev.spanDays > 1);
		if(allDayEvents.length) {
			const bandLanes = cal.assignLanes(allDayEvents);
			const rangeStart = dayBounds[0].startSec, rangeEnd = dayBounds[dayBounds.length - 1].endSec;
			let maxLane = -1;
			allDayEvents.forEach((ev) => { if(ev.end > rangeStart && ev.start < rangeEnd) maxLane = Math.max(maxLane, bandLanes.get(ev)); });
			const bandH = (maxLane + 1) * _CAL_MONTH_SLOT_H;

			const band = _calEl('div', 'cerb-ui-calendar--allday-band');
			const bandGutter = _calEl('div', 'cerb-ui-calendar--gutter-spacer', 'all-day');
			band.appendChild(bandGutter);
			const bandCols = _calEl('div', 'cerb-ui-calendar--allday-cols');
			bandCols.style.height = Math.max(_CAL_MONTH_SLOT_H, bandH) + 'px';
			bandCols.style.gridTemplateColumns = 'repeat(' + dayDates.length + ', 1fr)';
			// Overlay strips across the columns.
			const overlay = _calEl('div', 'cerb-ui-calendar--allday-strips');
			allDayEvents.forEach((ev) => {
				let colStart = -1, colEnd = -1;
				for(let ci = 0; ci < dayBounds.length; ci++) {
					if(ev.end > dayBounds[ci].startSec && ev.start < dayBounds[ci].endSec) {
						if(colStart === -1) colStart = ci;
						colEnd = ci;
					}
				}
				if(colStart === -1) return;
				const lane = bandLanes.get(ev);
				const strip = _calEl('div', 'cerb-ui-calendar--strip cerb-ui-calendar--strip-allday');
				if(ev.start < dayBounds[colStart].startSec) strip.classList.add('cerb-ui-calendar--continues-left');
				if(ev.end > dayBounds[colEnd].endSec) strip.classList.add('cerb-ui-calendar--continues-right');
				strip.style.left = (colStart / dayDates.length * 100) + '%';
				strip.style.width = ((colEnd - colStart + 1) / dayDates.length * 100) + '%';
				strip.style.top = (lane * _CAL_MONTH_SLOT_H) + 'px';
				strip.style.backgroundColor = ev.color;
				const bandTextColor = CerbUI.color.idealTextColor(ev.color);
				if(bandTextColor) strip.style.color = bandTextColor;
				strip.title = ev.label;
				if(ev.icon) strip.appendChild(_calEl('span', 'cerb-icons cerb-icon-' + ev.icon + ' cerb-ui-calendar--strip-icon'));
				strip.appendChild(_calEl('span', 'cerb-ui-calendar--strip-label', ev.label));
				strip.addEventListener('click', (e) => { e.stopPropagation(); this._eventClick(ev, e); });
				overlay.appendChild(strip);
			});
			bandCols.appendChild(overlay);
			band.appendChild(bandCols);
			wrap.appendChild(band);
		}

		// Day column headers (week only)
		if(isWeek) {
			const header = _calEl('div', 'cerb-ui-calendar--week-header');
			header.appendChild(_calEl('div', 'cerb-ui-calendar--gutter-spacer'));
			const cols = _calEl('div', 'cerb-ui-calendar--week-header-cols');
			dayDates.forEach((dd, ci) => {
				const h = _calEl('button', 'cerb-ui-calendar--daycol-header');
				h.type = 'button';
				if(dayBounds[ci].startSec === todayMid) h.classList.add('cerb-ui-calendar--day-today');
				h.appendChild(_calEl('span', 'cerb-ui-calendar--daycol-dow', cal.DAY_ABBR[dd.getDay()]));
				h.appendChild(_calEl('span', 'cerb-ui-calendar--daycol-num', String(dd.getDate())));
				h.addEventListener('click', () => this._drillTo('day', new Date(dd.getFullYear(), dd.getMonth(), dd.getDate())));
				cols.appendChild(h);
			});
			header.appendChild(cols);
			wrap.appendChild(header);
		}

		// Scrollable grid: hour gutter + day canvases
		const grid = _calEl('div', 'cerb-ui-calendar--grid');

		const gutter = _calEl('div', 'cerb-ui-calendar--gutter');
		for(let h = startHour; h < endHour; h++) {
			const hr = _calEl('div', 'cerb-ui-calendar--hour');
			hr.style.height = HOUR_H + 'px';
			hr.appendChild(_calEl('span', 'cerb-ui-calendar--hour-label', this._fmtHour(h)));
			gutter.appendChild(hr);
		}
		grid.appendChild(gutter);

		const cols = _calEl('div', 'cerb-ui-calendar--day-cols');
		cols.style.gridTemplateColumns = 'repeat(' + dayDates.length + ', 1fr)';

		dayDates.forEach((dd, ci) => {
			const canvas = _calEl('div', 'cerb-ui-calendar--canvas');
			canvas.style.height = canvasH + 'px';

			// Hour gridlines
			for(let h = 0; h < hours; h++) {
				const line = _calEl('div', 'cerb-ui-calendar--hourline');
				line.style.top = (h * HOUR_H) + 'px';
				canvas.appendChild(line);
			}

			const dayStart = dayBounds[ci].startSec, dayEnd = dayBounds[ci].endSec;
			const winStart = dayStart + startHour * 3600;
			const winEnd = dayStart + endHour * 3600;

			// Timed events for this day, clamped to the visible window.
			const dayTimed = events.filter((ev) => !ev.allDay && ev.spanDays <= 1 && ev.end > winStart && ev.start < winEnd);
			const intervals = dayTimed.map((ev) => ({ start: Math.max(ev.start, winStart), end: Math.min(ev.end, winEnd) }));
			const packing = cal.packColumns(intervals);

			dayTimed.forEach((ev, i) => {
				const s = Math.max(ev.start, winStart), e = Math.min(ev.end, winEnd);
				const top = (s - winStart) / 3600 * HOUR_H;
				const height = Math.max(_CAL_MIN_EVENT_H, (e - s) / 3600 * HOUR_H);
				const pk = packing[i];
				const box = _calEl('div', 'cerb-ui-calendar--event');
				box.style.top = top + 'px';
				box.style.height = height + 'px';
				box.style.left = (pk.col / pk.cols * 100) + '%';
				box.style.width = 'calc(' + (100 / pk.cols) + '% - 3px)';
				box.style.setProperty('--cerb-cal-event-color', ev.color);
				box.title = ev.label;
				box.appendChild(_calEl('span', 'cerb-ui-calendar--event-time', this._fmtTime(ev.start)));
				box.appendChild(_calEl('span', 'cerb-ui-calendar--event-label', ev.label));
				box.addEventListener('click', (evt) => { evt.stopPropagation(); this._eventClick(ev, evt); });
				canvas.appendChild(box);
			});

			// Now-line (only on today's column)
			if(dayStart === todayMid && nowSec >= winStart && nowSec <= winEnd) {
				const now = _calEl('div', 'cerb-ui-calendar--nowline');
				now.style.top = ((nowSec - winStart) / 3600 * HOUR_H) + 'px';
				canvas.appendChild(now);
			}

			// Click empty space → create a timed event snapped to the nearest 30 min.
			canvas.addEventListener('click', (e) => {
				if(e.target.closest('.cerb-ui-calendar--event')) return;
				const rect = canvas.getBoundingClientRect();
				const y = e.clientY - rect.top;
				const mins = Math.round((y / HOUR_H * 60) / 30) * 30;
				const startSec = winStart + Math.max(0, Math.min(mins, hours * 60 - 30)) * 60;
				this._cellClick(startSec, startSec + 3600, false);
			});

			cols.appendChild(canvas);
		});

		grid.appendChild(cols);
		wrap.appendChild(grid);
		this._body.appendChild(wrap);
	}

	// ── Year view ─────────────────────────────────────────────────────────────

	_renderYear() {
		const cal = CerbUI.cal;
		const year = this.viewDate.getFullYear();
		const events = this._visibleEvents();
		const todayMid = cal.dayMidnightSec(Math.floor(Date.now() / 1000), this.tz);

		// One pass: bucket each day-midnight → ordered list of {sourceId,color,label,start,allDay} for tooltips,
		// and a per-source presence set for pips.
		const byDay = new Map();
		events.forEach((ev) => {
			// Walk each day the event covers within the year.
			let dayMid = cal.dayMidnightSec(ev.start, this.tz);
			const lastMid = cal.dayMidnightSec(Math.max(ev.start, (ev.end > ev.start ? ev.end - 1 : ev.end)), this.tz);
			let guard = 0;
			while(dayMid <= lastMid && guard++ < 400) {
				if(!byDay.has(dayMid)) byDay.set(dayMid, []);
				byDay.get(dayMid).push(ev);
				dayMid += cal.DAY_SECS; // approximate; re-normalized below via dayMidnightSec
				dayMid = cal.dayMidnightSec(dayMid, this.tz);
			}
		});

		const grid = _calEl('div', 'cerb-ui-calendar--year');
		const order = this.opts.startOfWeek === 'sun' ? [0, 1, 2, 3, 4, 5, 6] : [1, 2, 3, 4, 5, 6, 0];

		for(let m = 0; m < 12; m++) {
			const mini = _calEl('div', 'cerb-ui-calendar--minimonth');

			const cap = _calEl('button', 'cerb-ui-calendar--minicaption', cal.MONTH_NAMES[m]);
			cap.type = 'button';
			cap.addEventListener('click', () => this._drillTo('month', new Date(year, m, 1)));
			mini.appendChild(cap);

			const wk = _calEl('div', 'cerb-ui-calendar--miniweekdays');
			order.forEach((dow) => wk.appendChild(_calEl('span', 'cerb-ui-calendar--miniweekday', cal.WEEKDAY_ABBR[dow])));
			mini.appendChild(wk);

			const mgrid = _calEl('div', 'cerb-ui-calendar--minigrid');
			const cells = cal.monthGridCells(year, m, this.opts.startOfWeek);
			cells.forEach((c) => {
				const cell = _calEl('div', 'cerb-ui-calendar--minicell');
				if(!c.inMonth) { cell.classList.add('cerb-ui-calendar--day-other'); }
				const dayMid = cal.dayStartSec(c.year, c.month, c.day, this.tz);
				// Only mark today in the month it belongs to — not in an adjacent month's padding days.
				if(c.inMonth && dayMid === todayMid) cell.classList.add('cerb-ui-calendar--day-today');
				cell.appendChild(_calEl('span', 'cerb-ui-calendar--mininum', String(c.day)));

				const dayEvents = c.inMonth ? (byDay.get(dayMid) || []) : [];
				if(dayEvents.length) {
					cell.classList.add('cerb-ui-calendar--minicell-marked');
					const pips = _calEl('div', 'cerb-ui-calendar--minipips');
					const seenSource = [];
					dayEvents.forEach((ev) => { if(seenSource.indexOf(ev.sourceId) === -1) seenSource.push(ev.sourceId); });
					seenSource.slice(0, 3).forEach((sid) => {
						const src = this.sources.get(sid);
						const pip = _calEl('span', 'cerb-ui-pip');
						pip.style.color = (src && src.descriptor.color) || 'var(--cerb-color-action-primary)';
						pips.appendChild(pip);
					});
					if(seenSource.length > 3) pips.appendChild(_calEl('span', 'cerb-ui-calendar--minipip-more', '+'));
					cell.appendChild(pips);

					this._wireYearCell(cell, new Date(c.year, c.month, c.day), dayEvents);
				}
				mgrid.appendChild(cell);
			});
			mini.appendChild(mgrid);
			grid.appendChild(mini);
		}

		this._body.appendChild(grid);
	}

	_wireYearCell(cell, dateObj, dayEvents) {
		const showTip = (interactive) => {
			const tip = this._getTooltip();
			tip.anchor(this._dayTooltipNode(dateObj, dayEvents), cell, {
				my: 'center bottom', at: 'center top', interactive: interactive,
			});
		};
		cell.addEventListener('mouseenter', () => showTip(false));
		cell.addEventListener('mouseleave', () => { if(this._tooltip && !this._tooltip.el.classList.contains('cerb-ui-tooltip--interactive')) this._tooltip.hide(); });
		cell.addEventListener('click', () => this._drillTo('day', dateObj));
	}

	_dayTooltipNode(dateObj, dayEvents) {
		const cal = CerbUI.cal;
		const node = _calEl('div', 'cerb-ui-calendar--tip');
		node.appendChild(_calEl('div', 'cerb-ui-calendar--tip-date',
			cal.DAY_ABBR[dateObj.getDay()] + ', ' + cal.MONTH_ABBR[dateObj.getMonth()] + ' ' + dateObj.getDate()));
		const list = _calEl('div', 'cerb-ui-calendar--tip-list');
		dayEvents.slice(0, 8).forEach((ev) => {
			const row = _calEl('div', 'cerb-ui-calendar--tip-row');
			const sw = _calEl('span', 'cerb-ui-calendar--swatch');
			sw.style.backgroundColor = ev.color;
			row.appendChild(sw);
			row.appendChild(_calEl('span', 'cerb-ui-calendar--tip-label', ev.label));
			if(!ev.allDay) row.appendChild(_calEl('span', 'cerb-ui-calendar--tip-time', this._fmtTime(ev.start)));
			list.appendChild(row);
		});
		if(dayEvents.length > 8) list.appendChild(_calEl('div', 'cerb-ui-calendar--tip-more', '+' + (dayEvents.length - 8) + ' more'));
		node.appendChild(list);
		return node;
	}

	_getTooltip() {
		if(!this._tooltip && window.CerbUI && CerbUI.Tooltip) this._tooltip = new CerbUI.Tooltip();
		return this._tooltip;
	}

	// ── Time formatting ───────────────────────────────────────────────────────

	_fmtHour(h) {
		if(this.opts.timeFormat === '24h') return (h < 10 ? '0' + h : String(h)) + ':00';
		if(h === 0) return '12 AM';
		if(h === 12) return '12 PM';
		return (h < 12 ? h + ' AM' : (h - 12) + ' PM');
	}

	_fmtTime(sec) {
		const p = CerbUI.cal.epochParts(sec, this.tz);
		if(this.opts.timeFormat === '24h')
			return (p.hour < 10 ? '0' + p.hour : p.hour) + ':' + (p.min < 10 ? '0' + p.min : p.min);
		const suffix = p.hour < 12 ? 'a' : 'p';
		let h12 = p.hour % 12; if(h12 === 0) h12 = 12;
		return (p.min === 0) ? (h12 + suffix) : (h12 + ':' + (p.min < 10 ? '0' + p.min : p.min) + suffix);
	}

	// ── Interaction: event click, cell click / create, drill-down ─────────────

	_eventClick(ev, domEvt) {
		this._dispatch('eventclick', { event: ev });
		if(typeof this.opts.onEventClick === 'function' && this.opts.onEventClick(ev, domEvt) === false) return;

		// Default: open the record peek (view), else navigate the url.
		if(ev.context && ev.contextId != null && typeof genericAjaxPopup === 'function') {
			const url = 'c=internal&a=invoke&module=records&action=showPeekPopup'
				+ '&context=' + encodeURIComponent(ev.context) + '&context_id=' + encodeURIComponent(ev.contextId);
			const $popup = genericAjaxPopup('peek' + this.uid, url, null, false, '50%');
			this._bindPeekRefresh($popup);
		} else if(ev.url) {
			window.location.href = ev.url;
		}
	}

	_cellClick(startSec, endSec, allDay) {
		this._dispatch('cellclick', { start: startSec, end: endSec, allDay: allDay, view: this.view });
		if(typeof this.opts.onCellClick === 'function' && this.opts.onCellClick({ start: startSec, end: endSec, allDay: allDay, view: this.view }) === false)
			return;
		if(typeof this.opts.onCreate === 'function') { this.opts.onCreate({ start: startSec, end: endSec, allDay: allDay }); return; }
		this._defaultCreate(startSec, endSec, allDay);
	}

	_defaultCreate(startSec, endSec, allDay) {
		if(this.opts.calendarId == null || typeof genericAjaxPopup !== 'function') return;
		const edit = 'calendar.id:' + this.opts.calendarId + ' start:' + startSec + ' end:' + endSec;
		const url = 'c=internal&a=invoke&module=records&action=showPeekPopup'
			+ '&context=cerberusweb.contexts.calendar_event&context_id=0&edit=' + encodeURIComponent(edit);
		const $popup = genericAjaxPopup('peek' + this.uid, url, null, false, '50%');
		this._bindPeekRefresh($popup, () => this._dispatch('create', { start: startSec, end: endSec, allDay: allDay }));
	}

	// A raw-opened peek bubbles `peek_saved`/`peek_deleted` on the popup (not the cerb-peek-* rebroadcast).
	_bindPeekRefresh($popup, after) {
		if($popup && typeof $popup.on === 'function') {
			$popup.on('peek_saved peek_deleted', () => { this.refresh(); if(after) after(); });
		}
	}

	_drillTo(toView, date) {
		if(!this.opts.views.includes(toView)) return;
		if(typeof this.opts.onDrillDown === 'function' && this.opts.onDrillDown(this.view, toView, date) === false) return;
		this.viewDate = date;
		this.setView(toView);
	}

	// ── Navigation ────────────────────────────────────────────────────────────

	prev() { this._step(-1); }
	next() { this._step(1); }

	_step(dir) {
		const d = new Date(this.viewDate);
		if(this.view === 'day') d.setDate(d.getDate() + dir);
		else if(this.view === 'week') d.setDate(d.getDate() + 7 * dir);
		else if(this.view === 'year') d.setFullYear(d.getFullYear() + dir);
		else d.setMonth(d.getMonth() + dir);
		this.setDate(d);
	}

	today() { this.setDate(new Date()); }

	// ── Public API ────────────────────────────────────────────────────────────

	setView(view) {
		if(!this.opts.views.includes(view) || view === this.view) return;
		const prev = this.view;
		this.view = view;
		if(this._switcher) this._switcher.setValue(view);
		this._dispatch('viewchange', { view: view, prevView: prev });
		this._fetchRange();
	}

	getView() { return this.view; }

	setDate(date) {
		const d = this._toDate(date);
		if(!d) return;
		this.viewDate = d;
		this._dispatch('datechange', { date: d, rangeStart: this._range.start, rangeEnd: this._range.end });
		this._fetchRange();
	}

	getDate() { return this.viewDate; }

	addSource(descriptor) {
		this._addSourceModel(descriptor);
		this._renderLegend();
		this._fetchRange();
	}

	removeSource(id) {
		this.sources.delete(id);
		// Drop its cache entries.
		Array.from(this._cache.keys()).forEach((k) => { if(k.indexOf(id + '|') === 0) this._cache.delete(k); });
		this._renderLegend();
		this.render();
	}

	toggleSource(id, visible) {
		const s = this.sources.get(id);
		if(!s) return;
		s.visible = (visible == null) ? !s.visible : !!visible;
		const item = this._legendEl.querySelector('[data-source-id="' + id + '"]');
		if(item) item.classList.toggle('is-off', !s.visible);
		this._dispatch('sourcetoggle', { sourceId: id, visible: s.visible });
		this.render();
	}

	refresh() {
		this._cache.clear();
		this.sources.forEach((s) => { s.loaded = false; });
		this._fetchRange();
	}

	getEvents() { return this._visibleEvents(); }

	// ── Helpers ───────────────────────────────────────────────────────────────

	_toDate(v) {
		if(v == null) return null;
		if(v instanceof Date) return isNaN(v.getTime()) ? null : v;
		if(typeof v === 'number') return new Date(v * 1000); // epoch seconds
		const d = new Date(v);
		return isNaN(d.getTime()) ? null : d;
	}

	_dispatch(name, detail) {
		this.el.dispatchEvent(new CustomEvent('cerb-ui-calendar:' + name, { detail: detail, bubbles: true }));
	}

	destroy() {
		CerbUI.Calendar._instances.delete(this.el);
		if(this._switcher) this._switcher.destroy();
		if(this._tooltip) this._tooltip.destroy();
		this.el.textContent = '';
		this.el.classList.remove('cerb-ui-calendar');
	}
};
