{$reorder_uniqid = uniqid()}
<form id="frmDecisionNodeReorder{$reorder_uniqid}" method="post" class="cerb-ui-form">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="behavior">
<input type="hidden" name="action" value="saveDecisionReorderPopup">
{if isset($node)}<input type="hidden" name="id" value="{$node->id}">{/if}
{if isset($trigger)}<input type="hidden" name="trigger_id" value="{$trigger->id}">{/if}
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div class="cerb-ui-header--title-sm">{if !empty($node)}{$node->title}{elseif !empty($trigger)}{$trigger->title}{/if}</div>
	</div>

	{* [TODO] Show a tree excluding the current branch *}
	<div class="container">
		{foreach from=$children item=child}
		{* Resolve the tile's icon glyph, accent color, and kind eyebrow per node type *}
		{if $child->node_type == 'switch'}
			{$child_glyph = 'branch'}{$child_accent = 'var(--cerb-color-tag-blue)'}{$child_kind = 'decision'}
		{elseif $child->node_type == 'loop'}
			{$child_glyph = 'repeat'}{$child_accent = 'var(--cerb-color-tag-purple)'}{$child_kind = 'loop'}
		{elseif $child->node_type == 'subroutine'}
			{$child_glyph = 'function'}{$child_accent = 'var(--cerb-color-tag-cyan)'}{$child_kind = 'subroutine'}
		{elseif $child->node_type == 'action'}
			{$child_glyph = 'zap'}{$child_accent = 'var(--cerb-color-background-contrast-200)'}{$child_kind = 'action'}
		{elseif $child->node_type == 'outcome'}
			{$child_kind = 'outcome'}
			{if preg_match('#^yes($|,| )#i',$child->title)}
				{$child_glyph = 'check'}{$child_accent = 'var(--cerb-color-tag-green)'}
			{elseif preg_match('#^no($|,| )#i',$child->title)}
				{$child_glyph = 'circle-remove'}{$child_accent = 'var(--cerb-color-tag-red)'}
			{else}
				{$child_glyph = 'signpost'}{$child_accent = 'var(--cerb-color-background-contrast-200)'}
			{/if}
		{else}
			{$child_glyph = 'circle'}{$child_accent = 'var(--cerb-color-background-contrast-200)'}{$child_kind = $child->node_type}
		{/if}

		<div class="item cerb-u-flex cerb-u-items-center cerb-u-gap-2" style="margin:2px;">
			<span class="cerb-icons cerb-icon-move" style="cursor:move;" title="Drag to rearrange"></span>
			<input type="hidden" name="child_id[]" value="{$child->id}">
			<div class="cerb-ui-tile">
				<span class="cerb-ui-tile--icon" style="background:{$child_accent};"><span class="cerb-icons cerb-icon-{$child_glyph}"></span></span>
				<div class="cerb-ui-tile--text">
					<div class="cerb-ui-tile--kind">{$child_kind}</div>
					<div class="cerb-ui-tile--name">{$child->title}</div>
				</div>
			</div>
		</div>
		{/foreach}
	</div>
</div>

<div>
	<button type="button" class="cerb-ui-button cerb-u-anim-group"><span class="cerb-icons cerb-icon-circle-ok cerb-u-anim-pulse-hover"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
</div>
</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#frmDecisionNodeReorder{$reorder_uniqid}');
	let $popup = genericAjaxPopupFind($frm);

	Devblocks.formDisableSubmit($frm);
	
	$popup.one('popup_open', function() {
		$popup.dialog('option','title',"Reorder");

		if(window.CerbUI && CerbUI.Sortable)
			new CerbUI.Sortable($frm.find('div.container').get(0), { items:'div.item' });
		
		$frm.find('button.cerb-ui-button').click(function() {
			genericAjaxPost($frm,'','',function() {
				{if !empty($node)}{$trigger_id = $node->trigger_id}{elseif !empty($trigger)}{$trigger_id = $trigger->id}{/if}
				// Refresh every on-page instance of this behavior's tree (it can appear in multiple widgets/cards).
				$('[data-behavior-tree-id="{$trigger_id}"]').each(function() {
					genericAjaxGet(this.id, 'c=profiles&a=invoke&module=behavior&action=renderDecisionTree&id={$trigger_id}&tree_dom_id=' + encodeURIComponent(this.id));
				});
				genericAjaxPopupDestroy($popup);
			});
		})
	});
});
</script>