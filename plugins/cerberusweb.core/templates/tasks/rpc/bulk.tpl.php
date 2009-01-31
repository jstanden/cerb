<table cellpadding="0" cellspacing="0" border="0" width="98%">
	<tr>
		<td align="left" width="1%" nowrap="nowrap" style="padding-right:5px;"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/gear.gif{/devblocks_url}" align="absmiddle"></td>
		<td align="left" width="100%"><h1>Bulk Update</h1></td>
	</tr>
</table>

<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formBatchUpdate" name="formBatchUpdate">
<input type="hidden" name="c" value="tasks">
<input type="hidden" name="a" value="doTaskBulkUpdate">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="ids" value="{$ids}">

<div style="height:400px;overflow:auto;">

<h2>With:</h2>

<label><input type="radio" name="filter" value="" {if empty($ids)}checked{/if}> Whole list</label> 
<label><input type="radio" name="filter" value="checks" {if !empty($ids)}checked{/if}> Only checked</label> 
<br>
<br>

<H2>Do:</H2>
<table cellspacing="0" cellpadding="2" width="100%">
	<tr>
		<td width="0%" nowrap="nowrap" align="right">Due:</td>
		<td width="100%">
			<input type="text" name="due" value="" size="35">
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" align="right">Status:</td>
		<td width="100%">
			<select name="status">
				<option value=""></option>
				<option value="0">Active</option>
				<option value="1">Completed</option>
			</select>
			<button type="button" onclick="this.form.status.selectedIndex = 1;">active</button>
			<button type="button" onclick="this.form.status.selectedIndex = 2;">completed</button>
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" align="right">Worker:</td>
		<td width="100%">
			<select name="worker_id">
				<option value=""></option>
				<option value="0">Anybody</option>
				{foreach from=$workers item=worker key=worker_id name=workers}
					{if $worker_id==$active_worker->id}{math assign=me_worker_id equation="x+1" x=$smarty.foreach.workers.iteration}{/if}
					<option value="{$worker_id}">{$worker->getName()}</option>
				{/foreach}
			</select>
	      	{if !empty($me_worker_id)}
	      		<button type="button" onclick="this.form.worker_id.selectedIndex = {$me_worker_id};">me</button>
	      		<button type="button" onclick="this.form.worker_id.selectedIndex = 1;">anybody</button>
	      	{/if}
		</td>
	</tr>
</table>

{include file="file:$core_tpl/internal/custom_fields/bulk/form.tpl.php" checkboxes=true}	

<br>
</div>

<button type="button" onclick="genericPanel.hide();genericAjaxPost('formBatchUpdate','view{$view_id}');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
<br>
</form>