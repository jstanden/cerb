{$headers = $message->getHeaders()}
<div class="block" style="margin-bottom:10px;">
	{$sender_id = $message->address_id}
	{if isset($message_senders.$sender_id)}
		{$sender = $message_senders.$sender_id}
		{$sender_org_id = $sender->contact_org_id}
		{$sender_org = $message_sender_orgs.$sender_org_id}
		{$sender_contact = $sender->getContact()}
		{$sender_worker = $message->getWorker()}
		{$is_outgoing = $message->is_outgoing}
		{$is_not_sent = $message->is_not_sent}

		{if $expanded}
		{$attachments = DAO_Attachment::getByContextIds(CerberusContexts::CONTEXT_MESSAGE, $message->id)}
		{else}
		{$attachments = []}
		{/if}

		{if !$embed}
		<div class="toolbar-minmax" style="float:right;display:none;">
			{if $active_worker->hasPriv('contexts.cerberusweb.contexts.message.update')}
			<button type="button" class="edit" data-context="{CerberusContexts::CONTEXT_MESSAGE}" data-context-id="{$message->id}" data-edit="true"><span class="glyphicons glyphicons-cogwheel"></span></button>
			{/if}

			{if $expanded && $attachments}
			<button type="button" class="cerb-search-trigger" data-context="{CerberusContexts::CONTEXT_ATTACHMENT}" data-query="on.message:(id:{$message->id})"><span class="glyphicons glyphicons-paperclip"></span></button>
			{/if}

			{$permalink_url = "{devblocks_url full=true}c=profiles&type=ticket&mask={$ticket->mask}{/devblocks_url}/#message{$message->id}"}
			<button type="button" onclick="genericAjaxPopup('permalink', 'c=internal&a=showPermalinkPopup&url={$permalink_url|escape:'url'}');" title="{'common.permalink'|devblocks_translate|lower}"><span class="glyphicons glyphicons-link"></span></button>

			{if !$expanded}
				<button id="btnMsgMax{$message->id}" type="button" onclick="genericAjaxGet('message{$message->id}','c=display&a=getMessage&id={$message->id}');" title="{'common.maximize'|devblocks_translate|lower}"><span class="glyphicons glyphicons-resize-full"></span></button>
			{else}
				<button id="btnMsgMin{$message->id}" type="button" onclick="genericAjaxGet('message{$message->id}','c=display&a=getMessage&id={$message->id}&hide=1');" title="{'common.minimize'|devblocks_translate|lower}"><span class="glyphicons glyphicons-resize-small"></span></button>
			{/if}
		</div>
		{/if}

		<div style="display:inline;margin-right:5px;">
			<span class="tag" style="color:white;{if !$is_outgoing}background-color:rgb(185,50,40);{else}background-color:rgb(100,140,25);{/if}">{if $is_outgoing}{if $is_not_sent}{'mail.saved'|devblocks_translate|lower}{else}{'mail.sent'|devblocks_translate|lower}{/if}{else}{'mail.received'|devblocks_translate|lower}{/if}</span>

			{if $message->was_encrypted}
			<span class="tag" style="background-color:rgb(250,220,74);color:rgb(165,100,33);" title="{'common.encrypted'|devblocks_translate|capitalize}"><span class="glyphicons glyphicons-lock"></span></span>
			{/if}
		</div>

		{if $sender_worker}
			{if $message->was_signed}<span class="glyphicons glyphicons-circle-ok" style="{if $expanded}font-size:1.3em;{/if}color:rgb(66,131,73);" title="{'common.signed'|devblocks_translate|capitalize}"></span>{/if}
			<a href="javascript:;" class="cerb-peek-trigger" style="font-weight:bold;{if $expanded}font-size:1.3em;{/if}" data-context="{CerberusContexts::CONTEXT_WORKER}" data-context-id="{$sender_worker->id}">{if 0 != strlen($sender_worker->getName())}{$sender_worker->getName()}{else}&lt;{$sender_worker->getEmailString()}&gt;{/if}</a>
			&nbsp;
			{if $sender_worker->title}
				{$sender_worker->title}
			{/if}
		{else}
			{if $sender_contact}
				{$sender_org = $sender_contact->getOrg()}
				{if $message->was_signed}<span class="glyphicons glyphicons-circle-ok" style="{if $expanded}font-size:1.3em;{/if}color:rgb(66,131,73);" title="{'common.signed'|devblocks_translate|capitalize}"></span>{/if}
				<a href="javascript:;" class="cerb-peek-trigger" style="font-weight:bold;{if $expanded}font-size:1.3em;{/if}" data-context="{CerberusContexts::CONTEXT_CONTACT}" data-context-id="{$sender_contact->id}">{$sender_contact->getName()}</a>
				&nbsp;
				{if $sender_contact->title}
					{$sender_contact->title}
				{/if}
				{if $sender_contact->title && $sender_org} at {/if}
				{if $sender_org}
					<a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_ORG}" data-context-id="{$sender_org->id}"><b>{$sender_org->name}</b></a>
				{/if}
			{else}
				{$sender_org = $sender->getOrg()}
				{if $message->was_signed}<span class="glyphicons glyphicons-circle-ok" style="{if $expanded}font-size:1.3em;{/if}color:rgb(66,131,73);" title="{'common.signed'|devblocks_translate|capitalize}"></span>{/if}
				<a href="javascript:;" class="cerb-peek-trigger" style="font-weight:bold;{if $expanded}font-size:1.3em;{/if}" data-context="{CerberusContexts::CONTEXT_ADDRESS}" data-context-id="{$sender_id}">&lt;{$sender->email}&gt;</a>
				&nbsp;
				{if $sender_org}
					<a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_ORG}" data-context-id="{$sender_org->id}"><b>{$sender_org->name}</b></a>
				{/if}
			{/if}
		{/if}

		<div style="float:left;margin:0px 5px 5px 0px;">
			{if $sender_worker}
				<img src="{devblocks_url}c=avatars&context=worker&context_id={$sender_worker->id}{/devblocks_url}?v={$sender_worker->updated}" style="height:64px;width:64px;border-radius:64px;">
			{else}
				{if $sender_contact}
				<img src="{devblocks_url}c=avatars&context=contact&context_id={$sender_contact->id}{/devblocks_url}?v={$sender_contact->updated_at}" style="height:64px;width:64px;border-radius:64px;">
				{else}
				<img src="{devblocks_url}c=avatars&context=address&context_id={$sender->id}{/devblocks_url}?v={$sender->updated}" style="height:64px;width:64px;border-radius:64px;">
				{/if}
			{/if}
		</div>

		<br>
	{/if}

	<div {if !$embed}id="{$message->id}sh"{/if} style="display:block;margin-top:2px;">
		{if isset($headers.from)}<b>{'message.header.from'|devblocks_translate|capitalize}:</b> {$headers.from|escape|nl2br nofilter}<br>{/if}
		{if isset($headers.to)}<b>{'message.header.to'|devblocks_translate|capitalize}:</b> {$headers.to|escape|nl2br nofilter}<br>{/if}
		{if isset($headers.cc)}<b>{'message.header.cc'|devblocks_translate|capitalize}:</b> {$headers.cc|escape|nl2br nofilter}<br>{/if}
		{if isset($headers.bcc)}<b>{'message.header.bcc'|devblocks_translate|capitalize}:</b> {$headers.bcc|escape|nl2br nofilter}<br>{/if}
		{if isset($headers.subject)}<b>{'message.header.subject'|devblocks_translate|capitalize}:</b> {$headers.subject}<br>{/if}
		<b>{'message.header.date'|devblocks_translate|capitalize}:</b> {$message->created_date|devblocks_date} (<abbr title="{$headers.date}">{$message->created_date|devblocks_prettytime}</abbr>)

		{if !empty($message->response_time)}
			<span style="margin-left:10px;color:rgb(100,140,25);">Replied in {$message->response_time|devblocks_prettysecs:2}</span>
		{/if}
		<br>
	</div>

	{if !$embed && $expanded}
	<div style="margin:2px;margin-left:10px;" id="{$message->id}skip" class="cerb-no-print">
		<button type="button" onclick="document.location='#{$message->id}act';">{'display.convo.skip_to_bottom'|devblocks_translate|lower}</button>
	</div>
	{/if}

	{if $expanded}
	<div style="clear:both;display:block;padding-top:10px;">
		{if DAO_WorkerPref::get($active_worker->id, 'mail_disable_html_display', 0)}
			{$html_body = null}
		{else}
			{$html_body = $message->getContentAsHtml()}
		{/if}

		{if !empty($html_body)}
			<div class="emailBodyHtml">
				{$html_body nofilter}
			</div>
		{else}
			<pre class="emailbody">{$message->getContent()|trim|escape|devblocks_hyperlinks|devblocks_hideemailquotes nofilter}</pre>
		{/if}
		<br>

		{if $active_worker->hasPriv('core.display.actions.attachments.download')}
			{include file="devblocks:cerberusweb.core::internal/attachments/list.tpl" context="{CerberusContexts::CONTEXT_MESSAGE}" context_id=$message->id attachments=$attachments}
		{/if}

        {$values = array_shift(DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_MESSAGE, $message->id))|default:[]}
		{if $values}
        {$message_custom_fields = Page_Profiles::getProfilePropertiesCustomFields(CerberusContexts::CONTEXT_MESSAGE, $values)}
        {$message_custom_fieldsets = Page_Profiles::getProfilePropertiesCustomFieldsets(CerberusContexts::CONTEXT_MESSAGE, $message->id, $values)}
        <div style="margin-top:10px;">
            {if $message_custom_fields}
                <fieldset class="properties" style="padding:5px 0;border:0;">
                    <legend>{'common.properties'|devblocks_translate|capitalize}</legend>

                    <div style="padding:0px 5px;display:flex;flex-flow:row wrap;">
                        {foreach from=$message_custom_fields item=v key=k name=message_custom_fields}
                            <div style="flex:0 0 200px;text-overflow:ellipsis;">
                                {include file="devblocks:cerberusweb.core::internal/custom_fields/profile_cell_renderer.tpl"}
                            </div>
                        {/foreach}
                    </div>
                </fieldset>
            {/if}

            {include file="devblocks:cerberusweb.core::internal/custom_fieldsets/profile_fieldsets.tpl" properties=$message_custom_fieldsets}
        </div>
		{/if}

		{if !$embed}
		<table width="100%" cellpadding="0" cellspacing="0" border="0" class="cerb-no-print">
			<tr>
				<td align="left" id="{$message->id}act">
					{* If not requester *}
					{if !$message->is_outgoing && !isset($requesters.{$sender_id})}
						<button type="button" data-cerb-button="requester-add"><span class="glyphicons glyphicons-circle-plus" style="color:rgb(0,180,0);"></span> {'display.ui.add_to_recipients'|devblocks_translate}</button>
					{/if}

					{if $active_worker->hasPriv('core.display.actions.reply')}
						<button type="button" class="reply split-left cerb-button-reply" title="{if 2 == $mail_reply_button}{'display.reply.only_these_recipients'|devblocks_translate}{elseif 1 == $mail_reply_button}{'display.reply.no_quote'|devblocks_translate}{else}{'display.reply.quote'|devblocks_translate}{/if}"><span class="glyphicons glyphicons-share" style="color:rgb(0,180,0);"></span> {'common.reply'|devblocks_translate|capitalize}</button><!--
						--><button type="button" class="split-right" onclick="$ul=$(this).next('ul');$ul.toggle();if($ul.is(':hidden')) { $ul.blur(); } else { $ul.find('a:first').focus(); }"><span class="glyphicons glyphicons-chevron-down" style="font-size:12px;color:white;"></span></button>
						<ul class="cerb-popupmenu cerb-float" style="margin-top:-5px;">
							<li><a href="javascript:;" class="cerb-button-reply-quote">{'display.reply.quote'|devblocks_translate}</a></li>
							<li><a href="javascript:;" class="cerb-button-reply-only-these">{'display.reply.only_these_recipients'|devblocks_translate}</a></li>
							<li><a href="javascript:;" class="cerb-button-reply-noquote">{'display.reply.no_quote'|devblocks_translate}</a></li>
							{if $active_worker->hasPriv('core.display.actions.forward')}<li><a href="javascript:;" class="cerb-button-reply-forward">{'display.ui.forward'|devblocks_translate|capitalize}</a></li>{/if}
							<li><a href="javascript:;" class="cerb-button-reply-relay" data-message-id="{$message->id}">Relay to worker email</a></li>
						</ul>
					{/if}

					{if $active_worker->hasPriv('contexts.cerberusweb.contexts.message.comment')}
						<button type="button" class="cerb-sticky-trigger" data-context="{CerberusContexts::CONTEXT_COMMENT}" data-context-id="0" data-edit="context:{CerberusContexts::CONTEXT_MESSAGE} context.id:{$message->id}"><span class="glyphicons glyphicons-edit"></span> {'display.ui.sticky_note'|devblocks_translate|capitalize}</button>
					{/if}

					<button type="button" onclick="genericAjaxPopup('message_headers','c=profiles&a=handleSectionAction&section=ticket&action=showMessageFullHeadersPopup&id={$message->id}');"><span class="glyphicons glyphicons-envelope"></span> {'message.headers'|devblocks_translate|capitalize}</button>

					<button type="button" onclick="$('#{$message->id}options').toggle();"><span class="glyphicons glyphicons-more"></span></button>
				</td>
			</tr>
		</table>
		{/if}

		{if !$embed}
		<form id="{$message->id}options" style="padding-top:10px;display:none;" method="post" action="{devblocks_url}{/devblocks_url}">
			<input type="hidden" name="c" value="display">
			<input type="hidden" name="a" value="">
			<input type="hidden" name="id" value="{$message->id}">
			<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

			{if $ticket->first_message_id != $message->id && $active_worker->hasPriv('core.display.actions.split')} {* Don't allow splitting of a single message *}
				<button type="button" onclick="$frm=$(this).closest('form');$frm.find('input:hidden[name=a]').val('doSplitMessage');$frm.submit();" title="Split message into new ticket"><span class="glyphicons glyphicons-duplicate"></span> {'display.button.split_ticket'|devblocks_translate|capitalize}</button>
			{/if}

			{if $message->is_outgoing}
				<button type="button" onclick="genericAjaxPopup('message_resend','c=profiles&a=handleSectionAction&section=ticket&action=showResendMessagePopup&id={$message->id}');"><span class="glyphicons glyphicons-share"></span> Send Again</button>
			{/if}

			{* Plugin Toolbar *}
			{if !empty($message_toolbaritems)}
				{foreach from=$message_toolbaritems item=renderer}
					{if !empty($renderer)}{$renderer->render($message)}{/if}
				{/foreach}
			{/if}
		</form>
		{/if}
	</div> <!-- end visible -->
	{/if}
</div>

{if !$embed}
<div id="{$message->id}b"></div>
<div id="{$message->id}notes">
	{include file="devblocks:cerberusweb.core::display/modules/conversation/notes.tpl"}
</div>
<div id="reply{$message->id}"></div>
{/if}

{if !$embed}
<script type="text/javascript">
$(function() {
	var $msg = $('#message{$message->id}').unbind();
	
	$msg.hover(
		function() {
			$msg.find('div.toolbar-minmax').show();
		},
		function() {
			$msg.find('div.toolbar-minmax').hide();
		}
	);
	
	$msg.find('.cerb-search-trigger')
		.cerbSearchTrigger()
		;
	
	try {
		if($('#{$message->id}act').visible()) {
			$('#{$message->id}skip').hide();
		}
	} catch(e) {
	}
});
</script>
{/if}

{if !$embed && $active_worker->hasPriv('core.display.actions.reply')}
<script type="text/javascript">
$(function() {
	var $msg = $('#message{$message->id}');
	var $actions = $('#{$message->id}act');
	var $notes = $('#{$message->id}notes');
	
	$msg.find('.cerb-peek-trigger')
		.cerbPeekTrigger()
		;

	$msg.find('.cerb-sticky-trigger')
		.cerbPeekTrigger()
			.on('cerb-peek-saved', function(e) {
				e.stopPropagation();
				
				if(e.id && e.comment_html) {
					var $new_note = $('<div id="comment' + e.id + '"/>').hide();
					$new_note.html(e.comment_html).prependTo($notes).fadeIn();
				}
			})
			;
	
	// Edit
	
	$msg.find('button.edit')
		.cerbPeekTrigger()
		.on('cerb-peek-opened', function(e) {
		})
		.on('cerb-peek-saved', function(e) {
			e.stopPropagation();
			genericAjaxGet('message{$message->id}','c=display&a=getMessage&id={$message->id}&hide=0');
		})
		.on('cerb-peek-deleted', function(e) {
			e.stopPropagation();
			$('#message{$message->id}').remove();
			
		})
		.on('cerb-peek-closed', function(e) {
		})
		;

	$actions.find('[data-cerb-button=requester-add]').on('click', function() {
		$(this).remove();

		var formData = new FormData();
		formData.append('c', 'display');
		formData.append('a', 'requesterAdd');
		formData.append('ticket_id', '{$ticket->id}');
		formData.append('email', '{$sender->email}');

		genericAjaxPost(formData, '', '');
	});
	
	$actions
		.find('ul.cerb-popupmenu')
		.hover(
			function(e) { }, 
			function(e) { $(this).hide(); }
		)
		.find('> li')
		.click(function(e) {
			$(this).closest('ul.cerb-popupmenu').hide();
	
			e.stopPropagation();
			if(!$(e.target).is('li'))
			return;
	
			$(this).find('a').trigger('click');
		})
		;
	
	$actions.find('button.cerb-button-reply')
		.on('click', function(e) {
			if(e.originalEvent && e.originalEvent.detail && e.originalEvent.detail > 1)
				return;
			
			var evt = jQuery.Event('cerb_reply');
			evt.message_id = '{$message->id}';
			evt.is_forward = 0;
			evt.draft_id = 0;
			evt.reply_mode = '{$mail_reply_button}';
			evt.is_confirmed = 0;
			
			$msg.trigger(evt);
		})
		;
	
	$actions.find('a.cerb-button-reply-quote')
		.on('click', function(e) {
			if(e.originalEvent && e.originalEvent.detail && e.originalEvent.detail > 1)
				return;
			
			var evt = jQuery.Event('cerb_reply');
			evt.message_id = '{$message->id}';
			evt.is_forward = 0;
			evt.draft_id = 0;
			evt.reply_mode = 0;
			evt.is_confirmed = 0;
			
			$msg.trigger(evt);
		})
		;
	
	$actions.find('a.cerb-button-reply-only-these')
		.on('click', function(e) {
			if(e.originalEvent && e.originalEvent.detail && e.originalEvent.detail > 1)
				return;
			
			var evt = jQuery.Event('cerb_reply');
			evt.message_id = '{$message->id}';
			evt.is_forward = 0;
			evt.draft_id = 0;
			evt.reply_mode = 2;
			evt.is_confirmed = 0;
			
			$msg.trigger(evt);
		})
		;
	
	$actions.find('a.cerb-button-reply-noquote')
		.on('click', function(e) {
			if(e.originalEvent && e.originalEvent.detail && e.originalEvent.detail > 1)
				return;
			
			var evt = jQuery.Event('cerb_reply');
			evt.message_id = '{$message->id}';
			evt.is_forward = 0;
			evt.draft_id = 0;
			evt.reply_mode = 1;
			evt.is_confirmed = 0;
			
			$msg.trigger(evt);
		})
		;
	
	$actions.find('a.cerb-button-reply-forward')
		.on('click', function(e) {
			if(e.originalEvent && e.originalEvent.detail && e.originalEvent.detail > 1)
				return;
			
			var evt = jQuery.Event('cerb_reply');
			evt.message_id = '{$message->id}';
			evt.is_forward = 1;
			evt.draft_id = 0;
			evt.reply_mode = 0;
			evt.is_confirmed = 0;
			
			$msg.trigger(evt);
		})
		;
	
	$actions.find('a.cerb-button-reply-relay')
		.on('click', function() {
			genericAjaxPopup('relay', 'c=display&a=showRelayMessagePopup&id={$message->id}', null, false, '50%');
		})
		;
	});
</script>
{/if}