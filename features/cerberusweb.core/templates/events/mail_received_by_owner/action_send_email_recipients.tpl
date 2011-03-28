<b>{'common.content'|devblocks_translate|capitalize}:</b><br>
<textarea name="{$namePrefix}[content]" rows="10" cols="45" style="width:100%;">{$params.content}</textarea>
<br>

<select onchange="$field=$(this).siblings('textarea');$field.focus().insertAtCursor($(this).val());$(this).val('');">
	<option value="">-- insert at cursor --</option>
	{foreach from=$token_labels key=k item=v}
	<option value="{literal}{{{/literal}{$k}{literal}}}{/literal}">{$v}</option>
	{/foreach}
</select>
<br>
<br>

<label><input type="checkbox" name="{$namePrefix}[is_autoreply]" value="1" {if $params.is_autoreply}checked="checked"{/if}> Don't save a copy of this message in the conversation history.</label>
<br>

