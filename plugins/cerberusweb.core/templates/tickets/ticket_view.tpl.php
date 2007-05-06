<table cellpadding="0" cellspacing="0" border="0" class="tableBlue" width="100%" class="tableBg">
	<tr>
		<td nowrap="nowrap" class="tableThBlue">{$view->name}</td>
		<td nowrap="nowrap" class="tableThBlue" align="right">
			<a href="javascript:;" onclick="ajax.getRefresh('{$view->id}');" class="tableThLink">{$translate->_('common.refresh')|lower}</a><span style="font-size:12px"> | </span>
			{if $view->id != 'search'}<a href="{devblocks_url}c=tickets&a=searchview&id={$view->id}{/devblocks_url}" class="tableThLink">{$translate->_('common.search')|lower}</a><span style="font-size:12px"> | </span>{/if}
			<a href="javascript:;" onclick="ajax.getCustomize('{$view->id}');" class="tableThLink">{$translate->_('common.customize')|lower}</a>
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
			<th><a href="javascript:;" onclick="ajax.getSortBy('{$view->id}','t_mask');">{$translate->_('ticket.id')}</a></th>
			{elseif $header=="t_status"}
			<th><a href="javascript:;" onclick="ajax.getSortBy('{$view->id}','t_status');">{$translate->_('ticket.status')}</a></th>
			{elseif $header=="t_priority"}
			<th><a href="javascript:;" onclick="ajax.getSortBy('{$view->id}','t_priority');">{$translate->_('ticket.priority')}</a></th>
			{elseif $header=="t_last_wrote"}
			<th><a href="javascript:;" onclick="ajax.getSortBy('{$view->id}','t_last_wrote');">{$translate->_('ticket.last_wrote')}</a></th>
			{elseif $header=="t_first_wrote"}
			<th><a href="javascript:;" onclick="ajax.getSortBy('{$view->id}','t_first_wrote');">{$translate->_('ticket.first_wrote')}</a></th>
			{elseif $header=="t_created_date"}
			<th><a href="javascript:;" onclick="ajax.getSortBy('{$view->id}','t_created_date');">{$translate->_('ticket.created')}</a></th>
			{elseif $header=="t_updated_date"}
			<th><a href="javascript:;" onclick="ajax.getSortBy('{$view->id}','t_updated_date');">{$translate->_('ticket.updated')}</a></th>
			{elseif $header=="t_due_date"}
			<th><a href="javascript:;" onclick="ajax.getSortBy('{$view->id}','t_due_date');">{$translate->_('ticket.due')}</a></th>
			{elseif $header=="t_tasks"}
			<th><a href="javascript:;" onclick="ajax.getSortBy('{$view->id}','t_tasks');">{$translate->_('common.tasks')}</a></th>
			{elseif $header=="m_name"}
			<th><a href="javascript:;" onclick="ajax.getSortBy('{$view->id}','m_name');">{$translate->_('ticket.mailbox')}</a></th>
			{elseif $header=="t_spam_score"}
			<th><a href="javascript:;" onclick="ajax.getSortBy('{$view->id}','t_spam_score');">{$translate->_('common.spam')}</a></th>
			{elseif $header=="tm_name"}
			<th><a href="javascript:;" onclick="ajax.getSortBy('{$view->id}','tm_name');">{$translate->_('common.team')}</a></th>
			{elseif $header=="cat_name"}
			<th><a href="javascript:;" onclick="ajax.getSortBy('{$view->id}','cat_name');">{$translate->_('common.category')}</a></th>
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
			<td colspan="{math equation="x" x=$smarty.foreach.headers.total}"><a href="{devblocks_url}c=display&id={$result.t_mask}{/devblocks_url}" class="ticketLink" style="font-size:12px;"><b>{$result.t_subject}</b></a></td>
		</tr>
		<tr class="{if $smarty.foreach.results.iteration % 2}tableRowBg{else}tableRowAltBg{/if}">
		{foreach from=$view->view_columns item=column name=columns}
			{if $column=="t_mask"}
			<td><a href="{devblocks_url}c=display&id={$result.t_mask}{/devblocks_url}">{$result.t_mask}</a></td>
			{elseif $column=="t_status"}
			<td>
				{if $result.t_status=='O'}
					{$translate->_('status.open')|lower}
				{elseif $result.t_status=='W'}
					{$translate->_('status.waiting')|lower}
				{elseif $result.t_status=='C'}
					{$translate->_('status.closed')|lower}
				{elseif $result.t_status=='D'}
					{$translate->_('status.deleted')|lower}
				{/if}
			</td>
			{elseif $column=="t_priority"}
			<td>
				{if $result.t_priority >= 75}
					<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/star_red.gif{/devblocks_url}" title="{$result.t_priority}">
				{elseif $result.t_priority >= 50}
					<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/star_yellow.gif{/devblocks_url}" title="{$result.t_priority}">
				{elseif $result.t_priority >= 25}
					<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/star_green.gif{/devblocks_url}" title="{$result.t_priority}">
				{else}
					<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/star_alpha.gif{/devblocks_url}" title="{$result.t_priority}">
				{/if}
			</td>
			{elseif $column=="t_last_wrote"}
			<td><a href="javascript:;" onclick="genericAjaxPanel('c=tickets&a=showContactPanel&address={$ticket->last_wrote}',this);">{$result.t_last_wrote}</a></td>
			{elseif $column=="t_first_wrote"}
			<td><a href="javascript:;" onclick="genericAjaxPanel('c=tickets&a=showContactPanel&address={$ticket->first_wrote}',this);">{$result.t_first_wrote}</a></td>
			{elseif $column=="t_created_date"}
			<td>{$result.t_created_date|date_format}</td>
			{elseif $column=="t_updated_date"}
			<td>{$result.t_updated_date|date_format}</td>
			{elseif $column=="t_due_date"}
			<td>{$result.t_due_date|date_format}</td>
			{elseif $column=="t_tasks"}
			<td align='center'>{if !empty($result.t_tasks)}{$result.t_tasks}{/if}</td>
			{elseif $column=="tm_name"}
			<td><a href="{devblocks_url}c=tickets&a=dashboards&m=team&id={$result.tm_id}{/devblocks_url}">{$result.tm_name}</a></td>
			{elseif $column=="cat_name"}
			<td>{$result.cat_name}</td>
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
		    {if $first_view}<div id="tourDashboardShortcuts"></div>{/if}
			<select name="action_id" onchange="toggleDiv('action{$view->id}',(this.selectedIndex>0)?'inline':'none');">
				<option value="">-- perform shortcut --</option>
				<optgroup label="Shared Shortcuts" style="color:rgb(0,180,0);">
				{foreach from=$viewActions item=action}
				<option value="{$action->id}">{$action->name}</option>
				{/foreach}
				</optgroup>
			</select>
			<span id="action{$view->id}" style="display:none;">
				<input type="button" value="Apply" onclick="ajax.viewRunAction('{$view->id}');">
				<a href="javascript:;" onclick="genericAjaxPanel('c=tickets&a=showViewActions&id='+selectValue(document.getElementById('viewForm{$view->id}').action_id)+'&view_id={$view->id}',this,true,'500px');">edit shortcut</a> | 
			</span>
			<a href="javascript:;" onclick="genericAjaxPanel('c=tickets&a=showViewActions&id=0&view_id={$view->id}',this,true,'500px');">new shortcut</a>
			| 
			<a href="javascript:;" onclick="genericAjaxPanel('c=tickets&a=showBatchPanel&view_id={$view->id}',this,true,'500px');">{if $first_view}<span id="tourDashboardBatch">batch update</span>{else}batch update{/if}</a>
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
			{if $fromRow > $toRow}{assign var=fromRow value=$toRow}{/if}
			
			{if $view->renderPage > 0}
				<a href="javascript:;" onclick="ajax.getPage('{$view->id}',0);">&lt;&lt;</a>
				<a href="javascript:;" onclick="ajax.getPage('{$view->id}','{$prevPage}');">&lt;{$translate->_('common.prev')|capitalize}</a>
			{/if}
			(Showing {$fromRow}-{$toRow} of {$total})
			{if $toRow < $total}
				<a href="javascript:;" onclick="ajax.getPage('{$view->id}','{$nextPage}');">{$translate->_('common.next')|capitalize}&gt;</a>
				<a href="javascript:;" onclick="ajax.getPage('{$view->id}','{$lastPage}');">&gt;&gt;</a>
			{/if}
		</td>
	</tr>
</table>
</form>
<br>