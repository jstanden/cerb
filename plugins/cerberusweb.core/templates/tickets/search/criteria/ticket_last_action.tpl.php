<input type="hidden" name="oper" value="in">

<b>Values:</b><br>
<blockquote style="margin:5px;">
	<label><input name="last_action[]" type="checkbox" value="O">New Ticket</label><br>
	<label><input name="last_action[]" type="checkbox" value="R">Customer Reply</label><br>
	<label><input name="last_action[]" type="checkbox" value="W">Worker Reply</label><br>
</blockquote>

<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
