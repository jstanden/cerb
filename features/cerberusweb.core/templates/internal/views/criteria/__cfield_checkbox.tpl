<input type="hidden" name="oper" value="=">

<b>{'search.value'|devblocks_translate|capitalize}:</b><br>
<blockquote style="margin:5px;">
	<label><input type="radio" name="value" value="1" {if $param->value}checked="checked"{/if}> {'common.yes'|devblocks_translate|capitalize}</label><br>
	<label><input type="radio" name="value" value="0" {if $param && !$param->value}checked="checked"{/if}> {'common.no'|devblocks_translate|capitalize}</label><br>
</blockquote>
