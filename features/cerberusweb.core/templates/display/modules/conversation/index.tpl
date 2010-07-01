<form style="margin:5px;">
	{if $active_worker->hasPriv('core.display.actions.comment')}<button type="button" id="btnComment"><span class="cerb-sprite sprite-document_edit"></span> Comment</button>{/if}
	{if !$expand_all}<button id="btnReadAll" title="{$translate->_('display.shortcut.read_all')}" type="button" onclick="document.location='{devblocks_url}c=display&id={$ticket->mask}&tab=conversation&opt=read_all{/devblocks_url}';"><span class="cerb-sprite sprite-document"></span> {$translate->_('display.button.read_all')|capitalize}</button>{/if} 
</form>

{if is_array($pending_drafts)}
<div class="ui-widget">
	<div class="ui-state-highlight ui-corner-all" style="padding: 0 .7em; margin: 0.2em; "> 
		<p><span class="ui-icon ui-icon-info" style="float: left; margin-right: .3em;"></span> 
		This ticket has <strong>{$drafts|count}</strong> pending draft(s): 
		{foreach from=$pending_drafts item=draft name=drafts}
			<a href="#draft{$draft->id}">{$draft->updated|devblocks_prettytime}</a>{if !$smarty.foreach.drafts.last}, {/if} 
		{/foreach}
		</p>
	</div>
</div>
{/if}

<div id="tourDisplayConversation"></div>
{if $expand_all}
	<b>{$translate->_('display.convo.order_oldest')}</b>
{else}
	<b>{$translate->_('display.convo.order_newest')}</b>
{/if}

<div id="conversation">
{if !empty($ticket)}
	{if !empty($convo_timeline)}
		{foreach from=$convo_timeline item=convo_set name=items}
			{if $convo_set.0=='m'}
				{assign var=message_id value=$convo_set.1}
				{assign var=message value=$messages.$message_id}
				<div id="{$message->id}t" style="background-color:rgb(255,255,255);">
					{assign var=expanded value=false}
					{if $expand_all || $latest_message_id==$message_id || isset($message_notes.$message_id)}{assign var=expanded value=true}{/if}
					{include file="$core_tpl/display/modules/conversation/message.tpl" expanded=$expanded}
				</div>
				
			{elseif $convo_set.0=='c'}
				{assign var=comment_id value=$convo_set.1}
				{assign var=comment value=$comments.$comment_id}
				<div id="comment{$comment->id}" style="background-color:rgb(255,255,255);">
					{include file="$core_tpl/display/modules/conversation/comment.tpl"}
				</div>
				
			{elseif $convo_set.0=='d'}
				{assign var=draft_id value=$convo_set.1}
				{assign var=draft value=$drafts.$draft_id}
				<div id="draft{$draft->id}" style="background-color:rgb(255,255,255);">
					{include file="$core_tpl/display/modules/conversation/draft.tpl"}
				</div>
			{/if}
			
		{/foreach}
	{/if}
	
{else}
  {$translate->_('display.convo.no_messages')}
  <br>
{/if}
</div>

<script type="text/javascript" language="JavaScript">
	$('#btnComment').click(function(event) {
		$popup = genericAjaxPopup('peek', 'c=internal&a=commentShowPopup&context=cerberusweb.contexts.ticket&context_id={$ticket->id}', null, false, '550');
		$popup.one('comment_save', function(event) {
			$tabs = $('#btnComment').closest('div.ui-tabs');
			if(0 != $tabs) {
				$tabs.tabs('load', $tabs.tabs('option','selected'));
			}
		});
	});
	
	function displayReply(msgid, is_forward, draft_id) {
		msgid = parseInt(msgid);
		var div = document.getElementById('reply' + msgid);
		if(null == div) return;
		is_forward = (null == is_forward || 0 == is_forward) ? 0 : 1;
		draft_id = (null == draft_id) ? 0 : parseInt(draft_id);
		
		genericAjaxGet('', 'c=display&a=reply&forward='+is_forward+'&draft_id='+draft_id+'&id=' + msgid,
			function(html) {
				var div = document.getElementById('reply' + msgid);
				if(null == div) return;
				
				$('#reply'+msgid).html(html);
				
				document.location = '#reply' + msgid;
	
				var frm_reply = document.getElementById('reply' + msgid + '_part2');
				
				if(null != frm_reply.content) {
					if(!is_forward) {
						frm_reply.content.focus();
						setElementSelRange(frm_reply.content, 0, 0);
					} else {
						frm_reply.to.focus();
					}
				}
			}
		);
	}
	
	function displayAddNote(msgid) {
		var div = document.getElementById('reply' + msgid);
		if(null == div) return;
		
		genericAjaxGet('','c=display&a=addNote&id=' + msgid,
			function(html) {
				var div = document.getElementById('reply' + msgid);
				if(null == div) return;
				
				$('#reply'+msgid).html(html);
				document.location = '#reply' + msgid;
				
				var frm = document.getElementById('reply' + msgid + '_form');
				if(null != frm && null != frm.content) {
					frm.content.focus();
					setElementSelRange(frm.content, 0, 0);
				}
			}
		);
	}
</script>
