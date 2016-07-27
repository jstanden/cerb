<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formBatchUpdate" name="formBatchUpdate" onsubmit="return false;">
<input type="hidden" name="c" value="tickets">
<input type="hidden" name="a" value="doBulkUpdate">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="ids" value="{$ids}">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<fieldset class="peek">
	<legend>{'common.bulk_update.with'|devblocks_translate|capitalize}</legend>
	<label><input type="radio" name="filter" value="" {if empty($ids)}checked{/if}> {'common.bulk_update.filter.all'|devblocks_translate}</label> 
	{if !empty($ids)}
		<label><input type="radio" name="filter" value="checks" {if !empty($ids)}checked{/if}> {'common.bulk_update.filter.checked'|devblocks_translate}</label> 
	{/if}
	{if empty($ids)}
		<label><input type="radio" name="filter" value="sample"> {'common.bulk_update.filter.random'|devblocks_translate} </label><input type="text" name="filter_sample_size" size="5" maxlength="4" value="100" class="input_number">
	{/if}
</fieldset>

<fieldset class="peek">
	<legend>Set Fields</legend>
	<table cellspacing="0" cellpadding="2" width="100%">
		{if $active_worker->hasPriv('core.ticket.actions.move')}
		<tr>
			<td width="0%" nowrap="nowrap" align="right" valign="top">Move to:</td>
			<td width="100%"><select name="do_move">
				<option value=""></option>
				{foreach from=$group_buckets item=buckets key=groupId}
					{$group = $groups.$groupId}
					{foreach from=$buckets item=bucket}
					{if $bucket->is_default || !empty($active_worker_memberships.$groupId)}
						<option value="{$bucket->id}">{$group->name}: {$bucket->name}</option>
					{/if}
					{/foreach}
				{/foreach}
			</select></td>
		</tr>
		{/if}
		
		<tr>
			<td width="0%" nowrap="nowrap" align="right" valign="top">Status:</td>
			<td width="100%" valign="top">
				<select name="do_status" onchange="$val=$(this).val();$waiting=$('#bulk{$view_id}_waiting');if($val=={Model_Ticket::STATUS_WAITING} || $val=={Model_Ticket::STATUS_CLOSED}){ $waiting.show(); } else { $waiting.hide(); }">
					<option value=""></option>
					<option value="{Model_Ticket::STATUS_OPEN}">Open</option>
					<option value="{Model_Ticket::STATUS_WAITING}">Waiting</option>
					{if $active_worker->hasPriv('core.ticket.actions.close')}
					<option value="{Model_Ticket::STATUS_CLOSED}">Closed</option>
					{/if}
					{if $active_worker->hasPriv('core.ticket.actions.delete')}
					<option value="{Model_Ticket::STATUS_DELETED}">Deleted</option>
					{/if}
				</select>
				<button type="button" onclick="$(this.form).find('select[name=do_status]').val('{Model_Ticket::STATUS_OPEN}').trigger('change');">open</button>
				<button type="button" onclick="$(this.form).find('select[name=do_status]').val('{Model_Ticket::STATUS_WAITING}').trigger('change');">waiting</button>
				{if $active_worker->hasPriv('core.ticket.actions.close')}<button type="button" onclick="$(this.form).find('select[name=do_status]').val('{Model_Ticket::STATUS_CLOSED}').trigger('change');">closed</button>{/if}
				{if $active_worker->hasPriv('core.ticket.actions.delete')}<button type="button" onclick="$(this.form).find('select[name=do_status]').val('{Model_Ticket::STATUS_DELETED}').trigger('change');">deleted</button>{/if}
				
				<div id="bulk{$view_id}_waiting" style="display:none;">
					<b>{'display.reply.next.resume'|devblocks_translate}</b>
					<br>
					<i>{'display.reply.next.resume_eg'|devblocks_translate}</i>
					<br> 
					<input type="text" name="do_reopen" size="55" value="">
					<br>
					{'display.reply.next.resume_blank'|devblocks_translate}
					<br>
				</div>
			</td>
		</tr>
		
		{if $active_worker->hasPriv('core.ticket.actions.spam')}
		<tr>
			<td width="0%" nowrap="nowrap" align="right" valign="top">Spam:</td>
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
			<td width="0%" nowrap="nowrap" align="right" valign="top">{'common.owner'|devblocks_translate|capitalize}:</td>
			<td width="100%">
				<button type="button" class="chooser-abstract" data-field-name="do_owner" data-context="{CerberusContexts::CONTEXT_WORKER}" data-single="true" data-query="" data-autocomplete="if-null"><span class="glyphicons glyphicons-search"></span></button>
				<ul class="bubbles chooser-container"></ul>
			</td>
		</tr>
		{/if}
		
		{if 1}
		<tr>
			<td width="0%" nowrap="nowrap" align="right" valign="top">{'common.organization'|devblocks_translate|capitalize}:</td>
			<td width="100%">
				<button type="button" class="chooser-abstract" data-field-name="do_org" data-context="{CerberusContexts::CONTEXT_ORG}" data-single="true" data-query="" data-autocomplete="if-null" data-create="if-null"><span class="glyphicons glyphicons-search"></span></button>
				<ul class="bubbles chooser-container"></ul>
			</td>
		</tr>
		{/if}
		
		{if $active_worker->hasPriv('core.watchers.assign')}
		<tr>
			<td width="0%" nowrap="nowrap" align="right" valign="top">Add watchers:</td>
			<td width="100%">
				<div>
					<button type="button" class="chooser-abstract" data-field-name="do_watcher_add_ids[]" data-context="{CerberusContexts::CONTEXT_WORKER}" data-query="isDisabled:n" data-autocomplete="true"><span class="glyphicons glyphicons-search"></span></button>
					<ul class="bubbles chooser-container" style="display:block;"></ul>
				</div>
			</td>
		</tr>
		{/if}
		
		{if $active_worker->hasPriv('core.watchers.unassign')}
		<tr>
			<td width="0%" nowrap="nowrap" align="right" valign="top">Remove watchers:</td>
			<td width="100%">
				<div>
					<button type="button" class="chooser-abstract" data-field-name="do_watcher_remove_ids[]" data-context="{CerberusContexts::CONTEXT_WORKER}" data-query="isDisabled:n" data-autocomplete="true"><span class="glyphicons glyphicons-search"></span></button>
					<ul class="bubbles chooser-container" style="display:block;"></ul>
				</div>
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
	<input type="hidden" name="broadcast_format" value="">
	
	<blockquote id="bulkTicketBroadcast" style="display:none;margin:10px;">
		<b>Reply:</b>
		
		<div style="margin:0px 0px 5px 10px;">
			<textarea name="broadcast_message" style="width:100%;height:200px;border:1px solid rgb(180,180,180);padding:2px;"></textarea>
			<div>
				<button type="button" onclick="ajax.chooserSnippet('snippets',$('#bulkTicketBroadcast textarea[name=broadcast_message]'), { '{CerberusContexts::CONTEXT_TICKET}':'', '{CerberusContexts::CONTEXT_WORKER}':'{$active_worker->id}' });">{'common.snippets'|devblocks_translate|capitalize}</button>
				<select class="insert-placeholders">
					<option value="">-- insert at cursor --</option>
					{foreach from=$token_labels key=k item=v}
					<option value="{literal}{{{/literal}{$k}{literal}}}{/literal}">{$v}</option>
					{/foreach}
				</select>
			</div>
		</div>
		
		<b>{'common.attachments'|devblocks_translate|capitalize}:</b><br>
	
		<div style="margin:0px 0px 5px 10px;">
			<button type="button" class="chooser_file"><span class="glyphicons glyphicons-paperclip"></span></button>
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
	
