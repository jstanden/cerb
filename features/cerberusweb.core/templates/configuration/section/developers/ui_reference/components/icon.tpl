	<div class="cerb-uiref-component" id="icon">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-picture"></span>Icon</div>

		<div class="cerb-uiref-icons-bar">
			<input type="search" id="uiref-icon-filter" placeholder="Filter icons…">
			<label style="display:inline-flex;align-items:center;gap:0.4em;cursor:pointer;"><input type="checkbox" id="uiref-icon-labels"> Show labels</label>
			<span class="cerb-uiref-utils--note">Use <code>&lt;span class="cerb-icons cerb-icon-NAME"&gt;&lt;/span&gt;</code> — click any icon to copy its markup.</span>
		</div>

		<div class="cerb-uiref-icons" id="uiref-icon-grid">
		{foreach from=$icons_cerb item=icon}
			<div class="cerb-uiref-icon" data-icon-name="{$icon}" title="{$icon}">
				<span class="cerb-icons cerb-icon-{$icon}"></span>
				<span class="cerb-uiref-icon--label">{$icon}</span>
			</div>
		{/foreach}
		</div>

		<div class="cerb-ui-header" style="margin-top:3em;">
			<div class="cerb-ui-header--label">Animation utilities &mdash; add a <code>cerb-u-anim-*</code> class to any icon (click to copy the class)</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-uiref-anim-row" id="uiref-icon-anim">
					<div class="cerb-uiref-icon" data-anim-class="cerb-u-anim-spin" title="cerb-u-anim-spin"><span class="cerb-icons cerb-icon-refresh cerb-u-anim-spin"></span><span class="cerb-uiref-icon--label">cerb-u-anim-spin</span></div>
					<div class="cerb-uiref-icon" data-anim-class="cerb-u-anim-pulse" title="cerb-u-anim-pulse"><span class="cerb-icons cerb-icon-heart cerb-u-anim-pulse"></span><span class="cerb-uiref-icon--label">cerb-u-anim-pulse</span></div>
					<div class="cerb-uiref-icon" data-anim-class="cerb-u-anim-ping" title="cerb-u-anim-ping"><span class="cerb-icons cerb-icon-star cerb-u-anim-ping"></span><span class="cerb-uiref-icon--label">cerb-u-anim-ping</span></div>
					<div class="cerb-uiref-icon" data-anim-class="cerb-u-anim-shake" title="cerb-u-anim-shake"><span class="cerb-icons cerb-icon-bell cerb-u-anim-shake"></span><span class="cerb-uiref-icon--label">cerb-u-anim-shake</span></div>
				</div>
			</div>
		</div>

		<div class="cerb-ui-header" style="margin-top:3em;">
			<div class="cerb-ui-header--label">Magic gradient styles &mdash; add a <code>cerb-u-anim-magic-*</code> class to any icon to draw attention (click to copy the class)</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-uiref-anim-row" id="uiref-icon-anim-magic">
					<div class="cerb-uiref-icon" data-anim-class="cerb-u-anim-magic-sweep" title="cerb-u-anim-magic-sweep"><span class="cerb-icons cerb-icon-sparkles cerb-u-anim-magic-sweep"></span><span class="cerb-uiref-icon--label">cerb-u-anim-magic-sweep</span></div>
					<div class="cerb-uiref-icon" data-anim-class="cerb-u-anim-magic-aurora" title="cerb-u-anim-magic-aurora"><span class="cerb-icons cerb-icon-zap cerb-u-anim-magic-aurora"></span><span class="cerb-uiref-icon--label">cerb-u-anim-magic-aurora</span></div>
					<div class="cerb-uiref-icon" data-anim-class="cerb-u-anim-magic-cycle" title="cerb-u-anim-magic-cycle"><span class="cerb-icons cerb-icon-star cerb-u-anim-magic-cycle"></span><span class="cerb-uiref-icon--label">cerb-u-anim-magic-cycle</span></div>
				</div>
			</div>
		</div>

		<div class="cerb-ui-header" style="margin-top:3em;">
			<div class="cerb-ui-header--label">Hover animation utilities &mdash; add a <code>cerb-u-anim-*-hover</code> class to any icon (click to copy the class)</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-uiref-anim-row" id="uiref-icon-anim-hover">
					<div class="cerb-uiref-icon" data-anim-class="cerb-u-anim-spin-hover" title="cerb-u-anim-spin-hover"><span class="cerb-icons cerb-icon-refresh cerb-u-anim-spin-hover"></span><span class="cerb-uiref-icon--label">cerb-u-anim-spin-hover</span></div>
					<div class="cerb-uiref-icon" data-anim-class="cerb-u-anim-pulse-hover" title="cerb-u-anim-pulse-hover"><span class="cerb-icons cerb-icon-heart cerb-u-anim-pulse-hover"></span><span class="cerb-uiref-icon--label">cerb-u-anim-pulse-hover</span></div>
					<div class="cerb-uiref-icon" data-anim-class="cerb-u-anim-ping-hover" title="cerb-u-anim-ping-hover"><span class="cerb-icons cerb-icon-star cerb-u-anim-ping-hover"></span><span class="cerb-uiref-icon--label">cerb-u-anim-ping-hover</span></div>
					<div class="cerb-uiref-icon" data-anim-class="cerb-u-anim-shake-hover" title="cerb-u-anim-shake-hover"><span class="cerb-icons cerb-icon-trash cerb-u-anim-shake-hover"></span><span class="cerb-uiref-icon--label">cerb-u-anim-shake-hover</span></div>
				</div>
			</div>

			<div class="cerb-ui-header" style="margin-top:3em;">
				<div class="cerb-ui-header--label">Parent hover &mdash; a <code>-hover</code> icon also plays when an enclosing <code>&lt;button&gt;</code> is hovered (no markup), or any ancestor with <code>cerb-u-anim-group</code></div>
			</div>
			<div class="cerb-uiref-example">
				<div class="cerb-uiref-demo">
					<button type="button" class="cerb-ui-button"><span class="cerb-icons cerb-icon-trash cerb-u-anim-shake-hover"></span> Delete</button>
					<span class="cerb-u-anim-group" style="display:inline-flex; align-items:center; gap:0.4em; padding:0.5em 0.8em; border-radius:6px; background:var(--cerb-color-background); cursor:default;"><span class="cerb-icons cerb-icon-star cerb-u-anim-spin-hover"></span> Any parent (group)</span>
				</div>

				<div class="cerb-uiref-code">
					<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
					<pre data-cerb-uiref-source>&lt;button class="cerb-ui-button"&gt;&lt;span class="cerb-icons cerb-icon-trash cerb-u-anim-shake-hover"&gt;&lt;/span&gt; Delete&lt;/button&gt;
