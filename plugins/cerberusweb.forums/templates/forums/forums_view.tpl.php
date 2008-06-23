{assign var=results value=$view->getData()}
{assign var=total value=$results[1]}
{assign var=data value=$results[0]}
<table cellpadding="0" cellspacing="0" border="0" class="tableBlue" width="100%">
	<tr>
		<td nowrap="nowrap" class="tableThBlue">{$view->name}</td>
		<td nowrap="nowrap" class="tableThBlue" align="right">
			<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewRefresh&id={$view->id}');" class="tableThLink">{$translate->_('common.refresh')|lower}</a>
			<!-- {if $view->id != 'search'}<span style="font-size:12px"> | </span><a href="{devblocks_url}c=internal&a=searchview&id={$view->id}{/devblocks_url}" class="tableThLink">{$translate->_('common.search')|lower} list</a>{/if} -->
			<span style="font-size:12px"> | </span><a href="javascript:;" onclick="genericAjaxGet('customize{$view->id}','c=internal&a=viewCustomize&id={$view->id}');toggleDiv('customize{$view->id}','block');" class="tableThLink">{$translate->_('common.customize')|lower}</a>
		</td>
	</tr>
</table>

<form id="customize{$view->id}" name="customize{$view->id}" action="#" onsubmit="return false;" style="display:none;"></form>
<form id="viewForm{$view->id}" name="viewForm{$view->id}" action="{devblocks_url}{/devblocks_url}" method="POST">
<input type="hidden" name="id" value="{$view->id}">
<input type="hidden" name="c" value="forums">
<input type="hidden" name="a" value="">
<table cellpadding="0" cellspacing="0" border="0" width="100%" class="tableRowBg">

	{* Column Headers *}
	<tr class="tableTh">
		<th style="text-align:center"><input type="checkbox" onclick="checkAll('view{$view->id}',this.checked);"></th>
		{foreach from=$view->view_columns item=header name=headers}
			{* start table header, insert column title and link *}
			<th nowrap="nowrap">
			{if $header=="x"}<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewSortBy&id={$view->id}&sortBy=a_id');">{$translate->_('contact_org.id')|capitalize}</a>
			{else}<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewSortBy&id={$view->id}&sortBy={$header}');">{$view_fields.$header->db_label|capitalize}</a>
			{/if}
			
			{* add arrow if sorting by this column, finish table header tag *}
			{if $header==$view->renderSortBy}
				{if $view->renderSortAsc}
					<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/arrow_up.gif{/devblocks_url}" align="top">
				{else}
					<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/arrow_down.gif{/devblocks_url}" align="top">
				{/if}
			{/if}
			</th>
		{/foreach}
	</tr>

	{* Column Data *}
	{foreach from=$data item=result key=idx name=results}

	{assign var=rowIdPrefix value="row_"|cat:$view->id|cat:"_"|cat:$result.t_id}
	{if $smarty.foreach.results.iteration % 2}
		{assign var=tableRowBg value="tableRowBg"}
	{else}
		{assign var=tableRowBg value="tableRowAltBg"}
	{/if}
	
		<tr class="{$tableRowBg}" id="{$rowIdPrefix}_s" onmouseover="toggleClass(this.id,'tableRowHover');toggleClass('{$rowIdPrefix}','tableRowHover');" onmouseout="toggleClass(this.id,'{$tableRowBg}');toggleClass('{$rowIdPrefix}','{$tableRowBg}');" onclick="if(getEventTarget(event)=='TD') checkAll('{$rowIdPrefix}_s');">
			<td align="center" rowspan="2"><input type="checkbox" name="row_id[]" value="{$result.t_id}"></td>
			<td colspan="{math equation="x" x=$smarty.foreach.headers.total}">{if $result.t_is_closed}<img src="{devblocks_url}c=resource&p=cerberusweb.forums&f=images/check_gray.gif{/devblocks_url}" align="top"> {/if}<a href="{devblocks_url}c=forums&a=explorer{/devblocks_url}?start={$result.t_id}" target="_blank" class="ticketLink" style="font-size:12px;"><b id="subject_{$result.t_id}_{$view->id}">{$result.t_title}</b></a></td>
		</tr>
		<tr class="{$tableRowBg}" id="{$rowIdPrefix}" onmouseover="toggleClass(this.id,'tableRowHover');toggleClass('{$rowIdPrefix}_s','tableRowHover');" onmouseout="toggleClass(this.id,'{$tableRowBg}');toggleClass('{$rowIdPrefix}_s','{$tableRowBg}');" onclick="if(getEventTarget(event)=='TD') checkAll('{$rowIdPrefix}_s');">
		{foreach from=$view->view_columns item=column name=columns}
			{if $column=="t_id"}
				<td>{$result.t_id}&nbsp;</td>
			{elseif $column=="t_forum_id"}
				{assign var=thread_source_id value=$result.t_forum_id}
				<td>
					{if isset($sources.$thread_source_id)}
						{$sources.$thread_source_id->name}&nbsp;
					{/if}
				</td>
			{elseif $column=="t_worker_id"}
				{assign var=thread_worker_id value=$result.t_worker_id}
				<td>
					{if isset($workers.$thread_worker_id)}
						{$workers.$thread_worker_id->getName()}&nbsp;
					{/if}
				</td>
			{elseif $column=="t_last_updated"}
				<td>{$result.t_last_updated|devblocks_date}&nbsp;</td>
			{elseif $column=="t_link"}
				<td><a href="{$result.t_link}" target="_blank" title="{$result.t_link}">{$result.t_link|truncate:64:'...':true:true}</a>&nbsp;</td>
			{else}
				<td>{$result.$column}&nbsp;</td>
			{/if}
		{/foreach}
		</tr>
	{/foreach}
	
