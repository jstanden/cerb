{$peek_context = CerberusContexts::CONTEXT_TICKET}
{$peek_context_id = $ticket->id}

<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmTicketPeek" onsubmit="return false;">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="ticket">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="id" value="{$ticket->id}">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<table cellpadding="2" cellspacing="0" border="0" width="100%" style="margin-bottom:10px;">

	{* Subject *}
	<tr>
		<td width="0%" nowrap="nowrap">Subject: </td>
		<td width="100%">
			<input type="text" name="subject" size="45" maxlength="255" style="width:98%;" value="{$ticket->subject}">
		</td>
	</tr>
	
	{* Org *}
	<tr>
		<td width="1%" nowrap="nowrap" valign="middle">{'common.organization'|devblocks_translate|capitalize}:</td>
		<td width="99%" valign="top">
				<button type="button" class="chooser-abstract" data-field-name="org_id" data-context="{CerberusContexts::CONTEXT_ORG}" data-single="true" data-query="" data-autocomplete="" data-autocomplete-if-empty="true" data-create="if-null"><span class="glyphicons glyphicons-search"></span></button>
				
				<ul class="bubbles chooser-container">
					{$ticket_org = $ticket->getOrg()}
					{if $ticket_org}
						<li><img class="cerb-avatar" src="{devblocks_url}c=avatars&context=org&context_id={$ticket_org->id}{/devblocks_url}?v={$ticket_org->updated}"><input type="hidden" name="org_id" value="{$ticket_org->id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_ORG}" data-context-id="{$ticket_org->id}">{$ticket_org->name}</a></li>
					{/if}
				</ul>
		</td>
	</tr>
	
	{* Group/Bucket *}
	<tr>
		<td width="0%" nowrap="nowrap" valign="middle">Bucket: </td>
		<td width="100%">
			<div>
				<select name="group_id">
					{foreach from=$groups item=group key=group_id}
					<option value="{$group_id}" {if $active_worker->isGroupMember($group_id)}member="true"{/if} {if $ticket->group_id == $group_id}selected="selected"{/if}>{$group->name}</option>
					{/foreach}
				</select>
				<select class="ticket-peek-bucket-options" style="display:none;">
					{foreach from=$buckets item=bucket key=bucket_id}
					<option value="{$bucket_id}" group_id="{$bucket->group_id}">{$bucket->name}</option>
					{/foreach}
				</select>
				<select name="bucket_id">
					{foreach from=$buckets item=bucket key=bucket_id}
						{if $bucket->group_id == $ticket->group_id}
						<option value="{$bucket_id}" {if $ticket->bucket_id == $bucket_id}selected="selected"{/if}>{$bucket->name}</option>
						{/if}
					{/foreach}
				</select>
			</div>
		</td>
	</tr>
	
	{* Status *}
	<tr>
		<td width="0%" nowrap="nowrap" valign="top">{'common.status'|devblocks_translate|capitalize}: </td>
		<td width="100%">
			<label><input type="radio" name="status_id" value="{Model_Ticket::STATUS_OPEN}" onclick="toggleDiv('ticketClosed','none');" {if $ticket->status_id == Model_Ticket::STATUS_OPEN}checked{/if}> {'status.open'|devblocks_translate|capitalize}</label>
			<label><input type="radio" name="status_id" value="{Model_Ticket::STATUS_WAITING}" onclick="toggleDiv('ticketClosed','block');" {if $ticket->status_id == Model_Ticket::STATUS_WAITING}checked{/if}> {'status.waiting'|devblocks_translate|capitalize}</label>
			{if $active_worker->hasPriv('core.ticket.actions.close') || ($ticket->status_id == Model_Ticket::STATUS_CLOSED)}<label><input type="radio" name="status_id" value="{Model_Ticket::STATUS_CLOSED}" onclick="toggleDiv('ticketClosed','block');" {if $ticket->status_id == Model_Ticket::STATUS_CLOSED}checked{/if}> {'status.closed'|devblocks_translate|capitalize}</label>{/if}
			{if $active_worker->hasPriv("contexts.{$peek_context}.delete") || ($ticket->status_id == Model_Ticket::STATUS_DELETED)}<label><input type="radio" name="status_id" value="{Model_Ticket::STATUS_DELETED}" onclick="toggleDiv('ticketClosed','none');" {if $ticket->status_id == Model_Ticket::STATUS_DELETED}checked{/if}> {'status.deleted'|devblocks_translate|capitalize}</label>{/if}
			
			<div id="ticketClosed" style="display:{if in_array($ticket->status_id,[Model_Ticket::STATUS_WAITING,Model_Ticket::STATUS_CLOSED])}block{else}none{/if};margin:5px 0px 5px 15px;">
				<b>{'display.reply.next.resume'|devblocks_translate}:</b><br>
				<i>{'display.reply.next.resume_eg'|devblocks_translate}</i><br>
				<input type="text" name="ticket_reopen" size="32" class="input_date" value="{if !empty($ticket->reopen_at)}{$ticket->reopen_at|devblocks_date}{/if}" style="width:75%;"><br>
				{'display.reply.next.resume_blank'|devblocks_translate}<br>
			</div>
		</td>
	</tr>
	
	{* Spam Training *}
	{if '' == $ticket->spam_training && $active_worker->hasPriv('core.ticket.actions.spam')}
	<tr>
		<td width="0%" nowrap="nowrap">Spam Training: </td>
		<td width="100%">
			<label><input type="radio" name="spam_training" value="" {if !$field_overrides.spam_training}checked="checked"{/if}> Unknown</label>
			<label><input type="radio" name="spam_training" value="S" {if 'S' == $field_overrides.spam_training}checked="checked"{/if}> Spam</label>
			<label><input type="radio" name="spam_training" value="N" {if 'N' == $field_overrides.spam_training}checked="checked"{/if}> Not Spam</label> 
		</td>
	</tr>
	{/if}
	
	{* Importance *}
	<tr>
		<td width="0%" nowrap="nowrap" valign="middle">{'common.importance'|devblocks_translate|capitalize}: </td>
		<td width="100%">
			<div class="cerb-delta-slider-container">
				<input type="hidden" name="importance" value="{$ticket->importance|default:0}">
				<div class="cerb-delta-slider {if $ticket->importance < 50}cerb-slider-green{elseif $ticket->importance > 50}cerb-slider-red{else}cerb-slider-gray{/if}" title="{$ticket->importance}">
					<span class="cerb-delta-slider-midpoint"></span>
				</div>
			</div>
		</td>
	</tr>
	
	{* Owner *}
	<tr>
		<td width="1%" nowrap="nowrap" valign="middle">{'common.owner'|devblocks_translate|capitalize}:</td>
		<td width="99%" valign="top">
			<button type="button" class="chooser-abstract" data-field-name="owner_id" data-context="{CerberusContexts::CONTEXT_WORKER}" data-single="true" data-query="group:(id:{$ticket->group_id})" data-autocomplete="group:(id:{$ticket->group_id})" data-autocomplete-if-empty="true"><span class="glyphicons glyphicons-search"></span></button>
			
			<ul class="bubbles chooser-container">
				{$owner = $ticket->getOwner()}
				{if $owner}
					<li><img class="cerb-avatar" src="{devblocks_url}c=avatars&context=worker&context_id={$owner->id}{/devblocks_url}?v={$owner->updated}"><input type="hidden" name="owner_id" value="{$owner->id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_WORKER}" data-context-id="{$owner->id}">{$owner->getName()}</a></li>
				{/if}
			</ul>
		</td>
	</tr>
	
	{* Participants *}
	<tr>
		<td width="1%" nowrap="nowrap" valign="top">{'common.participants'|devblocks_translate|capitalize}:</td>
		<td width="99%" valign="top">
			<ul class="bubbles chooser-container" style="display:block;">
				{if !empty($requesters)}
				{foreach from=$requesters item=requester}
					<li><img class="cerb-avatar" src="{devblocks_url}c=avatars&context=address&context_id={$requester->id}{/devblocks_url}?v={$requester->updated}"><input type="hidden" name="participants[]" value="{$requester->id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_ADDRESS}" data-context-id="{$requester->id}">{$requester->getNameWithEmail()}</a></li>
				{/foreach}
				{/if}
			</ul>
			
			<button type="button" class="chooser-abstract" data-field-name="participants[]" data-context="{CerberusContexts::CONTEXT_ADDRESS}" data-query="isBanned:n isDefunct:n" data-autocomplete="" data-create="true"><span class="glyphicons glyphicons-search"></span></button>
		</td>
	</tr>
	
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false tbody=true}
</table>

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=CerberusContexts::CONTEXT_TICKET context_id=$ticket->id}

