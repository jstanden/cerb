{*
 * Read-only cerb-ui meter: an ordinal level drawn as a row of small blocks, filled from the left.
 * No JS -- pairs with the CerbUI meter styling (cerb-ui/_meter.scss).
 *
 * Params:
 *   level  how many blocks are filled (default 0 -- an unrated meter draws every block empty)
 *   of     how many blocks in total (required; nothing renders without it)
 *   color  a palette hue for the filled blocks: red, blue, green, gray, orange, purple (optional --
 *          anything else draws in the component's default gray)
 *   label  tooltip text (optional)
 *}
{$_of = $of|default:0}
{$_level = $level|default:0}
{if $_of > 0}
<span class="cerb-ui-meter{if $color|default:''} cerb-ui-meter--{$color}{/if}"{if $label|default:''} title="{$label}"{/if}>{section name=blocks start=1 loop=$_of+1}<span class="cerb-ui-meter--block{if $smarty.section.blocks.index <= $_level} cerb-ui-meter--block-on{/if}"></span>{/section}</span>
{/if}
