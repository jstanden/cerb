{if !empty($values_to_contexts)}
<b>On:</b>
<div style="margin-left:10px;">
<select name="{$namePrefix}[on]">
	{foreach from=$values_to_contexts item=context_data key=val_key}
	<option value="{$val_key}" context="{$context_data.context}">{$context_data.label}</option>
	{/foreach}
</select>
</div>
{/if}

<b>{'common.title'|devblocks_translate|capitalize}:</b><br>
<div style="margin-left:10px;">
	<input type="text" name="{$namePrefix}[title]" size="45" value="{$params.title}" style="width:100%;" class="placeholders">
</div>

<b>{'task.due_date'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;">
	<input type="text" name="{$namePrefix}[due_date]" size="45" value="{$params.due_date}" class="input_date placeholders">
</div>

<b>{'common.comment'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;">
	<textarea name="{$namePrefix}[comment]" cols="45" rows="5" style="width:100%;" class="placeholders">{$params.comment}</textarea>
</div>

<b>{'common.notify_workers'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	{include file="devblocks:cerberusweb.core::internal/decisions/actions/_shared_var_worker_picker.tpl" param_name="notify_worker_id" values_to_contexts=$values_to_contexts}
</div>

<b>{'common.watchers'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	{include file="devblocks:cerberusweb.core::internal/decisions/actions/_shared_var_worker_picker.tpl" param_name="worker_id" values_to_contexts=$values_to_contexts}
</div>

<script type="text/javascript">
$action = $('fieldset#{$namePrefix}');
$action.find('textarea').elastic();
</script>