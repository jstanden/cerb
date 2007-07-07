<input type="hidden" name="oper" value="in">

<b>Values:</b><br>
<blockquote style="margin:5px;">
	<label><input name="status[]" type="checkbox" value="0">Open</label><br>
	<label><input name="status[]" type="checkbox" value="1">Closed</label><br>
</blockquote>

<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
