{*
 * Read-only cerb-ui slider: a solid colored dot on a track, positioned + colored by value.
 * No JS — pairs with the CerbUI.Slider styling (cerb-ui/_slider.scss).
 *
 * Params:
 *   value        the raw value (required)
 *   min          scale minimum (default 0)
 *   max          scale maximum (default 100)
 *   midpoint     neutral point; below = "low" color, above = "high" color (default 50)
 *   invert       swap low/high colors — below = red, above = green (default false)
 *   tick         show the midpoint marker (default false)
 *   width        host width (default '40px')
 *   track_height track thickness (default '8px')
 *   thumb        dot size (default '10px')
 *}
{$_min = $min|default:0}
{$_max = $max|default:100}
{$_val = $value|default:0}
{$_mid = $midpoint|default:50}
{$_range = (($_max - $_min) != 0) ? ($_max - $_min) : 1}
{$_pct = (($_val - $_min) / $_range) * 100}
{if $_pct < 0}{$_pct = 0}{elseif $_pct > 100}{$_pct = 100}{/if}
<div class="cerb-ui-slider cerb-ui-slider--readonly{if $_val < $_mid} cerb-ui-slider--below{elseif $_val > $_mid} cerb-ui-slider--above{else} cerb-ui-slider--at{/if}{if $invert|default:false} cerb-ui-slider--invert{/if}" style="width:{$width|default:'40px'};--cerb-ui-slider-track-h:{$track_height|default:'8px'};--cerb-ui-slider-thumb:{$thumb|default:'10px'};" title="{$_val}">
	<div class="cerb-ui-slider--track">
		{if $tick|default:false}<span class="cerb-ui-slider--midpoint" style="left:{(($_mid - $_min) / $_range) * 100}%;"></span>{/if}
		<div class="cerb-ui-slider--thumb" style="left:{$_pct}%;"></div>
	</div>
</div>
