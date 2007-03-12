<img alt="powered by cerberus helpdesk" src="{devblocks_url}images/cerberus_logo_small.gif{/devblocks_url}" border="0">
{if empty($ticket)}
We did not find a ticket to match the supplied ID / mask.
{else}
<h2 style="color: rgb(102,102,102);">Ticket #{$ticket_id}</h2>
{foreach from=$ticket->getMessages() item=message name=messages}
<table style="text-align: left; width: 100%;" class="displayConversationTable" border="0" cellpadding="2" cellspacing="0">
  <tbody>
    <tr>
      <td>
        {if isset($message->headers.from)}<b>From:</b> {$message->headers.from|escape:"htmlall"|nl2br}<br>{/if}
        {if isset($message->headers.to)}<b>To:</b> {$message->headers.to|escape:"htmlall"|nl2br}<br>{/if}
        {if isset($message->headers.subject)}<b>Subject:</b> {$message->headers.subject|escape:"htmlall"|nl2br}<br>{/if}
        {if isset($message->headers.date)}<b>Date:</b> {$message->headers.date|escape:"htmlall"|nl2br}<br>{/if}
      
      	<br>
      	{$message->getContent()|trim|nl2br}
      	<br>

      	[ <a href="{devblocks_url}mobile/display/{$ticket_id}/{$message->id}{/devblocks_url}"">Reply</a> 
      	/ <a href="{devblocks_url}mobile/forward/{$ticket_id}/{$message->id}{/devblocks_url}"">Forward</a> 
      	/ <a href="{devblocks_url}mobile/comment/{$ticket_id}/{$message->id}{/devblocks_url}"">Comment</a> ] 
      	<br>
      	{assign var=attachments value=$message->getAttachments()}
      	{if !empty($attachments)}
      	<b>Attachments: </b>[ 
      		{foreach from=$attachments item=attachment name=attachments}
				<a href="{$smarty.const.DEVBLOCKS_ATTACHMENT_ACCESS_PATH}/{$attachment->filepath}">{$attachment->display_name}
				{if !$smarty.foreach.attachments.last}</a>, {else}</a> ]{/if}
			{/foreach}<br>
			{/if}
      </td>
    </tr>
  </tbody>
</table>
{if !$smarty.foreach.messages.last}<hr>{/if}
{/foreach}
{/if}{*end of if empty($ticket)*}