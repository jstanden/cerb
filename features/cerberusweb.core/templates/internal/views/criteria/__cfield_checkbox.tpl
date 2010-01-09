<b>{$translate->_('search.operator')|capitalize}:</b><br>
<blockquote style="margin:5px;">
	<select name="oper">
		<option value="=">{$translate->_('search.oper.equals')}</option>
		<option value="equals or null">{$translate->_('search.oper.equals.or_null')}</option>
	</select>
</blockquote>

<b>{$translate->_('search.value')|capitalize}:</b><br>
<blockquote style="margin:5px;">
	<label><input type="radio" name="value" value="1"> {$translate->_('common.yes')|capitalize}</label><br>
	<label><input type="radio" name="value" value="0"> {$translate->_('common.no')|capitalize}</label><br>
</blockquote>
