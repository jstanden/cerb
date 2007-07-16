<div id="tourDisplayConversation"></div>
<!-- <h2>Ticket Conversation</h2> -->
{if !empty($ticket)}
	{foreach from=$messages item=message name=messages key=message_id}
		<div id="{$message->id}t" style="background-color:rgb(255,255,255);">
		{assign var=expanded value=false}
		{if $smarty.foreach.messages.first || isset($message_notes.$message_id)}{assign var=expanded value=true}{/if}
		{include file="$path/display/modules/conversation/message.tpl.php" expanded=$expanded}
		</div>
	{/foreach}
{else}
  No messages on ticket.
  <br>
{/if}