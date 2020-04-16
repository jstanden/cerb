$.fn.extend({
	insertAtCursor: function (myValue) {
		this.each(function () {
			var $this = $(this).focus();
			var txt = $this.val();
			var pos = $this.caret('pos');
			var newTxt = '';
			var cursorPos = pos;

			newTxt = txt.substring(0, pos);
			newTxt += myValue;

			$this.val(newTxt);

			// Move the cursor to the end
			cursorPos = $this.val().length;

			// Scroll down all the way
			this.scrollTop = this.scrollHeight;

			// Append the rest of the content
			newTxt += txt.substring(pos);
			$this.val(newTxt);

			// Trigger a resize (if used)
			$this.trigger('autosize.resize');

			$this.caret('pos', cursorPos);

			$this.focus();
		});

		return this;
	}
});

