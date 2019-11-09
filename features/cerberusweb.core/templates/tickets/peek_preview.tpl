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
		
		var url = 'c=display&a=reply&forward='+is_forward+'&draft_id='+draft_id+'&reply_mode='+reply_mode+'&is_confirmed='+is_confirmed+'&timestamp={time()}&id=' + msgid;
		
		var $popup_reply = genericAjaxPopup('reply' + msgid, url, null, false, '70%');
		
		$popup_reply.on('cerb-reply-sent cerb-reply-saved cerb-reply-draft', function(e) {
			genericAjaxPopup($layer,'c=internal&a=showPeekPopup&context={CerberusContexts::CONTEXT_TICKET}&context_id={$message->ticket_id}&view_id={$view_id}','reuse',false,'50%');
			{if $view_id}
			genericAjaxGet('view{$view_id}', 'c=internal&a=viewRefresh&id={$view_id}');
			{/if}
		});
	});
	{/if}
});
</script>