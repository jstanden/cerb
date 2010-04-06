{assign var=results value=$view->getData()}
{assign var=total value=$results[1]}
{assign var=data value=$results[0]}
<table cellpadding="0" cellspacing="0" border="0" class="worklist" width="100%">
	<tr>
		<td nowrap="nowrap"><span class="title">{$view->name}</span></td>
		<td nowrap="nowrap" align="right">
			<a href="javascript:;" onclick="$('#btnExplore{$view->id}').click();">explore</a>
			 | <a href="javascript:;" onclick="genericAjaxGet('customize{$view->id}','c=internal&a=viewCustomize&id={$view->id}');toggleDiv('customize{$view->id}','block');">{$translate->_('common.customize')|lower}</a>
			{if $active_worker->hasPriv('core.home.workspaces')} | <a href="javascript:;" onclick="genericAjaxGet('{$view->id}_tips','c=internal&a=viewShowCopy&view_id={$view->id}');toggleDiv('{$view->id}_tips','block');">{$translate->_('common.copy')|lower}</a>{/if}
			 | <a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewRefresh&id={$view->id}');"><span class="cerb-sprite sprite-refresh"></span></a>
		</td>
	</tr>
</table>

<div id="{$view->id}_tips" class="block" style="display:none;margin:10px;padding:5px;">Analyzing...</div>
<form id="customize{$view->id}" name="customize{$view->id}" action="#" onsubmit="return false;" style="display:none;"></form>
<form id="viewForm{$view->id}" name="viewForm{$view->id}" action="{devblocks_url}{/devblocks_url}" method="POST">
<button id="btnExplore{$view->id}" type="button" style="display:none;" onclick="this.form.a.value='viewForumsExplore';this.form.submit();"></button>
<input type="hidden" name="view_id" value="{$view->id}">
<input type="hidden" name="c" value="forums">
<input type="hidden" name="a" value="">
<table cellpadding="0" cellspacing="0" border="0" width="100%" class="worklistBody">

	{* Column Headers *}
	<tr>
		<th style="text-align:center"><input type="checkbox" onclick="checkAll('view{$view->id}',this.checked);"></th>
		{foreach from=$view->view_columns item=header name=headers}
			{* start table header, insert column title and link *}
			<th nowrap="nowrap">
			<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewSortBy&id={$view->id}&sortBy={$header}');">{$view_fields.$header->db_label|capitalize}</a>
			
			{* add arrow if sorting by this column, finish table header tag *}
			{if $header==$view->renderSortBy}
				{if $view->renderSortAsc}
					<span class="cerb-sprite sprite-sort_ascending"></span>
				{else}
					<span class="cerb-sprite sprite-sort_descending"></span>
				{/if}
			{/if}
			</th>
		{/foreach}
	</tr>

	{* Column Data *}
	{foreach from=$data item=result key=idx name=results}

	{assign var=rowIdPrefix value="row_"|cat:$view->id|cat:"_"|cat:$result.t_id}
	{if $smarty.foreach.results.iteration % 2}
		{assign var=tableRowClass value="even"}
	{else}
		{assign var=tableRowClass value="odd"}
	{/if}
	
		<tr class="{$tableRowClass}" id="{$rowIdPrefix}_s" onmouseover="$(this).addClass('hover');$('#{$rowIdPrefix}').addClass('hover');" onmouseout="$(this).removeClass('hover');$('#{$rowIdPrefix}').removeClass('hover');" onclick="if(getEventTarget(event)=='TD') checkAll('{$rowIdPrefix}_s');">
			<td align="center" rowspan="2"><input type="checkbox" name="row_id[]" value="{$result.t_id}"></td>
			<td colspan="{math equation="x" x=$smarty.foreach.headers.total}">{if $result.t_is_closed}<img src="{devblocks_url}c=resource&p=cerberusweb.forums&f=images/check_gray.gif{/devblocks_url}" align="top"> {/if}<a href="{$result.t_link|escape}" target="_blank" class="subject">{$result.t_title}</a></td>
		</tr>
		<tr class="{$tableRowClass}" id="{$rowIdPrefix}" onmouseover="$(this).addClass('hover');$('#{$rowIdPrefix}_s').addClass('hover');" onmouseout="$(this).removeClass('hover');$('#{$rowIdPrefix}_s').removeClass('hover');" onclick="if(getEventTarget(event)=='TD') checkAll('{$rowIdPrefix}_s');">
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
				<td><abbr title="{$result.t_last_updated|devblocks_date}">{$result.t_last_updated|devblocks_prettytime}</abbr>&nbsp;</td>
			{elseif $column=="t_link"}
				<td><a href="{$result.t_link}" target="_blank" title="{$result.t_link}">{$result.t_link|truncate:64:'...':true:true}</a>&nbsp;</td>
			{else}
				<td>{$result.$column}&nbsp;</td>
			{/if}
		{/foreach}
		</tr>
	{/foreach}
	
</table>
<table cellpadding="2" cellspacing="0" border="0" width="100%" id="{$view->id}_actions">
	{if $total}
	<tr>
		<td colspan="2">
			<button type="button" id="btnForumThreadClose" onclick="this.form.a.value='viewCloseThreads';genericAjaxPost('viewForm{$view->id}','view{$view->id}','c=forums');"><span class="cerb-sprite sprite-folder_ok"></span> {'common.close'|devblocks_translate|lower}</button>
		
			{literal}<select name="assign_worker_id" onchange="if(''!=selectValue(this)){this.form.a.value='viewAssignThreads';{/literal}genericAjaxPost('viewForm{$view->id}','view{$view->id}','c=forums');{literal}}">{/literal}
				<option value="">-- {$translate->_('common.assign')|lower} --</option>
				{foreach from=$workers item=worker key=worker_id}
					<option value="{$worker_id}">{$worker->getName()}</option>
				{/foreach}
				<option value="0">- {$translate->_('common.unassign')|capitalize} -</option>
			</select>
		
			<br>
			
			{*
			{if $pref_keyboard_shortcuts}
			{$translate->_('common.keyboard')|capitalize}: (<b>c</b>) {$translate->_('common.close')|lower}, (<b>s</b>) {$translate->_('common.synchronize')|lower} <br>
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
				<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewPage&id={$view->id}&page={$prevPage}');">&lt;{$translate->_('common.previous_short')|capitalize}</a>
			{/if}
			({'views.showing_from_to'|devblocks_translate:$fromRow:$toRow:$total})
			{if $toRow < $total}
				<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewPage&id={$view->id}&page={$nextPage}');">{$translate->_('common.next')|capitalize}&gt;</a>
				<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewPage&id={$view->id}&page={$lastPage}');">&gt;&gt;</a>
			{/if}
		</td>
	</tr>
</table>
</form>
<br>
