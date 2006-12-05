<table cellpadding="0" cellspacing="0" border="0" class="tableBlue" width="100%" class="tableBg">
	<tr>
		<td nowrap="nowrap" class="tableThBlue">{$view->name}</td>
		<td nowrap="nowrap" class="tableThBlue" align="right">
			<a href="javascript:;" onclick="ajax.getRefresh('{$view->id}');" class="tableThLink">{$translate->say('common.refresh')|lower}</a><span style="font-size:12px"> | </span>
			<a href="index.php?c=core.module.dashboard&a=searchview&id={$view->id}" class="tableThLink">{$translate->say('common.search')|lower}</a><span style="font-size:12px"> | </span>
			<a href="javascript:;" onclick="ajax.getCustomize('{$view->id}');" class="tableThLink">{$translate->say('common.customize')|lower}</a>
		</td>
	</tr>
</table>
<form id="customize{$view->id}" action="#" onsubmit="return false;"></form>
<table cellpadding="0" cellspacing="0" border="0" width="100%" class="tableRowBg">

	{* Column Headers *}
	<tr class="tableTh">
		<th style="text-align:center"><a href="#">all</a></th>
		{foreach from=$view->columns item=header name=headers}
			{if $header=="t.mask"}
			<th><a href="javascript:;" onclick="ajax.getSortBy('{$view->id}','t.mask');">{$translate->say('ticket.id')}</a></th>
			{elseif $header=="t.status"}
			<th><a href="javascript:;" onclick="ajax.getSortBy('{$view->id}','t.status');">{$translate->say('ticket.status')}</a></th>
			{elseif $header=="t.priority"}
			<th><a href="javascript:;" onclick="ajax.getSortBy('{$view->id}','t.priority');">{$translate->say('ticket.priority')}</a></th>
			{elseif $header=="t.last_wrote"}
			<th><a href="javascript:;" onclick="ajax.getSortBy('{$view->id}','t.last_wrote');">{$translate->say('ticket.last_wrote')}</a></th>
			{elseif $header=="t.first_wrote"}
			<th><a href="javascript:;" onclick="ajax.getSortBy('{$view->id}','t.first_wrote');">{$translate->say('ticket.first_wrote')}</a></th>
			{elseif $header=="t.created_date"}
			<th><a href="javascript:;" onclick="ajax.getSortBy('{$view->id}','t.created_date');">{$translate->say('ticket.created')}</a></th>
			{elseif $header=="t.updated_date"}
			<th><a href="javascript:;" onclick="ajax.getSortBy('{$view->id}','t.updated_date');">{$translate->say('ticket.updated')}</a></th>
			{/if}
		{/foreach}
	</tr>

	{* Column Data *}
	{assign var=results value=$view->getTickets()}
	{assign var=total value=$results[1]}
	{assign var=tickets value=$results[0]}
	{foreach from=$tickets item=ticket key=idx name=tickets}
		<tr class="{if $smarty.foreach.tickets.iteration % 2}tableRowBg{else}tableRowAltBg{/if}">
			<td align="center" rowspan="2"><input type="checkbox" name="ticket_id[]" value=""></td>
			<td colspan="{math equation="x" x=$smarty.foreach.headers.total}"><a href="index.php?c=core.module.dashboard&a=viewticket&id={$ticket->id}" class="ticketLink"><b>{$ticket->subject}</b></a></td>
		</tr>
		<tr class="{if $smarty.foreach.tickets.iteration % 2}tableRowBg{else}tableRowAltBg{/if}">
		{foreach from=$view->columns item=column name=columns}
			{if $column=="t.mask"}
			<td><a href="index.php?c=core.module.dashboard&a=viewticket&id={$ticket->id}">{$ticket->mask}</a></td>
			{elseif $column=="t.status"}
			<td>
				{if $ticket->status=='O'}
					{$translate->say('status.open')|lower}
				{elseif $ticket->status=='W'}
					{$translate->say('status.waiting')|lower}
				{elseif $ticket->status=='C'}
					{$translate->say('status.closed')|lower}
				{elseif $ticket->status=='D'}
					{$translate->say('status.deleted')|lower}
				{/if}
			</td>
			{elseif $column=="t.priority"}
			<td>
				{if $ticket->priority == 100}
					<img src="images/star_red.gif" title="{$ticket->priority}">
				{elseif $ticket->priority >= 90}
					<img src="images/star_yellow.gif" title="{$ticket->priority}">
				{elseif $ticket->priority >= 75}
					<img src="images/star_green.gif" title="{$ticket->priority}">
				{elseif $ticket->priority >= 50}
					<img src="images/star_blue.gif" title="{$ticket->priority}">
				{elseif $ticket->priority >= 25}
					<img src="images/star_grey.gif" title="{$ticket->priority}">
				{else}
					<img src="images/star_alpha.gif" title="{$ticket->priority}">
				{/if}
			</td>
			{elseif $column=="t.last_wrote"}
			<td><a href="#">{$ticket->last_wrote}</a></td>
			{elseif $column=="t.first_wrote"}
			<td><a href="#">{$ticket->first_wrote}</a></td>
			{elseif $column=="t.created_date"}
			<td>{$ticket->created_date|date_format}</td>
			{elseif $column=="t.updated_date"}
			<td>{$ticket->updated_date|date_format}</td>
			{/if}
		{/foreach}
		</tr>
		<tr>
			<td class="tableBg" colspan="{math equation="x+1" x=$smarty.foreach.headers.total}"></td>
		</tr>
	{/foreach}
	
</table>
<table cellpadding="2" cellspacing="0" border="0" width="100%" class="tableBg">
	<tr>
		<td>
			<select name="">
				<option value="">-- perform action --
			</select>
		</td>
	</tr>
	<tr>
		<td align="right">
			{math assign=fromRow equation="(x*y)+1" x=$view->renderPage y=$view->renderLimit}
			{math assign=toRow equation="(x-1)+y" x=$fromRow y=$view->renderLimit}
			{math assign=nextPage equation="x+1" x=$view->renderPage}
			{math assign=prevPage equation="x-1" x=$view->renderPage}
			{math assign=lastPage equation="ceil(x/y)-1" x=$total y=$view->renderLimit}
			
			{* Sanity checks *}
			{if $toRow > $total}{assign var=toRow value=$total}{/if}
			
			{if $view->renderPage > 0}
				<a href="javascript:;" onclick="ajax.getPage('{$view->id}',0);">&lt;&lt;</a>
				<a href="javascript:;" onclick="ajax.getPage('{$view->id}','{$prevPage}');">&lt;{$translate->say('common.prev')|capitalize}</a>
			{/if}
			(Showing {$fromRow}-{$toRow} of {$total})
			{if $toRow < $total}
				<a href="javascript:;" onclick="ajax.getPage('{$view->id}','{$nextPage}');">{$translate->say('common.next')|capitalize}&gt;</a>
				<a href="javascript:;" onclick="ajax.getPage('{$view->id}','{$lastPage}');">&gt;&gt;</a>
			{/if}
		</td>
	</tr>
</table>