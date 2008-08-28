<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmTimeEntry">
<input type="hidden" name="c" value="timetracking">
<input type="hidden" name="a" value="saveEntry">

<h1>Time Tracking</h1>

<table cellpadding="2" cellspacing="0" width="100%">
	<tr>
		<td width="99%"><b>Category</b></td>
		<td width="1%" nowrap="nowrap"><b>Time Spent</b></td>
	</tr>
	<tr>
		<td>
			<select name="category_id">
				<option value=""></option>
				<optgroup label="Billable">
					<option value="1" onclick="toggleDiv('divTimeEntryBilling','block');">Installations</option>
					<option value="2" onclick="toggleDiv('divTimeEntryBilling','block');">Troubleshooting</option>
				</optgroup>
				<optgroup label="Not Billable">
					<option value="3" onclick="toggleDiv('divTimeEntryBilling','none');">General Support</option>
				</optgroup>
			</select>
		</td>
		<td nowrap="nowrap"><input type="text" name="time_spent" size="5" value="{$total_mins}"> mins</td>
	</tr>
</table>
<br>

<b>Notes:</b><br>
<!-- <input type="text" name="" size="" maxlength="255" style="width:98%;" value="{$origin}"><br> -->
<textarea name="notes" rows="5" cols="45" style="width:98%;">{$origin|escape}</textarea><br>
<br>

<div id="divTimeEntryBilling" style="display:none;">
	<b>Bill to Client:</b> (start typing to autocomplete; leave blank to add later)<br>
	<div id="contactautocomplete" style="width:98%;" class="yui-ac">
		<input type="text" name="bill_to" id="contactinput" value="" class="yui-ac-input">
		<div id="contactcontainer" class="yui-ac-container"></div>
		<br>
	</div>
	<br>
</div>

<button type="button" onclick="genericAjaxPost('frmTimeEntry','','c=timetracking&a=saveEntry',{literal}function(o){genericPanel.hide();}{/literal});"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
<button type="button" onclick="genericPanel.hide();"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete.gif{/devblocks_url}" align="top"> {$translate->_('common.cancel')|capitalize}</button>
</form>