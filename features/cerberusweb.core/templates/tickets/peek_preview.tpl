{$preview_id = "preview{uniqid()}"}
<div id="{$preview_id}">
{if $message && $message instanceof Model_Message}
	{include file="devblocks:cerberusweb.core::display/modules/conversation/message.tpl" expanded=true embed=true}

	{if $is_writeable}
	<div style="margin-top:10px;">
		<button type="button" class="cerb-button-reply"><span class="glyphicons glyphicons-share" style="color:rgb(0,180,0);"></span> {'common.reply'|devblocks_translate|capitalize}</button>
	</div>
	{/if}
	
{elseif $comment && $comment instanceof Model_Comment}
	{include file="devblocks:cerberusweb.core::internal/comments/comment.tpl" embed=true}
{elseif $draft && $draft instanceof Model_MailQueue}
	{include file="devblocks:cerberusweb.core::display/modules/conversation/draft.tpl" embed=true}
{/if}
</div>

<script type="text/javascript">
$(function() {
	var $preview = $('#{$preview_id}');
	$preview.find('.cerb-peek-trigger').cerbPeekTrigger();
	$preview.find('.cerb-search-trigger').cerbSearchTrigger();

	{if $message && $message instanceof Model_Message}
	$preview.find('.cerb-button-reply').on('click', function(e) {
		var $popup = genericAjaxPopupFind($preview);
		var $layer = $popup.attr('data-layer');
		
		e.preventDefault();
		e.stopPropagation();
		
		var msgid = {$message->id};
		var is_forward = 0;
		var draft_id = 0;
		var reply_mode = 0;
		var is_confirmed = 0;

		var formData = new FormData();
		formData.set('c', 'profiles');
		formData.set('a', 'invoke');
		formData.set('module', 'ticket');
		formData.set('action', 'reply');
		formData.set('forward', is_forward);
		formData.set('draft_id', draft_id);
		formData.set('reply_mode', reply_mode);
		formData.set('is_confirmed', is_confirmed);
		formData.set('timestamp', '{time()}');
		formData.set('id', msgid);

		var $popup_reply = genericAjaxPopup('reply' + msgid, formData, null, false, '70%');
		
		$popup_reply.on('cerb-reply-sent cerb-reply-saved cerb-reply-draft', function(e) {
			genericAjaxPopup($layer,'c=internal&a=invoke&module=records&action=showPeekPopup&context={CerberusContexts::CONTEXT_TICKET}&context_id={$message->ticket_id}&view_id={$view_id}','reuse',false,'50%');
			{if $view_id}
			genericAjaxGet('view{$view_id}', 'c=internal&a=invoke&module=worklists&action=refresh&id={$view_id}');
			{/if}
		});
	});
	{/if}
});
</script>