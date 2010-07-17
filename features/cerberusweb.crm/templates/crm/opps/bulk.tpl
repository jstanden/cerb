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
	
	{*
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
	*}
	
	<tr>
		<td width="0%" nowrap="nowrap" align="right">{'crm.opportunity.closed_date'|devblocks_translate|capitalize}:</td>
		<td width="100%">
			<input type="text" name="closed_date" size=35 value=""><button type="button" onclick="devblocksAjaxDateChooser(this.form.closed_date,'#dateOppBulkClosed');">&nbsp;<span class="cerb-sprite sprite-calendar"></span>&nbsp;</button>
			<div id="dateOppBulkClosed"></div>
      	</td>
	</tr>
	
	{if $active_worker->hasPriv('crm.opp.view.actions.broadcast')}
	<tr>
		<td width="0%" nowrap="nowrap" align="right"><label for="chkMassReply">Broadcast:</label></td>
		<td width="100%">
			<input type="checkbox" name="do_broadcast" id="chkMassReply" onclick="$('#bulkOppBroadcast').toggle();">
		</td>
	</tr>
	{/if}
</table>

{if $active_worker->hasPriv('crm.opp.view.actions.broadcast')}
<blockquote id="bulkOppBroadcast" style="display:none;margin:10px;">
	<b>From:</b> <br>
	<select name="broadcast_group_id">
		{foreach from=$groups item=group key=group_id}
		{if $active_worker_memberships.$group_id}
		<option value="{$group->id}|escape">{$group->name}</option>
		{/if}
		{/foreach}
	</select>
	<br>
	<b>Subject:</b> <br>
	<input type="text" name="broadcast_subject" value="" style="width:100%;border:1px solid rgb(180,180,180);padding:2px;"><br>
	<b>Compose:</b> {*[<a href="#">syntax</a>]*}<br>
	<textarea name="broadcast_message" style="width:100%;height:200px;border:1px solid rgb(180,180,180);padding:2px;"></textarea>
	<br>
	<button type="button" onclick="genericAjaxPost('formBatchUpdate','bulkOppBroadcastTest','c=crm&a=doOppBulkUpdateBroadcastTest');"><span class="cerb-sprite sprite-gear"></span> Test</button><!--
	--><select onchange="insertAtCursor(this.form.broadcast_message,this.options[this.selectedIndex].value);this.selectedIndex=0;this.form.broadcast_message.focus();">
		<option value="">-- insert at cursor --</option>
		{foreach from=$token_labels key=k item=v}
		<option value="{literal}{{{/literal}{$k}{literal}}}{/literal}">{$v|escape}</option>
		{/foreach}
	</select>
	<br>
	<div id="bulkOppBroadcastTest"></div>
	<b>{$translate->_('common.options')|capitalize}:</b> 
	<label><input type="radio" name="broadcast_is_queued" value="0" checked="checked"> Save as drafts</label>
	<label><input type="radio" name="broadcast_is_queued" value="1"> Send now</label>
	<br>
	<b>{$translate->_('common.status')|capitalize}:</b> 
	<label><input type="radio" name="broadcast_next_is_closed" value="0"> {$translate->_('status.open')|capitalize}</label>
	<label><input type="radio" name="broadcast_next_is_closed" value="2" checked="checked"> {$translate->_('status.waiting')|capitalize}</label>
	<label><input type="radio" name="broadcast_next_is_closed" value="1"> {$translate->_('status.closed')|capitalize}</label>
</blockquote>
{/if}

{include file="file:$core_tpl/internal/custom_fields/bulk/form.tpl" bulk=true}

<br>

<button type="button" onclick="genericAjaxPopupClose('peek');genericAjaxPost('formBatchUpdate','view{$view_id}');"><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')|capitalize}</button>
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open', function(event,ui) {
		$(this).dialog('option','title',"{$translate->_('common.bulk_update')|capitalize|escape:'quotes'}");
	} );
</script>
