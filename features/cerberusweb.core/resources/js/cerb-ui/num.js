/*
 * CerbUI.num — compact number formatters for chips/legends/stat values. Plain functions on the
 * global, like CerbUI.palettes.
 */
CerbUI.num = {
	// Compact a count: 1500 -> "1.5k" (drops a trailing ".0"); below 1000 returns the number as-is
	compact: function(n) {
		return n >= 1000 ? (n / 1000).toFixed(1).replace(/\.0$/, '') + 'k' : ('' + n);
	},

	// A small subset of d3-format, covering the patterns the charts actually use (grow as needed):
	//   ','    thousands grouping, no decimals        1234    -> "1,234"
	//   '.2f'  fixed decimals                          1.5     -> "1.50"
	//   ',.2f' grouping + fixed decimals               1234.5  -> "1,234.50"
	//   '.1%'  percent with N decimals                 0.25    -> "25.0%"
	//   ''     (or unknown) -> String(n)
	// Returns a formatter function (n) => string, so it can be reused per-axis/series like d3.format().
	format: function(pattern) {
		pattern = pattern || '';
		const percent = pattern.indexOf('%') !== -1;
		const group = pattern.indexOf(',') !== -1;
		const m = pattern.match(/\.(\d+)/);
		const digits = m ? parseInt(m[1], 10) : (percent || pattern === '') ? (percent ? 0 : null) : 0;
		return function(n) {
			n = Number(n);
			if(!isFinite(n)) return '' + n;
			if(digits == null) return '' + n; // empty pattern: leave the value untouched
			let s = (percent ? n * 100 : n).toFixed(digits);
			if(group) {
				const parts = s.split('.');
				parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
				s = parts.join('.');
			}
			return percent ? (s + '%') : s;
		};
	},

	// Convenience for a ratio (0..1) -> "25.0%" (defaults to 1 decimal, matching the old d3.format('.1%'))
	percent: function(n, digits) {
		digits = (digits == null) ? 1 : digits;
		return (Number(n) * 100).toFixed(digits) + '%';
	},

	// Humanized short duration, keeping the 2 largest non-zero units: "2h 5m", "90s" -> "1m 30s", "0s".
	// `unit` is the unit of the incoming value (seconds default) — matches the old shortEnglishHumanizer used
	// for duration axis ticks (largest:2), without pulling in humanize-duration.js.
	duration: function(value, unit) {
		const perMs = { milliseconds: 1, seconds: 1000, minutes: 60000, hours: 3600000 };
		let ms = Math.round(Number(value) * (perMs[unit] != null ? perMs[unit] : 1000));
		if(!isFinite(ms)) return '' + value;
		if(ms === 0) return '0' + ({ milliseconds: 'ms', seconds: 's', minutes: 'm', hours: 'h' }[unit] || 's');
		const neg = ms < 0;
		ms = Math.abs(ms);
		const units = [
			['y', 31557600000], ['mo', 2629800000], ['w', 604800000], ['d', 86400000],
			['h', 3600000], ['m', 60000], ['s', 1000], ['ms', 1]
		];
		const parts = [];
		for(let i = 0; i < units.length && parts.length < 2; i++) {
			if(ms >= units[i][1]) {
				const c = Math.floor(ms / units[i][1]);
				ms -= c * units[i][1];
				parts.push(c + units[i][0]);
			}
		}
		return (neg ? '-' : '') + parts.join(' ');
	}
};
