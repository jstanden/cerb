<b>{$translate->_('ticket.status')|capitalize}:</b><br>

<input type="hidden" name="oper" value="in">

<label><input name="value[]" type="checkbox" value="waiting"> {$translate->_('status.open')|capitalize}</label>
<label><input name="value[]" type="checkbox" value="open"> {$translate->_('status.waiting')|capitalize}</label>
<label><input name="value[]" type="checkbox" value="closed"> {$translate->_('status.closed')|capitalize}</label>
