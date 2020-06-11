<b>Replace:</b>
<label><input type="checkbox" name="{$namePrefix}[is_regexp]" value="1" {if !empty($params.is_regexp)}checked="checked"{/if}> Regular expression</label> [<a href="http://us2.php.net/manual/en/pcre.pattern.php" target="_blank" rel="noopener noreferrer">?</a>]
<div style="margin-bottom:2px;">
	<textarea name="{$namePrefix}[replace]" rows="3" cols="45" style="width:100%;">{$params.replace}</textarea>
</div>

<b>With:</b>
<div style="margin-bottom:10px;">
	<textarea name="{$namePrefix}[with]" rows="3" cols="45" style="width:100%;" class="placeholders">{$params.with}</textarea>
</div>

{if !$is_sent}
<div style="margin-bottom:10px;">
	<b>{'common.in'|devblocks_translate|capitalize}:</b>
	<label><input type="radio" name="{$namePrefix}[replace_mode]" value="text" {if $params.replace_mode == 'text'}checked="checked"{/if}> {'common.text.plain'|devblocks_translate|capitalize}</label>
	<label><input type="radio" name="{$namePrefix}[replace_mode]" value="html" {if $params.replace_mode == 'html'}checked="checked"{/if}> HTML</label>
	<label><input type="radio" name="{$namePrefix}[replace_mode]" value="" {if !$params.replace_mode}checked="checked"{/if}> Both</label>
</div>
{/if}

<div style="margin-bottom:10px;">
	<b>{'common.on'|devblocks_translate|capitalize}:</b>
	<label><input type="radio" name="{$namePrefix}[replace_on]" value="sent" {if $params.replace_on == 'sent'}checked="checked"{/if}> Only sent message</label>
	<label><input type="radio" name="{$namePrefix}[replace_on]" value="saved" {if $params.replace_on == 'saved'}checked="checked"{/if}> Only saved copy</label>
	<label><input type="radio" name="{$namePrefix}[replace_on]" value="" {if !$params.replace_on}checked="checked"{/if}> Both</label>
</div>