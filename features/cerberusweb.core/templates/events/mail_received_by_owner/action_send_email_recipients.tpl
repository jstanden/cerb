<b>{'common.content'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	<textarea name="{$namePrefix}[content]" rows="3" cols="45" style="width:100%;" class="placeholders">{$params.content}</textarea>
</div>

<b>{'message.headers.custom'|devblocks_translate|capitalize}:</b> (one per line, e.g. "X-Precedence: Bulk")
<div style="margin-left:10px;margin-bottom:0.5em;">
	<textarea name="{$namePrefix}[headers]" rows="3" cols="45" style="width:100%;" class="placeholders">{$params.headers}</textarea>
</div>

<label><input type="checkbox" name="{$namePrefix}[is_autoreply]" value="1" {if $params.is_autoreply}checked="checked"{/if}> Don't save a copy of this message in the conversation history.</label>
<br>

<script type="text/javascript">
$action = $('fieldset#{$namePrefix}');
$action.find('textarea').elastic();
</script>
