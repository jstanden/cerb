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

<b>{'common.watchers'|devblocks_translate|capitalize}:</b>
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

<b>{'common.notify_workers'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;">
	<button type="button" class="chooser_notify_workers unbound"><span class="cerb-sprite sprite-view"></span></button>
	<ul class="chooser-container bubbles" style="display:block;">
	{include file="devblocks:cerberusweb.core::internal/decisions/actions/_shared_var_worker_bubbles.tpl" checkbox_name="[notify_worker_id][]" param_value=$params.notify_worker_id trigger=$trigger}
	{if isset($params.notify_worker_id)}
	{foreach from=$params.notify_worker_id item=worker_id}
		{$context_worker = $workers.$worker_id}
		{if !empty($context_worker)}
		<li>{$context_worker->getName()}<input type="hidden" name="{$namePrefix}[notify_worker_id][]" value="{$context_worker->id}"><a href="javascript:;" onclick="$(this).parent().remove();"><span class="ui-icon ui-icon-trash" style="display:inline-block;width:14px;height:14px;"></span></a></li>
		{/if}
	{/foreach}
	{/if}
	</ul>
</div>

<script type="text/javascript">
$action = $('fieldset#{$namePrefix}');
$action.find('textarea').elastic();
</script>