<b>Replace:</b>
<label><input type="checkbox" name="{$namePrefix}[is_regexp]" value="1" {if !empty($params.is_regexp)}checked="checked"{/if}> Regular expression</label> [<a href="http://us2.php.net/manual/en/pcre.pattern.php" target="_blank" rel="noopener noreferrer">?</a>]
<div style="margin-bottom:2px;">
	<textarea name="{$namePrefix}[replace]" rows="3" cols="45" style="width:100%;">{$params.replace}</textarea>
</div>

<div style="margin-bottom:10px;">
	<b>In:</b> 
	<label><input type="radio" name="{$namePrefix}[replace_mode]" value="text" {if $params.replace_mode == 'text'}checked="checked"{/if}> Plaintext</label>
	<label><input type="radio" name="{$namePrefix}[replace_mode]" value="html" {if $params.replace_mode == 'html'}checked="checked"{/if}> HTML</label>
	<label><input type="radio" name="{$namePrefix}[replace_mode]" value="" {if !$params.replace_mode}checked="checked"{/if}> Both</label>
</div>

<b>With:</b>
<div style="margin-bottom:10px;">
	<textarea name="{$namePrefix}[with]" rows="3" cols="45" style="width:100%;" class="placeholders">{$params.with}</textarea>
</div>
