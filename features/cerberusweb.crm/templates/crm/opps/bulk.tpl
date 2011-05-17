<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formBatchUpdate" name="formBatchUpdate" onsubmit="return false;">
<input type="hidden" name="c" value="crm">
<input type="hidden" name="a" value="doOppBulkUpdate">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="opp_ids" value="{$opp_ids}">

<fieldset>
	<legend>{$translate->_('common.bulk_update.with')|capitalize}</legend>
	<label><input type="radio" name="filter" value="" {if empty($opp_ids)}checked{/if}> {$translate->_('common.bulk_update.filter.all')}</label> 
	{if !empty($opp_ids)}
		<label><input type="radio" name="filter" value="checks" {if !empty($opp_ids)}checked{/if}> {$translate->_('common.bulk_update.filter.checked')}</label>
	{else}
		<label><input type="radio" name="filter" value="sample"> {'common.bulk_update.filter.random'|devblocks_translate} </label><input type="text" name="filter_sample_size" size="5" maxlength="4" value="100" class="input_number">
	{/if}
</fieldset>

<fieldset>
	<legend>Set Fields</legend>
	<table cellspacing="0" cellpadding="2" width="100%">
		<tr>
			<td width="0%" nowrap="nowrap" valign="top" align="right">{'common.status'|devblocks_translate|capitalize}:</td>
			<td width="100%"><select name="status">
				<option value=""></option>
				<option value="open">{'crm.opp.status.open'|devblocks_translate}</option>
				<option value="won">{'crm.opp.status.closed.won'|devblocks_translate}</option>
				<option value="lost">{'crm.opp.status.closed.lost'|devblocks_translate}</option>
				{if $active_worker->hasPriv('crm.opp.actions.delete')}
				<option value="deleted">{'status.deleted'|devblocks_translate|capitalize}</option>
				{/if}
	      	</select>
	      	<br>
			<button type="button" onclick="this.form.status.selectedIndex = 1;">{'crm.opp.status.open'|devblocks_translate|lower}</button>
			<button type="button" onclick="this.form.status.selectedIndex = 2;">{'crm.opp.status.closed.won'|devblocks_translate|lower}</button>
			<button type="button" onclick="this.form.status.selectedIndex = 3;">{'crm.opp.status.closed.lost'|devblocks_translate|lower}</button>
			{if $active_worker->hasPriv('crm.opp.actions.delete')}
			<button type="button" onclick="this.form.status.selectedIndex = 4;">{'status.deleted'|devblocks_translate|lower}</button>
			{/if}
	      	</td>
		</tr>
		{if $active_worker->hasPriv('core.watchers.assign') || $active_worker->hasPriv('core.watchers.unassign')}
		<tr>
			<td width="0%" nowrap="nowrap" align="right" valign="top">{'common.watchers'|devblocks_translate|capitalize}:</td>
			<td width="100%">
				{if $active_worker->hasPriv('core.watchers.assign')}
				<button type="button" class="chooser-worker add"><span class="cerb-sprite sprite-view"></span></button>
				<br>
				{/if}
				
				{if $active_worker->hasPriv('core.watchers.unassign')}
				<button type="button" class="chooser-worker remove"><span class="cerb-sprite sprite-view"></span></button>
				{/if}
			</td>
		</tr>
		{/if}
		<tr>
			<td width="0%" nowrap="nowrap" align="right">{'crm.opportunity.closed_date'|devblocks_translate|capitalize}:</td>
			<td width="100%">
				<input type="text" name="closed_date" size=35 value=""><button type="button" onclick="devblocksAjaxDateChooser(this.form.closed_date,'#dateOppBulkClosed');">&nbsp;<span class="cerb-sprite sprite-calendar"></span>&nbsp;</button>
				<div id="dateOppBulkClosed"></div>
	      	</td>
		</tr>
	</table>
</fieldset>

{if !empty($custom_fields)}
<fieldset>
	<legend>Set Custom Fields</legend>
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=true}	
</fieldset>
{/if}

{if $active_worker->hasPriv('crm.opp.view.actions.broadcast')}
<fieldset>
	<legend>Send Broadcast</legend>
	
	<label><input type="checkbox" name="do_broadcast" id="chkMassReply" onclick="$('#bulkOppBroadcast').toggle();">{'common.enabled'|devblocks_translate|capitalize}</label>
	
	<blockquote id="bulkOppBroadcast" style="display:none;margin:10px;">
		<b>From:</b> <br>
		<select name="broadcast_group_id">
			{foreach from=$groups item=group key=group_id}
			{if $active_worker_memberships.$group_id}
			<option value="{$group->id}">{$group->name}</option>
			{/if}
			{/foreach}
		</select>
		<br>
		<b>Subject:</b> <br>
		<input type="text" name="broadcast_subject" value="" style="width:100%;"><br>
		<b>Compose:</b> {*[<a href="#">syntax</a>]*}<br>
		<textarea name="broadcast_message" style="width:100%;height:200px;"></textarea>
		<br>
		<button type="button" onclick="genericAjaxPost('formBatchUpdate','bulkOppBroadcastTest','c=crm&a=doOppBulkUpdateBroadcastTest');"><span class="cerb-sprite sprite-gear"></span> Test</button><!--
		--><select onchange="insertAtCursor(this.form.broadcast_message,this.options[this.selectedIndex].value);this.selectedIndex=0;this.form.broadcast_message.focus();">
			<option value="">-- insert at cursor --</option>
			{foreach from=$token_labels key=k item=v}
			<option value="{literal}{{{/literal}{$k}{literal}}}{/literal}">{$v}</option>
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
</fieldset>
{/if}

<button type="button" onclick="genericAjaxPopupClose('peek');genericAjaxPost('formBatchUpdate','view{$view_id}');"><span class="cerb-sprite2 sprite-tick-circle-frame"></span> {$translate->_('common.save_changes')|capitalize}</button>
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open', function(event,ui) {
		$(this).dialog('option','title',"{$translate->_('common.bulk_update')|capitalize}");
	
		$('#formBatchUpdate button.chooser-worker').each(function() {
			$button = $(this);
			context = 'cerberusweb.contexts.worker';
			
			if($button.hasClass('remove'))
				ajax.chooser(this, context, 'do_watcher_remove_ids', { autocomplete: true, autocomplete_class:'input_remove' } );
			else
				ajax.chooser(this, context, 'do_watcher_add_ids', { autocomplete: true, autocomplete_class:'input_add'} );
		});
	});
</script>
