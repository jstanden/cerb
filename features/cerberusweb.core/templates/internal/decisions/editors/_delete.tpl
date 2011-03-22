<form id="frmDecisionNodeDelete" onsubmit="return false;">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="saveDecisionDeletePopup">
{if isset($node)}<input type="hidden" name="id" value="{$node->id}">{/if}
{if isset($trigger)}<input type="hidden" name="trigger_id" value="{$trigger->id}">{/if}

<fieldset>
	<legend>Are you sure you want to delete this {if !empty($trigger)}trigger{else}{$node->node_type}{/if}?</legend>
	This {if !empty($trigger)}trigger{else}{$node->node_type}{/if} and all its children will be permanently deleted.
</fieldset>

<button type="button" onclick="genericAjaxPost('frmDecisionNodeDelete','','',function() { window.location.reload(); });"><span class="cerb-sprite sprite-forbidden"></span> Yes, permanently delete it!</button>
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open', function(event,ui) {
		$(this).dialog('option','title',"Delete {if !empty($trigger)}Trigger{else}{$node->node_type|capitalize}{/if}");
	});
</script>
