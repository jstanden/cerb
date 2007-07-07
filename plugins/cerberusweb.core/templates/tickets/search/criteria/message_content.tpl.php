<b>Operator:</b><br>
<blockquote style="margin:5px;">
	<select name="oper">
		<option value="like">matches</option>
		<option value="not like">doesn't match</option>
	</select>
</blockquote>

<b>Keyword or Phrase:</b><br>
<blockquote style="margin:5px;">
	<input type="text" name="content">
</blockquote>

<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
