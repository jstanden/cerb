<b>{$translate->_('search.operator')|capitalize}:</b><br>
<blockquote style="margin:5px;">
	<select name="oper">
		<option value="like">{$translate->_('search.oper.matches')}</option>
		<option value="not like">{$translate->_('search.oper.matches.not')}</option>
		<option value="=">{$translate->_('search.oper.equals')}</option>
		<option value="!=">{$translate->_('search.oper.equals.not')}</option>
		<option value="is null">{$translate->_('search.oper.null')}</option>
	</select>
</blockquote>

<b>{$translate->_('search.value')|capitalize}:</b><br>
<blockquote style="margin:5px;">
	<input type="text" name="value"><br>
	<i>{$translate->_('search.string.examples')|nl2br nofilter}</i>
</blockquote>

