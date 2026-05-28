<input type="hidden" name="oper" value="between">

<b>{'search.date.between'|devblocks_translate|capitalize}:</b><br>
<blockquote style="margin:5px;">
	<input type="text" id="searchDateFrom" name="from" size="20" style="width:98%;"><br>
	-{'search.date.between.and'|devblocks_translate}-<br>
	<input type="text" id="searchDateTo" name="to" size="20" value="now" style="width:98%;"><br>
	<br>
	{'search.date.examples'|devblocks_translate|escape|nl2br nofilter}
</blockquote>

