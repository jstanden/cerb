<img alt="powered by cerberus helpdesk" src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/cerberus_logo_small.gif{/devblocks_url}" border="0">
{if empty($ticket)}
We did not find a ticket to match the supplied ID / mask.
{else}
<table>
  <tr>
    <td valign="top"><h2 style="color: rgb(102,102,102);">Ticket #{$ticket_id}</h2></td>
    <td valign="top">[ <a href="{devblocks_url}mobile/display/{$ticket_id}/full{/devblocks_url}"">Show Full Ticket</a> ]</td>
  </tr>
</table>
  {if isset($message->headers.from)}<b>From:</b> {$message->headers.from|escape:"htmlall"|nl2br}<br>{/if}
  {if isset($message->headers.to)}<b>To:</b> {$message->headers.to|escape:"htmlall"|nl2br}<br>{/if}
  {if isset($message->headers.subject)}<b>Subject:</b> {$message->headers.subject|escape:"htmlall"|nl2br}<br>{/if}
  {if isset($message->headers.date)}<b>Date:</b> {$message->headers.date|escape:"htmlall"|nl2br}<br>{/if}
<br>
  {$message->getContent()|trim|nl2br}
<br><br>
<form id="reply{$message->id}" action="{devblocks_url}mobile/reply/{$ticket_id}/{$message->id}{/devblocks_url}" method="POST" enctype="multipart/form-data">
<input type="hidden" name="page_type" value="{$page_type}">
<input type="hidden" name="id" value="{$message->id}">

{if $page_type=="forward"}Forward to: <input type="text" name="to" size="32" maxlength="255"/><br>
{elseif $page_type=="comment"}Add Comment to Ticket:<br>
{else}Reply to Requesters:<br>{/if}

<textarea name="content" rows="5" cols="32"></textarea><br>

{if $page_type=="forward"}<input type="submit" value="Forward">
{elseif $page_type=="comment"}<input type="submit" value="Comment">
{else}<input type="submit" value="Reply">{/if}

</form>
{/if}{*end of if empty($ticket)*}