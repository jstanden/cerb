{$peek_context = CerberusContexts::CONTEXT_TICKET}
{$peek_context_id = $ticket->id}

<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmTicketPeek" onsubmit="return false;">
<input type="hidden" name="c" value="tickets">
<input type="hidden" name="a" value="savePeek">
<input type="hidden" name="id" value="{$ticket->id}">
{if !empty($link_context)}
<input type="hidden" name="link_context" value="{$link_context}">
<input type="hidden" name="link_context_id" value="{$link_context_id}">
{/if}
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

{if !$edit_mode && !empty($message)}
<div id="ticketPeekMessage" style="margin-bottom:10px;">
		{assign var=headers value=$message->getHeaders()}
		{if !empty($headers.to)}<b>{'message.header.to'|devblocks_translate|capitalize}:</b> {$headers.to|truncate:255}<br>{/if}
		{if !empty($headers.cc)}<b>{'message.header.cc'|devblocks_translate|capitalize}:</b> {$headers.cc|truncate:255}<br>{/if}
		{if !empty($headers.from)}<b>{'message.header.from'|devblocks_translate|capitalize}:</b> {$headers.from}<br>{/if}
		<b>{'message.header.date'|devblocks_translate|capitalize}:</b> {$message->created_date|devblocks_date} ({$message->created_date|devblocks_prettytime})
		<div id="ticketPeekContent" style="width:400;height:200px;overflow:auto;border:1px solid rgb(180,180,180);margin:2px;padding:3px;background-color:rgb(255,255,255);">
			<pre class="emailbody">{$content|trim|escape|devblocks_hyperlinks|devblocks_hideemailquotes nofilter}</pre>
		</div>
		
		{if !is_null($p) && !is_null($p_count)}
		<div style="float:right;">
			{if 0 != $p}<a href="javascript:;" onclick="genericAjaxPopup('peek','c=internal&a=showPeekPopup&context={CerberusContexts::CONTEXT_TICKET}&context_id={$ticket->id}&view_id={$view_id}&msgid={$ticket->first_message_id}', null, false, '650');">&lt;&lt;</a>{/if}
			{if isset($p_prev) && $p_prev}<a href="javascript:;" onclick="genericAjaxPopup('peek','c=internal&a=showPeekPopup&context={CerberusContexts::CONTEXT_TICKET}&context_id={$ticket->id}&view_id={$view_id}&msgid={$p_prev}', null, false, '650');">&lt;{'common.previous_short'|devblocks_translate|capitalize}</a>{/if}
			({$p+1} of {$p_count})
			{if isset($p_next) && $p_next}<a href="javascript:;" onclick="genericAjaxPopup('peek','c=internal&a=showPeekPopup&context={CerberusContexts::CONTEXT_TICKET}&context_id={$ticket->id}&view_id={$view_id}&msgid={$p_next}', null, false, '650');">{'common.next'|devblocks_translate|capitalize}&gt;</a>{/if}
			{if $p+1 != $p_count}<a href="javascript:;" onclick="genericAjaxPopup('peek','c=internal&a=showPeekPopup&context={CerberusContexts::CONTEXT_TICKET}&context_id={$ticket->id}&view_id={$view_id}&msgid={$ticket->last_message_id}', null, false, '650');">&gt;&gt;</a>{/if}
		</div>
		{/if}
		
		<br clear="all" style="clear:all;">
</div>
{/if}

<div style="margin:0px 0px 10px 0px;">
	<span>
		{$recommend_btn_domid = uniqid()}
		{$object_recommendations = DAO_ContextRecommendation::getByContexts($peek_context, array($peek_context_id))}
		{include file="devblocks:cerberusweb.core::internal/recommendations/context_recommend_button.tpl" context=$peek_context context_id=$peek_context_id full=true recommend_btn_domid=$recommend_btn_domid recommend_group_id=$ticket->group_id recommend_bucket_id=$ticket->bucket_id}
	</span>

	<span>
		{$watchers_btn_domid = uniqid()}
		{$object_watchers = DAO_ContextLink::getContextLinks($peek_context, array($peek_context_id), CerberusContexts::CONTEXT_WORKER)}
		{include file="devblocks:cerberusweb.core::internal/watchers/context_follow_button.tpl" context=$peek_context context_id=$peek_context_id full=true watchers_btn_domid=$watchers_btn_domid watchers_group_id=$ticket->group_id watchers_bucket_id=$ticket->bucket_id}
	</span>
