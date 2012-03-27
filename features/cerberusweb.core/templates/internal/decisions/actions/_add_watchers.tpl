{if !empty($values_to_contexts)}
<b>On:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
<select name="{$namePrefix}[on]">
	{foreach from=$values_to_contexts item=context_data key=val_key}
	<option value="{$val_key}" context="{$context_data.context}">{$context_data.label}</option>
	{/foreach}
</select>
</div>
{/if}

<b>Add these watchers:</b>

<div style="margin-left:10px;">
	<button type="button" class="chooser_worker unbound"><span class="cerb-sprite sprite-view"></span></button>
	<ul class="chooser-container bubbles" style="display:block;">
		{include file="devblocks:cerberusweb.core::internal/decisions/actions/_shared_var_worker_bubbles.tpl" checkbox_name="[worker_id][]" param_value=$params.worker_id trigger=$trigger}
	
		{if isset($params.worker_id)}
		{foreach from=$params.worker_id item=worker_id}
			{$context_worker = $workers.$worker_id}
			{if !empty($context_worker)}
			<li>{$context_worker->getName()}<input type="hidden" name="{$namePrefix}[worker_id][]" value="{$context_worker->id}"><a href="javascript:;" onclick="$(this).parent().remove();"><span class="ui-icon ui-icon-trash" style="display:inline-block;width:14px;height:14px;"></span></a></li>
			{/if}
		{/foreach}
		{/if}
	</ul>
</div>
