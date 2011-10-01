<b>Replace:</b>
<label><input type="checkbox" name="{$namePrefix}[is_regexp]" value="1" {if !empty($params.is_regexp)}checked="checked"{/if}> Regular expression</label> [<a href="http://us2.php.net/manual/en/pcre.pattern.php" target="_blank">?</a>]
<br>
<textarea name="{$namePrefix}[replace]" rows="5" cols="45" style="width:100%;">{$params.replace}</textarea>
<br>

<b>With:</b><br>
<textarea name="{$namePrefix}[with]" rows="5" cols="45" style="width:100%;">{$params.with}</textarea>
<br>

<button type="button" onclick="genericAjaxPost($(this).closest('form').attr('id'),$(this).nextAll('div.tester').first(),'c=internal&a=testDecisionEventSnippets&prefix={$namePrefix}&field=with');">{'common.test'|devblocks_translate|capitalize}</button>
<select onchange="$field=$(this).siblings('textarea:nth(1)');$field.focus().insertAtCursor($(this).val());$(this).val('');">
	<option value="">-- insert at cursor --</option>
	{foreach from=$token_labels key=k item=v}
	<option value="{literal}{{{/literal}{$k}{literal}}}{/literal}">{$v}</option>
	{/foreach}
</select>
<div class="tester"></div>
<br>
