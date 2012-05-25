<b>{$translate->_('search.operator')|capitalize}:</b><br>
<blockquote style="margin:5px;">
	<select name="oper">
		<option value="=" {if $param && $param->operator=='='}selected="selected"{/if}>{$translate->_('search.oper.equals')}</option>
		<option value="equals or null" {if $param && $param->operator=='equals or null'}selected="selected"{/if}>{$translate->_('search.oper.equals.or_null')}</option>
	</select>
</blockquote>

<b>{$translate->_('search.value')|capitalize}:</b><br>
<blockquote style="margin:5px;">
	<label><input type="radio" name="value" value="1" {if $param->value}checked="checked"{/if}> {$translate->_('common.yes')|capitalize}</label><br>
	<label><input type="radio" name="value" value="0" {if $param && !$param->value}checked="checked"{/if}> {$translate->_('common.no')|capitalize}</label><br>
</blockquote>
