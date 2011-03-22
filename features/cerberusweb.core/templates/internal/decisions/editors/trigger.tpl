<form id="frmDecisionNodeSwitch" onsubmit="return false;">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="saveDecisionPopup">
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

{*
<fieldset>
	<legend>Determine an outcome based on multiple choices</legend>
	
	A switch-based decision will evaluate multiple choices and choose the first outcome that satisfies all conditions. 
	Each outcome may use different conditions.  For example, you can use a switch to choose from a list: language, 
	time of day, day of week, service level, etc.
</fieldset>
*}

<button type="button" onclick="genericAjaxPost('frmDecisionNodeSwitch','','',function() { window.location.reload(); });"><span class="cerb-sprite sprite-check"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open', function(event,ui) {
		$(this).dialog('option','title',"{if empty($trigger->id)}New {/if}Trigger");
	});
</script>
