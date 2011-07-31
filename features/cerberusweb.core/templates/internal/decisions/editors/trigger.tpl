<form id="frmDecisionBehavior{$trigger->id}" onsubmit="return false;">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="">
<input type="hidden" name="trigger_id" value="{if isset($trigger->id)}{$trigger->id}{else}0{/if}">
{if empty($trigger->id)}
<input type="hidden" name="context" value="{$context}">
<input type="hidden" name="context_id" value="{$context_id}">
{/if}

<b>{'common.title'|devblocks_translate|capitalize}:</b><br>
<input type="text" name="title" value="{$trigger->title}" style="width:100%;"><br>
<br>

<b>{'common.event'|devblocks_translate|capitalize}:</b><br>
{if empty($ext)}
	<select name="event_point">
		{foreach from=$events item=event key=event_id}
		<option value="{$event_id}">{$event->name}</option>
		{/foreach}
	</select>
	<br>
{else}
	{$ext->name}<br>
{/if}
<br>

<b>{'common.status'|devblocks_translate|capitalize}:</b><br>
<label><input type="radio" name="is_disabled" value="0" {if empty($trigger->is_disabled)}checked="checked"{/if}> {'common.enabled'|devblocks_translate|capitalize}</label>
<label><input type="radio" name="is_disabled" value="1" {if !empty($trigger->is_disabled)}checked="checked"{/if}> {'common.disabled'|devblocks_translate|capitalize}</label>
<br>
<br>
</form>

{if isset($trigger->id)}
<fieldset class="delete" style="display:none;">
	<legend>Delete this trigger?</legend>
	<p>Are you sure you want to permanently delete this behavior and all its effects?</p>
	<button type="button" class="green" onclick="genericAjaxPost('frmDecisionBehavior{$trigger->id}','','c=internal&a=saveDecisionDeletePopup',function() { genericAjaxPopupDestroy('node_trigger{$trigger->id}'); genericAjaxGet('decisionTree{$trigger->id}','c=internal&a=showDecisionTree&id={$trigger->id}'); });"> {'common.yes'|devblocks_translate|capitalize}</button>
	<button type="button" class="red" onclick="$(this).closest('fieldset').hide().next('form.toolbar').show();"> {'common.no'|devblocks_translate|capitalize}</button>
</fieldset>
{/if}

<form class="toolbar">
	{if !empty($trigger->id)}
		<button type="button" onclick="genericAjaxPost('frmDecisionBehavior{$trigger->id}','','c=internal&a=saveDecisionPopup',function() { genericAjaxPopupDestroy('node_trigger{$trigger->id}'); genericAjaxGet('decisionTree{$trigger->id}','c=internal&a=showDecisionTree&id={$trigger->id}'); });"><span class="cerb-sprite2 sprite-tick-circle-frame"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	{else}
		<button type="button" onclick="genericAjaxPost('frmDecisionBehavior','','c=internal&a=saveDecisionPopup&json=1',function(json) { $popup = genericAjaxPopupFetch('node_trigger'); event = jQuery.Event('trigger_create'); event.trigger_id = json.trigger_id; $popup.trigger(event); genericAjaxPopupDestroy('node_trigger');  });"><span class="cerb-sprite2 sprite-tick-circle-frame"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	{/if}
	{if isset($trigger->id)}<button type="button" onclick="$(this).closest('form').hide().prev('fieldset.delete').show();"><span class="cerb-sprite2 sprite-cross-circle-frame"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('node_trigger{$trigger->id}');
	$popup.one('popup_open', function(event,ui) {
		$(this).dialog('option','title',"{if empty($trigger->id)}New {/if}Behavior");
		$(this).find('input:text').first().focus();
	});
</script>
