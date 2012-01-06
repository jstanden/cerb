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

<fieldset class="vars">
<legend>Variables</legend>

{foreach from=$trigger->variables key=k item=var}
<div>
	<a href="javascript:;" onclick="$(this).closest('div').remove();"><span class="cerb-sprite2 sprite-minus-circle-frame" style="vertical-align:middle;"></span></a>
	<input type="hidden" name="var_key[]" value="{$var.key}">
	<input type="text" name="var_label[]" value="{$var.label}" size="45">
	<input type="hidden" name="var_type[]" value="{$var.type}">
	{if $var.type == 'S'}
	Text
	{elseif $var.type == 'N'}
	Number
	{elseif $var.type == 'E'}
	Date
	{elseif $var.type == 'C'}
	True/False
	{elseif $var.type == 'W'}
	Worker
	{/if}
</div>
{/foreach}

<div style="display:none;" class="template">
	<a href="javascript:;" onclick="$(this).closest('div').remove();"><span class="cerb-sprite2 sprite-minus-circle-frame" style="vertical-align:middle;"></span></a>
	<input type="hidden" name="var_key[]" value="">
	<input type="text" name="var_label[]" value="" size="45">
	<select name="var_type[]">
		<option value="S">Text</option>
		<option value="N">Number</option>
		<option value="E">Date</option>
		<option value="C">True/False</option>
		<option value="W">Worker</option>
	</select>
</div>

<div style="margin-top:2px;">
	<button type="button" class="add"><span class="cerb-sprite2 sprite-plus-circle-frame" style="verical-align:middle;"></span></button>
</div>

</fieldset>
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
		<button type="button" onclick="genericAjaxPost('frmDecisionBehavior','','c=internal&a=saveDecisionPopup&json=1',function(json) { $popup = genericAjaxPopupFetch('node_trigger'); event = jQuery.Event('trigger_create'); event.trigger_id = json.trigger_id; event.event_point = json.event_point; $popup.trigger(event); genericAjaxPopupDestroy('node_trigger');  });"><span class="cerb-sprite2 sprite-tick-circle-frame"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	{/if}
	{if isset($trigger->id)}<button type="button" onclick="$(this).closest('form').hide().prev('fieldset.delete').show();"><span class="cerb-sprite2 sprite-cross-circle-frame"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('node_trigger{$trigger->id}');
	$popup.one('popup_open', function(event,ui) {
		$(this).dialog('option','title',"{if empty($trigger->id)}New {/if}Behavior");
		$(this).find('input:text').first().focus();
		
		$(this).find('fieldset.vars button.add').click(function() {
			$template = $(this).closest('fieldset').find('div.template');
			$div = $template.clone().removeClass('template').show();
			$div.insertBefore($template);
		});
	});
</script>
