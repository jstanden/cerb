	<div class="cerb-uiref-component" id="palette">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-color-palette"></span>Palette</div>

		{* Example: the six tag hues -- the whole named-color vocabulary of the design system *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Six named hues. <strong>Theme-invariant</strong> &mdash; unlike the grays, these are the same value in light and dark, so a hue means one thing everywhere</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-u-flex cerb-u-gap-4 cerb-u-items-center cerb-u-flex-wrap">
					<span class="cerb-u-flex cerb-u-items-center cerb-u-gap-2"><span class="cerb-uiref-grayswatch" style="background:var(--cerb-color-tag-red);"></span><code>red</code></span>
					<span class="cerb-u-flex cerb-u-items-center cerb-u-gap-2"><span class="cerb-uiref-grayswatch" style="background:var(--cerb-color-tag-blue);"></span><code>blue</code></span>
					<span class="cerb-u-flex cerb-u-items-center cerb-u-gap-2"><span class="cerb-uiref-grayswatch" style="background:var(--cerb-color-tag-green);"></span><code>green</code></span>
					<span class="cerb-u-flex cerb-u-items-center cerb-u-gap-2"><span class="cerb-uiref-grayswatch" style="background:var(--cerb-color-tag-gray);"></span><code>gray</code></span>
					<span class="cerb-u-flex cerb-u-items-center cerb-u-gap-2"><span class="cerb-uiref-grayswatch" style="background:var(--cerb-color-tag-orange);"></span><code>orange</code></span>
					<span class="cerb-u-flex cerb-u-items-center cerb-u-gap-2"><span class="cerb-uiref-grayswatch" style="background:var(--cerb-color-tag-purple);"></span><code>purple</code></span>
				</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}var(--cerb-color-tag-red)      var(--cerb-color-tag-gray)
var(--cerb-color-tag-blue)     var(--cerb-color-tag-orange)
var(--cerb-color-tag-green)    var(--cerb-color-tag-purple)

/* Defined once, in cerb.css/theme/cerb-theme.scss. The .dark block does NOT
   override them -- that's deliberate, so "the green one" is the same swatch
   in either theme. The GRAYS are the ones that flip; see Utilities. */{/literal}</pre>
			</div>
		</div>

		{* Example: the same hue across every component that accepts one *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Every component that takes a hue takes it the same way &mdash; a <code>--&lt;hue&gt;</code> modifier on the block. One hue, six components</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-u-flex cerb-u-gap-4 cerb-u-items-center cerb-u-flex-wrap">
					<span class="cerb-u-flex cerb-u-items-center cerb-u-gap-2"><span class="cerb-ui-pip cerb-ui-pip--purple"></span> <code class="cerb-u-fs-n1">pip</code></span>
					<span class="cerb-u-flex cerb-u-items-center cerb-u-gap-2"><span class="cerb-ui-pill cerb-ui-pill--purple">pill</span></span>
					<span class="cerb-u-flex cerb-u-items-center cerb-u-gap-2"><span class="cerb-ui-meter cerb-ui-meter--purple"><span class="cerb-ui-meter--block cerb-ui-meter--block-on"></span><span class="cerb-ui-meter--block cerb-ui-meter--block-on"></span><span class="cerb-ui-meter--block cerb-ui-meter--block-on"></span><span class="cerb-ui-meter--block"></span></span> <code class="cerb-u-fs-n1">meter</code></span>
					<span class="cerb-u-flex cerb-u-items-center cerb-u-gap-2"><span class="cerb-ui-rating cerb-ui-rating--purple"><button type="button" class="cerb-ui-rating--on"><span class="cerb-icons cerb-icon-star"></span></button><button type="button" class="cerb-ui-rating--on"><span class="cerb-icons cerb-icon-star"></span></button><button type="button"><span class="cerb-icons cerb-icon-star"></span></button></span> <code class="cerb-u-fs-n1">rating</code></span>
					<label class="cerb-ui-toggle cerb-ui-toggle--purple"><input type="checkbox" checked><span class="cerb-ui-toggle--slider"></span></label>
					<div class="cerb-ui-chip cerb-ui-chip--purple"><div class="cerb-ui-chip--head">chip</div><div><div class="cerb-ui-chip--label">head</div><div class="cerb-ui-chip--value">tinted</div></div></div>
				</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}&lt;span class="cerb-ui-pip  cerb-ui-pip--purple"&gt;&lt;/span&gt;
&lt;span class="cerb-ui-pill cerb-ui-pill--purple"&gt;…&lt;/span&gt;
&lt;label class="cerb-ui-toggle cerb-ui-toggle--purple"&gt;…&lt;/label&gt;

&lt;!-- What a hue PAINTS is the component's call, not the caller's: --&gt;
  Pip      the dot, and its --live ping ring (currentColor carries it)
  Pill     fill + border, with white text
  Meter    the filled blocks
  Rating   the --on / --preview glyphs
  Toggle   the CHECKED slider only -- off stays neutral, or you lose the state read
  Chip     the frame + --head cell; the data cells stay neutral{/literal}</pre>
			</div>
		</div>

		{* Example: adding a seventh adopter, + the one case that must NOT use a class *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Giving a new component the same vocabulary</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-uiref-utils--note">A component exposes <strong>one</strong> hue hook and lets the consumer name the hue &mdash; it never enumerates the six itself, and it never learns a domain vocabulary (there is no <code>--intelligence</code>; that mapping belongs on the record that has rating axes).</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}// cerb-ui/_widget.scss -- read the hook, keep the neutral default in the fallback
.cerb-ui-widget { border-color: var(--cerb-ui-widget-color, var(--cerb-color-background-contrast-220)); }

// ...then emit the six modifiers from the shared generator (cerb-ui/_palette.scss)
@include cerb-tag-color-modifiers(".cerb-ui-widget--", "--cerb-ui-widget-color");

// An optional content block rides EVERY hue rule -- for what isn't the hue itself
// (a filled component's white text), and it can target a child:
@include cerb-tag-color-modifiers(".cerb-ui-widget--", "--cerb-ui-widget-color") {
	.cerb-ui-widget--head { color: rgb(255,255,255); }
}

// $prop is usually the component's custom property -- but not always. The pip's dot
// is already currentColor, so plain `color` IS its hook:
@include cerb-tag-color-modifiers(".cerb-ui-pip--", "color");

// GOTCHAS
// - Quote hue names in $cerb-tag-hues: bare `red`/`purple` are Sass COLOR VALUES,
//   and interpolating one emits the CSS keyword (invalid in a custom-property name).
// - If the default is a real declaration INSIDE the block (not a var() fallback),
//   the @include must come after it -- same 0,1,0 specificity, source order decides.

// NOT for a color that is DATA. Calendar/datepicker pips and the node-editor legend
// carry arbitrary hex from records; those stay inline. A class family is for a palette
// CHOICE, which is why agent_model.icon_color is free-form hex and not a hue name.
&lt;span class="cerb-ui-pip" style="color:#8a3ffc;"&gt;&lt;/span&gt;{/literal}</pre>
			</div>
		</div>
	</div>
