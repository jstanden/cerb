<b>{'message.header.subject'|devblocks_translate|capitalize}:</b><br>
<input type="text" name="{$namePrefix}[subject]" value="{$params.subject}" size="45" style="width:100%;">
<br>

<b>{'common.content'|devblocks_translate|capitalize}:</b>
<div>
	<textarea name="{$namePrefix}[content]" rows="10" cols="45" style="width:100%;" class="placeholders">{$params.content}</textarea>
</div>
<br>
