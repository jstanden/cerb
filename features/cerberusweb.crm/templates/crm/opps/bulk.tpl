<table cellpadding="0" cellspacing="0" border="0" width="98%">
	<tr>
		<td align="left" width="1%" nowrap="nowrap" style="padding-right:5px;"><span class="cerb-sprite sprite-folder_gear"></span></td>
		<td align="left" width="100%"><h1>{$translate->_('common.bulk_update')|capitalize}</h1></td>
	</tr>
</table>

<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formBatchUpdate" name="formBatchUpdate">
<input type="hidden" name="c" value="crm">
<input type="hidden" name="a" value="doOppBulkUpdate">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="opp_ids" value="{$opp_ids}">

<h2>{$translate->_('common.bulk_update.with')|capitalize}:</h2>

<label><input type="radio" name="filter" value="" {if empty($opp_ids)}checked{/if}> {$translate->_('common.bulk_update.filter.all')}</label> 
<label><input type="radio" name="filter" value="checks" {if !empty($opp_ids)}checked{/if}> {$translate->_('common.bulk_update.filter.checked')}</label> 
<br>
<br>

<H2>{$translate->_('common.bulk_update.do')|capitalize}:</H2>
<table cellspacing="0" cellpadding="2" width="100%">
	<tr>
		<td width="0%" nowrap="nowrap" valign="top" align="right">{'common.status'|devblocks_translate|capitalize}:</td>
		<td width="100%"><select name="status">
			<option value=""></option>
			<option value="open">{'crm.opp.status.open'|devblocks_translate}</option>
			<option value="won">{'crm.opp.status.closed.won'|devblocks_translate}</option>
			<option value="lost">{'crm.opp.status.closed.lost'|devblocks_translate}</option>
      	</select>
		<button type="button" onclick="this.form.status.selectedIndex = 1;">{'crm.opp.status.open'|devblocks_translate|lower}</button>
		<button type="button" onclick="this.form.status.selectedIndex = 2;">{'crm.opp.status.closed.won'|devblocks_translate|lower}</button>
		<button type="button" onclick="this.form.status.selectedIndex = 3;">{'crm.opp.status.closed.lost'|devblocks_translate|lower}</button>
      	</td>
	</tr>
	
	<tr>
		<td width="0%" align="right" nowrap="nowrap">{'common.worker'|devblocks_translate|capitalize}:</td>
		<td width="100%"><select name="worker_id">
			<option value=""></option>
			<option value="0">- {'common.anybody'|devblocks_translate|lower} -</option>
			{foreach from=$workers item=worker key=worker_id name=workers}
				{if $worker_id==$active_worker->id}{math assign=me_worker_id equation="x+1" x=$smarty.foreach.workers.iteration}{/if}
				<option value="{$worker_id}">{$worker->getName()}</option>
			{/foreach}
		</select>
      	{if !empty($me_worker_id)}
      		<button type="button" onclick="this.form.worker_id.selectedIndex = {$me_worker_id};">{'common.me'|devblocks_translate|lower}</button>
      		<button type="button" onclick="this.form.worker_id.selectedIndex = 1;">{'common.anybody'|devblocks_translate|lower}</button>
      	{/if}
		</td>
	</tr>
	
	<tr>
		<td width="0%" nowrap="nowrap" align="right">{'crm.opportunity.closed_date'|devblocks_translate|capitalize}:</td>
		<td width="100%">
			<input type="text" name="closed_date" size=35 value=""><button type="button" onclick="devblocksAjaxDateChooser(this.form.closed_date,'#dateOppBulkClosed');">&nbsp;<span class="cerb-sprite sprite-calendar"></span>&nbsp;</button>
			<div id="dateOppBulkClosed"></div>
      	</td>
	</tr>
</table>
	
{include file="file:$core_tpl/internal/custom_fields/bulk/form.tpl" bulk=true}

<br>

<button type="button" onclick="genericPanel.dialog('close');genericAjaxPost('formBatchUpdate','view{$view_id}');"><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')|capitalize}</button>
</form>