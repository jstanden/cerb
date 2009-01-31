<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmOppFields">
<input type="hidden" name="c" value="crm">
<input type="hidden" name="a" value="saveOppProperties">
<input type="hidden" name="opp_id" value="{$opp->id}">

<blockquote style="margin:10px;">

	<table cellpadding="0" cellspacing="2" border="0" width="98%">
		<tr>
			<td width="0%" nowrap="nowrap" align="right">{$translate->_('crm.opportunity.name')|capitalize}: </td>
			<td width="100%"><input type="text" name="name" value="{$opp->name|escape}" style="width:98%;"></td>
		</tr>
		<tr>
			<td width="0%" nowrap="nowrap" align="right" valign="top">{$translate->_('crm.opportunity.amount')|capitalize}: </td>
			<td width="100%">
				<input type="text" name="amount" size="10" maxlength="12" style="border:1px solid rgb(180,180,180);padding:2px;" value="{if empty($opp->amount)}0{else}{math equation="floor(x)" x=$opp->amount}{/if}" autocomplete="off">
				 . 
				<input type="text" name="amount_cents" size="3" maxlength="2" style="border:1px solid rgb(180,180,180);padding:2px;" value="{if empty($opp->amount)}00{else}{math equation="(x-floor(x))*100" x=$opp->amount}{/if}" autocomplete="off">
			</td>
		</tr>
		<tr>
			<td width="0%" nowrap="nowrap">{$translate->_('crm.opportunity.worker_id')|capitalize}:</td>
			<td width="100%"><select name="worker_id">
				<option value="0"></option>
				{foreach from=$workers item=worker key=worker_id name=workers}
					{if $worker_id==$active_worker->id}{math assign=me_worker_id equation="x" x=$smarty.foreach.workers.iteration}{/if}
					<option value="{$worker_id}" {if $opp->worker_id==$worker_id}selected{/if}>{$worker->getName()}</option>
				{/foreach}
			</select>
	      	{if !empty($me_worker_id)}
	      		<button type="button" onclick="this.form.worker_id.selectedIndex = {$me_worker_id};">me</button>
	      	{/if}
      		<button type="button" onclick="this.form.worker_id.selectedIndex = 0;">nobody</button>
			</td>
		</tr>
	</table>

	{include file="file:$core_tpl/internal/custom_fields/bulk/form.tpl.php" bulk=false}
	<br>
	
	<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
</blockquote>

</form>