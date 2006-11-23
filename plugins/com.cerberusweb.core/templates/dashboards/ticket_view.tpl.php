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
<div id="customize{$view->id}"></div>
<table cellpadding="0" cellspacing="0" border="0" width="100%" class="tableRowBg">

	{* Column Headers *}
	<tr class="tableTh">
		<th style="text-align:center"><a href="#">all</a></th>
		<th><a href="javascript:;" onclick="ajax.getSortBy('{$view->id}','t.id');">ID</a></th>
		<th><a href="javascript:;" onclick="ajax.getSortBy('{$view->id}','t.subject');">Status</a></th>
		<th><a href="javascript:;" onclick="ajax.getSortBy('{$view->id}','t.priority');">Priority</a></th>
		<th><a href="javascript:;" onclick="ajax.getSortBy('{$view->id}','t.last_wrote');">Wrote Last</a></th>
		<th><a href="javascript:;" onclick="ajax.getSortBy('{$view->id}','t.created_date');">Created</a></th>
		<th><a href="javascript:;" onclick="ajax.getSortBy('{$view->id}','t.updated_date');">Updated</a></th>
	</tr>

	{* Column Data *}
	{assign var=results value=$view->getTickets()}
	{assign var=total value=$results[1]}
	{assign var=tickets value=$results[0]}
	{foreach from=$tickets item=ticket key=idx name=tickets}
		<tr class="{if $smarty.foreach.tickets.iteration % 2}tableRowBg{else}tableRowAltBg{/if}">
			<td align="center" rowspan="2"><input type="checkbox" name="ticket_id[]" value=""></td>
			<td colspan="6"><a href="index.php?c=core.module.dashboard&a=viewticket&id={$ticket->id}" class="ticketLink"><b>{$ticket->subject}</b></a></td>
		</tr>
		<tr class="{if $smarty.foreach.tickets.iteration % 2}tableRowBg{else}tableRowAltBg{/if}">
			<td><a href="index.php?c=core.module.dashboard&a=viewticket&id={$ticket->id}" style="font-size:90%">{$ticket->mask}</a></td>
			<td>{$ticket->status}</td>
			<td><img src="images/star_alpha.gif" title="{$ticket->priority}"></td>
			<td><a href="#" style="font-size:90%">{$ticket->last_wrote}</a></td>
			<td>{$ticket->created_date}</td>
			<td>{$ticket->updated_date}</td>
		</tr>
		<tr>
			<td class="tableBg" colspan="7"></td>
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
			<a href="javascript:;" onclick="ajax.getRefresh('{$view->id}');">&lt;&lt;</a>
			<a href="javascript:;" onclick="ajax.getRefresh('{$view->id}');">&lt;{$translate->say('common.prev')|capitalize}</a>
			(Showing x-y of {$total})
			<a href="javascript:;" onclick="ajax.getRefresh('{$view->id}');">{$translate->say('common.next')|capitalize}&gt;</a>
			<a href="javascript:;" onclick="ajax.getRefresh('{$view->id}');">&gt;&gt;</a>
		</td>
	</tr>
</table>