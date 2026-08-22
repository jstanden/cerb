	<div class="cerb-uiref-component" id="rating">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-star"></span>Rating</div>

		<div class="cerb-uiref-demo">
			<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2">
				<div class="cerb-ui-rating" id="uiref-rating-demo" data-cerb-input="uiref-rating-input" data-cerb-label="uiref-rating-label" data-cerb-labels="Poor,Fair,Good,Great,Excellent">
					<button type="button" data-value="1" title="Poor"><span class="cerb-icons cerb-icon-star"></span></button>
					<button type="button" data-value="2" title="Fair"><span class="cerb-icons cerb-icon-star"></span></button>
					<button type="button" data-value="3" title="Good"><span class="cerb-icons cerb-icon-star"></span></button>
					<button type="button" data-value="4" title="Great"><span class="cerb-icons cerb-icon-star"></span></button>
					<button type="button" data-value="5" title="Excellent"><span class="cerb-icons cerb-icon-star"></span></button>
				</div>
				<span class="cerb-ui-rating--label" id="uiref-rating-label"></span>
			</div>
			<input type="hidden" id="uiref-rating-input" value="3">
			<div class="cerb-uiref-result">Value: <b id="uiref-rating-out">3</b></div>
		</div>

		<div class="cerb-uiref-code">
			<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
			<pre data-cerb-uiref-source>{literal}&lt;!-- Filled from the left UP TO the selected value. Any .cerb-icons glyph works. --&gt;
&lt;div class="cerb-ui-rating"
	data-cerb-input="myInput"                          &lt;!-- id of the hidden input to write --&gt;
	data-cerb-label="myLabel"                          &lt;!-- id of an element to receive the tier name --&gt;
	data-cerb-labels="Poor,Fair,Good,Great,Excellent"&gt; &lt;!-- one per button, in order --&gt;
	&lt;button type="button" data-value="1" title="Poor"&gt;&lt;span class="cerb-icons cerb-icon-star"&gt;&lt;/span&gt;&lt;/button&gt;
	&lt;button type="button" data-value="2" title="Fair"&gt;&lt;span class="cerb-icons cerb-icon-star"&gt;&lt;/span&gt;&lt;/button&gt;
	&lt;!-- … --&gt;
&lt;/div&gt;
&lt;span class="cerb-ui-rating--label" id="myLabel"&gt;&lt;/span&gt;
&lt;input type="hidden" id="myInput" value="3"&gt;{/literal}</pre>
		</div>

		<div class="cerb-uiref-code">
			<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
			<pre data-cerb-uiref-source>{literal}new CerbUI.Rating(el, {
	// Every option is optional -- each falls back to the data-* attribute above.
	input:      '#myInput',                              // element or selector; else data-cerb-input
	label:      '#myLabel',                              // element or selector; else data-cerb-label
	labels:     ['Poor','Fair','Good','Great','Excellent'], // else data-cerb-labels (comma-separated)
	emptyLabel: 'Unrated',                               // shown at 0 (default: 'Unrated')
	onSelect:   function(value, rating) { /* … */ },     // fires on click only, not on setValue()
});

// Methods
const r = CerbUI.Rating.from(el);   // the instance for an element
r.getValue();                       // -> number (0 when unrated)
r.setValue(4);                      // repaint + write the input, silently
r.setValue(4, {fireCallback: true}); // …and fire onSelect
r.destroy();                        // unbind

// Clicking the CURRENTLY selected button clears back to 0 -- without it a mis-click can't be undone.
// Values are compared, never counted: data-value can be 1..5, or sparse (10/20/30/40) to leave room
// for a tier to be inserted later without rewriting stored rows. Setting the input's value and
// re-running setValue() is how you restore state.{/literal}</pre>
		</div>

		<div class="cerb-uiref-demo">
			<div class="cerb-ui-rating" id="uiref-rating-sparse-demo" data-cerb-labels="Basic,Efficient,Advanced,Frontier">
				<button type="button" data-value="10" title="Basic"><span class="cerb-icons cerb-icon-brain"></span></button>
				<button type="button" data-value="20" title="Efficient"><span class="cerb-icons cerb-icon-brain"></span></button>
				<button type="button" data-value="30" title="Advanced" class="cerb-ui-rating--on"><span class="cerb-icons cerb-icon-brain"></span></button>
				<button type="button" data-value="40" title="Frontier"><span class="cerb-icons cerb-icon-brain"></span></button>
			</div>
			<div class="cerb-ui-rating cerb-ui-rating--blue" id="uiref-rating-tint-demo">
				<button type="button" data-value="1"><span class="cerb-icons cerb-icon-coins"></span></button>
				<button type="button" data-value="2" class="cerb-ui-rating--on"><span class="cerb-icons cerb-icon-coins"></span></button>
				<button type="button" data-value="3"><span class="cerb-icons cerb-icon-coins"></span></button>
				<button type="button" data-value="4"><span class="cerb-icons cerb-icon-coins"></span></button>
			</div>
		</div>

		<div class="cerb-uiref-code">
			<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
			<pre data-cerb-uiref-source>{literal}&lt;!-- No input: the initial value is inferred from the last button already marked --on.
     data-value is sparse here, leaving room to insert a tier later. --&gt;
&lt;div class="cerb-ui-rating" data-cerb-labels="Basic,Efficient,Advanced,Frontier"&gt;
	&lt;button type="button" data-value="10"&gt;&lt;span class="cerb-icons cerb-icon-brain"&gt;&lt;/span&gt;&lt;/button&gt;
	&lt;button type="button" data-value="30" class="cerb-ui-rating--on"&gt;…&lt;/button&gt;
&lt;/div&gt;

&lt;!-- Retint -- e.g. an axis where MORE isn't BETTER, so it reads as a gauge not a score. One
     modifier per --cerb-color-tag-* hue: red, blue, green, gray, orange, purple. --&gt;
&lt;div class="cerb-ui-rating cerb-ui-rating--blue"&gt;…&lt;/div&gt;

&lt;!-- A one-off outside the palette sets the variable the modifiers set: --&gt;
&lt;div class="cerb-ui-rating" style="--cerb-ui-rating-color:var(--cerb-color-action-primary);"&gt;…&lt;/div&gt;{/literal}</pre>
		</div>
	</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}">
{literal}
(function() {
	if(!window.CerbUI || !CerbUI.Rating)
		return;

	const el = document.getElementById('uiref-rating-demo');
	const out = document.getElementById('uiref-rating-out');

	if(el) {
		new CerbUI.Rating(el, {
			onSelect: function(value) { out.textContent = value; }
		});
	}

	['uiref-rating-sparse-demo', 'uiref-rating-tint-demo'].forEach(function(id) {
		const node = document.getElementById(id);
		if(node) new CerbUI.Rating(node);
	});
})();
{/literal}
</script>
