<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formBatchUpdate" name="formBatchUpdate" onsubmit="return false;">
<input type="hidden" name="c" value="tickets">
<input type="hidden" name="a" value="doBatchUpdate">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="ticket_ids" value="">

<h2>{$translate->_('common.bulk_update.with')|capitalize}:</h2>
<label><input type="radio" name="filter" value="" onclick="toggleDiv('categoryFilterPanelSender','none');toggleDiv('categoryFilterPanelSubject','none');" {if empty($ticket_ids)}checked{/if}> {$translate->_('common.bulk_update.filter.all')}</label> 
<label><input type="radio" name="filter" value="checks" onclick="toggleDiv('categoryFilterPanelSender','none');toggleDiv('categoryFilterPanelSubject','none');" {if !empty($ticket_ids)}checked{/if}> {$translate->_('common.bulk_update.filter.checked')}</label> 
<label><input type="radio" name="filter" value="sender" onclick="toggleDiv('categoryFilterPanelSender','block');toggleDiv('categoryFilterPanelSubject','none');"> Similar senders</label>
<label><input type="radio" name="filter" value="subject" onclick="toggleDiv('categoryFilterPanelSender','none');toggleDiv('categoryFilterPanelSubject','block');"> Similar subjects</label>
<br>
<br>

<div style='display:none;' id='categoryFilterPanelSender'>
<label><b>When sender matches:</b> (one per line, use * for wildcards)</label><br>
<textarea rows='3' cols='45' style='width:95%' name='senders' wrap="off">{foreach from=$unique_senders key=sender item=total name=senders}{$sender}{if !$smarty.foreach.senders.last}{"\n"}{/if}{/foreach}</textarea><br>
<br>
</div>

<div style='display:none;' id='categoryFilterPanelSubject'>
<label><b>When subject matches:</b> (one per line, use * for wildcards)</label><br>
<textarea rows='3' cols='45' style='width:95%' name='subjects' wrap="off">{foreach from=$unique_subjects key=subject item=total name=subjects}{$subject}{if !$smarty.foreach.subjects.last}{"\n"}{/if}{/foreach}</textarea><br>
<br>
</div>

