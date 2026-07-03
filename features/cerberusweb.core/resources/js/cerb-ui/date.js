/*
 * CerbUI.date — relative-time formatters for a seconds delta (not Date objects). Used by countdown
 * rings and "ran X ago" labels. Plain functions on the global, like CerbUI.palettes.
 */
CerbUI.date = {
	// Countdown to a future moment: "now", "45s", "2:05", "20h", "1d"
	remain: function(sec) {
		if(sec <= 0) return 'now';
		if(sec < 60) return sec + 's';
		if(sec < 3600) { const m = Math.floor(sec / 60), s = sec % 60; return m + ':' + (s < 10 ? '0' : '') + s; }
		// At the hour/day scale, show only the biggest unit (e.g. "20h", "1d") — the minor unit is just noise
		if(sec < 86400) return Math.floor(sec / 3600) + 'h';
		return Math.floor(sec / 86400) + 'd';
	},
	// Relative past: "just now", "45s ago", "3m ago", "2h ago", "1d ago"
	ago: function(sec) {
		if(sec < 5) return 'just now';
		if(sec < 60) return sec + 's ago';
		if(sec < 3600) return Math.floor(sec / 60) + 'm ago';
		if(sec < 86400) return Math.floor(sec / 3600) + 'h ago';
		return Math.floor(sec / 86400) + 'd ago';
	},

	_days: ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
	_months: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],

	// A small strftime subset (replaces d3.timeFormat) for absolute date/time axis ticks + tooltips.
	// Returns a formatter (date) => string. Supported: %Y %y %m %d %e %H %M %S %I %p %a %A %b %B %%.
	// The GNU `-` flag (%-I, %-d, %-m, %-H, %-M, %-S, %-y) is honored to suppress zero-padding — so
	// an otherwise-unhandled pattern renders (e.g. "3 PM") instead of leaking a literal "%-I".
	strftime: function(pattern) {
		const pad = (n) => (n < 10 ? '0' + n : '' + n);
		const D = CerbUI.date;
		return function(date) {
			const d = (date instanceof Date) ? date : new Date(date);
			if(isNaN(d.getTime())) return '';
			const h = d.getHours();
			return String(pattern).replace(/%(-?)([YymdeHMSIpaAbB%])/g, function(_, flag, t) {
				const num = (flag === '-') ? ((n) => '' + n) : pad;
				switch(t) {
					case 'Y': return '' + d.getFullYear();
					case 'y': return num(d.getFullYear() % 100);
					case 'm': return num(d.getMonth() + 1);
					case 'd': return num(d.getDate());
					case 'e': return (d.getDate() < 10 ? ' ' : '') + d.getDate();
					case 'H': return num(h);
					case 'M': return num(d.getMinutes());
					case 'S': return num(d.getSeconds());
					case 'I': return num((h % 12) || 12);
					case 'p': return h < 12 ? 'AM' : 'PM';
					case 'a': return D._days[d.getDay()].slice(0, 3);
					case 'A': return D._days[d.getDay()];
					case 'b': return D._months[d.getMonth()].slice(0, 3);
					case 'B': return D._months[d.getMonth()];
					case '%': return '%';
				}
				return _;
			});
		};
	}
};