<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
<br>
</form>

<script type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFind('#formBatchUpdate');
	
	$popup.one('popup_open', function(event,ui) {
		var $this = $(this);
		var $frm = $('#formBatchUpdate');
		
		$popup.dialog('option','title',"{'common.bulk_update'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		
		$popup.find('button.chooser-abstract').cerbChooserTrigger();
		
		$popup.find('button.submit').click(function() {
			genericAjaxPost('formBatchUpdate', 'view{$view_id}', null, function() {
				genericAjaxPopupClose($popup);
			});
		});
		
		$frm.find('button.chooser_file').each(function() {
			ajax.chooserFile(this,'broadcast_file_ids');
		});
		
		var $content = $frm.find('textarea[name=broadcast_message]');
		
		$this.find('select.insert-placeholders').change(function(e) {
			var $select = $(this);
			var $val = $select.val();
			
			if($val.length == 0)
				return;
			
			$content.insertAtCursor($val).focus();
			
			$select.val('');
		});
		
		// Text editor
		
		var markitupPlaintextSettings = $.extend(true, { }, markitupPlaintextDefaults);
		var markitupParsedownSettings = $.extend(true, { }, markitupParsedownDefaults);
		
		var markitupBroadcastFunctions = {
			switchToMarkdown: function(markItUp) { 
				$content.markItUpRemove().markItUp(markitupParsedownSettings);
				$content.closest('form').find('input:hidden[name=broadcast_format]').val('parsedown');
				
				// Template chooser
				
				var $ul = $content.closest('.markItUpContainer').find('.markItUpHeader UL');
				var $li = $('<li style="margin-left:10px;"></li>');
				
				var $select = $('<select name="broadcast_html_template_id"></select>');
				$select.append($('<option value="0"/>').text(' - {'common.default'|devblocks_translate|lower|escape:'javascript'} -'));
				
				{foreach from=$html_templates item=html_template}
				var $option = $('<option/>').attr('value','{$html_template->id}').text('{$html_template->name|escape:'javascript'}');
				$select.append($option);
				{/foreach}
				
				$li.append($select);
				$ul.append($li);
			},
			
			switchToPlaintext: function(markItUp) {
				$content.markItUpRemove().markItUp(markitupPlaintextSettings);
				$content.closest('form').find('input:hidden[name=broadcast_format]').val('');
			}
		};
		
		markitupPlaintextSettings.markupSet.unshift(
			{ name:'Switch to Markdown', openWith: markitupBroadcastFunctions.switchToMarkdown, className:'parsedown' }
		);
		
		markitupPlaintextSettings.markupSet.push(
			{ separator:' ' },
			{ name:'Preview', key: 'P', call:'preview', className:"preview" }
		);
		
		var previewParser = function(content) {
			genericAjaxPost(
				'formBatchUpdate',
				'',
				'c=tickets&a=doBulkUpdateBroadcastTest',
				function(o) {
					content = o;
				},
				{
					async: false
				}
			);
			
			return content;
		};
		
		markitupPlaintextSettings.previewParser = previewParser;
		markitupPlaintextSettings.previewAutoRefresh = false;
		
		markitupParsedownSettings.previewParser = previewParser;
		markitupParsedownSettings.previewAutoRefresh = false;
		delete markitupParsedownSettings.previewInWindow;
		
		markitupParsedownSettings.markupSet.unshift(
			{ name:'Switch to Plaintext', openWith: markitupBroadcastFunctions.switchToPlaintext, className:'plaintext' },
			{ separator:' ' }
		);
		
		markitupParsedownSettings.markupSet.splice(
			6,
			0,
			{ name:'Upload an Image', openWith: 
				function(markItUp) {
					$chooser=genericAjaxPopup('chooser','c=internal&a=chooserOpenFile&single=1',null,true,'750');
					
					$chooser.one('chooser_save', function(event) {
						if(!event.response || 0 == event.response)
							return;
						
						$content.insertAtCursor("![inline-image](" + event.response[0].url + ")");
					});
				},
				key: 'U',
				className:'image-inline'
			}
		);
		
		try {
			$content.markItUp(markitupPlaintextSettings);
			
		} catch(e) {
			if(window.console)
				console.log(e);
		}
	});
});
</script>