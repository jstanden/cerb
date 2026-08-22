	<div class="cerb-uiref-component" id="meter">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-signal"></span>Meter</div>

		{* Example: the base read -- N blocks, filled from the left up to the level *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">A level out of N, filled from the left. CSS only -- no JS to construct</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<span class="cerb-ui-meter" title="Advanced">
					<span class="cerb-ui-meter--block cerb-ui-meter--block-on"></span>
					<span class="cerb-ui-meter--block cerb-ui-meter--block-on"></span>
					<span class="cerb-ui-meter--block cerb-ui-meter--block-on"></span>
					<span class="cerb-ui-meter--block"></span>
				</span>
				&nbsp;&nbsp;
				<span class="cerb-ui-meter" title="Unrated">
					<span class="cerb-ui-meter--block"></span>
					<span class="cerb-ui-meter--block"></span>
					<span class="cerb-ui-meter--block"></span>
					<span class="cerb-ui-meter--block"></span>
				</span>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}&lt;!-- An unrated meter keeps ALL its blocks, just unfilled. A missing meter leaves a hole and
     breaks the comparison down a column; a row of gray reads as "not rated". --&gt;
&lt;span class="cerb-ui-meter" title="Advanced"&gt;
	&lt;span class="cerb-ui-meter--block cerb-ui-meter--block-on"&gt;&lt;/span&gt;
	&lt;span class="cerb-ui-meter--block cerb-ui-meter--block-on"&gt;&lt;/span&gt;
	&lt;span class="cerb-ui-meter--block cerb-ui-meter--block-on"&gt;&lt;/span&gt;
	&lt;span class="cerb-ui-meter--block"&gt;&lt;/span&gt;
&lt;/span&gt;{/literal}</pre>
			</div>
		</div>

		{* Example: the six palette hues, emitted by cerb-tag-color-modifiers() *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Hue modifiers -- one per <code>--cerb-color-tag-*</code> palette color</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-4 cerb-u-flex-wrap">
					<span class="cerb-u-flex cerb-u-items-center cerb-u-gap-2">
						<span class="cerb-ui-meter cerb-ui-meter--red"><span class="cerb-ui-meter--block cerb-ui-meter--block-on"></span><span class="cerb-ui-meter--block cerb-ui-meter--block-on"></span><span class="cerb-ui-meter--block cerb-ui-meter--block-on"></span><span class="cerb-ui-meter--block cerb-ui-meter--block-on"></span></span>
						<span class="cerb-u-text-muted cerb-u-fs-n1">red</span>
					</span>
					<span class="cerb-u-flex cerb-u-items-center cerb-u-gap-2">
						<span class="cerb-ui-meter cerb-ui-meter--blue"><span class="cerb-ui-meter--block cerb-ui-meter--block-on"></span><span class="cerb-ui-meter--block cerb-ui-meter--block-on"></span><span class="cerb-ui-meter--block cerb-ui-meter--block-on"></span><span class="cerb-ui-meter--block"></span></span>
						<span class="cerb-u-text-muted cerb-u-fs-n1">blue</span>
					</span>
					<span class="cerb-u-flex cerb-u-items-center cerb-u-gap-2">
						<span class="cerb-ui-meter cerb-ui-meter--green"><span class="cerb-ui-meter--block cerb-ui-meter--block-on"></span><span class="cerb-ui-meter--block cerb-ui-meter--block-on"></span><span class="cerb-ui-meter--block"></span><span class="cerb-ui-meter--block"></span></span>
						<span class="cerb-u-text-muted cerb-u-fs-n1">green</span>
					</span>
					<span class="cerb-u-flex cerb-u-items-center cerb-u-gap-2">
						<span class="cerb-ui-meter cerb-ui-meter--gray"><span class="cerb-ui-meter--block cerb-ui-meter--block-on"></span><span class="cerb-ui-meter--block"></span><span class="cerb-ui-meter--block"></span><span class="cerb-ui-meter--block"></span></span>
						<span class="cerb-u-text-muted cerb-u-fs-n1">gray</span>
					</span>
					<span class="cerb-u-flex cerb-u-items-center cerb-u-gap-2">
						<span class="cerb-ui-meter cerb-ui-meter--orange"><span class="cerb-ui-meter--block cerb-ui-meter--block-on"></span><span class="cerb-ui-meter--block cerb-ui-meter--block-on"></span><span class="cerb-ui-meter--block cerb-ui-meter--block-on"></span><span class="cerb-ui-meter--block"></span></span>
						<span class="cerb-u-text-muted cerb-u-fs-n1">orange</span>
					</span>
					<span class="cerb-u-flex cerb-u-items-center cerb-u-gap-2">
						<span class="cerb-ui-meter cerb-ui-meter--purple"><span class="cerb-ui-meter--block cerb-ui-meter--block-on"></span><span class="cerb-ui-meter--block cerb-ui-meter--block-on"></span><span class="cerb-ui-meter--block cerb-ui-meter--block-on"></span><span class="cerb-ui-meter--block cerb-ui-meter--block-on"></span></span>
						<span class="cerb-u-text-muted cerb-u-fs-n1">purple</span>
					</span>
				</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}&lt;!-- The meter carries no vocabulary of its own -- the CONSUMER names a hue, so a set of
     meters that mean different things stays distinguishable down a column. --&gt;
&lt;span class="cerb-ui-meter cerb-ui-meter--purple"&gt;…&lt;/span&gt;
&lt;span class="cerb-ui-meter cerb-ui-meter--green"&gt;…&lt;/span&gt;{/literal}</pre>
			</div>
		</div>

		{* Example: any other hue, per instance *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Retint one instance with <code>--cerb-ui-meter-color</code></div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<span class="cerb-ui-meter" style="--cerb-ui-meter-color:var(--cerb-color-tag-red);">
					<span class="cerb-ui-meter--block cerb-ui-meter--block-on"></span>
					<span class="cerb-ui-meter--block cerb-ui-meter--block-on"></span>
					<span class="cerb-ui-meter--block cerb-ui-meter--block-on"></span>
					<span class="cerb-ui-meter--block cerb-ui-meter--block-on"></span>
					<span class="cerb-ui-meter--block"></span>
				</span>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}&lt;span class="cerb-ui-meter" style="--cerb-ui-meter-color:var(--cerb-color-tag-red);"&gt;…&lt;/span&gt;

&lt;!-- A host with its own class names paints from the same geometry and hues rather than
     re-declaring "a 5px block", which drifts the moment one copy is nudged:

     @include cerb-ui-meter-blocks;   // the row
     @include cerb-ui-meter-block;    // one block, unfilled
     @include cerb-ui-meter-block-on; // the filled state

     // ...and the hue modifiers, from cerb-ui/_palette (any component with one hue hook):
     @include cerb-tag-color-modifiers(".my-component--meter-", "--cerb-ui-meter-color");
--&gt;{/literal}</pre>
			</div>
		</div>

		{* Where it differs from its two neighbors -- picking the wrong one is the common mistake *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Meter vs. Rating vs. Distribution bar</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<ul>
					<li><b>Meter</b> -- reports an ordinal level out of a fixed small N. Read-only, cell-sized.</li>
					<li><b>Rating</b> -- the same read, but INTERACTIVE: buttons, any <code>.cerb-icons</code> glyph, hover preview. Use it to edit.</li>
					<li><b>Distribution bar</b> -- proportions of a whole that sum to 100%, at continuous widths. Not an ordinal level.</li>
				</ul>
			</div>
		</div>
	</div>
