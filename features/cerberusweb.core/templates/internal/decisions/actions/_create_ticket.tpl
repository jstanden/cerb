<table cellpadding="0" cellspacing="0" width="100%">
	<tr>
		<td>
			<b>{'message.header.to'|devblocks_translate|capitalize}:</b>
		</td>
		<td>
			<select name="{$namePrefix}[group_id]">
				{foreach from=$groups item=group key=group_id}
				<option value="{$group_id}" {if $group_id==$params.group_id}selected="selected"{/if}>{$group->name}</option>
				{/foreach}
			</select>
		</td>
	</tr>
	<tr>
		<td>
			<b>{'ticket.requesters'|devblocks_translate|capitalize}:</b>
		</td>
		<td>
			<input type="text" name="{$namePrefix}[requesters]" value="{$params.requesters}" size="45" style="width:100%;">
		</td>
	</tr>
	<tr>
		<td>
			<b>{'message.header.subject'|devblocks_translate|capitalize}:</b>
		</td>
		<td>
			<input type="text" name="{$namePrefix}[subject]" value="{$params.subject}" size="45" style="width:100%;">
		</td>
	</tr>
</table>
<br>

<b>{'common.content'|devblocks_translate|capitalize}:</b><br>
<textarea name="{$namePrefix}[content]" rows="10" cols="45" style="width:100%;">{$params.content}</textarea>

<button type="button" onclick="genericAjaxPost($(this).closest('form').attr('id'),$(this).nextAll('div.tester').first(),'c=internal&a=testDecisionEventSnippets&prefix={$namePrefix}&field=content');">{'common.test'|devblocks_translate|capitalize}</button>
<select onchange="$field=$(this).siblings('textarea');$field.focus().insertAtCursor($(this).val());$(this).val('');">
	<option value="">-- insert at cursor --</option>
	{foreach from=$token_labels key=k item=v}
	<option value="{literal}{{{/literal}{$k}{literal}}}{/literal}">{$v}</option>
	{/foreach}
</select>
<div class="tester"></div>
<br>
