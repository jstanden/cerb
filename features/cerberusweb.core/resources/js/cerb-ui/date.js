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
	}
};
