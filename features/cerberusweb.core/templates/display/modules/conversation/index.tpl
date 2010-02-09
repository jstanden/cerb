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
			{/if}
			
		{/foreach}
	{/if}
	
{else}
  {$translate->_('display.convo.no_messages')}
  <br>
{/if}
</div>

<script type="text/javascript" language="JavaScript1.2">
	function displayReply(msgid, is_forward) {
		var div = document.getElementById('reply' + msgid);
		if(null == div) return;
		is_forward = (null == is_forward || 0 == is_forward) ? 0 : 1;
		
		genericAjaxGet('', 'c=display&a=reply&forward='+is_forward+'&id=' + msgid,
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
