<table cellpadding="0" cellspacing="0" border="0" class="tableBlue" width="100%" class="tableBg">
	<tr>
		<td nowrap="nowrap" class="tableThBlue">{$view->name}</td>
		<td nowrap="nowrap" class="tableThBlue" align="right">
			<a href="javascript:;" onclick="ajax.getRefresh('{$view->id}');" class="tableThLink">{$translate->say('common.refresh')|lower}</a><span style="font-size:12px"> | </span>
			{if $view->type == 'D'}<a href="{devblocks_url}c=tickets&a=searchview&id={$view->id}{/devblocks_url}" class="tableThLink">{$translate->say('common.search')|lower}</a><span style="font-size:12px"> | </span>{/if}
			<a href="javascript:;" onclick="ajax.getCustomize('{$view->id}');" class="tableThLink">{$translate->say('common.customize')|lower}</a>
		</td>
	</tr>
</table>
<form id="customize{$view->id}" action="#" onsubmit="return false;" style="display:none;"></form>
<form id="viewForm{$view->id}" name="viewForm{$view->id}">
<input type="hidden" name="c" value="tickets">
<input type="hidden" name="a" value="runAction">
<input type="hidden" name="id" value="{$view->id}">
<table cellpadding="0" cellspacing="0" border="0" width="100%" class="tableRowBg">

	{* Column Headers *}
	<tr class="tableTh">
		<th style="text-align:center"><input type="checkbox" onclick="checkAll('view{$view->id}',this.checked);"></th>
		{foreach from=$view->view_columns item=header name=headers}
			{if $header=="t_mask"}
			<th><a href="javascript:;" onclick="ajax.getSortBy('{$view->id}','t_mask');">{$translate->say('ticket.id')}</a></th>
			{elseif $header=="t_status"}
			<th><a href="javascript:;" onclick="ajax.getSortBy('{$view->id}','t_status');">{$translate->say('ticket.status')}</a></th>
			{elseif $header=="t_priority"}
			<th><a href="javascript:;" onclick="ajax.getSortBy('{$view->id}','t_priority');">{$translate->say('ticket.priority')}</a></th>
			{elseif $header=="t_last_wrote"}
			<th><a href="javascript:;" onclick="ajax.getSortBy('{$view->id}','t_last_wrote');">{$translate->say('ticket.last_wrote')}</a></th>
			{elseif $header=="t_first_wrote"}
			<th><a href="javascript:;" onclick="ajax.getSortBy('{$view->id}','t_first_wrote');">{$translate->say('ticket.first_wrote')}</a></th>
			{elseif $header=="t_created_date"}
			<th><a href="javascript:;" onclick="ajax.getSortBy('{$view->id}','t_created_date');">{$translate->say('ticket.created')}</a></th>
			{elseif $header=="t_updated_date"}
			<th><a href="javascript:;" onclick="ajax.getSortBy('{$view->id}','t_updated_date');">{$translate->say('ticket.updated')}</a></th>
			{elseif $header=="m_name"}
			<th><a href="javascript:;" onclick="ajax.getSortBy('{$view->id}','m_name');">{$translate->say('ticket.mailbox')}</a></th>
			{elseif $header=="t_spam_score"}
			<th><a href="javascript:;" onclick="ajax.getSortBy('{$view->id}','t_spam_score');">{$translate->say('common.spam')}</a></th>
			{/if}
		{/foreach}
	</tr>

	{* Column Data *}
	{assign var=results value=$view->getTickets()}
	{assign var=total value=$results[1]}
	{assign var=tickets value=$results[0]}
	{foreach from=$tickets item=result key=idx name=results}
		<tr class="{if $smarty.foreach.results.iteration % 2}tableRowBg{else}tableRowAltBg{/if}">
			<td align="center" rowspan="2"><input type="checkbox" name="ticket_id[]" value="{$result.t_id}"></td>
			<td colspan="{math equation="x" x=$smarty.foreach.headers.total}"><a href="{devblocks_url}c=display&id={$result.t_mask}{/devblocks_url}" class="ticketLink"><b>{$result.t_subject}</b></a></td>
		</tr>
		<tr class="{if $smarty.foreach.results.iteration % 2}tableRowBg{else}tableRowAltBg{/if}">
		{foreach from=$view->view_columns item=column name=columns}
			{if $column=="t_mask"}
			<td><a href="{devblocks_url}c=display&id={$result.t_mask}{/devblocks_url}">{$result.t_mask}</a></td>
			{elseif $column=="t_status"}
			<td>
				{if $result.t_status=='O'}
					{$translate->say('status.open')|lower}
				{elseif $result.t_status=='W'}
					{$translate->say('status.waiting')|lower}
				{elseif $result.t_status=='C'}
					{$translate->say('status.closed')|lower}
				{elseif $result.t_status=='D'}
					{$translate->say('status.deleted')|lower}
				{/if}
			</td>
			{elseif $column=="t_priority"}
			<td>
				{if $result.t_priority >= 75}
					<img src="{devblocks_url}images/star_red.gif{/devblocks_url}" title="{$result.t_priority}">
				{elseif $result.t_priority >= 50}
					<img src="{devblocks_url}images/star_yellow.gif{/devblocks_url}" title="{$result.t_priority}">
				{elseif $result.t_priority >= 25}
					<img src="{devblocks_url}images/star_green.gif{/devblocks_url}" title="{$result.t_priority}">
				{else}
					<img src="{devblocks_url}images/star_alpha.gif{/devblocks_url}" title="{$result.t_priority}">
				{/if}
			</td>
			{elseif $column=="t_last_wrote"}
			<td><a href="javascript:;" onclick="ajax.showContactPanel('{$ticket->last_wrote}',this);">{$result.t_last_wrote}</a></td>
			{elseif $column=="t_first_wrote"}
			<td><a href="javascript:;" onclick="ajax.showContactPanel('{$ticket->first_wrote}',this);">{$result.t_first_wrote}</a></td>
			{elseif $column=="t_created_date"}
			<td>{$result.t_created_date|date_format}</td>
			{elseif $column=="t_updated_date"}
			<td>{$result.t_updated_date|date_format}</td>
			{elseif $column=="m_name"}
			<td><a href="{devblocks_url}c=tickets&a=mailbox&id={$result.m_id}{/devblocks_url}">{$result.m_name}</a></td>
			{elseif $column=="t_spam_score"}
			<td>{math equation="x*100" format="%0.2f" x=$result.t_spam_score}%</td>
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
			<select name="action_id" onchange="toggleDiv('action{$view->id}',(this.selectedIndex>0)?'inline':'none');">
				<option value="">-- perform action --
				{foreach from=$viewActions item=action}
				<option value="{$action->id}">{$action->name}
				{/foreach}
			</select>
			<span id="action{$view->id}" style="display:none;">
				<input type="button" value="Apply" onclick="ajax.viewRunAction('{$view->id}');">
				<a href="javascript:;" onclick="ajax.showViewActionPanel(selectValue(document.getElementById('viewForm{$view->id}').action_id),'{$view->id}',this);">edit action</a> | 
			</span>
			<a href="javascript:;" onclick="ajax.showViewActionPanel('0','{$view->id}',this);">new action</a>
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
</form>
<br>