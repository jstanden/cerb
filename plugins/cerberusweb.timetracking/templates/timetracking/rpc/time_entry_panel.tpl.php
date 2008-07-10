<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmTimeEntry">
<input type="hidden" name="c" value="timetracking">
<input type="hidden" name="a" value="saveEntry">

<h1>Time Tracking</h1>

<b>Time Spent:</b>
<input type="text" name="time_spent" size="5" value="{$total_mins}"> minutes

 &nbsp; - &nbsp;   

<b>Billable?</b>
{*
<label><input type="radio" name="is_billable" value="1" onclick="document.getElementById('divTimeEntryBilling').style.display = (this.checked) ? 'block' : 'none';"> Yes</label> 
<label><input type="radio" name="is_billable" value="0" checked="checked" onclick="document.getElementById('divTimeEntryBilling').style.display = (this.checked) ? 'none' : 'block';"> No</label>
*} 
<label><input type="radio" name="is_billable" value="1"> Yes</label> 
<label><input type="radio" name="is_billable" value="0" checked="checked"> No</label> 
<br>
<br>

{*
<div id="divTimeEntryBilling" style="display:none;">
	<b>Bill to:</b> (type the first few letters of an org, or leave blank to add later)<br>
	<div id="contactautocomplete" style="width:98%;" class="yui-ac">
		<input type="text" name="bill_to" id="contactinput" value="" class="yui-ac-input">
		<div id="contactcontainer" class="yui-ac-container"></div>
		<br>
	</div>
	
	<br>
</div>
*}

<b>Work Log:</b><br>
<textarea name="notes" rows="8" cols="45" style="width:98%;">{$worklog|escape}</textarea><br>
<br>

<button type="button" onclick="genericAjaxPost('frmTimeEntry','','c=timetracking&a=saveEntry',{literal}function(o){genericPanel.hide();}{/literal});"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
<button type="button" onclick="genericPanel.hide();"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete.gif{/devblocks_url}" align="top"> {$translate->_('common.close')|capitalize}</button>
</form>