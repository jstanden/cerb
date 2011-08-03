<b>{'common.content'|devblocks_translate|capitalize}:</b><br>
<textarea name="{$namePrefix}[content]" rows="5" cols="45" style="width:100%;">{$params.content}</textarea><br>

<button type="button" onclick="genericAjaxPost($(this).closest('form').attr('id'),$(this).nextAll('div.tester').first(),'c=internal&a=testDecisionEventSnippets&prefix={$namePrefix}&field=content');">{'common.test'|devblocks_translate|capitalize}</button>
<select onchange="$field=$(this).siblings('textarea');$field.focus().insertAtCursor($(this).val());$(this).val('');">
	<option value="">-- insert at cursor --</option>
	{foreach from=$token_labels key=k item=v}
	<option value="{literal}{{{/literal}{$k}{literal}}}{/literal}">{$v}</option>
	{/foreach}
</select>
<div class="tester"></div>
<br>

<b>Notify:</b>  
<label><input type="checkbox" name="{$namePrefix}[notify_watchers]" value="1" {if $params.notify_watchers}checked="checked"{/if}> Watchers</label>
<br>
<div style="margin-left:10px;">
	<button type="button" class="chooser_notify_workers unbound"><span class="cerb-sprite sprite-view"></span></button>
	<ul class="chooser-container bubbles" style="display:block;">
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
