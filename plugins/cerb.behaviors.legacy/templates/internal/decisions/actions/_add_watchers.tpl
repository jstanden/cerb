{if !empty($values_to_contexts)}
<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">On</label>
	<select name="{$namePrefix}[on]">
		{foreach from=$values_to_contexts item=context_data key=val_key}
		{if $context_data.label}<option value="{$val_key}" context="{$context_data.context}" {if $params.on == $val_key}selected="selected"{/if}>{$context_data.label}</option>{/if}
		{/foreach}
	</select>
</div>
{/if}

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">Add these watchers</label>
	{include file="devblocks:cerb.behaviors.legacy::internal/decisions/actions/_shared_var_worker_picker.tpl" param_name="worker_id" values_to_contexts=$values_to_contexts}
</div>