</table>
<table cellpadding="2" cellspacing="0" border="0" width="100%" class="tableBg" id="{$view->id}_actions">
	{if $total}
	<tr>
		<td colspan="2">
			<button type="button" id="btnForumThreadClose" onclick="this.form.a.value='viewCloseThreads';this.form.submit();"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/folder_ok.gif{/devblocks_url}" align="top"> close</button>
		
			{literal}<select name="assign_worker_id" onchange="if(''!=selectValue(this)){this.form.a.value='viewAssignThreads';this.form.submit();}">{/literal}
				<option value="">-- assign worker --</option>
				{foreach from=$workers item=worker key=worker_id}
					<option value="{$worker_id}">{$worker->getName()}</option>
				{/foreach}
				<option value="0">- unassign -</option>
			</select>
		
			<br>
			
			keyboard: (<b>c</b>) close, (<b>s</b>) synchronize <br>
		
			<!-- <span id="tourDashboardBatch"><button type="button" onclick="ajax.showBatchPanel('{$view->id}','{$dashboard_team_id}');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/folder_gear.gif{/devblocks_url}" align="top"> bulk update</button></span>  -->
			
			<!-- <a href="javascript:;" onclick="toggleDiv('view{$view->id}_more');">More &raquo;</a>-->

			<!-- 
			<div id="view{$view_id}_more" style="display:none;padding-top:5px;padding-bottom:5px;">
				<button type="button" onclick="ajax.viewTicketsAction('{$view->id}','merge');">merge</button>
			</div>
			 -->

		</td>
	</tr>
	{/if}
	<tr>
		<td align="right" valign="top" nowrap="nowrap">
			{math assign=fromRow equation="(x*y)+1" x=$view->renderPage y=$view->renderLimit}
			{math assign=toRow equation="(x-1)+y" x=$fromRow y=$view->renderLimit}
			{math assign=nextPage equation="x+1" x=$view->renderPage}
			{math assign=prevPage equation="x-1" x=$view->renderPage}
			{math assign=lastPage equation="ceil(x/y)-1" x=$total y=$view->renderLimit}
			
			{* Sanity checks *}
			{if $toRow > $total}{assign var=toRow value=$total}{/if}
			{if $fromRow > $toRow}{assign var=fromRow value=$toRow}{/if}
			
			{if $view->renderPage > 0}
				<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewPage&id={$view->id}&page=0');">&lt;&lt;</a>
				<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewPage&id={$view->id}&page={$prevPage}');">&lt;{$translate->_('common.prev')|capitalize}</a>
			{/if}
			(Showing {$fromRow}-{$toRow} of {$total})
			{if $toRow < $total}
				<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewPage&id={$view->id}&page={$nextPage}');">{$translate->_('common.next')|capitalize}&gt;</a>
				<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewPage&id={$view->id}&page={$lastPage}');">&gt;&gt;</a>
			{/if}
		</td>
	</tr>
</table>
</form>
<br>
