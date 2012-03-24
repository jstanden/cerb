{if is_array($trigger->variables)}
<b>On:</b>
<div style="margin-left:10px;">
<select name="{$namePrefix}[on]">
	<option value="">this object</option>
	{foreach from=$trigger->variables item=var_data key=var_key}
	{if substr($var_data.type,0,4) == 'ctx_'}
	<option value="{$var_key}">(variable) {$var_data.label}</option>
	{/if}
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
