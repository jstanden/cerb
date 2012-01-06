<b>{'message.header.to'|devblocks_translate|capitalize}:</b><br>
<input type="text" name="{$namePrefix}[to]" value="{$params.to}" size="45" style="width:100%;"><br>
<br>

<b>{'message.header.subject'|devblocks_translate|capitalize}:</b><br>
<input type="text" name="{$namePrefix}[subject]" value="{$params.subject}" size="45" style="width:100%;"><br>
<br>

<b>{'common.content'|devblocks_translate|capitalize}:</b><br>
<div>
	<textarea name="{$namePrefix}[content]" rows="3" cols="45" style="width:100%;" class="placeholders">{$params.content}</textarea>
</div>

<script type="text/javascript">
$action = $('fieldset#{$namePrefix}');
$action.find('textarea').elastic();
</script>