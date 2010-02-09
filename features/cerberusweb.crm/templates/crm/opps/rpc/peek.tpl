<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formOppPeek" name="formOppPeek" onsubmit="return false;">
<input type="hidden" name="c" value="crm">
<input type="hidden" name="a" value="saveOppPanel">
<input type="hidden" name="opp_id" value="{$opp->id}">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="do_delete" value="0">

<table cellpadding="0" cellspacing="2" border="0" width="98%">
	<tr>
		<td width="0%" nowrap="nowrap" align="right" valign="top">{$translate->_('crm.opportunity.email_address')|capitalize}: </td>
		<td width="100%">
			<input type="text" name="email" id="emailinput" value="{$address->email|escape}" style="border:1px solid rgb(180,180,180);padding:2px;width:98%;">
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" align="right" valign="top">{$translate->_('crm.opportunity.name')|capitalize}: </td>
		<td width="100%">
			<input type="text" name="name" style="width:98%;border:1px solid rgb(180,180,180);padding:2px;" value="{$opp->name|escape}" autocomplete="off">
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" align="right" valign="top">{$translate->_('common.status')|capitalize}: </td>
		<td width="100%">
			<label><input type="radio" name="status" value="0" onclick="toggleDiv('oppPeekClosedDate','none');" {if empty($opp->id) || 0==$opp->is_closed}checked="checked"{/if}> {'crm.opp.status.open'|devblocks_translate|capitalize}</label>
			<label><input type="radio" name="status" value="1" onclick="toggleDiv('oppPeekClosedDate','');" {if $opp->is_closed && $opp->is_won}checked="checked"{/if}> {'crm.opp.status.closed.won'|devblocks_translate|capitalize}</label>
			<label><input type="radio" name="status" value="2" onclick="toggleDiv('oppPeekClosedDate','');" {if $opp->is_closed && !$opp->is_won}checked="checked"{/if}> {'crm.opp.status.closed.lost'|devblocks_translate|capitalize}</label>
		</td>
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
		<td width="0%" nowrap="nowrap" align="right" valign="top">{$translate->_('crm.opportunity.worker_id')|capitalize}: </td>
		<td width="100%">
			<select name="worker_id" style="border:1px solid rgb(180,180,180);padding:2px;">
				<option value="0">- {'common.anybody'|devblocks_translate|lower} -</option>
				{foreach from=$workers item=worker key=worker_id name=workers}
					{if $worker_id==$active_worker->id}{math assign=me_worker_id equation="x" x=$smarty.foreach.workers.iteration}{/if}
					<option value="{$worker_id}" {if $opp->worker_id==$worker_id}selected{/if}>{$worker->getName()}</option>
				{/foreach}
			</select>
			{if !empty($me_worker_id)}
				<button type="button" onclick="this.form.worker_id.selectedIndex = {$me_worker_id};">{$translate->_('common.me')|lower}</button>
			{/if}
			<button type="button" onclick="this.form.worker_id.selectedIndex = 0;">{$translate->_('common.anybody')|lower}</button>
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" align="right" valign="top">{$translate->_('crm.opportunity.created_date')|capitalize}: </td>
		<td width="100%">
			<input type="text" name="created_date" size=35 value="{if !empty($opp->created_date)}{$opp->created_date|devblocks_date}{else}now{/if}"><button type="button" onclick="devblocksAjaxDateChooser(this.form.created_date,'#dateOppCreated');">&nbsp;<span class="cerb-sprite sprite-calendar"></span>&nbsp;</button>
			<div id="dateOppCreated"></div>
		</td>
	</tr>
	<tr id="oppPeekClosedDate" {if !$opp->is_closed}style="display:none;"{/if}>
		<td width="0%" nowrap="nowrap" align="right" valign="top">{$translate->_('crm.opportunity.closed_date')|capitalize}: </td>
		<td width="100%">
			<input type="text" name="closed_date" size="35" value="{if !empty($opp->closed_date)}{$opp->closed_date|devblocks_date}{/if}"><button type="button" onclick="devblocksAjaxDateChooser(this.form.closed_date,'#dateOppClosed');">&nbsp;<span class="cerb-sprite sprite-calendar"></span>&nbsp;</button>
			<div id="dateOppClosed"></div>
		</td>
	</tr>
	{if empty($opp->id)}
	<tr>
		<td width="0%" nowrap="nowrap" align="right" valign="top">{'common.comment'|devblocks_translate|capitalize}: </td>
		<td width="100%">
			<textarea name="comment" style="width:98%;height:120px;border:1px solid rgb(180,180,180);padding:2px;"></textarea><br>
		</td>
	</tr>
	{/if}
</table>

{include file="file:$core_tpl/internal/custom_fields/bulk/form.tpl" bulk=false}
<br>

{if ($active_worker->hasPriv('crm.opp.actions.create') && (empty($opp) || $active_worker->id==$opp->worker_id))
	|| ($active_worker->hasPriv('crm.opp.actions.update_nobody') && empty($opp->worker_id)) 
	|| $active_worker->hasPriv('crm.opp.actions.update_all')} 
	<button type="button" onclick="genericPanel.dialog('close');genericAjaxPost('formOppPeek', 'view{$view_id}')"><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')}</button>
	<button type="button" onclick="if(confirm('Are you sure you want to permanently delete this opportunity?')) { this.form.do_delete.value='1';genericPanel.dialog('close');genericAjaxPost('formOppPeek', 'view{$view_id}'); } "><span class="cerb-sprite sprite-delete2"></span> {$translate->_('common.delete')|capitalize}</button>
	<button type="button" onclick="genericPanel.dialog('close');"><span class="cerb-sprite sprite-delete"></span> {$translate->_('common.cancel')|capitalize}</button>
{else}
	<div class="error">You do not have permission to modify this record.</div>
{/if}
<br>
</form>

<script language="JavaScript1.2" type="text/javascript">
	genericPanel.one('dialogopen',function(event,ui) {
		genericPanel.dialog('option','title', '{'Opportunity'|devblocks_translate|escape:'quotes'}');
		ajax.emailAutoComplete('#emailinput');
	} );
</script>
