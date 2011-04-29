<form id="frmDecisionNodeReorder" onsubmit="return false;">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="saveDecisionReorderPopup">
{if isset($node)}<input type="hidden" name="id" value="{$node->id}">{/if}
{if isset($trigger)}<input type="hidden" name="trigger_id" value="{$trigger->id}">{/if}

<fieldset>
	<legend>{if !empty($node)}{$node->title}{elseif !empty($trigger)}{$trigger->event_point}{/if}</legend>
	
	{* [TODO] Show a tree excluding the current branch *}
	<div class="container">
		{foreach from=$children item=child}
		<div class="item" style="margin:2px;">
			<span class="ui-icon ui-icon-arrowthick-2-n-s" style="display:inline-block;vertical-align:middle;"></span>
			<div class="badge badge-lightgray">
				<input type="hidden" name="child_id[]" value="{$child->id}">
				<b>{$child->title}</b> 
			</div>
		</div>
		{/foreach}
	</div>
	
</fieldset>
{if !empty($node)}{$trigger_id = $node->trigger_id}{elseif !empty($trigger)}{$trigger_id = $trigger->id}{/if}
<button type="button" onclick="genericAjaxPost('frmDecisionNodeReorder','','',function() { genericAjaxPopupDestroy('peek');genericAjaxGet('decisionTree{$trigger_id}','c=internal&a=showDecisionTree&id={$trigger_id}'); });"><span class="cerb-sprite2 sprite-tick-circle-frame"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open', function(event,ui) {
		$(this).dialog('option','title',"Reorder");

		$('#frmDecisionNodeReorder DIV.container').sortable({ items:'DIV.item', placeholder:'ui-state-highlight' });
	});
</script>
