<form style="margin:5px;">
	{if $active_worker->hasPriv("contexts.{CerberusContexts::CONTEXT_TICKET}.comment")}<button type="button" id="btnComment" class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_COMMENT}" data-context-id="0" data-edit="context:{CerberusContexts::CONTEXT_TICKET} context.id:{$ticket->id}"><span class="glyphicons glyphicons-conversation"></span> {'common.comment'|devblocks_translate|capitalize}</button>{/if}
	{if !$expand_all}<button id="btnReadAll" title="{'display.shortcut.read_all'|devblocks_translate}" type="button" onclick="document.location='{devblocks_url}c=profiles&type=ticket&id={$ticket->mask}&tab=conversation&opt=read_all{/devblocks_url}';"><span class="glyphicons glyphicons-book-open"></span> {'display.button.read_all'|devblocks_translate|capitalize}</button>{/if} 
</form>

{if is_array($pending_drafts)}
<div style="color:rgb(236,87,29);">
	<p>
	<span class="glyphicons glyphicons-circle-exclamation-mark"></span> 
	This ticket has <strong>{$drafts|count nofilter}</strong> pending draft{if $drafts|count == 1}{else}s{/if}: 
	{foreach from=$pending_drafts item=draft name=drafts}
		<a href="#draft{$draft->id}">{$draft->updated|devblocks_prettytime}</a>{if !$smarty.foreach.drafts.last}, {/if} 
	{/foreach}
	</p>
</div>
{/if}

<div id="tourDisplayConversation"></div>
{if $expand_all}
	<b>{'display.convo.order_oldest'|devblocks_translate}</b>
{else}
	<b>{'display.convo.order_newest'|devblocks_translate}</b>
{/if}

<div id="conversation" style="margin-top:10px;">
{if !empty($ticket)}
	{if !empty($convo_timeline)}
		{$state = ''}
	
		{foreach from=$convo_timeline item=convo_set name=items}
			{$last_state = $state}
		
			{if $convo_set.0=='m'}
				{$state = 'message'}
			{elseif $convo_set.0=='c'}
				{$state = 'comment'}
			{elseif $convo_set.0=='d'}
				{$state = 'draft'}
			{elseif $convo_set.0=='l'}
				{$state = 'log'}
			{/if}
			
			{if $last_state == 'log' && $state != 'log'}
				</div>
			{/if}
			
			{if $state == 'message'}
				{assign var=message_id value=$convo_set.1}
				{assign var=message value=$messages.$message_id}
				
				<div id="{$message->id}t">
					{assign var=expanded value=false}
					{if $expand_all || ($focus_ctx == CerberusContexts::CONTEXT_MESSAGE && $focus_ctx_id==$message_id) || $latest_message_id==$message_id || isset($message_notes.$message_id)}{assign var=expanded value=true}{/if}
					{include file="devblocks:cerberusweb.core::display/modules/conversation/message.tpl" expanded=$expanded}
				</div>
				
			{elseif $state == 'comment'}
				{assign var=comment_id value=$convo_set.1}
				{assign var=comment value=$comments.$comment_id}
				
				<div id="comment{$comment->id}">
					{include file="devblocks:cerberusweb.core::internal/comments/comment.tpl"}
				</div>
				
			{elseif $state == 'draft'}
				{assign var=draft_id value=$convo_set.1}
				{assign var=draft value=$drafts.$draft_id}
				
				<div id="draft{$draft->id}">
					{include file="devblocks:cerberusweb.core::display/modules/conversation/draft.tpl"}
				</div>
				
			{elseif $state == 'log'}
				{assign var=entry_id value=$convo_set.1}
				{assign var=entry value=$activity_log.$entry_id}
				
				{if $last_state != 'log'}
					{$last_entry_timestamp = null}
					<div class="block hover-underline" style="margin-bottom:10px;">
				{/if}
				
				<div id="log{$entry->id}" style="margin-bottom:5px;">
					{include file="devblocks:cerberusweb.core::display/modules/conversation/activity_log_entry.tpl"}
					{$last_entry_timestamp = $entry->created|devblocks_prettytime}
				</div>
			{/if}
			
		{/foreach}
	{/if}
	
{else}
  {'display.convo.no_messages'|devblocks_translate}
  <br>
{/if}
</div>

<script type="text/javascript">
	{if $focus_ctx && focus_ctx_id}
	$(function() {
		{if $focus_ctx == CerberusContexts::CONTEXT_COMMENT}
		var $anchor = $('#comment{$focus_ctx_id}');
		{elseif $focus_ctx == CerberusContexts::CONTEXT_MESSAGE}
		var $anchor = $('#{$focus_ctx_id}t');
		{/if}
		
		if($anchor.length > 0) {
			var offset = $anchor.offset();
			window.scrollTo(offset.left, offset.top);
			
			$anchor.find('> div.block').effect('highlight', { }, 750);
		}
	});
	{/if}

	$('#btnComment')
		.cerbPeekTrigger()
			.on('cerb-peek-saved', function(e) {
				if(e.id && e.comment_html) {
					var $convo = $('#conversation');
					var $new_comment = $('<div id="comment' + e.id + '"/>').hide();
					$new_comment.html(e.comment_html).prependTo($convo).fadeIn();
				}
			})
		;
	
	var displayReply = function(msgid, is_forward, draft_id, reply_mode, is_confirmed) {
		var msgid = parseInt(msgid);
		var $div = $('#reply' + msgid);
		
		if(0 == $div.length)
			return;
		
		var is_forward = (null == is_forward || 0 == is_forward) ? 0 : 1;
		var draft_id = (null == draft_id) ? 0 : parseInt(draft_id);
		var reply_mode = (null == reply_mode) ? 0 : parseInt(reply_mode);
		var is_confirmed = (null == is_confirmed) ? 0 : parseInt(is_confirmed);

		// If the reply window is already open, just focus it
		if($div.find('> div.reply_frame').length > 0) {
			$div.find('input:text:first').first().focus();
			return;
		}
		
		showLoadingPanel();
		
		genericAjaxGet('', 'c=display&a=reply&forward='+is_forward+'&draft_id='+draft_id+'&reply_mode='+reply_mode+'&is_confirmed='+is_confirmed+'&timestamp={time()}&id=' + msgid,
			function(html) {
				var $div = $('#reply' + msgid);
				
				hideLoadingPanel();
				
				if(0 == $div.length)
					return;
				
				$div.html(html);
				
				var offset = $div.offset();
				window.scrollTo(offset.left, offset.top);
			}
		);
	}
</script>
