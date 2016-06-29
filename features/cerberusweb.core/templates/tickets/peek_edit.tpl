{$peek_context = CerberusContexts::CONTEXT_TICKET}
{$peek_context_id = $ticket->id}

<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmTicketPeek" onsubmit="return false;">
<input type="hidden" name="c" value="tickets">
<input type="hidden" name="a" value="savePeekJson">
<input type="hidden" name="id" value="{$ticket->id}">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($link_context)}
<input type="hidden" name="link_context" value="{$link_context}">
<input type="hidden" name="link_context_id" value="{$link_context_id}">
{/if}
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<fieldset class="peek" style="margin-bottom:0;">
	<legend>{'common.ticket'|devblocks_translate|capitalize}</legend>
	
	<table cellpadding="2" cellspacing="0" border="0" width="100%" style="margin-bottom:10px;">
	
		{* Subject *}
		<tr>
			<td width="0%" nowrap="nowrap" align="right">Subject: </td>
			<td width="100%">
				<input type="text" name="subject" size="45" maxlength="255" style="width:98%;" autofocus="true" value="{$ticket->subject}">
			</td>
		</tr>
		
		{* Org *}
		<tr>
			<td width="1%" nowrap="nowrap" align="right" valign="middle">{'common.organization'|devblocks_translate|capitalize}:</td>
			<td width="99%" valign="top">
					<button type="button" class="chooser-abstract" data-field-name="org_id" data-context="{CerberusContexts::CONTEXT_ORG}" data-single="true" data-query="" data-autocomplete="if-null" data-create="if-null"><span class="glyphicons glyphicons-search"></span></button>
					
					<ul class="bubbles chooser-container">
						{$ticket_org = $ticket->getOrg()}
						{if $ticket_org}
							<li><img class="cerb-avatar" src="{devblocks_url}c=avatars&context=org&context_id={$ticket_org->id}{/devblocks_url}?v={$ticket_org->updated}"><input type="hidden" name="org_id" value="{$ticket_org->id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_ORG}" data-context-id="{$ticket_org->id}">{$ticket_org->name}</a></li>
						{/if}
					</ul>
			</td>
		</tr>
		
		{* Group/Bucket *}
		{if $active_worker->hasPriv('core.ticket.actions.move')}
		<tr>
			<td width="0%" nowrap="nowrap" valign="middle" align="right">Bucket: </td>
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
		{/if}
		
		{* Status *}
		<tr>
			<td width="0%" nowrap="nowrap" valign="top" align="right">{'common.status'|devblocks_translate|capitalize}: </td>
			<td width="100%">
				<label><input type="radio" name="status_id" value="{Model_Ticket::STATUS_OPEN}" onclick="toggleDiv('ticketClosed','none');" {if $ticket->status_id == Model_Ticket::STATUS_OPEN}checked{/if}> {'status.open'|devblocks_translate|capitalize}</label>
				<label><input type="radio" name="status_id" value="{Model_Ticket::STATUS_WAITING}" onclick="toggleDiv('ticketClosed','block');" {if $ticket->status_id == Model_Ticket::STATUS_WAITING}checked{/if}> {'status.waiting'|devblocks_translate|capitalize}</label>
				{if $active_worker->hasPriv('core.ticket.actions.close') || ($ticket->status_id == Model_Ticket::STATUS_CLOSED)}<label><input type="radio" name="status_id" value="{Model_Ticket::STATUS_CLOSED}" onclick="toggleDiv('ticketClosed','block');" {if $ticket->status_id == Model_Ticket::STATUS_CLOSED}checked{/if}> {'status.closed'|devblocks_translate|capitalize}</label>{/if}
				{if $active_worker->hasPriv('core.ticket.actions.delete') || ($ticket->status_id == Model_Ticket::STATUS_DELETED)}<label><input type="radio" name="status_id" value="{Model_Ticket::STATUS_DELETED}" onclick="toggleDiv('ticketClosed','none');" {if $ticket->status_id == Model_Ticket::STATUS_DELETED}checked{/if}> {'status.deleted'|devblocks_translate|capitalize}</label>{/if}
				
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
			<td width="0%" nowrap="nowrap" align="right">Spam Training: </td>
			<td width="100%">
				<label><input type="radio" name="spam_training" value="" checked="checked"> Unknown</label>
				<label><input type="radio" name="spam_training" value="S"> Spam</label>
				<label><input type="radio" name="spam_training" value="N"> Not Spam</label> 
			</td>
		</tr>
		{/if}
		
		{* Importance *}
		<tr>
			<td width="0%" nowrap="nowrap" valign="middle" align="right">{'common.importance'|devblocks_translate|capitalize}: </td>
			<td width="100%">
				<div class="cerb-delta-slider-container">
				<input type="hidden" name="importance" value="{$ticket->importance|default:0}">
					<div class="cerb-delta-slider {if $ticket->importance < 50}cerb-slider-green{elseif $ticket->importance > 50}cerb-slider-red{else}cerb-slider-gray{/if}">
						<span class="cerb-delta-slider-midpoint"></span>
					</div>
				</div>
			</td>
		</tr>
		
		{* Owner *}
		<tr>
			<td width="1%" nowrap="nowrap" align="right" valign="middle">{'common.owner'|devblocks_translate|capitalize}:</td>
			<td width="99%" valign="top">
					<button type="button" class="chooser-abstract" data-field-name="owner_id" data-context="{CerberusContexts::CONTEXT_WORKER}" data-single="true" data-query="inGroups:{$ticket->group_id}" data-autocomplete="if-null"><span class="glyphicons glyphicons-search"></span></button>
					
					<ul class="bubbles chooser-container">
						{$owner = $ticket->getOwner()}
						{if $owner}
							<li><img class="cerb-avatar" src="{devblocks_url}c=avatars&context=worker&context_id={$owner->id}{/devblocks_url}?v={$owner->updated}"><input type="hidden" name="owner_id" value="{$owner->id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_WORKER}" data-context-id="{$owner->id}">{$owner->getName()}</a></li>
						{/if}
					</ul>
			</td>
		</tr>
		
	</table>
	
