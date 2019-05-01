<b>{'common.message'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	<textarea name="{$namePrefix}[message]" rows="3" cols="45" style="width:100%;height:6em;" class="placeholders">{$params.message}</textarea>
</div>

<b>{'common.format'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	<label><input type="radio" name="{$namePrefix}[format]" value="" {if !$params.format}checked="checked"{/if}> Plaintext</label>
	<label><input type="radio" name="{$namePrefix}[format]" value="markdown" {if 'markdown' == $params.format}checked="checked"{/if}> Markdown</label>
	<label><input type="radio" name="{$namePrefix}[format]" value="html" {if 'html' == $params.format}checked="checked"{/if}> HTML</label>
</div>
