<b>{$translate->_('search.operator')|capitalize}:</b><br>
<blockquote style="margin:5px;">
	<select name="oper">
		<option value="=">{$translate->_('search.oper.equals')}</option>
		<option value="!=">{$translate->_('search.oper.equals.not')}</option>
		<option value="&gt;">&gt;</option>
		<option value="&lt;">&lt;</option>
	</select>
</blockquote>

<b>{$translate->_('search.value')|capitalize}:</b><br>
<blockquote style="margin:5px;">
	<input type="text" name="value"><br>
</blockquote>

