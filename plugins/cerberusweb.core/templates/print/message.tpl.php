<html>
<head>
	<title>Ticket #{$ticket->mask}: {$ticket->subject} - {$settings->get('helpdesk_title','')}</title>
</head>

<body onload="window.print();">
{assign var=logo_url value=$settings->get('helpdesk_logo_url','')}
{if empty($logo_url)}
<img src="{devblocks_url}images/logo.jpg{/devblocks_url}">
{else}
<img src="{$logo_url}">
{/if}
<br>

<h2 style="margin-bottom:0px;">{$ticket->subject|escape}</h2>

{assign var=ticket_team_id value=$ticket->team_id}
{assign var=ticket_team value=$teams.$ticket_team_id}
{assign var=ticket_category_id value=$ticket->category_id}
{assign var=ticket_team_category_set value=$team_categories.$ticket_team_id}
{assign var=ticket_category value=$ticket_team_category_set.$ticket_category_id}

<b>Status:</b> {if $ticket->is_deleted}{$translate->_('status.deleted')}{elseif $ticket->is_closed}{$translate->_('status.closed')}{elseif $ticket->is_waiting}{$translate->_('status.waiting')}{else}{$translate->_('status.open')}{/if} &nbsp; 
<b>Team:</b> {$teams.$ticket_team_id->name} &nbsp; 
<b>Bucket:</b> {if !empty($ticket_category_id)}{$ticket_category->name}{else}Inbox{/if} &nbsp; 
<b>Mask:</b> {$ticket->mask} &nbsp; 
<b>Internal ID:</b> {$ticket->id} &nbsp; 
<br>
{if !empty($ticket->next_action) && !$ticket->is_closed}
	<b>Next Action:</b> {$ticket->next_action}<br>
{/if}
{if !empty($ticket->next_worker_id)}
	{assign var=next_worker_id value=$ticket->next_worker_id}
	<b>Next Worker:</b> {$workers.$next_worker_id->getName()}<br>
{/if}
<br>

{assign var=headers value=$message->getHeaders()}
<hr>
{if isset($headers.to)}<b>To:</b> {$headers.to|escape}<br>{/if}
{if isset($headers.cc)}<b>Cc:</b> {$headers.cc|escape}<br>{/if}
{if isset($headers.from)}<b>From:</b> {$headers.from|escape}<br>{/if}
{if isset($headers.date)}<b>Date:</b> {$headers.date|escape}<br>{/if}
{if isset($headers.subject)}<b>Subject:</b> {$headers.subject|escape}<br>{/if}
<br>
{$message->getContent()|escape|trim|nl2br}<br>
<br>

</body>
</html>