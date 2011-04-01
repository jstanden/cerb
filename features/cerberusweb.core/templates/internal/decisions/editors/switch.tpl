<form id="frmDecisionNodeSwitch" onsubmit="return false;">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="saveDecisionPopup">
{if isset($id)}<input type="hidden" name="id" value="{$id}">{/if}
{if isset($parent_id)}<input type="hidden" name="parent_id" value="{$parent_id}">{/if}
{if isset($type)}<input type="hidden" name="type" value="{$type}">{/if}
{if isset($trigger_id)}<input type="hidden" name="trigger_id" value="{$trigger_id}">{/if}

<b>{'common.title'|devblocks_translate|capitalize}:</b><br>
<input type="text" name="title" value="{$model->title}" style="width:100%;"><br>
<br>

<fieldset>
	<legend>Determine an outcome based on multiple choices</legend>
	
	A decision will evaluate multiple choices and choose the first outcome that satisfies all conditions. 
	Each outcome may use different conditions.  For example, you can use a decision to choose from a list: language, 
	time of day, day of week, service level, etc.
</fieldset>

<button type="button" class="green" onclick="genericAjaxPost('frmDecisionNodeSwitch','','',function() { window.location.reload(); });"><span class="cerb-sprite2 sprite-tick-circle-frame"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open', function(event,ui) {
		$(this).dialog('option','title',"{if empty($id)}New {/if}Decision");
	});
</script>