{include file="devblocks:cerberusweb.core::internal/cards/editors/comment.tpl"}

<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>

</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#frmTicketPeek');
	var $popup = genericAjaxPopupFind($frm);
	
	$popup.one('popup_open',function(event,ui) {
		var $btn_recommend = $('#{$recommend_btn_domid}');
		var $btn_watchers = $('#{$watchers_btn_domid}');
		var $chooser_owner = $popup.find('button[data-field-name="owner_id"]');
		
		// Buttons
		$popup.find('button.submit').click(Devblocks.callbackPeekEditSave);

		// Abstract choosers
		$popup.find('button.chooser-abstract').cerbChooserTrigger();
		
		// Abstract peeks
		$popup.find('.cerb-peek-trigger').cerbPeekTrigger();

		// Popup
		$popup.dialog('option','title',"{'common.edit'|devblocks_translate|capitalize|escape:'javascript' nofilter}: {'common.ticket'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		
		// Slider
		
		$frm.find('div.cerb-delta-slider').each(function() {
			var $this = $(this);
			var $input = $this.siblings('input:hidden');
			
			$this.slider({
				disabled: false,
				value: $input.val(),
				min: 0,
				max: 100,
				step: 1,
				range: 'min',
				slide: function(event, ui) {
					$this.removeClass('cerb-slider-gray cerb-slider-red cerb-slider-green');
					
					if(ui.value < 50) {
						$this.addClass('cerb-slider-green');
						$this.slider('option', 'range', 'min');
					} else if(ui.value > 50) {
						$this.addClass('cerb-slider-red');
						$this.slider('option', 'range', 'max');
					} else {
						$this.addClass('cerb-slider-gray');
						$this.slider('option', 'range', false);
					}
					
					$this.attr('title', ui.value);
				},
				stop: function(event, ui) {
					$input.val(ui.value);
				}
			});
		});
		
		// Group and bucket
		$frm.find('select[name=group_id]').on('change', function(e) {
			var $select = $(this);
			var group_id = $select.val();
			var $bucket_options = $select.siblings('select.ticket-peek-bucket-options').find('option')
			var $bucket = $select.siblings('select[name=bucket_id]');
			
			$bucket.children().remove();
			
			$bucket_options.each(function() {
				var parent_id = $(this).attr('group_id');
				if(parent_id == '*' || parent_id == group_id)
					$(this).clone().appendTo($bucket);
			});
			
			$frm.trigger('cerb-form-update');
			$bucket.focus();
		});
		
		$frm.find('select[name=bucket_id]').on('change', function(e) {
			$frm.trigger('cerb-form-update');
		});
		
		// Dates
		$frm.find('input.input_date').cerbDateInputHelper();
		
		// Linked form elements
		$frm.on('cerb-form-update', function() {
			$btn_recommend.attr('group_id', $frm.find('select[name=group_id]').val());
			$btn_recommend.attr('bucket_id', $frm.find('select[name=bucket_id]').val());
			$btn_recommend.trigger('refresh');
			
			$btn_watchers.attr('group_id', $frm.find('select[name=group_id]').val());
			$btn_watchers.attr('bucket_id', $frm.find('select[name=bucket_id]').val());
			$btn_watchers.trigger('refresh');
			
			// When the group changes, change the owner chooser defaults
			var group_id = $frm.find('select[name=group_id]').val();
			$chooser_owner.attr('data-query', 'group:(id:' + group_id + ')');
			$chooser_owner.attr('data-autocomplete', 'group:(id:' + group_id + ')');
		});
		
		{if $focus_submit}
		$frm.find('button.submit').focus();
		{else}
		$frm.find('input:text,textarea').first().focus();
		{/if}
		
		// [UI] Editor behaviors
		{include file="devblocks:cerberusweb.core::internal/peek/peek_editor_common.js.tpl" peek_context=$peek_context peek_context_id=$peek_context_id}
	});
});
</script>