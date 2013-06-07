<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formBatchUpdate" name="formBatchUpdate" onsubmit="return false;">
<input type="hidden" name="c" value="tickets">
<input type="hidden" name="a" value="doBulkUpdate">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="ticket_ids" value="">

<fieldset class="peek">
	<legend>{$translate->_('common.bulk_update.with')|capitalize}</legend>
	<label><input type="radio" name="filter" value="" onclick="toggleDiv('categoryFilterPanelSender','none');toggleDiv('categoryFilterPanelSubject','none');" {if empty($ticket_ids)}checked{/if}> {$translate->_('common.bulk_update.filter.all')}</label> 
	{if !empty($ticket_ids)}
		<label><input type="radio" name="filter" value="checks" onclick="toggleDiv('categoryFilterPanelSender','none');toggleDiv('categoryFilterPanelSubject','none');" {if !empty($ticket_ids)}checked{/if}> {$translate->_('common.bulk_update.filter.checked')}</label> 
	{/if}
	<label><input type="radio" name="filter" value="sender" onclick="toggleDiv('categoryFilterPanelSender','block');toggleDiv('categoryFilterPanelSubject','none');"> Similar senders</label>
	<label><input type="radio" name="filter" value="subject" onclick="toggleDiv('categoryFilterPanelSender','none');toggleDiv('categoryFilterPanelSubject','block');"> Similar subjects</label>
	{if empty($ticket_ids)}
		<label><input type="radio" name="filter" value="sample" onclick="toggleDiv('categoryFilterPanelSender','none');toggleDiv('categoryFilterPanelSubject','none');"> {'common.bulk_update.filter.random'|devblocks_translate} </label><input type="text" name="filter_sample_size" size="5" maxlength="4" value="100" class="input_number">
	{/if}
	
	<div style='display:none;' id='categoryFilterPanelSender'>
	<label><b>When sender matches:</b> (one per line, use * for wildcards)</label><br>
	<textarea rows='3' cols='45' style='width:95%' name='senders' wrap="off">{foreach from=$unique_senders key=sender item=total name=senders}{$sender}{if !$smarty.foreach.senders.last}{"\n"}{/if}{/foreach}</textarea><br>
	</div>
	
	<div style='display:none;' id='categoryFilterPanelSubject'>
	<label><b>When subject matches:</b> (one per line, use * for wildcards)</label><br>
	<textarea rows='3' cols='45' style='width:95%' name='subjects' wrap="off">{foreach from=$unique_subjects key=subject item=total name=subjects}{$subject}{if !$smarty.foreach.subjects.last}{"\n"}{/if}{/foreach}</textarea><br>
	</div>
</fieldset>

<fieldset class="peek">
	<legend>Set Fields</legend>
	<table cellspacing="0" cellpadding="2" width="100%">
		{if $active_worker->hasPriv('core.ticket.actions.move')}
		<tr>
			<td width="0%" nowrap="nowrap">Move to:</td>
			<td width="100%"><select name="do_move">
				<option value=""></option>
				<optgroup label="Move to Group">
				{foreach from=$groups item=group}
					<option value="t{$group->id}">{$group->name}</option>
				{/foreach}
				</optgroup>
				
				{foreach from=$group_buckets item=buckets key=groupId}
					{assign var=group value=$groups.$groupId}
					{if !empty($active_worker_memberships.$groupId)}
						<optgroup label="{$group->name}">
						{foreach from=$buckets item=bucket}
							<option value="c{$bucket->id}">{$bucket->name}</option>
						{/foreach}
						</optgroup>
					{/if}
				{/foreach}
			</select></td>
		</tr>
		{/if}
		
		<tr>
			<td width="0%" nowrap="nowrap" valign="top">Status:</td>
			<td width="100%" valign="top">
				<select name="do_status" onchange="$val=$(this).val();$waiting=$('#bulk{$view_id}_waiting');if($val==3 || $val==1){ $waiting.show(); } else { $waiting.hide(); }">
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
				<button type="button" onclick="$(this.form).find('select[name=do_status]').val('0').trigger('change');">open</button>
				<button type="button" onclick="$(this.form).find('select[name=do_status]').val('3').trigger('change');">waiting</button>
				{if $active_worker->hasPriv('core.ticket.actions.close')}<button type="button" onclick="$(this.form).find('select[name=do_status]').val('1').trigger('change');">closed</button>{/if}
				{if $active_worker->hasPriv('core.ticket.actions.delete')}<button type="button" onclick="$(this.form).find('select[name=do_status]').val('2').trigger('change');">deleted</button>{/if}
				
				<div id="bulk{$view_id}_waiting" style="display:none;">
					<b>{$translate->_('display.reply.next.resume')}</b>
					<br>
					<i>{$translate->_('display.reply.next.resume_eg')}</i>
					<br> 
					<input type="text" name="do_reopen" size="55" value="">
					<br>
					{$translate->_('display.reply.next.resume_blank')}
					<br>
				</div>
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
		
		{if 1}
		<tr>
			<td width="0%" nowrap="nowrap" valign="top">{'common.owner'|devblocks_translate|capitalize}:</td>
			<td width="100%"><select name="do_owner">
				<option value=""></option>
				<option value="0">{'common.nobody'|devblocks_translate|lower}</option>
				{foreach from=$workers item=owner key=owner_id}
				<option value="{$owner_id}">{$owner->getName()}</option>
				{/foreach}
			</select>
			<button type="button" onclick="$(this).prev('select[name=do_owner]').val('{$active_worker->id}');">me</button>
			<button type="button" onclick="$(this).prevAll('select[name=do_owner]').val('0');">nobody</button>
			</td>
		</tr>
		{/if}
		
		{if 1}
		<tr>
			<td width="0%" nowrap="nowrap" valign="top">{'contact_org.name'|devblocks_translate|capitalize}:</td>
			<td width="100%">
				<input type="text" name="do_org" value="" style="width:98%;">
			</td>
		</tr>
		{/if}
		
		{if $active_worker->hasPriv('core.watchers.assign') || $active_worker->hasPriv('core.watchers.unassign')}
		<tr>
			<td width="0%" nowrap="nowrap" valign="top">{'common.watchers'|devblocks_translate|capitalize}:</td>
			<td width="100%">
				{if $active_worker->hasPriv('core.watchers.assign')}
				<button type="button" class="chooser-worker add"><span class="cerb-sprite sprite-view"></span></button>
				<ul class="bubbles chooser-container" style="display:block;"></ul>
				{/if}

				{if $active_worker->hasPriv('core.watchers.unassign')}
				<button type="button" class="chooser-worker remove"><span class="cerb-sprite sprite-view"></span></button>
				<ul class="bubbles chooser-container" style="display:block;"></ul>
				{/if}
			</td>
		</tr>
		{/if}
	</table>
