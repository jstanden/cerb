<table cellpadding="0" cellspacing="5" border="0" width="100%">
	<tr>
		<td align="right">
			<b>{'common.title'|devblocks_translate|capitalize}:</b>
		</td>
		<td>
			<input type="text" name="{$namePrefix}[title]" size="45" value="{$params.title}" style="width:100%;">
		</td>
	</tr>
	<tr>
		<td align="right">
			<b>{'task.due_date'|devblocks_translate|capitalize}:</b>
		</td>
		<td>
			<input type="text" name="{$namePrefix}[due_date]" size="45" value="{$params.due_date}" class="input_date">
		</td>
	</tr>
	<tr>
		<td align="right" valign="top">
			<b>{'common.watchers'|devblocks_translate|capitalize}:</b>
		</td>
		<td>
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
		</td>
	</tr>
	<tr>
		<td align="right" valign="top">
			<b>{'common.comment'|devblocks_translate|capitalize}:</b>
		</td>
		<td>
			<textarea name="{$namePrefix}[comment]" cols="45" rows="5" style="width:100%;">{$params.comment}</textarea><br>
			<button type="button" onclick="genericAjaxPost($(this).closest('form').attr('id'),$(this).nextAll('div.tester').first(),'c=internal&a=testDecisionEventSnippets&prefix={$namePrefix}&field=comment');">{'common.test'|devblocks_translate|capitalize}</button>
			<select onchange="$field=$(this).siblings('textarea');$field.focus().insertAtCursor($(this).val());$(this).val('');">
				<option value="">-- insert at cursor --</option>
				{foreach from=$token_labels key=k item=v}
				<option value="{literal}{{{/literal}{$k}{literal}}}{/literal}">{$v}</option>
				{/foreach}
			</select>
			<div class="tester"></div>
		</td>
	</tr>
	<tr>
		<td align="right" valign="top">
			<b>{'common.notify_workers'|devblocks_translate|capitalize}:</b>
		</td>
		<td>
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
		</td>
	</tr>
	{* [TODO] Custom Fields *}
</table>
