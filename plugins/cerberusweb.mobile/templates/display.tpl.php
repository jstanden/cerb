{if empty($ticket)}
We did not find a ticket to match the supplied ID / mask.
{else}
<h2 style="color: rgb(102,102,102);">Ticket #{$ticket_id}</h2>
{foreach from=$ticket->getMessages() item=message name=messages}
{assign var=headers value=$message->getHeaders()}
<table style="text-align: left; width: 100%;" border="0" cellpadding="2" cellspacing="0">
  <tbody>
    <tr>
      <td>
        {if isset($headers.from)}<b>From:</b> {$headers.from|escape:"htmlall"|nl2br}<br>{/if}
        {if isset($headers.to)}<b>To:</b> {$headers.to|escape:"htmlall"|nl2br}<br>{/if}
        {if isset($headers.subject)}<b>Subject:</b> {$headers.subject|escape:"htmlall"|nl2br}<br>{/if}
        {if isset($headers.date)}<b>Date:</b> {$headers.date|escape:"htmlall"|nl2br}<br>{/if}
      
      	<br>
      	{$message->getContent()|trim|nl2br}
      	<br>

      	[ <a href="{devblocks_url}c=mobile&a=display&id={$ticket_id}&m_id={$message->id}{/devblocks_url}"">Reply</a> 
      	/ <a href="{devblocks_url}c=mobile&a=forward&id={$ticket_id}&m_id={$message->id}{/devblocks_url}"">Forward</a> 
      	/ <a href="{devblocks_url}c=mobile&a=comment&id={$ticket_id}&m_id={$message->id}{/devblocks_url}"">Comment</a> ] 
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