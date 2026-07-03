/*
 * CerbUI.cal — pure calendar logic shared by CerbUI.Calendar (no DOM, no jQuery).
 *
 * Mirrors the chart-core.js pattern: everything here is a pure function so it can be unit-tested headless
 * (Node) with no browser. The view renderers in calendar.js consume these helpers for grid geometry, event
 * normalization, spanning-strip lane assignment, and day-column overlap packing.
 *
 * Timezone: server event epochs represent wall-clock time in the worker's timezone. Every epoch→parts
 * conversion goes through epochParts(sec, tzOffsetMinutes) so there is ONE place that owns the interpretation
 * (never mix a raw Date.getHours() with a server epoch). tzOffsetMinutes is "minutes east of UTC" (like
 * -new Date().getTimezoneOffset()); pass null to use the browser's local timezone (the default).
 */
CerbUI.cal = (function() {
	'use strict';

	const MONTH_NAMES = [
		'January', 'February', 'March', 'April', 'May', 'June',
		'July', 'August', 'September', 'October', 'November', 'December',
	];
	const MONTH_ABBR = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
	const DAY_ABBR = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
	const WEEKDAY_ABBR = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'];

	const DAY_SECS = 86400;
	// Treat an event covering ~a full day (server clamps all-day to 23:59:59 = 86399s) as all-day. A little
	// tolerance below 86399 absorbs DST days that are an hour short/long.
	const ALLDAY_MIN_SECS = 86340;

	// ── Week helpers ─────────────────────────────────────────────────────────

	// Column offset of a day-of-week (0=Sun..6=Sat) within a week that starts on 'mon' or 'sun'.
	function startOfWeekOffset(dow, mode) {
		return (mode === 'sun') ? dow : (dow + 6) % 7;
	}

	// Midnight Date of the week-start containing `date` (browser-local; used for day/week view geometry).
	function startOfWeekDate(date, mode) {
		const d = new Date(date.getFullYear(), date.getMonth(), date.getDate());
		d.setDate(d.getDate() - startOfWeekOffset(d.getDay(), mode));
		return d;
	}

	// The 7 midnight Dates of the week containing `date`.
	function weekDays(date, mode) {
		const start = startOfWeekDate(date, mode);
		const out = [];
		for(let i = 0; i < 7; i++)
			out.push(new Date(start.getFullYear(), start.getMonth(), start.getDate() + i));
		return out;
	}

	// ── Month grid (42 cells, 6 rows × 7 cols) ────────────────────────────────

	// Build the visible month grid, padding with prev/next-month days so strips that bleed from adjacent
	// months render continuously. Each cell is calendar-coordinate only (no epoch — day bounds are tz-aware
	// and computed separately via dayBounds); mirrors CerbUI.DatePicker.renderGrid's 42-cell loop.
	function monthGridCells(year, month0, mode) {
		const firstDow = new Date(year, month0, 1).getDay();
		const daysInMonth = new Date(year, month0 + 1, 0).getDate();
		const daysInPrev = new Date(year, month0, 0).getDate();
		const offset = startOfWeekOffset(firstDow, mode);

		const cells = [];
		for(let i = 0; i < 42; i++) {
			let cy, cm, cd, inMonth;
			if(i < offset) {
				cm = month0 === 0 ? 11 : month0 - 1;
				cy = month0 === 0 ? year - 1 : year;
				cd = daysInPrev - offset + i + 1;
				inMonth = false;
			} else if(i < offset + daysInMonth) {
				cy = year; cm = month0; cd = i - offset + 1; inMonth = true;
			} else {
				cm = month0 === 11 ? 0 : month0 + 1;
				cy = month0 === 11 ? year + 1 : year;
				cd = i - offset - daysInMonth + 1;
				inMonth = false;
			}
			cells.push({
				year: cy, month: cm, day: cd, inMonth: inMonth,
				dow: new Date(cy, cm, cd).getDay(),
				col: i % 7, rowIdx: Math.floor(i / 7), index: i,
			});
		}
		return cells;
	}

	// ── Timezone-aware epoch <-> parts ────────────────────────────────────────

	// The `tz` argument accepted by every function below is one of:
	//   null / ''            → the browser's local timezone (DST-aware via native Date)
	//   an IANA name string  → e.g. 'America/Los_Angeles' (DST-aware via Intl)
	//   a number             → a FIXED offset in minutes east of UTC (legacy; NOT DST-aware — avoid, it
	//                          mis-buckets events across a DST boundary)
	// A fixed offset can't be right year-round (it's off by an hour in the other season), which rolls an
	// all-day event's 23:59:59 end into the next day. Prefer the IANA name (or null) so DST is handled.

	const _DOW_INDEX = { Sun: 0, Mon: 1, Tue: 2, Wed: 3, Thu: 4, Fri: 5, Sat: 6 };
	const _dtfCache = new Map();

	// A cached Intl.DateTimeFormat that emits an epoch's wall-clock parts in a given IANA zone.
	function _zoneFormatter(zone) {
		let f = _dtfCache.get(zone);
		if(!f) {
			f = new Intl.DateTimeFormat('en-US', {
				timeZone: zone, hourCycle: 'h23',
				year: 'numeric', month: '2-digit', day: '2-digit',
				hour: '2-digit', minute: '2-digit', second: '2-digit', weekday: 'short',
			});
			_dtfCache.set(zone, f);
		}
		return f;
	}

	function _zoneParts(sec, zone) {
		const p = {};
		_zoneFormatter(zone).formatToParts(new Date(sec * 1000)).forEach((x) => {
			if(x.type !== 'literal') p[x.type] = x.value;
		});
		let hour = parseInt(p.hour, 10);
		if(hour === 24) hour = 0; // some engines emit '24' at midnight under h23
		return { y: +p.year, m: +p.month - 1, d: +p.day, hour: hour, min: +p.minute, sec: +p.second, dow: _DOW_INDEX[p.weekday] };
	}

	// UTC-offset (seconds) of a zone at a given instant (wall-clock-as-UTC minus the actual instant).
	function _zoneOffsetSec(sec, zone) {
		const p = _zoneParts(sec, zone);
		const asUTC = Math.floor(Date.UTC(p.y, p.m, p.d, p.hour, p.min, p.sec) / 1000);
		return asUTC - sec;
	}

	// Break an epoch (seconds) into wall-clock parts in `tz` (see the tz note above).
	function epochParts(sec, tz) {
		if(tz == null || tz === '') {
			const d = new Date(sec * 1000);
			return { y: d.getFullYear(), m: d.getMonth(), d: d.getDate(), hour: d.getHours(), min: d.getMinutes(), dow: d.getDay() };
		}
		if(typeof tz === 'number') {
			const d = new Date((sec + tz * 60) * 1000);
			return { y: d.getUTCFullYear(), m: d.getUTCMonth(), d: d.getUTCDate(), hour: d.getUTCHours(), min: d.getUTCMinutes(), dow: d.getUTCDay() };
		}
		return _zoneParts(sec, tz); // IANA name → DST-aware
	}

	// Epoch (seconds) of local midnight for a calendar day, honoring `tz` (DST-aware for an IANA name).
	function dayStartSec(year, month0, day, tz) {
		if(tz == null || tz === '')
			return Math.floor(new Date(year, month0, day, 0, 0, 0, 0).getTime() / 1000);
		if(typeof tz === 'number')
			return Math.floor(Date.UTC(year, month0, day) / 1000) - tz * 60;

		// IANA: the instant whose wall-clock in `tz` is Y-M-D 00:00. Guess from UTC, correct by the zone's
		// offset at the guess, then re-check once (handles the offset changing across the guess on a DST edge).
		const utcGuess = Math.floor(Date.UTC(year, month0, day) / 1000);
		const off1 = _zoneOffsetSec(utcGuess, tz);
		let ts = utcGuess - off1;
		const off2 = _zoneOffsetSec(ts, tz);
		if(off2 !== off1) ts = utcGuess - off2;
		return ts;
	}

	// {startSec, endSec} bounds of a calendar day (endSec = next midnight, exclusive).
	function dayBounds(year, month0, day, tz) {
		const startSec = dayStartSec(year, month0, day, tz);
		const endSec = dayStartSec(year, month0, day + 1, tz);
		return { startSec: startSec, endSec: endSec };
	}

	// The local-midnight epoch of the day an event-instant falls on.
	function dayMidnightSec(sec, tz) {
		const p = epochParts(sec, tz);
		return dayStartSec(p.y, p.m, p.d, tz);
	}

	// Minutes since local midnight (0..1439) for an instant.
	function minutesOfDay(sec, tz) {
		const p = epochParts(sec, tz);
		return p.hour * 60 + p.min;
	}

	// ── Event classification ──────────────────────────────────────────────────

	function inferAllDay(startSec, endSec) {
		if(startSec === endSec) return true;
		return (endSec - startSec) >= ALLDAY_MIN_SECS;
	}

	// Number of distinct calendar days an event touches (>=1). Multi-day events (>1) are month-strip eligible.
	function spanDays(startSec, endSec, tzOffsetMinutes) {
		const a = dayMidnightSec(startSec, tzOffsetMinutes);
		// A span ending exactly at a day boundary shouldn't count the trailing day.
		const endAdj = (endSec > startSec) ? endSec - 1 : endSec;
		const b = dayMidnightSec(Math.max(startSec, endAdj), tzOffsetMinutes);
		return Math.max(1, Math.round((b - a) / DAY_SECS) + 1);
	}

	// ── Normalize the day-keyed server payload into flat de-duped rows ────────

	// Model_Calendar::getEvents() returns events keyed by day-midnight, splitting a multi-day event into one
	// clamped copy per day (but every copy carries the un-clamped ts_range_start/ts_range_end). Reconstruct
	// one flat event per source occurrence. The dedupe key MUST include the true start, because recurring
	// occurrences of the same profile share context_id and would otherwise collapse into one.
	function dedupeServerEvents(dayKeyedMap) {
		const seen = new Map();
		if(!dayKeyedMap || typeof dayKeyedMap !== 'object') return [];

		Object.keys(dayKeyedMap).forEach((dayTs) => {
			const rows = dayKeyedMap[dayTs];
			if(!Array.isArray(rows)) return;
			rows.forEach((row) => {
				const start = (row.ts_range_start != null) ? row.ts_range_start : row.ts;
				let end = (row.ts_range_end != null) ? row.ts_range_end : row.ts_end;
				if(end == null) end = start;
				const key = (row.context_id != null ? row.context_id : (row.context || '')) + ':' + start;
				if(seen.has(key)) return;
				seen.set(key, Object.assign({}, row, { start: start, end: end }));
			});
		});

		return Array.from(seen.values());
	}

	// ── Overlap packing (day/week vertical columns) ───────────────────────────

	// Greedy column packing for a set of {start,end} intervals (seconds). Each interval is assigned the
	// lowest free column; a "cluster" of transitively-overlapping intervals shares a column count so their
	// rendered widths match. Returns an array aligned to the input: [{col, cols}] (cols = columns in the
	// interval's cluster). Also used, per week-row, by the month single-day timed stack.
	function packColumns(items) {
		const n = items.length;
		const res = new Array(n);
		for(let i = 0; i < n; i++) res[i] = { col: 0, cols: 1 };
		if(!n) return res;

		const order = items.map((it, i) => i).sort((a, b) =>
			(items[a].start - items[b].start) || (items[a].end - items[b].end));

		let cluster = [];
		let colEnds = [];
		let clusterMaxEnd = -Infinity;

		const flush = () => {
			const cols = colEnds.length;
			cluster.forEach((idx) => { res[idx].cols = cols; });
			cluster = [];
			colEnds = [];
			clusterMaxEnd = -Infinity;
		};

		order.forEach((idx) => {
			const it = items[idx];
			if(cluster.length && it.start >= clusterMaxEnd) flush();

			let col = -1;
			for(let c = 0; c < colEnds.length; c++) {
				if(colEnds[c] <= it.start) { col = c; break; }
			}
			if(col === -1) { col = colEnds.length; colEnds.push(it.end); }
			else colEnds[col] = it.end;

			res[idx] = { col: col, cols: 0 };
			cluster.push(idx);
			clusterMaxEnd = Math.max(clusterMaxEnd, it.end);
		});

		if(cluster.length) flush();
		return res;
	}

	// ── Lane assignment (month spanning strips) ───────────────────────────────

	// Assign each strip-eligible event a stable horizontal lane across the WHOLE month, so a multi-week event
	// keeps the same visual track as it wraps week→week. Events are packed by start (then longest-span first)
	// into the lowest lane whose previous occupant ended at/before this event starts. Returns a Map(event→lane).
	// Input events must expose numeric `start`/`end` (seconds).
	function assignLanes(events) {
		const lanes = new Map();
		const order = events.slice().sort((a, b) =>
			(a.start - b.start) || ((b.end - b.start) - (a.end - a.start)));
		const laneEnds = [];

		order.forEach((ev) => {
			let lane = -1;
			for(let l = 0; l < laneEnds.length; l++) {
				if(laneEnds[l] <= ev.start) { lane = l; break; }
			}
			if(lane === -1) { lane = laneEnds.length; laneEnds.push(ev.end); }
			else laneEnds[lane] = ev.end;
			lanes.set(ev, lane);
		});

		return lanes;
	}

	return {
		MONTH_NAMES: MONTH_NAMES,
		MONTH_ABBR: MONTH_ABBR,
		DAY_ABBR: DAY_ABBR,
		WEEKDAY_ABBR: WEEKDAY_ABBR,
		DAY_SECS: DAY_SECS,
		startOfWeekOffset: startOfWeekOffset,
		startOfWeekDate: startOfWeekDate,
		weekDays: weekDays,
		monthGridCells: monthGridCells,
		epochParts: epochParts,
		dayStartSec: dayStartSec,
		dayBounds: dayBounds,
		dayMidnightSec: dayMidnightSec,
		minutesOfDay: minutesOfDay,
		inferAllDay: inferAllDay,
		spanDays: spanDays,
		dedupeServerEvents: dedupeServerEvents,
		packColumns: packColumns,
		assignLanes: assignLanes,
	};
})();
