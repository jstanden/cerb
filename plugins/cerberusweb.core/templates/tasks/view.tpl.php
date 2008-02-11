{assign var=total value=$results[1]}
{assign var=data value=$results[0]}
<table cellpadding="0" cellspacing="0" border="0" class="tableBlue" width="100%">
	<tr>
		<td nowrap="nowrap" class="tableThBlue">{$view->name} {if $view->id == 'search'}<a href="#{$view->id}_actions" style="color:rgb(255,255,255);font-size:11px;">jump to actions</a>{/if}</td>
		<td nowrap="nowrap" class="tableThBlue" align="right">
			<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewRefresh&id={$view->id}');" class="tableThLink">{$translate->_('common.refresh')|lower}</a>
			<!-- {if $view->id != 'search'}<span style="font-size:12px"> | </span><a href="{devblocks_url}c=contacts&a=searchview&id={$view->id}{/devblocks_url}" class="tableThLink">{$translate->_('common.search')|lower} list</a>{/if} -->
			<span style="font-size:12px"> | </span><a href="javascript:;" onclick="genericAjaxGet('customize{$view->id}','c=internal&a=viewCustomize&id={$view->id}');toggleDiv('customize{$view->id}','block');" class="tableThLink">{$translate->_('common.customize')|lower}</a>
		</td>
	</tr>
</table>

<form id="customize{$view->id}" name="customize{$view->id}" action="#" onsubmit="return false;" style="display:none;"></form>
<form id="viewForm{$view->id}" name="viewForm{$view->id}" action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="id" value="{$view->id}">
<input type="hidden" name="c" value="tasks">
<input type="hidden" name="a" value="">
<table cellpadding="1" cellspacing="0" border="0" width="100%" class="tableRowBg">

	{* Column Headers *}
	<tr class="tableTh">
		<th style="text-align:center"><input type="checkbox" onclick="checkAll('view{$view->id}',this.checked);this.blur();"></th>
		{foreach from=$view->view_columns item=header name=headers}
			{* start table header, insert column title and link *}
			<th nowrap="nowrap">
			{if $header=="bob_id"}<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewSortBy&id={$view->id}&sortBy=o_id');">{$translate->_('contact_org.id')|capitalize}</a>
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
			<td colspan="{math equation="x" x=$smarty.foreach.headers.total}">
				{if $result.t_is_completed}
					<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check2.gif{/devblocks_url}" align="top">
				{else}
					{if 1==$result.t_priority}
						<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/nav_up_red.gif{/devblocks_url}" align="top" title="{$translate->_('priority.high')|capitalize}">
					{elseif 2==$result.t_priority}
						<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/nav_right_green.gif{/devblocks_url}" align="top" title="{$translate->_('priority.normal')|capitalize}">
					{elseif 3==$result.t_priority}
						<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/nav_down_blue.gif{/devblocks_url}" align="top" title="{$translate->_('priority.low')|capitalize}">
					{else}
					{/if}
				{/if}
				<a href="javascript:;" onclick="genericAjaxPanel('c=tasks&a=showTaskPeek&id={$result.t_id}&view_id={$view->id}',this,false,'550px');" class="ticketLink" style="font-size:12px;"><b id="subject_{$result.t_id}_{$view->id}">{$result.t_title|escape}</b></a>&nbsp;
			</td>
		</tr>
		<tr class="{$tableRowBg}" id="{$rowIdPrefix}" onmouseover="toggleClass(this.id,'tableRowHover');toggleClass('{$rowIdPrefix}_s','tableRowHover');" onmouseout="toggleClass(this.id,'{$tableRowBg}');toggleClass('{$rowIdPrefix}_s','{$tableRowBg}');" onclick="if(getEventTarget(event)=='TD') checkAll('{$rowIdPrefix}_s');">
		{foreach from=$view->view_columns item=column name=columns}
			{if $column=="t_id"}
				<td>{$result.t_id}&nbsp;</td>
			{elseif $column=="t_completed_date"}
				<td>
					{if !empty($result.t_completed_date)}
						{$result.t_completed_date|date_format}&nbsp;
					{/if}
				</td>
			{elseif $column=="t_due_date"}
				{assign var=overdue value=0}
				{if $result.t_due_date}
					{math assign=overdue equation="(t-x)" t=$timestamp_now x=$result.t_due_date format="%d"}
				{/if}
				<td title="{$result.t_due_date|date_format:'%A %b %e, %Y  %I:%M%p GMT'}" style="{if $overdue > 0}color:rgb(220,0,0);font-weight:bold;{/if}">{$result.t_due_date|prettytime}</td>
			{elseif $column=="t_url"}
				<td>
					{if empty($result.t_url)}
					<a href="{$result.t_url}" target="_blank">{$result.t_url|truncate:64:'...':true}</a>
					{/if}
				</td>
			{elseif $column=="t_worker_id"}
				<td>
					{assign var=t_worker_id value=$result.t_worker_id}
					{if isset($workers.$t_worker_id)}
						{$workers.$t_worker_id->getName()}&nbsp;
					{/if}
				</td>
			{elseif $column=="t_priority"}
				<td>
				{if 1==$result.t_priority}
					{$translate->_('priority.high')|capitalize}
				{elseif 2==$result.t_priority}
					{$translate->_('priority.normal')|capitalize}
				{elseif 3==$result.t_priority}
					{$translate->_('priority.low')|capitalize}
				{else}
				{/if}
				</td>
			{elseif $column=="t_is_completed"}
				<td>
					{if $result.t_is_completed}
					<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check2.gif{/devblocks_url}" align="top">
					{/if}
				</td>
			{elseif $column=="t_source_extension"}
				<td>
					{assign var=source_extension value=$result.t_source_extension}
					{assign var=source_id value=$result.t_source_id}
					{assign var=source_renderer value=$source_renderers.$source_extension}
					{if !empty($source_id) && !empty($source_renderer)}
						{assign var=source_info value=$source_renderer->getSourceInfo($source_id)}
						<a href="{$source_info.url}" title="{$source_info.name|escape}">{$source_info.name|truncate:75:'...':true|escape}</a>
					{/if}
				</td>
			{else}
				<td>{$result.$column}</td>
			{/if}
		{/foreach}
		</tr>
	{/foreach}
	
