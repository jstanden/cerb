<div id="tourDisplayConversation"></div>
{if $expand_all}
	<b>{$translate->_('display.convo.order_oldest')}</b>
{else}
	<b>{$translate->_('display.convo.order_newest')}</b>
{/if}
<!-- <h2>Ticket Conversation</h2> -->

{if !empty($ticket)}
	{if !empty($convo_timeline)}
		{foreach from=$convo_timeline item=convo_set name=items}
			{if $convo_set.0=='m'}
				{assign var=message_id value=$convo_set.1}
				{assign var=message value=$messages.$message_id}
				<div id="{$message->id}t" style="background-color:rgb(255,255,255);">
					{assign var=expanded value=false}
					{if $expand_all || $latest_message_id==$message_id || isset($message_notes.$message_id)}{assign var=expanded value=true}{/if}
					{include file="$core_tpl/display/modules/conversation/message.tpl.php" expanded=$expanded}
				</div>
				
			{elseif $convo_set.0=='c'}
				{assign var=comment_id value=$convo_set.1}
				{assign var=comment value=$comments.$comment_id}
				<div id="comment{$comment->id}" style="background-color:rgb(255,255,255);">
					{include file="$core_tpl/display/modules/conversation/comment.tpl.php"}
				</div>
			{/if}
			
		{/foreach}
	{/if}
	
{else}
  {$translate->_('display.convo.no_messages')}
  <br>
{/if}
