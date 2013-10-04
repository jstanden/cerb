{if is_array($values_to_contexts)}
<b>On:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
<select name="{$namePrefix}[on]" class="on">
	{foreach from=$values_to_contexts item=context_data key=val_key name=context_data}
	{if $smarty.foreach.context_data.first && empty($params.on)}{$params.on = $val_key}{/if}
	{if !$context_data.is_polymorphic}
	<option value="{$val_key}" context="{$context_data.context}" {if $params.on==$val_key}{$selected_context = $context_data.context}selected="selected"{/if}>{$context_data.label}</option>
	{/if}
	{/foreach}
</select>
</div>
{/if}

<b>Unschedule this behavior:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	<select class="behavior_defaults" style="display:none;visibility:hidden;">
	{foreach from=$macros item=macro key=macro_id}
		{$is_selected = ($params.behavior_id==$macro_id)}
		{if $is_selected || !$macro->is_disabled}
		<option value="{$macro_id}" context="{$events_to_contexts.{$macro->event_point}}" {if $is_selected}selected="selected"{/if}>{$macro->title}</option>
		{/if}
	{/foreach}
	</select>
	<select name="{$namePrefix}[behavior_id]" class="behavior">
	{foreach from=$macros item=macro key=macro_id}
		{if $events_to_contexts.{$macro->event_point} == $selected_context}
		{$is_selected = ($params.behavior_id==$macro_id)}
		{if $is_selected || !$macro->is_disabled}
		<option value="{$macro_id}" {if $is_selected}selected="selected"{/if}>{$macro->title}</option>
		{/if}
		{/if}
	{/foreach}
	</select>
</div>

<script type="text/javascript">
$action = $('fieldset#{$namePrefix}');
$action.find('select.on').change(function(e) {
	var $on = $(this).find('option:selected');
	var ctx = $on.attr('context');

	var $sel_behavior = $(this).closest('fieldset').find('select.behavior');
	$sel_behavior.find('option').remove();
	
	var $sel_behavior_defaults = $(this).closest('fieldset').find('select.behavior_defaults');
	$sel_behavior_defaults.find('option').each(function() {
		var $this = $(this);
		if($this.attr('context') == ctx) {
			$sel_behavior.append($this.clone());
		}
	});
});
</script>
