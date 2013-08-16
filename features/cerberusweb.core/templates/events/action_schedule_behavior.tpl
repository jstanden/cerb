{if is_array($values_to_contexts)}
<b>On:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
<select name="{$namePrefix}[on]" class="on">
	{foreach from=$values_to_contexts item=context_data key=val_key name=context_data}
	{if $smarty.foreach.context_data.first && empty($params.on)}{$params.on = $val_key}{/if}
	<option value="{$val_key}" context="{$context_data.context}" {if $params.on==$val_key}{$selected_context = $context_data.context}selected="selected"{/if}>{$context_data.label}</option>
	{/foreach}
</select>
</div>
{/if}

<b>Schedule this behavior:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	<select class="behavior_defaults" style="display:none;visibility:hidden;">
	{foreach from=$macros item=macro key=macro_id}
		<option value="{$macro_id}" context="{$events_to_contexts.{$macro->event_point}}" {if $params.behavior_id==$macro_id}selected="selected"{/if}>{$macro->title}</option>
	{/foreach}
	</select>
	<select name="{$namePrefix}[behavior_id]" class="behavior">
	{foreach from=$macros item=macro key=macro_id}
		{if $events_to_contexts.{$macro->event_point} == $selected_context}
		{if empty($params.behavior_id)}{$params.behavior_id=$macro_id}{/if}
		<option value="{$macro_id}" {if $params.behavior_id==$macro_id}selected="selected"{/if}>{$macro->title}</option>
		{/if}
	{/foreach}
	</select>
</div>

<div class="parameters">
{include file="devblocks:cerberusweb.core::events/_action_behavior_params.tpl" params=$params macro_params=$macros.{$params.behavior_id}->variables}
</div>

<b>When should this behavior happen?</b> (default: now)
<div style="margin-left:10px;margin-bottom:0.5em;">
	<input type="text" name="{$namePrefix}[run_date]" value="{if empty($params.run_date)}now{else}{$params.run_date}{/if}" size="45" class="placeholders" style="width:100%;">
	<br>
	<i>e.g. +2 days; next Monday; tomorrow 8am; 5:30pm; Dec 21 2012</i>
</div>

<b>If duplicate behavior is scheduled:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	<label><input type="radio" name="{$namePrefix}[on_dupe]" value="" {if empty($params.on_dupe)}checked="checked"{/if}> Allow multiple occurrences</label><br>
	<label><input type="radio" name="{$namePrefix}[on_dupe]" value="first" {if 'first'==$params.on_dupe}checked="checked"{/if}> Only schedule earliest occurrence</label><br>
	<label><input type="radio" name="{$namePrefix}[on_dupe]" value="last" {if 'last'==$params.on_dupe}checked="checked"{/if}> Only schedule latest occurrence</label><br>
</div>

<script type="text/javascript">
$action = $('fieldset#{$namePrefix}');
$action.find('select.behavior').change(function(e) {
	$div = $(this).closest('fieldset').find('div.parameters');
	genericAjaxGet($div,'c=internal&a=showBehaviorParams&name_prefix={$namePrefix}&trigger_id=' + $(this).val());
});

$action.find('select.on').change(function(e) {
	$div = $(this).closest('fieldset').find('div.parameters');
	$div.html('');
	
	ctx = $(this).find('option:selected').attr('context');

	$sel_behavior = $(this).closest('fieldset').find('select.behavior');
	$sel_behavior.find('option').remove();
	
	$sel_behavior_defaults = $(this).closest('fieldset').find('select.behavior_defaults');
	$sel_behavior_defaults.find('option').each(function() {
		$this = $(this);
		if($this.attr('context') == ctx) {
			$sel_behavior.append($this.clone());
		}
	});
	
	$sel_behavior.trigger('change');
});
</script>
