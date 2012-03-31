<b>Operator:</b><br>
<blockquote style="margin:5px;">
	<select name="oper">
		<option value="&gt;=" {if $param && $param->operator=='>='}selected="selected"{/if}>&gt;=</option>
		<option value="&lt;=" {if $param && $param->operator=='<='}selected="selected"{/if}>&lt;=</option>
		<option value="=" {if $param && $param->operator=='='}selected="selected"{/if}>=</option>
	</select>
</blockquote>

<b>Percentage:</b> (0% to 99%)<br>
<blockquote style="margin:5px;">
	<input type="text" name="score" size="3" maxlength="2" value="{if $param && is_numeric($param->value)}{$param->value * 100}{/if}">%
</blockquote>
