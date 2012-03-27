{if is_array($values_to_contexts)}
<b>On:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
<select name="{$namePrefix}[on]" class="on">
	{foreach from=$values_to_contexts item=context_data key=val_key}
	<option value="{$val_key}" context="{$context_data.context}">{$context_data.label}</option>
	{/foreach}
</select>
</div>
{/if}

<b>Unschedule this behavior:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	<select class="behavior_defaults" style="display:none;visibility:hidden;">
	{foreach from=$macros item=macro key=macro_id}
		<option value="{$macro_id}" context="{$events_to_contexts.{$macro->event_point}}" {if $params.behavior_id==$macro_id}selected="selected"{/if}>{$macro->title}</option>
	{/foreach}
	</select>
	<select name="{$namePrefix}[behavior_id]" class="behavior">
	{foreach from=$macros item=macro key=macro_id}
		<option value="{$macro_id}" {if $params.behavior_id==$macro_id}selected="selected"{/if}>{$macro->title}</option>
	{/foreach}
	</select>
</div>

<script type="text/javascript">
$action = $('fieldset#{$namePrefix}');
$action.find('select.on').change(function(e) {
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
});
$action.find('select.on').trigger('change');
</script>
