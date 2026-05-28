<b>{'search.operator'|devblocks_translate|capitalize}:</b><br>
<blockquote style="margin:5px;">
	<select name="oper">
		<option value="like">{'search.oper.matches'|devblocks_translate}</option>
		<option value="not like">{'search.oper.matches.not'|devblocks_translate}</option>
		<option value="=">{'search.oper.equals'|devblocks_translate}</option>
		<option value="!=">{'search.oper.equals.not'|devblocks_translate}</option>
		<option value="is null">{'search.oper.null'|devblocks_translate}</option>
	</select>
</blockquote>

<b>{'search.value'|devblocks_translate|capitalize}:</b><br>
<blockquote style="margin:5px;">
	<input type="text" name="value"><br>
	<i>{'search.string.examples'|devblocks_translate|escape|nl2br nofilter}</i>
</blockquote>

