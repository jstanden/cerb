<b>{'ticket.status'|devblocks_translate|capitalize}:</b><br>

<input type="hidden" name="oper" value="in">

<label><input name="value[]" type="checkbox" value="waiting"> {'status.open'|devblocks_translate|capitalize}</label>
<label><input name="value[]" type="checkbox" value="open"> {'status.waiting'|devblocks_translate|capitalize}</label>
<label><input name="value[]" type="checkbox" value="closed"> {'status.closed'|devblocks_translate|capitalize}</label>