</table>
<table cellpadding="2" cellspacing="0" border="0" width="100%" class="tableBg" id="{$view->id}_actions">
	{if $total}
	<tr>
		<td colspan="2" valign="top">
			<button type="button" id="btnComplete" onclick="this.form.a.value='viewComplete';this.form.submit();"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check2.gif{/devblocks_url}" align="top" title="{$translate->_('tasks.complete')|capitalize}"> {$translate->_('tasks.complete')|capitalize}</button> 
			<button type="button" id="btnPostpone" onclick="this.form.a.value='viewPostpone';this.form.submit();"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/clock.gif{/devblocks_url}" align="top" title="{$translate->_('tasks.postpone')|capitalize}"> {$translate->_('tasks.postpone')|capitalize}</button>

			<button style="display:none;" type="button" id="btnPriorityHigh" onclick="this.form.a.value='viewPriorityHigh';this.form.submit();">&nbsp;<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/nav_up_red.gif{/devblocks_url}" align="top" title="{$translate->_('priority.high')|capitalize}">&nbsp;</button><!--  
			--><button style="display:none;" type="button" id="btnPriorityNormal" onclick="this.form.a.value='viewPriorityNormal';this.form.submit();">&nbsp;<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/nav_right_green.gif{/devblocks_url}" align="top" title="{$translate->_('priority.normal')|capitalize}">&nbsp;</button><!--
			--><button style="display:none;" type="button" id="btnPriorityLow" onclick="this.form.a.value='viewPriorityLow';this.form.submit();">&nbsp;<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/nav_down_blue.gif{/devblocks_url}" align="top" title="{$translate->_('priority.low')|capitalize}">&nbsp;</button><!--
			--><button style="display:none;" type="button" id="btnPriorityNone" onclick="this.form.a.value='viewPriorityNone';this.form.submit();">&nbsp;<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/star_alpha.gif{/devblocks_url}" align="top" title="{$translate->_('priority.none')|capitalize}">&nbsp;</button><!-- 
			
			--><button style="display:none;" type="button" id="btnDueToday" onclick="this.form.a.value='viewDueToday';this.form.submit();">&nbsp;&nbsp;</button><!-- 
			--><button style="display:none;" type="button" id="btnTake" onclick="this.form.a.value='viewTake';this.form.submit();">&nbsp;&nbsp;</button><!-- 
			--><button style="display:none;" type="button" id="btnSurrender" onclick="this.form.a.value='viewSurrender';this.form.submit();">&nbsp;&nbsp;</button><!-- 
			--><button style="display:none;" type="button" id="btnDelete" onclick="this.form.a.value='viewDelete';this.form.submit();">&nbsp;&nbsp;</button><!--
			-->

			{if $view->id=='tasks'}		
			<br>
			keyboard: (<b>1</b>) high priority, (<b>2</b>) normal, (<b>3</b>) low, (<b>4</b>) no priority, (<b>c</b>) completed, (<b>d</b>) due today, (<b>p</b>) postpone +24h, (<b>a</b>) assign to me, (<b>s</b>) surrender, (<b>x</b>) delete<br>
			{/if}
		
			{*
			{if !empty($workers)}
			<select name="worker_id" onchange="this.form.a.value='viewOppSetWorker';this.form.submit();">
				<option value="">-- set worker --</option>
				{foreach from=$workers item=worker key=worker_id}
					<option value="{$worker_id}">{$worker->getName()}</option>
				{/foreach}
				<option value="0">- unassign -</option>
			</select>
			{/if}
			*}
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