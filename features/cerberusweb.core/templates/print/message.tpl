<html>
<head>
	<title>Ticket #{$ticket->mask}: {$ticket->subject} - {$settings->get('cerberusweb.core','helpdesk_title','')}</title>
	<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset={$smarty.const.LANG_CHARSET_CODE}">
</head>

<body onload="window.print();">
{assign var=logo_url value=$settings->get('cerberusweb.core','helpdesk_logo_url','')}
{if empty($logo_url)}
<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/wgm/cerb5_logo.png{/devblocks_url}">
{else}
<img src="{$logo_url}">
{/if}
<br>

<h2 style="margin-bottom:0px;">{$ticket->subject}</h2>

{assign var=ticket_team_id value=$ticket->team_id}
{assign var=ticket_team value=$teams.$ticket_team_id}
{assign var=ticket_category_id value=$ticket->category_id}
{assign var=ticket_team_category_set value=$team_categories.$ticket_team_id}
{assign var=ticket_category value=$ticket_team_category_set.$ticket_category_id}

<b>Status:</b> {if $ticket->is_deleted}{$translate->_('status.deleted')}{elseif $ticket->is_closed}{$translate->_('status.closed')}{elseif $ticket->is_waiting}{$translate->_('status.waiting')}{else}{$translate->_('status.open')}{/if} &nbsp; 
<b>Team:</b> {$teams.$ticket_team_id->name} &nbsp; 
<b>Bucket:</b> {if !empty($ticket_category_id)}{$buckets.$ticket_category_id->name}{else}Inbox{/if} &nbsp; 
<b>Mask:</b> {$ticket->mask} &nbsp; 
<b>Internal ID:</b> {$ticket->id} &nbsp; 
<br>
{if !empty($context_watchers)}
	<b>{'common.watchers'|devblocks_translate|capitalize}:</b> 
	{foreach from=$context_watchers item=context_worker name=context_watchers}
	{$context_worker->getName()}{if !$smarty.foreach.context_watchers.last}, {/if}
	{/foreach}	
{/if}
<br>

{assign var=headers value=$message->getHeaders()}
<hr>
<div style="margin-left:20px;">
	{if isset($headers.to)}<b>To:</b> {$headers.to}<br>{/if}
	{if isset($headers.cc)}<b>Cc:</b> {$headers.cc}<br>{/if}
	{if isset($headers.from)}<b>From:</b> {$headers.from}<br>{/if}
	{if isset($headers.date)}<b>Date:</b> {$headers.date}<br>{/if}
	{if isset($headers.subject)}<b>Subject:</b> {$headers.subject}<br>{/if}
	<br>
	{$message->getContent()|trim|escape|nl2br nofilter}<br>
	<br>
	{assign var=message_id value=$message->id}
	{if isset($message_notes.$message_id) && is_array($message_notes.$message_id)}
		{foreach from=$message_notes.$message_id item=note name=notes key=note_id}
				<div style="margin:10px;margin-left:20px;">
					<b>[{$translate->_('display.ui.sticky_note')|capitalize}] </b>
					{if 1 == $note->type}
						<b>[warning]:</b>&nbsp;
					{elseif 2 == $note->type}
						<b>[error]:</b>&nbsp;
					{else}
						<br><b>From: </b>
						{assign var=note_worker_id value=$note->worker_id}
						{if $workers.$note_worker_id}
							{if empty($workers.$note_worker_id->first_name) && empty($workers.$note_worker_id->last_name)}&lt;{$workers.$note_worker_id->email}&gt;{else}{$workers.$note_worker_id->getName()}{/if}&nbsp;
						{else}
							(Deleted Worker)&nbsp;
						{/if}
					{/if}
					<br>
					<b>{$translate->_('message.header.date')|capitalize}:</b> {$note->created|devblocks_date}<br>
					{if !empty($note->content)}{$note->content}{/if}
				</div>
		{/foreach}
	{/if}
</div>

</body>
</html>
