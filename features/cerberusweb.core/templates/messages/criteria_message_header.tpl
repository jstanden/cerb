<b>Name:</b>

<blockquote style="margin:5px;">
	<input type="text" name="name" value="{$param->value[0]}" style="width:100%;"><br>
</blockquote>

<b>{'search.operator'|devblocks_translate|capitalize}:</b><br>
<blockquote style="margin:5px;">
	<select name="oper">
		<option value="like" {if $param && $param->operator=='like'}selected="selected"{/if}>{'search.oper.matches'|devblocks_translate}</option>
		<option value="not like" {if $param && $param->operator=='not like'}selected="selected"{/if}>{'search.oper.matches.not'|devblocks_translate}</option>
		<option value="=" {if $param && $param->operator=='='}selected="selected"{/if}>{'search.oper.equals'|devblocks_translate}</option>
		<option value="!=" {if $param && $param->operator=='!='}selected="selected"{/if}>{'search.oper.equals.not'|devblocks_translate}</option>
		<option value="is null" {if $param && $param->operator=='is null'}selected="selected"{/if}>{'search.oper.null'|devblocks_translate}</option>
	</select>
</blockquote>

<b>Value:</b>

<blockquote style="margin:5px;">
	<input type="text" name="value" value="{$param->value[1]}" style="width:100%;"><br>
	<i>{'search.string.examples'|devblocks_translate|escape|nl2br nofilter}</i>
</blockquote>