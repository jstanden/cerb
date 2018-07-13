<div class="cerb-conversation" id="widget{$widget->id}">
	{if !empty($merge_parent)}
	<div class="help-box">
		<h1>This record was merged</h1>
		
		<p>
		You can find the new record here: <a href="{devblocks_url}c=profiles&w=ticket&mask={$merge_parent->mask}{/devblocks_url}"><b>[#{$merge_parent->mask}] {$merge_parent->subject}</b></a>
		</p>
	</div>
	{/if}
	
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
	<div>
		<b>{'display.convo.order_oldest'|devblocks_translate}</b>
	</div>
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
				{/if}
				
				{if $state == 'message'}
					{assign var=message_id value=$convo_set.1}
					{assign var=message value=$messages.$message_id}
					
					<div id="message{$message->id}">
						{assign var=expanded value=false}
						{if $expand_all || $latest_message_id==$message_id || isset($message_notes.$message_id)}{assign var=expanded value=true}{/if}
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
					
				{/if}
				
			{/foreach}
		{else}
			<div style="color:rgb(120,120,120);text-align:center;font-size:1.2em;">
				({'display.convo.no_messages'|devblocks_translate})
			</div>
			<br>
		{/if}
	{/if}
	</div>
</div>

<script type="text/javascript">
$(function() {
	var $widget = $('#widget{$widget->id}');
	
	var $parent = $widget.closest('.cerb-profile-widget')
		.off('.widget{$widget->id}')
		;
	
	// Quick reply
	$parent
		.on('keydown.widget{$widget->id}', null, 'R', function(e) {
			e.preventDefault();
			e.stopPropagation();
			{if $expand_all}
			$('#conversation').find('div[id^="message"]').last().find('button.reply').click();
			{else}
			$('#conversation').find('div[id^="message"]').first().find('button.reply').click();
			{/if}
		})
		;
	
	// Reply menu
	$parent
		.on('keydown.widget{$widget->id}', null, 'Shift+R', function(e) {
			e.preventDefault();
			e.stopPropagation();
			{if $expand_all}
			$('#conversation').find('div[id^="message"]').last().find('button.split-right').click();
			{else}
			$('#conversation').find('div[id^="message"]').first().find('button.split-right').click();
			{/if}
		})
		;
	
	// Listen for new comments
	$parent
		.on('cerb_profile_comment_created.widget{$widget->id}', function(e) {
			if(e.comment_id && e.comment_html) {
				var $new_comment = $('<div id="comment' + e.comment_id + '"/>')
					.html(e.comment_html)
					.prependTo($('#conversation'))
				;
			}
		})
		;
	
	$widget.on('cerb_reply', function(e) {
		e.preventDefault();
		e.stopPropagation();
		
		if(undefined == e.message_id)
			return;
		
		var msgid = parseInt(e.message_id);
		var $div = $('#reply' + msgid);
		
		if(0 == $div.length)
			return;
		
		var is_forward = (null == e.is_forward || 0 == e.is_forward) ? 0 : 1;
		var draft_id = (null == e.draft_id) ? 0 : parseInt(e.draft_id);
		var reply_mode = (null == e.reply_mode) ? 0 : parseInt(e.reply_mode);
		var is_confirmed = (null == e.is_confirmed || 0 == e.is_confirmed) ? 0 : 1;
		
		// If the reply window is already open, just focus it
		if($div.find('> div.reply_frame').length > 0) {
			$div.find('input:text:first').first().focus();
			return;
		}
		
		var url = 'c=display&a=reply&forward='+is_forward+'&draft_id='+draft_id+'&reply_mode='+reply_mode+'&is_confirmed='+is_confirmed+'&timestamp={time()}&id=' + msgid;
		
		var $popup = genericAjaxPopup('reply' + msgid, url, null, false, '70%');
		
		$popup.on('cerb-reply-sent cerb-reply-saved cerb-reply-draft', function(e) {
			// Profile reload
			document.location.reload();
		});
	});
	
	var anchor = window.location.hash.substr(1);
	
	if('message' == anchor.substr(0,7) || 'comment' == anchor.substr(0,7) || 'draft' == anchor.substr(0,5)) {
		var $anchor = $('#' + anchor);
		
		if($anchor.length > 0) {
			var offset = $anchor.offset();
			window.scrollTo(offset.left, offset.top);
			$anchor.find('> div.block').effect('highlight', { }, 1000);
		}
	}
});
</script>
