<table cellpadding="0" cellspacing="0" border="0" width="98%">
	<tr>
		<td align="left" width="0%" nowrap="nowrap" style="padding-right:5px;"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/gear.gif{/devblocks_url}" align="absmiddle"></td>
		<td align="left" width="100%" nowrap="nowrap"><h1>Bulk Update</h1></td>
	</tr>
</table>

<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formBatchUpdate" name="formBatchUpdate">
<input type="hidden" name="c" value="contacts">
<input type="hidden" name="a" value="doAddressBatchUpdate">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="address_ids" value="">
<div style="height:400px;overflow:auto;">

<h2>With:</h2>

<label><input type="radio" name="filter" value="" {if empty($address_ids)}checked{/if}> Whole list</label> 
<label><input type="radio" name="filter" value="" {if !empty($address_ids)}checked{/if}> Only checked</label> 
<br>
<br>

<div id="bulkUpdateCustom" style="display:block;">
<H2>Do:</H2>
<table cellspacing="0" cellpadding="2" width="100%">
	<tr>
		<td width="0%" nowrap="nowrap">Service Level:</td>
		<td width="100%"><select name="sla">
			<option value=""></option>
			<option value="0">- None -</option>
      		{foreach from=$slas item=sla key=sla_id}
      			<option value="{$sla_id}">{$sla->name}</option>
      		{/foreach}
      	</select></td>
	</tr>
	<!--
	<tr>
		<td width="0%" nowrap="nowrap">Organization:</td>
		<td width="100%">
			<input type="text" name="org" value="" size="35">
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap">Country:</td>
		<td width="100%">
			<input type="text" name="country" value="" size="35">
		</td>
	</tr>
	-->
</table>

<br>
</div>

<button type="button" onclick="ajax.saveAddressBatchPanel('{$view_id}');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
<br>
</form>