	<div class="cerb-uiref-component" id="utilities">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-wrench"></span>Utilities</div>

		{* Utilities — a 2-col grid: click the name (left) to copy it; live example on the right. Grouped by
		   concern with cerb-ui-header--label subheaders. Scale families (m/p/fw/fs/opacity/border/rounded)
		   collapse to ONE row — the left shows the range (e.g. cerb-u-m-[0-5]), the right demos the steps inline. *}
		<div class="cerb-uiref-utils">

			<div class="cerb-uiref-utils--full cerb-ui-header--label">Display &amp; visibility</div>

			<code class="cerb-uiref-utils--name" data-cerb-uiref-copy data-cerb-uiref-source title="Copy to clipboard">cerb-u-hide</code>
			<div><span class="cerb-uiref-utils--note">an element with cerb-u-hide sits here &mdash;</span><span class="cerb-u-hide"> you can't see me</span> and isn't rendered.</div>

			<code class="cerb-uiref-utils--name" data-cerb-uiref-copy data-cerb-uiref-source title="Copy to clipboard">cerb-u-block</code>
			<div><span class="cerb-u-block">first block span</span><span class="cerb-u-block">second block span (stacks below)</span></div>

			<code class="cerb-uiref-utils--name" data-cerb-uiref-copy data-cerb-uiref-source title="Copy to clipboard">cerb-u-flex-inline</code>
			<div>text before <span class="cerb-u-flex-inline cerb-u-items-center cerb-u-gap-2"><span class="cerb-icons cerb-icon-check"></span><span>inline-flex sizes to content</span></span> text after (flows inline, unlike cerb-u-flex)</div>

			<div class="cerb-uiref-utils--full cerb-ui-header--label cerb-u-mt-3">Flexbox</div>

			<code class="cerb-uiref-utils--name" data-cerb-uiref-copy data-cerb-uiref-source title="Copy to clipboard">cerb-u-flex-column</code>
			<div class="cerb-u-flex cerb-u-flex-column cerb-u-gap-2" style="border:1px dashed var(--cerb-color-background-contrast-200);"><span class="cerb-uiref-utils--box"><span>top</span></span><span class="cerb-uiref-utils--box"><span>middle</span></span><span class="cerb-uiref-utils--box"><span>bottom (stacked)</span></span></div>

			<div class="cerb-uiref-utils--note cerb-uiref-utils--full">Pair with <code>cerb-u-flex</code>: <code>cerb-u-flex-column</code> stacks children vertically; <code>cerb-u-flex-row</code> is the default horizontal direction (use it to reset a column ancestor back to a row).</div>

			<code class="cerb-uiref-utils--name" data-cerb-uiref-copy data-cerb-uiref-source title="Copy to clipboard">cerb-u-flex-1</code>
			<div class="cerb-u-flex cerb-u-gap-2"><span class="cerb-uiref-utils--box cerb-u-flex-1"><span>flex-1</span></span><span class="cerb-uiref-utils--box cerb-u-flex-1"><span>flex-1</span></span></div>

			<code class="cerb-uiref-utils--name" data-cerb-uiref-copy data-cerb-uiref-source title="Copy to clipboard">cerb-u-flex-2</code>
			<div class="cerb-u-flex cerb-u-gap-2"><span class="cerb-uiref-utils--box cerb-u-flex-1"><span>flex-1</span></span><span class="cerb-uiref-utils--box cerb-u-flex-2"><span>flex-2 (twice the width — third + two-thirds)</span></span></div>

			<code class="cerb-uiref-utils--name" data-cerb-uiref-copy data-cerb-uiref-source title="Copy to clipboard">cerb-u-justify-center</code>
			<div class="cerb-u-flex cerb-u-justify-center cerb-u-gap-2" style="border:1px dashed var(--cerb-color-background-contrast-200);"><span class="cerb-uiref-utils--box"><span>centered in the row</span></span></div>

			<code class="cerb-uiref-utils--name" data-cerb-uiref-copy data-cerb-uiref-source title="Copy to clipboard">cerb-u-justify-end</code>
			<div class="cerb-u-flex cerb-u-justify-end cerb-u-gap-2" style="border:1px dashed var(--cerb-color-background-contrast-200);"><span class="cerb-uiref-utils--box"><span>pushed to the row's end</span></span></div>

			<code class="cerb-uiref-utils--name" data-cerb-uiref-copy data-cerb-uiref-source title="Copy to clipboard">cerb-u-justify-between</code>
			<div class="cerb-u-flex cerb-u-justify-between" style="border:1px dashed var(--cerb-color-background-contrast-200);"><span class="cerb-uiref-utils--box"><span>left</span></span><span class="cerb-uiref-utils--box"><span>right</span></span></div>

			<code class="cerb-uiref-utils--name" data-cerb-uiref-copy data-cerb-uiref-source title="Copy to clipboard">cerb-u-items-start</code>
			<div class="cerb-u-flex cerb-u-items-start cerb-u-gap-2" style="border:1px dashed var(--cerb-color-background-contrast-200);"><span class="cerb-uiref-utils--box"><span>tall<br>box</span></span><span class="cerb-uiref-utils--box"><span>top-aligned (vs items-center / items-stretch)</span></span></div>

			<code class="cerb-uiref-utils--name" data-cerb-uiref-copy data-cerb-uiref-source title="Copy to clipboard">cerb-u-items-stretch</code>
			<div class="cerb-u-flex cerb-u-items-stretch cerb-u-gap-2" style="border:1px dashed var(--cerb-color-background-contrast-200);"><span class="cerb-uiref-utils--box"><span>tall<br>box</span></span><span class="cerb-uiref-utils--box"><span>stretches to match height</span></span></div>

			<code class="cerb-uiref-utils--name" data-cerb-uiref-copy data-cerb-uiref-source title="Copy to clipboard">cerb-u-self-center</code>
			<div class="cerb-u-flex cerb-u-items-start cerb-u-gap-2" style="border:1px dashed var(--cerb-color-background-contrast-200);"><span class="cerb-uiref-utils--box"><span>tall<br>box</span></span><span class="cerb-uiref-utils--box cerb-u-self-center"><span>self-center (one child, vs the container's items-start)</span></span></div>

			<code class="cerb-uiref-utils--name" data-cerb-uiref-copy data-cerb-uiref-source title="Copy to clipboard">cerb-u-ml-auto</code>
			<div class="cerb-u-flex cerb-u-gap-2" style="border:1px dashed var(--cerb-color-background-contrast-200);"><span class="cerb-uiref-utils--box"><span>start</span></span><span class="cerb-uiref-utils--box cerb-u-ml-auto"><span>ml-auto pushes me right</span></span></div>

			<code class="cerb-uiref-utils--name" data-cerb-uiref-copy data-cerb-uiref-source title="Copy to clipboard">cerb-u-flex-shrink-0</code>
			<div class="cerb-u-flex cerb-u-gap-2" style="border:1px dashed var(--cerb-color-background-contrast-200);"><span class="cerb-uiref-utils--box cerb-u-flex-shrink-0"><span>fixed</span></span><span class="cerb-uiref-utils--note">…this filler shrinks while the fixed box keeps its size…</span></div>

			<div class="cerb-uiref-utils--full cerb-ui-header--label cerb-u-mt-3">Spacing</div>

			<code class="cerb-uiref-utils--name" data-cerb-uiref-copy data-cerb-uiref-source title="Copy to clipboard">cerb-u-p-[0-5]</code>
			<div class="cerb-u-flex cerb-u-gap-2 cerb-u-items-center cerb-u-flex-wrap"><span class="cerb-uiref-utils--box cerb-u-p-1"><span>p-1</span></span><span class="cerb-uiref-utils--box cerb-u-p-2"><span>p-2</span></span><span class="cerb-uiref-utils--box cerb-u-p-3"><span>p-3</span></span><span class="cerb-uiref-utils--box cerb-u-p-4"><span>p-4</span></span><span class="cerb-uiref-utils--box cerb-u-p-5"><span>p-5</span></span></div>

			<code class="cerb-uiref-utils--name" data-cerb-uiref-copy data-cerb-uiref-source title="Copy to clipboard">cerb-u-m-[0-5]</code>
			<div class="cerb-u-flex cerb-u-gap-2 cerb-u-items-center cerb-u-flex-wrap"><span class="cerb-uiref-utils--frame"><span class="cerb-uiref-utils--box cerb-u-m-1">m-1</span></span><span class="cerb-uiref-utils--frame"><span class="cerb-uiref-utils--box cerb-u-m-3">m-3</span></span><span class="cerb-uiref-utils--frame"><span class="cerb-uiref-utils--box cerb-u-m-5">m-5</span></span></div>

			<div class="cerb-uiref-utils--note cerb-uiref-utils--full">Per-side variants: <code>cerb-u-pt-</code> / <code>pr-</code> / <code>pb-</code> / <code>pl-</code> / <code>px-</code> / <code>py-</code> (and <code>cerb-u-mt-</code>, etc.). Steps 0&ndash;5 multiply <code>--cerb-u-spacer</code> (1rem): 0, .25, .5, 1, 1.5, 3&times;. Auto margins: <code>cerb-u-mr-auto</code> / <code>cerb-u-mx-auto</code> too.</div>

			<div class="cerb-uiref-utils--full cerb-ui-header--label cerb-u-mt-3">Sizing</div>

			<code class="cerb-uiref-utils--name" data-cerb-uiref-copy data-cerb-uiref-source title="Copy to clipboard">cerb-u-w-25</code>
			<div><div class="cerb-uiref-utils--bar cerb-u-w-25"></div></div>

			<code class="cerb-uiref-utils--name" data-cerb-uiref-copy data-cerb-uiref-source title="Copy to clipboard">cerb-u-w-50</code>
			<div><div class="cerb-uiref-utils--bar cerb-u-w-50"></div></div>

			<code class="cerb-uiref-utils--name" data-cerb-uiref-copy data-cerb-uiref-source title="Copy to clipboard">cerb-u-w-75</code>
			<div><div class="cerb-uiref-utils--bar cerb-u-w-75"></div></div>

			<code class="cerb-uiref-utils--name" data-cerb-uiref-copy data-cerb-uiref-source title="Copy to clipboard">cerb-u-w-100</code>
			<div><div class="cerb-uiref-utils--bar cerb-u-w-100"></div></div>

			<div class="cerb-uiref-utils--full cerb-ui-header--label cerb-u-mt-3">Text</div>

			<code class="cerb-uiref-utils--name" data-cerb-uiref-copy data-cerb-uiref-source title="Copy to clipboard">cerb-u-fw-[400-800]</code>
			<div class="cerb-u-flex cerb-u-gap-3 cerb-u-items-center cerb-u-flex-wrap"><span class="cerb-u-fw-400">400</span><span class="cerb-u-fw-500">500</span><span class="cerb-u-fw-600">600</span><span class="cerb-u-fw-700">700</span><span class="cerb-u-fw-800">800</span></div>

			<div class="cerb-uiref-utils--note cerb-uiref-utils--full">The step <strong>is</strong> the CSS value (<code>cerb-u-fw-700</code> = bold, <code>cerb-u-fw-400</code> = normal). Its own <code>fw-</code> prefix keeps weight out of the <code>text-</code> / <code>fs-</code> groups. <code>cerb-u-bold</code> is a readable alias for <code>cerb-u-fw-700</code>.</div>

			{* Inline character formats — each chip is copyable and styled by the class it names *}
			<div class="cerb-uiref-utils--full cerb-u-flex cerb-u-gap-2 cerb-u-items-center cerb-u-flex-wrap">
				<code class="cerb-uiref-utils--name cerb-u-bold" data-cerb-uiref-copy data-cerb-uiref-source title="Copy to clipboard">cerb-u-bold</code>
				<code class="cerb-uiref-utils--name cerb-u-italic" data-cerb-uiref-copy data-cerb-uiref-source title="Copy to clipboard">cerb-u-italic</code>
				<code class="cerb-uiref-utils--name cerb-u-text-uppercase" data-cerb-uiref-copy data-cerb-uiref-source title="Copy to clipboard">cerb-u-text-uppercase</code>
				<code class="cerb-uiref-utils--name cerb-u-text-muted" data-cerb-uiref-copy data-cerb-uiref-source title="Copy to clipboard">cerb-u-text-muted</code>
				<code class="cerb-uiref-utils--name cerb-u-underline-hover" data-cerb-uiref-copy data-cerb-uiref-source title="Copy to clipboard">cerb-u-underline-hover</code>
			</div>

			<code class="cerb-uiref-utils--name" data-cerb-uiref-copy data-cerb-uiref-source title="Copy to clipboard">cerb-u-text-center</code>
			<div class="cerb-u-text-center">centered text</div>

			<code class="cerb-uiref-utils--name" data-cerb-uiref-copy data-cerb-uiref-source title="Copy to clipboard">cerb-u-nowrap</code>
			<div class="cerb-u-nowrap" style="max-width:14em;overflow:hidden;">this long line stays on one row and never wraps</div>

			<code class="cerb-uiref-utils--name" data-cerb-uiref-copy data-cerb-uiref-source title="Copy to clipboard">cerb-u-fs</code>
			<div class="cerb-u-flex cerb-u-gap-3 cerb-u-items-center cerb-u-flex-wrap"><span class="cerb-u-fs-n5">fs-n5</span><span class="cerb-u-fs">fs</span><span class="cerb-u-fs-5">fs-5</span><span class="cerb-u-fs-2x">fs-2x</span></div>

			<div class="cerb-uiref-utils--note cerb-uiref-utils--full">Font size is <strong>anchored at <code>cerb-u-fs</code> (1em)</strong> with symmetric 0.05em steps — the number is the <em>step count</em>, not the value. Up: <code>cerb-u-fs-1</code> &hellip; <code>cerb-u-fs-10</code> (1.05em &rarr; 1.5em). Down: <code>cerb-u-fs-n1</code> &hellip; <code>cerb-u-fs-n10</code> (0.95em &rarr; 0.5em). <code>cerb-u-fs-2x</code> is a 2em shortcut. Its own <code>fs-</code> prefix keeps size out of the <code>text-</code> color group.</div>

			<div class="cerb-uiref-utils--full cerb-ui-header--label cerb-u-mt-3">Borders &amp; radius</div>

			<code class="cerb-uiref-utils--name" data-cerb-uiref-copy data-cerb-uiref-source title="Copy to clipboard">cerb-u-border-[0-5]</code>
			<div class="cerb-u-flex cerb-u-gap-2 cerb-u-items-center cerb-u-flex-wrap"><span class="cerb-ui-tile cerb-u-border-1">1px</span><span class="cerb-ui-tile cerb-u-border-3">3px</span><span class="cerb-ui-tile cerb-u-border-5">5px</span><span class="cerb-ui-tile cerb-u-border-b-0">b-0</span></div>

			<div class="cerb-uiref-utils--note cerb-uiref-utils--full">Per-side widths too: <code>cerb-u-border-t-</code> / <code>r-</code> / <code>b-</code> / <code>l-</code> <code>0</code>&ndash;<code>5</code> (e.g. <code>border-b-0</code> collapses just the bottom edge, <code>border-l-3</code> sets a 3px left rule). No x/y pairs yet — compose two per-side classes for t+b or l+r.</div>

			<code class="cerb-uiref-utils--name" data-cerb-uiref-copy data-cerb-uiref-source title="Copy to clipboard">cerb-u-rounded-[0-4]</code>
			<div class="cerb-u-flex cerb-u-gap-2 cerb-u-items-center cerb-u-flex-wrap"><span class="cerb-uiref-utils--box cerb-u-p-3 cerb-u-rounded-2"><span>2 · 6px</span></span><span class="cerb-uiref-utils--box cerb-u-p-3 cerb-u-rounded-4"><span>4 · 10px</span></span><span class="cerb-uiref-utils--box cerb-u-px-3 cerb-u-rounded-full"><span>full · pill</span></span></div>

			<div class="cerb-uiref-utils--note cerb-uiref-utils--full">Radius steps <code>cerb-u-rounded-0</code> &hellip; <code>cerb-u-rounded-4</code> (0 / 4 / 6 / 8 / 10px — inputs, chips, panels) plus <code>cerb-u-rounded-full</code> (999px pill).</div>

			<div class="cerb-uiref-utils--full cerb-ui-header--label cerb-u-mt-3">Effects</div>

			<code class="cerb-uiref-utils--name" data-cerb-uiref-copy data-cerb-uiref-source title="Copy to clipboard">cerb-u-opacity-[0-100]</code>
			<div class="cerb-u-flex cerb-u-gap-2 cerb-u-items-center cerb-u-flex-wrap"><span class="cerb-uiref-utils--box cerb-u-opacity-25"><span>25</span></span><span class="cerb-uiref-utils--box cerb-u-opacity-50"><span>50</span></span><span class="cerb-uiref-utils--box cerb-u-opacity-75"><span>75</span></span><span class="cerb-uiref-utils--box cerb-u-opacity-100"><span>100</span></span></div>

			<div class="cerb-uiref-utils--note cerb-uiref-utils--full">Steps <code>cerb-u-opacity-0</code> / <code>25</code> / <code>50</code> / <code>75</code> / <code>100</code>. Dim an element to a muted/idle state (e.g. the queue monitor's idle worker tiles), then toggle off (or to <code>opacity-100</code>) to restore. Pair with a CSS <code>transition: opacity</code> for a smooth fade.</div>

			<code class="cerb-uiref-utils--name" data-cerb-uiref-copy data-cerb-uiref-source title="Copy to clipboard">cerb-u-shadow-none</code>
			<div class="cerb-u-flex cerb-u-gap-3 cerb-u-items-center cerb-u-flex-wrap"><kbd class="cerb-ui-kbd">shadow</kbd><kbd class="cerb-ui-kbd cerb-u-shadow-none">shadow-none (reset)</kbd> <span class="cerb-uiref-utils--note">strips a component's inherited box-shadow</span></div>

			<div class="cerb-uiref-utils--full cerb-ui-header--label cerb-u-mt-3">Color · grayscale</div>

			<code class="cerb-uiref-utils--name" data-cerb-uiref-copy data-cerb-uiref-source title="Copy to clipboard">cerb-u-bgg-[1-10]</code>
			<div class="cerb-u-flex cerb-u-gap-3 cerb-u-items-center cerb-u-flex-wrap"><span class="cerb-u-flex cerb-u-items-center cerb-u-gap-1"><span class="cerb-uiref-grayswatch cerb-u-bgg-1"></span>1</span><span class="cerb-u-flex cerb-u-items-center cerb-u-gap-1"><span class="cerb-uiref-grayswatch cerb-u-bgg-2"></span>2</span><span class="cerb-u-flex cerb-u-items-center cerb-u-gap-1"><span class="cerb-uiref-grayswatch cerb-u-bgg-3"></span>3</span><span class="cerb-u-flex cerb-u-items-center cerb-u-gap-1"><span class="cerb-uiref-grayswatch cerb-u-bgg-4"></span>4</span><span class="cerb-u-flex cerb-u-items-center cerb-u-gap-1"><span class="cerb-uiref-grayswatch cerb-u-bgg-5"></span>5</span><span class="cerb-u-flex cerb-u-items-center cerb-u-gap-1"><span class="cerb-uiref-grayswatch cerb-u-bgg-6"></span>6</span><span class="cerb-u-flex cerb-u-items-center cerb-u-gap-1"><span class="cerb-uiref-grayswatch cerb-u-bgg-7"></span>7</span><span class="cerb-u-flex cerb-u-items-center cerb-u-gap-1"><span class="cerb-uiref-grayswatch cerb-u-bgg-8"></span>8</span><span class="cerb-u-flex cerb-u-items-center cerb-u-gap-1"><span class="cerb-uiref-grayswatch cerb-u-bgg-9"></span>9</span><span class="cerb-u-flex cerb-u-items-center cerb-u-gap-1"><span class="cerb-uiref-grayswatch cerb-u-bgg-10"></span>10</span></div>

			<code class="cerb-uiref-utils--name" data-cerb-uiref-copy data-cerb-uiref-source title="Copy to clipboard">cerb-u-fgg-[1-10]</code>
			<div class="cerb-u-flex cerb-u-gap-3 cerb-u-items-center cerb-u-flex-wrap"><span class="cerb-u-fgg-7">fgg-7 muted</span><span class="cerb-u-fgg-8">fgg-8</span><span class="cerb-u-fgg-9">fgg-9</span><span class="cerb-u-fgg-10">fgg-10 strong</span></div>

			<code class="cerb-uiref-utils--name" data-cerb-uiref-copy data-cerb-uiref-source title="Copy to clipboard">cerb-u-bdg-[1-10]</code>
			<div class="cerb-u-flex cerb-u-gap-2 cerb-u-items-center cerb-u-flex-wrap"><span class="cerb-ui-tile cerb-u-bdg-4">bdg-4 (border)</span><span class="cerb-ui-tile cerb-u-bdg-7">bdg-7</span></div>

			<code class="cerb-uiref-utils--name" data-cerb-uiref-copy data-cerb-uiref-source title="Copy to clipboard">cerb-u-bgg-hover</code>
			<div class="cerb-u-flex cerb-u-gap-2 cerb-u-items-center cerb-u-flex-wrap"><span class="cerb-uiref-hoverchip cerb-u-bgg-hover cerb-u-cursor-pointer">transparent → hovers to 240</span><span class="cerb-uiref-hoverchip cerb-u-bgg-2 cerb-u-bgg-hover cerb-u-cursor-pointer">bgg-2 → hovers to 230</span></div>

			<div class="cerb-uiref-utils--note cerb-uiref-utils--full">The number is <strong>distance from the page background</strong> (1 = subtlest / nearest bg, 10 = max contrast) — the <em>same</em> in light and dark, no mental inversion. The prefix picks the property: <code>cerb-u-bgg-</code> background, <code>cerb-u-fgg-</code> text, <code>cerb-u-bdg-</code> border-color (all share the ladder). Common anchors: <strong>4</strong> = default border, <strong>7</strong> = muted text, <strong>2</strong> = hover/fill. <code>cerb-u-bgg-hover</code> is one opt-in class: it hovers to the standard gray on a bare element, or one tier past the element's own <code>cerb-u-bgg-N</code>. These alias the hand-tuned <code>--cerb-color-background-contrast-*</code> theme vars.</div>

			<div class="cerb-uiref-utils--full cerb-ui-header--label cerb-u-mt-3">Position &amp; cursor</div>

			<code class="cerb-uiref-utils--name" data-cerb-uiref-copy data-cerb-uiref-source title="Copy to clipboard">cerb-u-relative</code>
			<div class="cerb-u-relative" style="border:1px dashed var(--cerb-color-background-contrast-200);height:2.6em;"><span class="cerb-uiref-utils--note" style="position:absolute;right:0.4em;bottom:0.3em;">positioning context for an absolute child</span></div>

			<code class="cerb-uiref-utils--name" data-cerb-uiref-copy data-cerb-uiref-source title="Copy to clipboard">cerb-u-cursor-pointer</code>
			<div class="cerb-u-flex cerb-u-gap-2 cerb-u-items-center cerb-u-flex-wrap"><span class="cerb-u-cursor-pointer cerb-uiref-utils--note">pointer</span><span class="cerb-u-cursor-grab cerb-uiref-utils--note">grab</span><span class="cerb-u-cursor-move cerb-uiref-utils--note">move</span><span class="cerb-u-cursor-not-allowed cerb-uiref-utils--note">not-allowed</span><span class="cerb-u-cursor-text cerb-uiref-utils--note">text</span> &mdash; hover each</div>

			<div class="cerb-uiref-utils--note cerb-uiref-utils--full">Cursor set: <code>cerb-u-cursor-pointer</code> / <code>default</code> / <code>move</code> / <code>grab</code> / <code>grabbing</code> / <code>text</code> / <code>not-allowed</code> / <code>crosshair</code>. (Directional <code>*-resize</code> cursors are JS-driven during a drag, so they're not utilities.)</div>

			<code class="cerb-uiref-utils--name" data-cerb-uiref-copy data-cerb-uiref-source title="Copy to clipboard">cerb-u-select-none</code>
			<div><span class="cerb-u-select-none cerb-uiref-utils--note">try to select this text — you can't (good for click-to-toggle affordances)</span></div>
		</div>
	</div>
