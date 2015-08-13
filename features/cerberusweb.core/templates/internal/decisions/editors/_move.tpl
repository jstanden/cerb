<form id="frmDecisionNodeMove" onsubmit="return false;">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="saveDecisionMovePopup">
{if isset($node)}<input type="hidden" name="id" value="{$node->id}">{/if}
{if isset($trigger)}<input type="hidden" name="trigger_id" value="{$trigger->id}">{/if}
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<fieldset>
	<legend>Move</legend>
	<select name="dir">
		<option value="-1">Before</option>
		<option value="1">After</option>
		<option value="-100">First</option>
		<option value="100">Last</option>
	</select>
	<br>
	
	{* [TODO] Show a tree excluding the current branch *}
	<select name="">
		<option value=""></option>
	</select>
	
</fieldset>

<button type="button" onclick="genericAjaxPost('frmDecisionNodeMove','','',function() { window.location.reload(); });"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
</form>

<script type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFetch('peek');
	
	$popup.one('popup_open', function(event,ui) {
		$(this).dialog('option','title',"Move {$node->node_type|capitalize|escape:'javascript' nofilter}");
	});
});
</script>