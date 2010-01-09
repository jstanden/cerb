{if empty($ticket)}
We did not find a ticket to match the supplied ID / mask.
{else}
<table>
  <tr>
    <td valign="top"><h2 style="color: rgb(102,102,102);">Ticket #{$ticket_id}</h2></td>
    <td valign="top">[ <a href="{devblocks_url}c=mobile&a=display&id={$ticket_id}&mode=full{/devblocks_url}"">Show Full Ticket</a> ]</td>
  </tr>
</table>
  {assign var=headers value=$message->getHeaders()}
  {if isset($headers.from)}<b>From:</b> {$headers.from|escape|nl2br}<br>{/if}
  {if isset($headers.to)}<b>To:</b> {$headers.to|escape|nl2br}<br>{/if}
  {if isset($headers.subject)}<b>Subject:</b> {$headers.subject|escape|nl2br}<br>{/if}
  {if isset($headers.date)}<b>Date:</b> {$headers.date|escape|nl2br}<br>{/if}
<br>
  {$message->getContent()|trim|nl2br}
<br><br>
<form id="reply{$message->id}" action="{devblocks_url}{/devblocks_url}" method="POST" enctype="multipart/form-data">
<input type="hidden" name="c" value="mobile">
<input type="hidden" name="a" value="display">
<input type="hidden" name="a2" value="reply">

<input type="hidden" name="page_type" value="{$page_type}">
<input type="hidden" name="ticket_id" value="{$ticket_id}">
<input type="hidden" name="message_id" value="{$message->id}">

{if $page_type=="forward"}Forward to: <input type="text" name="to" size="32" maxlength="255"/><br>
{elseif $page_type=="comment"}Add Comment to Ticket:<br>
{else}Reply to Requesters:<br>{/if}

<textarea name="content" rows="5" cols="32"></textarea><br>

{if $page_type=="forward"}<button type="submit">Forward</button>
{elseif $page_type=="comment"}<button type="submit">Comment</button>
{else}<button type="submit">Reply</button>{/if}

</form>
{/if}{*end of if empty($ticket)*}