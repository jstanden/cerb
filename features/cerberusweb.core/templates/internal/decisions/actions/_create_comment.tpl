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

<b>{'common.content'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;">
	<textarea name="{$namePrefix}[content]" rows="3" cols="45" style="width:100%;" class="placeholders">{$params.content}</textarea>
</div>

<div style="margin-top:5px;">
<b>{'common.notify_workers'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;">
	{include file="devblocks:cerberusweb.core::internal/decisions/actions/_shared_var_worker_picker.tpl" param_name="notify_worker_id" values_to_contexts=$values_to_contexts}
</div>
</div>

<script type="text/javascript">
$action = $('fieldset#{$namePrefix}');
$action.find('textarea').elastic();
</script>