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

<b>{'common.content'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;">
	<textarea name="{$namePrefix}[content]" rows="3" cols="45" style="width:100%;" class="placeholders">{$params.content}</textarea>
</div>

<div style="margin-top:5px;">
<b>{'common.notify_workers'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;">
	<button type="button" class="chooser_notify_workers unbound"><span class="cerb-sprite sprite-view"></span></button>
	<ul class="chooser-container bubbles" style="display:block;">
	{include file="devblocks:cerberusweb.core::internal/decisions/actions/_shared_var_worker_bubbles.tpl" checkbox_name="[notify_worker_id][]" param_value=$params.notify_worker_id trigger=$trigger}
	
	{if isset($params.notify_worker_id)}
	{foreach from=$params.notify_worker_id item=worker_id}
		{if is_numeric($worker_id) && isset($workers.$worker_id)}
			{$context_worker = $workers.$worker_id}
			{if !empty($context_worker)}
			<li>{$context_worker->getName()}<input type="hidden" name="{$namePrefix}[notify_worker_id][]" value="{$context_worker->id}"><a href="javascript:;" onclick="$(this).parent().remove();"><span class="ui-icon ui-icon-trash" style="display:inline-block;width:14px;height:14px;"></span></a></li>
			{/if}
		{/if}
	{/foreach}
	{/if}
	</ul>
</div>
</div>

<script type="text/javascript">
$action = $('fieldset#{$namePrefix}');
$action.find('textarea').elastic();
</script>