<H2>{$translate->_('common.bulk_update.do')|capitalize}:</H2>
<table cellspacing="0" cellpadding="2" width="100%">
	{if $active_worker->hasPriv('core.ticket.actions.move')}
	<tr>
		<td width="0%" nowrap="nowrap">Move to:</td>
		<td width="100%"><select name="do_move">
			<option value=""></option>
      		<optgroup label="Move to Group">
      		{foreach from=$teams item=team}
      			<option value="t{$team->id}">{$team->name}</option>
      		{/foreach}
      		</optgroup>
      		
      		{foreach from=$team_categories item=categories key=teamId}
      			{assign var=team value=$teams.$teamId}
      			{if !empty($active_worker_memberships.$teamId)}
	      			<optgroup label="{$team->name}">
	      			{foreach from=$categories item=category}
	    				<option value="c{$category->id}">{$category->name}</option>
	    			{/foreach}
	    			</optgroup>
    			{/if}
     		{/foreach}
      	</select></td>
	</tr>
	{/if}
	
	<tr>
		<td width="0%" nowrap="nowrap">Status:</td>
		<td width="100%">
			<select name="do_status">
				<option value=""></option>
				<option value="0">Open</option>
				<option value="3">Waiting</option>
				{if $active_worker->hasPriv('core.ticket.actions.close')}
				<option value="1">Closed</option>
				{/if}
				{if $active_worker->hasPriv('core.ticket.actions.delete')}
				<option value="2">Deleted</option>
				{/if}
			</select>
			<button type="button" onclick="this.form.do_status.selectedIndex = 1;">open</button>
			<button type="button" onclick="this.form.do_status.selectedIndex = 2;">waiting</button>
			{if $active_worker->hasPriv('core.ticket.actions.close')}<button type="button" onclick="this.form.do_status.selectedIndex = 3;">closed</button>{/if}
			{if $active_worker->hasPriv('core.ticket.actions.delete')}<button type="button" onclick="this.form.do_status.selectedIndex = 4;">deleted</button>{/if}
		</td>
	</tr>
	
	{if $active_worker->hasPriv('core.ticket.actions.spam')}
	<tr>
		<td width="0%" nowrap="nowrap">Spam:</td>
		<td width="100%"><select name="do_spam">
			<option value=""></option>
			<option value="1">Report Spam</option>
			<option value="0">Not Spam</option>
		</select>
		<button type="button" onclick="this.form.do_spam.selectedIndex = 1;">spam</button>
		<button type="button" onclick="this.form.do_spam.selectedIndex = 2;">not spam</button>
		</td>
	</tr>
	{/if}
	
	{if $active_worker->hasPriv('core.ticket.actions.assign')}
	<tr>
		<td width="0%" nowrap="nowrap" valign="top">{'common.owners'|devblocks_translate|capitalize}:</td>
		<td width="100%">
			<button type="button" class="chooser-worker add"><span class="cerb-sprite sprite-add"></span></button>
			<br>
			<button type="button" class="chooser-worker remove"><span class="cerb-sprite sprite-forbidden"></span></button>
		</td>
	</tr>
	{/if}
	
	{if $active_worker->hasPriv('core.ticket.view.actions.broadcast_reply')}
	<tr>
		<td width="0%" nowrap="nowrap" align="right"><label for="chkMassReply">Broadcast Reply:</label></td>
		<td width="100%">
			<input type="checkbox" name="do_broadcast" id="chkMassReply" onclick="$('#bulkTicketBroadcast').toggle();">
		</td>
	</tr>
	{/if}
</table>

{if $active_worker->hasPriv('core.ticket.view.actions.broadcast_reply')}
<blockquote id="bulkTicketBroadcast" style="display:none;margin:10px;">
	<b>Reply:</b><br>
	<textarea name="broadcast_message" style="width:100%;height:200px;border:1px solid rgb(180,180,180);padding:2px;"></textarea>
	<br>
	<button type="button" onclick="genericAjaxPost('formBatchUpdate','bulkTicketBroadcastTest','c=tickets&a=doBatchUpdateBroadcastTest');"><span class="cerb-sprite sprite-gear"></span> Test</button><!--
	--><select onchange="insertAtCursor(this.form.broadcast_message,this.options[this.selectedIndex].value);this.selectedIndex=0;this.form.broadcast_message.focus();">
		<option value="">-- insert at cursor --</option>
		{foreach from=$token_labels key=k item=v}
		<option value="{literal}{{{/literal}{$k}{literal}}}{/literal}">{$v|escape}</option>
		{/foreach}
	</select>
	<br>
	<div id="bulkTicketBroadcastTest"></div>
	<label><input type="radio" name="broadcast_is_queued" value="0" checked="checked"> Save as drafts</label>
	<label><input type="radio" name="broadcast_is_queued" value="1"> Send now</label>
</blockquote>
{/if}

{include file="file:$core_tpl/internal/custom_fields/bulk/form.tpl" bulk=true}
<br>

<button type="button" onclick="ajax.saveBatchPanel('{$view_id}');"><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')|capitalize}</button>
<br>
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open', function(event,ui) {
		$(this).dialog('option','title',"{$translate->_('common.bulk_update')|capitalize|escape:'quotes'}");
		
		$('#formBatchUpdate button.chooser-worker').each(function() {
			$button = $(this);
			context = 'cerberusweb.contexts.worker';
			
			if($button.hasClass('remove'))
				ajax.chooser(this, context, 'do_owner_remove_ids');
			else
				ajax.chooser(this, context, 'do_owner_add_ids');
		});
	});
</script>
