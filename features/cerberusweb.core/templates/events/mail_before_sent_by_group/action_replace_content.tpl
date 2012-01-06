<b>Replace:</b>
<label><input type="checkbox" name="{$namePrefix}[is_regexp]" value="1" {if !empty($params.is_regexp)}checked="checked"{/if}> Regular expression</label> [<a href="http://us2.php.net/manual/en/pcre.pattern.php" target="_blank">?</a>]
<br>
<textarea name="{$namePrefix}[replace]" rows="3" cols="45" style="width:100%;">{$params.replace}</textarea>
<br>

<b>With:</b>
	<textarea name="{$namePrefix}[with]" rows="3" cols="45" style="width:100%;" class="placeholders">{$params.with}</textarea>
</div>
<br>

<script type="text/javascript">
$action = $('fieldset#{$namePrefix}');
$action.find('textarea').elastic();
</script>