&lt;span class="cerb-u-anim-group"&gt;&lt;span class="cerb-icons cerb-icon-star cerb-u-anim-spin-hover"&gt;&lt;/span&gt; Any parent (group)&lt;/span&gt;</pre>
				</div>
			</div>
		</div>
	</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}">
(function() {
	// Icon browser: filter by name, click-to-copy markup, toggle labels (hidden by default)
	const $iconGrid = $('#uiref-icon-grid');
	if($iconGrid.length) {
		const $iconItems = $iconGrid.find('[data-icon-name]');

		$('#uiref-icon-filter').on('input search', function() {
			const q = $(this).val().trim().toLowerCase();
			$iconItems.each(function() {
				$(this).toggle(!q || $(this).attr('data-icon-name').indexOf(q) !== -1);
			});
		});

		$iconItems.on('click', function() {
			const markup = '<span class="cerb-icons cerb-icon-' + $(this).attr('data-icon-name') + '"></span>';
			navigator.clipboard.writeText(markup);
			Devblocks.createAlert('Copied icon to clipboard!');
		});

		$('#uiref-icon-labels').on('change', function() {
			$iconGrid.toggleClass('cerb-uiref-icons--labeled', this.checked);
		});
	}

	// Icon animations: click a demo tile to copy its cerb-u-anim-* class
	$('#uiref-icon-anim, #uiref-icon-anim-hover, #uiref-icon-anim-magic').on('click', '[data-anim-class]', function() {
		navigator.clipboard.writeText($(this).attr('data-anim-class'));
		Devblocks.createAlert('Copied animation class to clipboard!');
	});
})();
</script>
