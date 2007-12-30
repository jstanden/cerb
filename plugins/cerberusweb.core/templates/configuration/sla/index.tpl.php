{include file="file:$path/configuration/menu.tpl.php"}
<br>

<h2>Service Levels</h2>
<br>

<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="saveSla">

<div class="block">
	<h2>Reply Order</h2>
	Service Levels allow you to give preferential treatment to specific senders, groups 
	or organizations.  Priorities range from 1 (lowest) to 100 (highest).
	<br>
	<br>
	
	{if !empty($slas)}
	<table cellpadding="2" cellspacing="0" border="0">
		<tr style="background-color:rgb(240, 240, 240);">
			<td style="padding-right:10px;"><b>Priority</b></td>
			<td style="padding-right:10px;"><b>Name</b></td>
			<td style="padding-right:10px;"><b>Remove</b></td>
		</tr>
		
		{foreach from=$slas item=sla key=sla_id}
		<tr>
			<td align="center">
				<input type="text" name="sla_priorities[]" size="3" maxlength="3" value="{$sla->priority}">
			</td>
			<td>
				<input type="hidden" name="sla_ids[]" value="{$sla->id}">
				<input type="text" name="sla_names[]" size="45" value="{$sla->name|escape}">
			</td>
			<td align="center">
				<input type="checkbox" name="sla_deletes[]" value="{$sla_id}">
			</td>
		</tr>
		{/foreach}
		
	</table>
	<br>
	{/if}
	
	<h2>Add Service Level</h2>
	<b>Name:</b> (e.g., Paid Support, VIPs, Management, Friends)<br>
	<input type="text" name="add_sla" size="64" value=""><br>
	<br>
	
	<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
</div>

</form>
