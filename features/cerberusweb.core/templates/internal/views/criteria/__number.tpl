<b>{$translate->_('search.operator')|capitalize}:</b><br>
<blockquote style="margin:5px;">
	<select name="oper">
		<option value="=" {if $param && $param->operator=='='}selected="selected"{/if}>{$translate->_('search.oper.equals')}</option>
		<option value="!=" {if $param && $param->operator=='!='}selected="selected"{/if}>{$translate->_('search.oper.equals.not')}</option>
		<option value="&gt;" {if $param && $param->operator=='>'}selected="selected"{/if}>&gt;</option>
		<option value="&lt;" {if $param && $param->operator=='<'}selected="selected"{/if}>&lt;</option>
	</select>
</blockquote>

<b>{$translate->_('search.value')|capitalize}:</b><br>
<blockquote style="margin:5px;">
	<input type="text" name="value" value="{$param->value}"><br>
</blockquote>

