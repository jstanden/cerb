{$preview_id = "preview{uniqid()}"}
<div id="{$preview_id}">
{if $message && is_a($message, 'Model_Message')}
	{include file="devblocks:cerberusweb.core::display/modules/conversation/message.tpl" expanded=true embed=true}

	{if $is_writeable}
	<div style="margin-top:10px;">
		<button type="button" class="cerb-button-reply"><span class="glyphicons glyphicons-share" style="color:rgb(0,180,0);"></span> {'common.reply'|devblocks_translate|capitalize}</button>
	</div>
	{/if}
	
{elseif $comment && is_a($comment, 'Model_Comment')}
	{include file="devblocks:cerberusweb.core::internal/comments/comment.tpl" embed=true}
{elseif $draft && is_a($draft, 'Model_MailQueue')}
	{include file="devblocks:cerberusweb.core::display/modules/conversation/draft.tpl" embed=true}

	{if $is_writeable && in_array($draft->type,['mail.compose','ticket.reply','ticket.forward'])}
		<div style="margin-top:10px;">
			<button type="button" class="cerb-button-resume"><span class="glyphicons glyphicons-redo" style="color:rgb(0,180,0);"></span> {'common.resume'|devblocks_translate|capitalize}</button>
		</div>
	{/if}
{/if}
</div>

<script type="text/javascript">
$(function() {
	var $preview = $('#{$preview_id}');
	$preview.find('.cerb-peek-trigger').cerbPeekTrigger();
	$preview.find('.cerb-search-trigger').cerbSearchTrigger();

	{if $message && is_a($message, 'Model_Message') && $is_writeable}
	$preview.find('.cerb-button-reply').on('click', function(e) {
		var $popup = genericAjaxPopupFind($preview);
		var $layer = $popup.attr('data-layer');
		
		e.preventDefault();
		e.stopPropagation();
		
		var msg_id = '{$message->id}';
		var is_forward = 0;
		var draft_id = 0;
		var reply_mode = 0;

		var formData = new FormData();
		formData.set('c', 'profiles');
		formData.set('a', 'invoke');
		formData.set('module', 'ticket');
		formData.set('action', 'reply');
		formData.set('forward', is_forward);
		formData.set('draft_id', draft_id);
		formData.set('reply_mode', reply_mode);
		formData.set('timestamp', '{time()}');
		formData.set('id', String(msg_id));

		var $popup_reply = genericAjaxPopup('reply' + msg_id, formData, null, false, '70%');
		
		$popup_reply.on('cerb-reply-sent cerb-reply-saved cerb-reply-draft', function(e) {
			// Reload the popup outside the main loop so the scrollTop updates first
			setTimeout(function() {
				genericAjaxPopup($layer,'c=internal&a=invoke&module=records&action=showPeekPopup&context={CerberusContexts::CONTEXT_TICKET}&context_id={$message->ticket_id}&view_id={$view_id}','reuse',false,'50%');

				{if $view_id}
				genericAjaxGet('view{$view_id}', 'c=internal&a=invoke&module=worklists&action=refresh&id={$view_id}');
				{/if}
			}, 0);
		});
	});
	{elseif $draft && is_a($draft, 'Model_MailQueue') && $is_writeable}
	$preview.find('.cerb-button-resume').on('click', function(e) {
		var $popup = genericAjaxPopupFind($preview);
		var $layer = $popup.attr('data-layer');

		e.preventDefault();
		e.stopPropagation();

		var formData = new FormData();
		formData.set('c', 'profiles');
		formData.set('a', 'invoke');
		formData.set('module', 'draft');
		formData.set('action', 'resume');
		formData.set('draft_id', '{$draft->id}');
		
		var $popup_draft = genericAjaxPopup('draft',formData,null,false,'80%');
		
		$popup_draft.on('cerb-compose-sent cerb-compose-discard cerb-reply-sent cerb-reply-saved cerb-reply-discard', function() {
			genericAjaxPopupClose($popup);
			{if $view_id}
			genericAjaxGet('view{$view_id}', 'c=internal&a=invoke&module=worklists&action=refresh&id={$view_id}');
			{/if}
		});
		
		$popup_draft.on('cerb-compose-draft cerb-reply-draft', function() {
			// Reload the popup outside the main loop so the scrollTop updates first
			setTimeout(function() {
				genericAjaxPopup($layer, 'c=internal&a=invoke&module=records&action=showPeekPopup&context={CerberusContexts::CONTEXT_DRAFT}&context_id={$draft->id}', 'reuse', false, '50%');
				
				{if $view_id}
				genericAjaxGet('view{$view_id}', 'c=internal&a=invoke&module=worklists&action=refresh&id={$view_id}');
				{/if}
			}, 0);
		});
	});
	{/if}
});
</script>