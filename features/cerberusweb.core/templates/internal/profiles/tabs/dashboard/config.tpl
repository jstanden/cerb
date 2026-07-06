<div class="cerb-ui-panel cerb-ui-panel--spaced" id="tab{$tab->id}Config" style="margin-top:10px;">
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div class="cerb-ui-header--title-sm">{'common.layout'|devblocks_translate|capitalize}</div>
	</div>

	<div class="cerb-u-flex cerb-u-flex-wrap cerb-u-gap-2">
		<label class="cerb-layout-choice">
			<input type="radio" name="params[layout]" value="" {if empty($tab->extension_params.layout)}checked="checked"{/if}>
			<svg width="100" height="80">
				<g style="fill:var(--cerb-color-background-contrast-220);stroke:var(--cerb-color-background-contrast-180);stroke-width:1">
					<rect x="1" y="1" width="98" height="78" />
				</g>
				<g style="fill:var(--cerb-color-background-contrast-190);stroke:var(--cerb-color-background-contrast-180);stroke-width:1">
					<rect x="5" y="5" width="90" height="70" />
				</g>
			</svg>
		</label>

		<label class="cerb-layout-choice">
			<input type="radio" name="params[layout]" value="sidebar_left" {if 'sidebar_left' == $tab->extension_params.layout}checked="checked"{/if}>
			<svg width="100" height="80">
				<g style="fill:var(--cerb-color-background-contrast-220);stroke:var(--cerb-color-background-contrast-180);stroke-width:1">
					<rect x="1" y="1" width="98" height="78" />
				</g>
				<g style="fill:var(--cerb-color-background-contrast-190);stroke:var(--cerb-color-background-contrast-180);stroke-width:1">
					<rect x="5" y="5" width="30" height="70" />
					<rect x="40" y="5" width="55" height="70" />
				</g>
			</svg>
		</label>

		<label class="cerb-layout-choice">
			<input type="radio" name="params[layout]" value="sidebar_right" {if 'sidebar_right' == $tab->extension_params.layout}checked="checked"{/if}>
			<svg width="100" height="80">
				<g style="fill:var(--cerb-color-background-contrast-220);stroke:var(--cerb-color-background-contrast-180);stroke-width:1">
					<rect x="1" y="1" width="98" height="78" />
				</g>
				<g style="fill:var(--cerb-color-background-contrast-190);stroke:var(--cerb-color-background-contrast-180);stroke-width:1">
					<rect x="5" y="5" width="55" height="70" />
					<rect x="65" y="5" width="30" height="70" />
				</g>
			</svg>
		</label>

		<label class="cerb-layout-choice">
			<input type="radio" name="params[layout]" value="halves" {if 'halves' == $tab->extension_params.layout}checked="checked"{/if}>
			<svg width="100" height="80">
				<g style="fill:var(--cerb-color-background-contrast-220);stroke:var(--cerb-color-background-contrast-180);stroke-width:1">
					<rect x="1" y="1" width="98" height="78" />
				</g>
				<g style="fill:var(--cerb-color-background-contrast-190);stroke:var(--cerb-color-background-contrast-180);stroke-width:1">
					<rect x="5" y="5" width="42" height="70" />
					<rect x="53" y="5" width="42" height="70" />
				</g>
			</svg>
		</label>

		<label class="cerb-layout-choice">
			<input type="radio" name="params[layout]" value="thirds" {if 'thirds' == $tab->extension_params.layout}checked="checked"{/if}>
			<svg width="100" height="80">
				<g style="fill:var(--cerb-color-background-contrast-220);stroke:var(--cerb-color-background-contrast-180);stroke-width:1">
					<rect x="1" y="1" width="98" height="78" />
				</g>
				<g style="fill:var(--cerb-color-background-contrast-190);stroke:var(--cerb-color-background-contrast-180);stroke-width:1">
					<rect x="4" y="5" width="28" height="70" />
					<rect x="36" y="5" width="28" height="70" />
					<rect x="68" y="5" width="28" height="70" />
				</g>
			</svg>
		</label>
	</div>
</div>
