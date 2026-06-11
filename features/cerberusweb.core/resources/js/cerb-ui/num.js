/*
 * CerbUI.num — compact number formatters for chips/legends/stat values. Plain functions on the
 * global, like CerbUI.palettes.
 */
CerbUI.num = {
	// Compact a count: 1500 -> "1.5k" (drops a trailing ".0"); below 1000 returns the number as-is
	compact: function(n) {
		return n >= 1000 ? (n / 1000).toFixed(1).replace(/\.0$/, '') + 'k' : ('' + n);
	}
};
