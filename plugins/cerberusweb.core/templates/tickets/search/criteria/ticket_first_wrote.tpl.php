<b>Operator:</b><br>
<blockquote style="margin:5px;">
	<select name="oper">
		<option value="like">matches</option>
		<option value="not like">doesn't match</option>
		<option value="=">equals</option>
		<option value="!=">doesn't equal</option>
	</select>
</blockquote>

<b>E-mail Address:</b><br>
<blockquote style="margin:5px;">
	<input type="text" name="email"><br>
	<i>Use an asterisk (*) for wildcards.<br>
	For example: *@webgroupmedia.com</i><br>
</blockquote>

<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