</fieldset>

{if !empty($custom_fields)}
<fieldset class="peek">
	<legend>{'common.custom_fields'|devblocks_translate}</legend>
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false}
</fieldset>
{/if}

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=CerberusContexts::CONTEXT_TICKET context_id=$ticket->id}

<fieldset class="peek">
	<legend>{'common.comment'|devblocks_translate|capitalize}</legend>
	<textarea name="comment" rows="2" cols="60" style="width:98%;" placeholder="{'comment.notify.at_mention'|devblocks_translate}"></textarea>
</fieldset>

<div class="status"></div>

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

		// Abstract searches
		//$popup.find('button.cerb-search-trigger').cerbSearchTrigger();
		
		// Popup
		$popup.dialog('option','title',"{'common.edit'|devblocks_translate|capitalize|escape:'javascript' nofilter}: {'common.ticket'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		
		// Comments
		
		var $textarea = $(this).find('textarea[name=comment]');
		
		$textarea.autosize();
		
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
				},
				stop: function(event, ui) {
					$input.val(ui.value);
				}
			});
		});
		
		// @mentions
		
		var atwho_workers = {CerberusApplication::getAtMentionsWorkerDictionaryJson() nofilter};

		$textarea.atwho({
			at: '@',
			{literal}displayTpl: '<li>${name} <small style="margin-left:10px;">${title}</small> <small style="margin-left:10px;">@${at_mention}</small></li>',{/literal}
			{literal}insertTpl: '@${at_mention}',{/literal}
			data: atwho_workers,
			searchKey: '_index',
			limit: 10
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
		
		$frm.on('cerb-form-update', function() {
			$btn_recommend.attr('group_id', $frm.find('select[name=group_id]').val());
			$btn_recommend.attr('bucket_id', $frm.find('select[name=bucket_id]').val());
			$btn_recommend.trigger('refresh');
			
			$btn_watchers.attr('group_id', $frm.find('select[name=group_id]').val());
			$btn_watchers.attr('bucket_id', $frm.find('select[name=bucket_id]').val());
			$btn_watchers.trigger('refresh');
			
			// When the group changes, change the owner chooser defaults
			$chooser_owner.attr('data-query', 'inGroups:' + $frm.find('select[name=group_id]').val());
		});
	});
});
</script>