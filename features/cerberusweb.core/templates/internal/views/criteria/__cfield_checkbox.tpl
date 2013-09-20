<b>{'search.operator'|devblocks_translate|capitalize}:</b><br>
<blockquote style="margin:5px;">
	<select name="oper">
		<option value="=" {if $param && $param->operator=='='}selected="selected"{/if}>{'search.oper.equals'|devblocks_translate}</option>
		<option value="equals or null" {if $param && $param->operator=='equals or null'}selected="selected"{/if}>{'search.oper.equals.or_null'|devblocks_translate}</option>
	</select>
</blockquote>

<b>{'search.value'|devblocks_translate|capitalize}:</b><br>
<blockquote style="margin:5px;">
	<label><input type="radio" name="value" value="1" {if $param->value}checked="checked"{/if}> {'common.yes'|devblocks_translate|capitalize}</label><br>
	<label><input type="radio" name="value" value="0" {if $param && !$param->value}checked="checked"{/if}> {'common.no'|devblocks_translate|capitalize}</label><br>
</blockquote>
