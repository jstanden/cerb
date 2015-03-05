<form style="margin:5px;">
	{if $active_worker->hasPriv('core.display.actions.comment')}<button type="button" id="btnComment"><span class="cerb-sprite sprite-document_edit"></span> Comment</button>{/if}
	{if !$expand_all}<button id="btnReadAll" title="{'display.shortcut.read_all'|devblocks_translate}" type="button" onclick="document.location='{devblocks_url}c=profiles&type=ticket&id={$ticket->mask}&tab=conversation&opt=read_all{/devblocks_url}';"><span class="cerb-sprite sprite-document"></span> {'display.button.read_all'|devblocks_translate|capitalize}</button>{/if} 
</form>

{if is_array($pending_drafts)}
<div class="ui-widget">
	<div class="ui-state-highlight ui-corner-all" style="padding: 0 .7em; margin: 0.2em; "> 
		<p><span class="ui-icon ui-icon-info" style="float: left; margin-right: .3em;"></span> 
		This ticket has <strong>{$drafts|count nofilter}</strong> pending draft(s): 
		{foreach from=$pending_drafts item=draft name=drafts}
			<a href="#draft{$draft->id}">{$draft->updated|devblocks_prettytime}</a>{if !$smarty.foreach.drafts.last}, {/if} 
		{/foreach}
		</p>
	</div>
</div>
{/if}

<div id="tourDisplayConversation"></div>
{if $expand_all}
	<b>{'display.convo.order_oldest'|devblocks_translate}</b>
{else}
	<b>{'display.convo.order_newest'|devblocks_translate}</b>
{/if}

<div id="conversation">
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

	$('#btnComment').click(function(event) {
		var $popup = genericAjaxPopup('comment', 'c=internal&a=commentShowPopup&context={CerberusContexts::CONTEXT_TICKET}&context_id={$ticket->id}', null, false, '550');
		$popup.one('comment_save', function(event) {
			var $tabs = $('#btnComment').closest('div.ui-tabs');
			if(0 != $tabs) {
				$tabs.tabs('load', $tabs.tabs('option','active'));
			}
		});
	});
	
	var displayReply = function(msgid, is_forward, draft_id, is_quoted, is_confirmed) {
		var msgid = parseInt(msgid);
		var $div = $('#reply' + msgid);
		
		if(0 == $div.length)
			return;
		
		var is_forward = (null == is_forward || 0 == is_forward) ? 0 : 1;
		var draft_id = (null == draft_id) ? 0 : parseInt(draft_id);
		var is_quoted = (null == is_quoted) ? 1 : parseInt(is_quoted);
		var is_confirmed = (null == is_confirmed) ? 0 : parseInt(is_confirmed);

		// If the reply window is already open, just focus it
		if($div.find('> div.reply_frame').length > 0) {
			$div.find('input:text:first').first().focus();
			return;
		}
		
		showLoadingPanel();
		
		genericAjaxGet('', 'c=display&a=reply&forward='+is_forward+'&draft_id='+draft_id+'&is_quoted='+is_quoted+'&is_confirmed='+is_confirmed+'&timestamp={time()}&id=' + msgid,
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
	
	var displayAddNote = function(msgid) {
		var div = document.getElementById('reply' + msgid);
		if(null == div) return;
		
		genericAjaxGet('','c=display&a=addNote&id=' + msgid,
			function(html) {
				$div = $('#reply' + msgid);
				
				if(0 == $div.length)
					return;
				
				$div.html(html);
				
				var offset = $div.offset();
				window.scrollTo(offset.left, offset.top);
				
				$frm = $('#reply' + msgid + '_form');
				$textarea = $frm.find('textarea[name=content]');
				
				$textarea.focus();
			}
		);
	}
</script>
