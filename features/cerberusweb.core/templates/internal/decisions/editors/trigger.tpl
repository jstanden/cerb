<form id="frmDecisionBehavior" onsubmit="return false;">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="">
{if isset($trigger->id)}<input type="hidden" name="trigger_id" value="{$trigger->id}">{/if}

<b>{'common.title'|devblocks_translate|capitalize}:</b><br>
<input type="text" name="title" value="{$trigger->title}" style="width:100%;"><br>
<br>

<b>{'common.event'|devblocks_translate|capitalize}:</b><br>
{$ext->name}<br>
<br>

<b>{'common.status'|devblocks_translate|capitalize}:</b><br>
<label><input type="radio" name="is_disabled" value="0" {if empty($trigger->is_disabled)}checked="checked"{/if}> {'common.enabled'|devblocks_translate|capitalize}</label>
<label><input type="radio" name="is_disabled" value="1" {if !empty($trigger->is_disabled)}checked="checked"{/if}> {'common.disabled'|devblocks_translate|capitalize}</label>
<br>
<br>
</form>

{if isset($trigger->id)}
<fieldset class="delete" style="display:none;">
	<legend>Delete this action?</legend>
	<p>Are you sure you want to permanently delete this action?</p>
	<button type="button" class="green" onclick="genericAjaxPost('frmDecisionBehavior','','c=internal&a=saveDecisionDeletePopup',function() { window.location.reload(); });"> {'common.yes'|devblocks_translate|capitalize}</button>
	<button type="button" class="red" onclick="$(this).closest('fieldset').hide().next('form.toolbar').show();"> {'common.no'|devblocks_translate|capitalize}</button>
</fieldset>
{/if}

<form class="toolbar">
	<button type="button" onclick="genericAjaxPost('frmDecisionBehavior','','c=internal&a=saveDecisionPopup',function() { window.location.reload(); });"><span class="cerb-sprite2 sprite-tick-circle-frame"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	{if isset($trigger->id)}<button type="button" onclick="$(this).closest('form').hide().prev('fieldset.delete').show();"><span class="cerb-sprite2 sprite-cross-circle-frame"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open', function(event,ui) {
		$(this).dialog('option','title',"{if empty($trigger->id)}New {/if}Behavior");
	});
</script>