</fieldset>

{if !empty($custom_fields)}
<fieldset class="peek">
	<legend>Set Custom Fields</legend>
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=true}
</fieldset>
{/if}

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=CerberusContexts::CONTEXT_TICKET bulk=true}

{include file="devblocks:cerberusweb.core::internal/macros/behavior/bulk.tpl" macros=$macros}

{if $active_worker->hasPriv('core.ticket.view.actions.broadcast_reply')}
<fieldset class="peek">
	<legend>Send Broadcast Reply</legend>
	<label><input type="checkbox" name="do_broadcast" id="chkMassReply" onclick="$('#bulkTicketBroadcast').toggle();"> {'common.enabled'|devblocks_translate|capitalize}</label>
	
	<blockquote id="bulkTicketBroadcast" style="display:none;margin:10px;">
		<b>Reply:</b>
		
		<div style="margin:0px 0px 5px 10px;">
			<textarea name="broadcast_message" style="width:100%;height:200px;border:1px solid rgb(180,180,180);padding:2px;"></textarea>
			<br>
			<button type="button" onclick="ajax.chooserSnippet('snippets',$('#bulkTicketBroadcast textarea[name=broadcast_message]'), { '{CerberusContexts::CONTEXT_TICKET}':'', '{CerberusContexts::CONTEXT_WORKER}':'{$active_worker->id}' });">{'common.snippets'|devblocks_translate|capitalize}</button>
			<button type="button" onclick="genericAjaxPost('formBatchUpdate','bulkTicketBroadcastTest','c=tickets&a=doBulkUpdateBroadcastTest');"><span class="cerb-sprite2 sprite-gear"></span> Test</button><!--
			--><select class="insert-placeholders">
				<option value="">-- insert at cursor --</option>
				{foreach from=$token_labels key=k item=v}
				<option value="{literal}{{{/literal}{$k}{literal}}}{/literal}">{$v}</option>
				{/foreach}
			</select>
			<br>
			<div id="bulkTicketBroadcastTest"></div>
		</div>
		
		<b>{'common.attachments'|devblocks_translate|capitalize}:</b><br>
	
		<div style="margin:0px 0px 5px 10px;">
			<button type="button" class="chooser_file"><span class="cerb-sprite2 sprite-plus-circle"></span></button>
			<ul class="bubbles chooser-container">
		</div>
		
		<b>Then:</b>
		<div style="margin:0px 0px 5px 10px;">
			<label><input type="radio" name="broadcast_is_queued" value="0" checked="checked"> Save as drafts</label>
			<label><input type="radio" name="broadcast_is_queued" value="1"> Send now</label>
		</div>
	</blockquote>
</fieldset>
{/if}
	
<button type="button" onclick="ajax.saveBatchPanel('{$view_id}');"><span class="cerb-sprite2 sprite-tick-circle"></span> {$translate->_('common.save_changes')|capitalize}</button>
<br>
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open', function(event,ui) {
		var $this = $(this);
		var $frm = $('#formBatchUpdate');
		
		$this.dialog('option','title',"{$translate->_('common.bulk_update')|capitalize}");
		
		ajax.orgAutoComplete('#formBatchUpdate input:text[name=do_org]');
		
		$frm.find('button.chooser_file').each(function() {
			ajax.chooserFile(this,'broadcast_file_ids');
		});
		
		$this.find('select.insert-placeholders').change(function(e) {
			var $select = $(this);
			var $val = $select.val();
			
			if($val.length == 0)
				return;
			
			var $textarea = $select.siblings('textarea[name=broadcast_message]');
			
			$textarea.insertAtCursor($val).focus();
			
			$select.val('');
		});
		
		
		$frm.find('button.chooser-worker').each(function() {
			var $button = $(this);
			var context = 'cerberusweb.contexts.worker';
			
			if($button.hasClass('remove'))
				ajax.chooser(this, context, 'do_watcher_remove_ids', { autocomplete: true, autocomplete_class:'input_remove' } );
			else
				ajax.chooser(this, context, 'do_watcher_add_ids', { autocomplete: true, autocomplete_class:'input_add'} );
		});
	});
</script>
