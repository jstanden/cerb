<div id="tourDisplayConversation"></div>
<!-- <h2>Ticket Conversation</h2> -->
{if !empty($ticket)}
	{foreach from=$messages item=message name=messages}
		<div id="{$message->id}t" style="background-color:rgb(255,255,255);">
		{include file="$path/display/modules/conversation/message.tpl.php" expanded=$smarty.foreach.messages.first}
		</div>
	{/foreach}
{else}
  No messages on ticket.
  <br>
{/if}