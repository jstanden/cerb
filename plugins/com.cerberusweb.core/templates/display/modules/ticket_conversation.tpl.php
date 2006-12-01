<h1 class="subtitle" style="color: rgb(102,102,102);">Ticket Conversation</h1>
{if !empty($ticket)}
{foreach from=$ticket->getMessages() item=message name=messages}
<table style="text-align: left; width: 100%;" class="displayConversationTable" border="0" cellpadding="2" cellspacing="0">
  <tbody>
    <tr>
      <td>
      {if isset($message->headers.from)}<b>From:</b> {$message->headers.from|escape:"htmlall"|nl2br}<br>{/if}
      {if isset($message->headers.to)}<b>To:</b> {$message->headers.to|escape:"htmlall"|nl2br}<br>{/if}
      {if isset($message->headers.subject)}<b>Subject:</b> {$message->headers.subject|escape:"htmlall"|nl2br}<br>{/if}
      {if isset($message->headers.date)}<b>Date:</b> {$message->headers.date|escape:"htmlall"|nl2br}<br>{/if}

      {* // [TODO] Move this to an Ajax packet for full headers 
      	{if is_array($message->headers)}
      	{foreach from=$message->headers item=headerValue key=headerKey}
      		<b>{$headerKey|capitalize}:</b> 
      		{if is_array($headerValue)}
      			{foreach from=$headerValue item=subHeader}
      				&nbsp;&nbsp;&nbsp;{$subHeader|escape:"htmlall"|nl2br}<br>
      			{/foreach}
      		{else}
      			{$headerValue|escape:"htmlall"|nl2br}<br>
      		{/if}
      	{/foreach}
      	{/if}
      	*}
      
      	<br>
      	{$message->getContent()|trim|nl2br}
      	<br>
      	[ <a href="javascript:;" onclick="ajax.reply('{$message->id}');">Reply</a> ] 
      	[ <a href="javascript:;" onclick="ajax.forward('{$message->id}');">Forward</a> ] 
      	[ <a href="javascript:;" onclick="ajax.comment('{$message->id}');">Comment</a> ] 
      	[ <a href="#">More Options...</a> ] 
      	<br>
      	<b>Attachments: </b>
      		{foreach from=$message->getAttachments() item=attachment name=attachments}
				<a href="{$smarty.const.UM_ATTACHMENT_ACCESS_PATH}{$attachment->filepath}">{$attachment->display_name}</a>
				{if !$smarty.foreach.requesters.last}, {/if}
			{/foreach}<br>
      </td>
    </tr>
  </tbody>
</table>
<form id="reply{$message->id}" action="index.php" method="POST" enctype="multipart/form-data"></form>
{if !$smarty.foreach.messages.last}<br>{/if}
{/foreach}
{else}
  No messages on ticket.
  <br>
{/if}