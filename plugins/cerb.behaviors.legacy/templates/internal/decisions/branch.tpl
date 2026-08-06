{$node = $nodes[$node_id]}

{* Resolve the tile's icon glyph, accent color, and kind eyebrow per node type *}
{if $node->node_type == 'switch'}
	{$node_glyph = 'branch'}{$node_accent = 'var(--cerb-color-tag-blue)'}{$node_kind = 'decision'}
{elseif $node->node_type == 'loop'}
	{$node_glyph = 'repeat'}{$node_accent = 'var(--cerb-color-tag-purple)'}{$node_kind = 'loop'}
{elseif $node->node_type == 'subroutine'}
	{$node_glyph = 'function'}{$node_accent = 'var(--cerb-color-tag-cyan)'}{$node_kind = 'subroutine'}
{elseif $node->node_type == 'action'}
	{$node_glyph = 'zap'}{$node_accent = 'var(--cerb-color-background-contrast-200)'}{$node_kind = 'action'}
{elseif $node->node_type == 'outcome'}
	{$node_kind = 'outcome'}
	{if preg_match('#^yes($|,| )#i',$node->title)}
		{$node_glyph = 'check'}{$node_accent = 'var(--cerb-color-tag-green)'}
	{elseif preg_match('#^no($|,| )#i',$node->title)}
		{$node_glyph = 'remove'}{$node_accent = 'var(--cerb-color-tag-red)'}
	{else}
		{$node_glyph = 'signpost'}{$node_accent = 'var(--cerb-color-background-contrast-200)'}
	{/if}
{else}
	{$node_glyph = 'circle'}{$node_accent = 'var(--cerb-color-background-contrast-200)'}{$node_kind = $node->node_type}
{/if}

{* Label *}
<div class="node {$node->node_type}" {if $node->status_id}style="opacity:0.5;"{/if}>
	<input type="hidden" name="node_id" value="{$node_id}">

	<div class="cerb-ui-tile cerb-behavior-node" data-node-id="{$node->id}" data-trigger-id="{$trigger_id}">
		<span class="cerb-ui-tile--icon" style="background:{$node_accent};"><span class="cerb-icons cerb-icon-{$node_glyph}"></span></span>
		<div class="cerb-ui-tile--text">
			<div class="cerb-ui-tile--kind">{$node_kind}</div>
			<div class="cerb-ui-tile--name">{$node->title}</div>
		</div>
		{if $is_writeable}<span class="cerb-icons cerb-icon-chevron-down cerb-ui-tile--caret"></span>{/if}
	</div>

	{* Recurse Children *}
	<div class="branch {$node->node_type}" style="padding-bottom:2px;margin-left:10px;padding-left:10px;{if $node->node_type == 'outcome'}border-left:1px solid rgb(200,200,200);{/if}">
	{if is_array($tree[$node_id]) && !empty($tree[$node_id])}
		{foreach from=$tree[$node_id] item=child_id}
			{include file="devblocks:cerb.behaviors.legacy::internal/decisions/branch.tpl" node_id=$child_id trigger_id=$trigger_id nodes=$nodes tree=$tree depths=$depths is_writeable=$is_writeable}
		{/foreach}
	{/if}
	</div>
</div>


