<b>{'search.operator'|devblocks_translate|capitalize}:</b><br>
<blockquote style="margin:5px;">
	<select name="oper">
		<option value="=" {if $param && $param->operator=='='}selected="selected"{/if}>{'search.oper.equals'|devblocks_translate}</option>
		<option value="!=" {if $param && $param->operator=='!='}selected="selected"{/if}>{'search.oper.equals.not'|devblocks_translate}</option>
		<option value="&gt;" {if $param && $param->operator=='>'}selected="selected"{/if}>&gt;</option>
		<option value="&lt;" {if $param && $param->operator=='<'}selected="selected"{/if}>&lt;</option>
	</select>
</blockquote>

<b>{'search.value'|devblocks_translate|capitalize}:</b><br>
<blockquote style="margin:5px;">
	<input type="text" name="value" value="{$param->value}"><br>
</blockquote>

