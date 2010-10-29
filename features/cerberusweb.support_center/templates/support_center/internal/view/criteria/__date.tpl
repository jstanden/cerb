<input type="hidden" name="oper" value="between">

<b>{$translate->_('search.date.between')|capitalize}:</b><br>
<blockquote style="margin:5px;">
	<input type="text" id="searchDateFrom" name="from" size="20" style="width:98%;"><br>
	-{$translate->_('search.date.between.and')}-<br>
	<input type="text" id="searchDateTo" name="to" size="20" value="now" style="width:98%;"><br>
	<br>
	{$translate->_('search.date.examples')|nl2br}
</blockquote>

