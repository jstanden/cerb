<b>{'common.content'|devblocks_translate|capitalize}:</b>
<div>
	<textarea name="{$namePrefix}[content]" rows="10" cols="45" style="width:100%;" class="placeholders">{$params.content}</textarea>
</div>
<br>

<label><input type="checkbox" name="{$namePrefix}[is_autoreply]" value="1" {if $params.is_autoreply}checked="checked"{/if}> Don't save a copy of this message in the conversation history.</label>
<br>

<script type="text/javascript">
$action = $('fieldset#{$namePrefix}');
$action.find('textarea').elastic();
</script>
