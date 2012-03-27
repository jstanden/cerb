<b>{'message.header.to'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;">
	<select name="{$namePrefix}[group_id]">
		{foreach from=$groups item=group key=group_id}
		<option value="{$group_id}" {if $group_id==$params.group_id}selected="selected"{/if}>{$group->name}</option>
		{/foreach}
	</select>
</div>

<b>{'ticket.requesters'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;">
	<input type="text" name="{$namePrefix}[requesters]" value="{$params.requesters}" size="45" style="width:100%;" class="placeholders">
</div>

<b>{'message.header.subject'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;">
	<input type="text" name="{$namePrefix}[subject]" value="{$params.subject}" size="45" style="width:100%;" class="placeholders">
</div>

<b>{'common.content'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;">
	<textarea name="{$namePrefix}[content]" rows="3" cols="45" style="width:100%;" class="placeholders">{$params.content}</textarea>
</div>

<b>{'common.watchers'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;">
	<button type="button" class="chooser_worker unbound"><span class="cerb-sprite sprite-view"></span></button>
	<ul class="chooser-container bubbles" style="display:block;">
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

{if !empty($values_to_contexts)}
<b>Link to:</b>
<div style="margin-left:10px;">
<ul class="chooser-container bubbles" style="display:block;">
	{foreach from=$values_to_contexts item=context_data key=val_key}
	<li><label><input type="checkbox" name="{$namePrefix}[link_to][]" value="{$val_key}" {if in_array($val_key, $params.link_to)}checked="checked"{/if}> {$context_data.label}</label></li>
	{/foreach}
</ul>
</div>
{/if}

<script type="text/javascript">
$action = $('fieldset#{$namePrefix}');
$action.find('textarea').elastic();
</script>