</div>

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
			<td width="0%" nowrap="nowrap" align="right">{'contact_org.name'|devblocks_translate|capitalize}: </td>
			<td width="100%">
				<input type="hidden" name="org_id" value="{$ticket->org_id}">
				{$ticket_org = $ticket->getOrg()}
				{if !empty($ticket_org)}
				<div>
					<b>{$ticket_org->name}</b>
					(<a href="javascript:;" onclick="$p=$(this).closest('div');$p.next('div').show();$p.remove();">change</a>)
				</div>
				{/if}
				<div style="display:{if !empty($ticket_org)}none{else}block{/if};">
					<input type="text" name="org_name" size="45" maxlength="255" style="width:98%;" value="{if !empty($ticket)}{$ticket_org->name}{/if}">
				</div>
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
				<label><input type="radio" name="closed" value="0" onclick="toggleDiv('ticketClosed','none');" {if !$ticket->is_closed && !$ticket->is_waiting}checked{/if}> {'status.open'|devblocks_translate|capitalize}</label>
				<label><input type="radio" name="closed" value="2" onclick="toggleDiv('ticketClosed','block');" {if !$ticket->is_closed && $ticket->is_waiting}checked{/if}> {'status.waiting'|devblocks_translate|capitalize}</label>
				{if $active_worker->hasPriv('core.ticket.actions.close') || ($ticket->is_closed && !$ticket->is_deleted)}<label><input type="radio" name="closed" value="1" onclick="toggleDiv('ticketClosed','block');" {if $ticket->is_closed && !$ticket->is_deleted}checked{/if}> {'status.closed'|devblocks_translate|capitalize}</label>{/if}
				{if $active_worker->hasPriv('core.ticket.actions.delete') || ($ticket->is_deleted)}<label><input type="radio" name="closed" value="3" onclick="toggleDiv('ticketClosed','none');" {if $ticket->is_deleted}checked{/if}> {'status.deleted'|devblocks_translate|capitalize}</label>{/if}
				
				<div id="ticketClosed" style="display:{if $ticket->is_closed || $ticket->is_waiting}block{else}none{/if};margin:5px 0px 5px 15px;">
					<b>{'display.reply.next.resume'|devblocks_translate}:</b><br>
					<i>{'display.reply.next.resume_eg'|devblocks_translate}</i><br>
					<input type="text" name="ticket_reopen" size="64" class="input_date" value="{if !empty($ticket->reopen_at)}{$ticket->reopen_at|devblocks_date}{/if}"><br>
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
			<td width="0%" nowrap="nowrap" valign="middle" align="right">{'common.owner'|devblocks_translate|capitalize}: </td>
			<td width="100%">
				<div>
					<select name="owner_id">
						<option value="0"></option>
						{foreach from=$workers item=owner key=owner_id}
						{if $owner->isGroupMember($ticket->group_id)}
						<option value="{$owner_id}" {if $ticket->owner_id==$owner_id}selected="selected"{/if}>{$owner->getName()}</option>
						{/if}
						{/foreach}
					</select>
					<button type="button" onclick="$(this).prev('select[name=owner_id]').val('{$active_worker->id}');">{'common.me'|devblocks_translate|lower}</button>
					<button type="button" onclick="$(this).prevAll('select[name=owner_id]').first().val('0');">{'common.nobody'|devblocks_translate|lower}</button>
				</div>
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

{* Comments *}
{include file="devblocks:cerberusweb.core::internal/peek/peek_comments_pager.tpl" comments=$comments}

<fieldset class="peek">
	<legend>{'common.comment'|devblocks_translate|capitalize}</legend>
	<textarea name="comment" rows="2" cols="60" style="width:98%;" placeholder="{'comment.notify.at_mention'|devblocks_translate}"></textarea>
</fieldset>

{if !empty($ticket->id)}
<div style="float:right;">
	<a href="{devblocks_url}&c=profiles&type=ticket&id={$ticket->mask}{/devblocks_url}">{'addy_book.peek.view_full'|devblocks_translate}</a>
</div>
{/if}

<button type="button" onclick="genericAjaxPopupPostCloseReloadView(null,'frmTicketPeek','{$view_id}',false,'ticket_save');"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate}</button>

<br>

</form>

<script type="text/javascript">
	var $popup = genericAjaxPopupFetch('peek');
	
	$popup.one('popup_open',function(event,ui) {
		var $frm = $('#frmTicketPeek');
		var $btn_recommend = $('#{$recommend_btn_domid}');
		var $btn_watchers = $('#{$watchers_btn_domid}');
		
		$(this).dialog('option','title',"{$ticket->subject|escape:'javascript' nofilter}");
		$("#ticketPeekContent").css('width','100%');
		
		ajax.orgAutoComplete('#frmTicketPeek input:text[name=org_name]');
		
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
		});
	});
</script>