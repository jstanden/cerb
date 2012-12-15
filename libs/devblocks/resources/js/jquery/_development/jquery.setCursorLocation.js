$.fn.setCursorLocation = function(loc) {
	return this.each(function() {
		if (this.setSelectionRange) {
			this.focus();
			this.setSelectionRange(loc, loc);
		} else if (this.createTextRange) {
			var range = this.createTextRange();
			range.collapse(true);
			range.moveEnd('character', loc);
			range.moveStart('character', loc);
			range.select();
		}
	});
};