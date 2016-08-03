<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formBatchUpdate" name="formBatchUpdate" onsubmit="return false;">
<input type="hidden" name="c" value="tickets">
<input type="hidden" name="a" value="startBulkUpdateJson">
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
			<td width="100%">
				<select class="cerb-moveto-group">
					<option></option>
					{foreach from=$groups item=group}
					<option value="{$group->id}">{$group->name}</option>
					{/foreach}
				</select>
				<select class="cerb-moveto-bucket-options" style="display:none;">
					{foreach from=$buckets item=bucket}
					<option value="{$bucket->id}" data-group-id="{$bucket->group_id}">{$bucket->name}</option>
					{/foreach}
				</select>
				<select name="do_move" class="cerb-moveto-bucket" style="display:none;">
				</select>
			</td>
		</tr>
		{/if}
		
		<tr>
			<td width="0%" nowrap="nowrap" align="right" valign="top">Status:</td>
			<td width="100%" valign="top">
				<select name="do_status" onchange="$val=$(this).val();$waiting=$('#bulk{$view_id}_waiting');if($val=={Model_Ticket::STATUS_WAITING} || $val=={Model_Ticket::STATUS_CLOSED}){ $waiting.show(); } else { $waiting.hide(); }">
					<option value=""></option>
					<option value="{Model_Ticket::STATUS_OPEN}">{'status.open'|devblocks_translate|capitalize}</option>
					<option value="{Model_Ticket::STATUS_WAITING}">{'status.waiting.abbr'|devblocks_translate|capitalize}</option>
					{if $active_worker->hasPriv('core.ticket.actions.close')}
					<option value="{Model_Ticket::STATUS_CLOSED}">{'status.closed'|devblocks_translate|capitalize}</option>
					{/if}
					{if $active_worker->hasPriv('core.ticket.actions.delete')}
					<option value="{Model_Ticket::STATUS_DELETED}">{'status.deleted'|devblocks_translate|capitalize}</option>
					{/if}
				</select>
				<button type="button" onclick="$(this.form).find('select[name=do_status]').val('{Model_Ticket::STATUS_OPEN}').trigger('change');">{'status.open'|devblocks_translate|lower}</button>
				<button type="button" onclick="$(this.form).find('select[name=do_status]').val('{Model_Ticket::STATUS_WAITING}').trigger('change');">{'status.waiting.abbr'|devblocks_translate|lower}</button>
				{if $active_worker->hasPriv('core.ticket.actions.close')}<button type="button" onclick="$(this.form).find('select[name=do_status]').val('{Model_Ticket::STATUS_CLOSED}').trigger('change');">{'status.closed'|devblocks_translate|lower}</button>{/if}
				{if $active_worker->hasPriv('core.ticket.actions.delete')}<button type="button" onclick="$(this.form).find('select[name=do_status]').val('{Model_Ticket::STATUS_DELETED}').trigger('change');">{'status.deleted'|devblocks_translate|lower}</button>{/if}
				
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
{include file="devblocks:cerberusweb.core::internal/views/bulk_broadcast.tpl" context=CerberusContexts::CONTEXT_TICKET is_reply=true}
{/if}
	
<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
<br>
</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#formBatchUpdate');
	var $popup = genericAjaxPopupFind($frm);
	
	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"{'common.bulk_update'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		$popup.css('overflow', 'inherit');
		
		$popup.find('button.chooser-abstract').cerbChooserTrigger();
		
		$popup.find('button.submit').click(function() {
			genericAjaxPost('formBatchUpdate', '', null, function(json) {
				if(json.cursor) {
					// Pull the cursor
					var $tips = $('#{$view_id}_tips').html('');
					var $spinner = $('<span class="cerb-ajax-spinner"/>').appendTo($tips);
					genericAjaxGet($tips, 'c=internal&a=viewBulkUpdateWithCursor&view_id={$view_id}&cursor=' + json.cursor);
				}
				
				genericAjaxPopupClose($popup);
			});
		});
		
		// Move to
		
		var $select_moveto_group = $popup.find('select.cerb-moveto-group');
		var $select_moveto_bucket = $popup.find('select.cerb-moveto-bucket');
		var $select_moveto_bucket_options = $popup.find('select.cerb-moveto-bucket-options');
		
		$select_moveto_group.change(function() {
			var group_id = $(this).val();
			
			$select_moveto_bucket.find('> option').remove();
			
			$('<option/>').appendTo($select_moveto_bucket);
			
			if(0 == group_id.length) {
				$select_moveto_bucket.val('').hide();
				return;
			}
			
			$select_moveto_bucket_options.find('option').each(function() {
				var $opt = $(this);
				if($opt.attr('data-group-id') == group_id)
					$opt.clone().appendTo($select_moveto_bucket);
			});
			
			$select_moveto_bucket.val('').fadeIn();
		});
		
		{include file="devblocks:cerberusweb.core::internal/views/bulk_broadcast_jquery.tpl"}
	});
});
</script>