{assign var=results value=$view->getData()}
{assign var=total value=$results[1]}
{assign var=data value=$results[0]}
<table cellpadding="0" cellspacing="0" border="0" class="tableBlue" width="100%">
	<tr>
		<td nowrap="nowrap" class="tableThBlue">{$view->name} {if $view->id == 'search'}<a href="#{$view->id}_actions" style="color:rgb(255,255,255);font-size:11px;">{$translate->_('views.jump_to_actions')}</a>{/if}</td>
		<td nowrap="nowrap" class="tableThBlue" align="right">
			<a href="javascript:;" onclick="genericAjaxGet('customize{$view->id}','c=internal&a=viewCustomize&id={$view->id}');toggleDiv('customize{$view->id}','block');" class="tableThLink">{$translate->_('common.customize')|lower}</a>
			{* {if $view->id != 'search'}<span style="font-size:12px"> | </span><a href="{devblocks_url}c=internal&a=searchview&id={$view->id}{/devblocks_url}" class="tableThLink">{$translate->_('common.search')|lower} list</a>{/if} *}
			{if $active_worker->is_superuser || $active_worker->can_export}<span style="font-size:12px"> | </span><a href="javascript:;" onclick="genericAjaxGet('{$view->id}_tips','c=internal&a=viewShowExport&id={$view->id}');toggleDiv('{$view->id}_tips','block');" class="tableThLink">{$translate->_('common.export')|lower}</a>{/if}
			<span style="font-size:12px"> | </span><a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewRefresh&id={$view->id}');" class="tableThLink"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/refresh.gif{/devblocks_url}" border="0" align="absmiddle" title="{$translate->_('common.refresh')|lower}" alt="{$translate->_('common.refresh')|lower}"></a>			
		</td>
	</tr>
</table>

<div id="{$view->id}_tips" class="block" style="display:none;margin:10px;padding:5px;">Analyzing...</div>
<form id="customize{$view->id}" name="customize{$view->id}" action="#" onsubmit="return false;" style="display:none;"></form>
<form id="viewForm{$view->id}" name="viewForm{$view->id}" action="#">
<input type="hidden" name="id" value="{$view->id}">
<input type="hidden" name="c" value="feedback">
<input type="hidden" name="a" value="">

<table cellpadding="1" cellspacing="0" border="0" width="100%" class="tableRowBg">

	{* Column Headers *}
	<tr class="tableTh">
		<th style="text-align:center"><input type="checkbox" onclick="checkAll('view{$view->id}',this.checked);"></th>
		{foreach from=$view->view_columns item=header name=headers}
			{* start table header, insert column title and link *}
			<th nowrap="nowrap">
			{if $header=="x"}<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewSortBy&id={$view->id}&sortBy=f_id');">{$translate->_('feedback_entry.id')|capitalize}</a>
			{else}<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewSortBy&id={$view->id}&sortBy={$header}');">{$view_fields.$header->db_label|capitalize}</a>
			{/if}
			
			{* add arrow if sorting by this column, finish table header tag *}
			{if $header==$view->renderSortBy}
				{if $view->renderSortAsc}
					<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/sort_ascending.png{/devblocks_url}" align="absmiddle">
				{else}
					<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/sort_descending.png{/devblocks_url}" align="absmiddle">
				{/if}
			{/if}
			</th>
		{/foreach}
	</tr>

	{* Column Data *}
	{foreach from=$data item=result key=idx name=results}

	{assign var=rowIdPrefix value="row_"|cat:$view->id|cat:"_"|cat:$result.f_id}
	{if $smarty.foreach.results.iteration % 2}
		{assign var=tableRowBg value="tableRowBg"}
	{else}
		{assign var=tableRowBg value="tableRowAltBg"}
	{/if}
	
	{assign var=list_id value=$result.f_list_id}
	{assign var=worker_id value=$result.f_worker_id}
	{assign var=mood value=$result.f_quote_mood}
	
		<tr class="{$tableRowBg}" id="{$rowIdPrefix}" onmouseover="toggleClass(this.id,'tableRowHover');toggleClass('{$rowIdPrefix}_s','tableRowHover');" onmouseout="toggleClass(this.id,'{$tableRowBg}');toggleClass('{$rowIdPrefix}_s','{$tableRowBg}');" onclick="if(getEventTarget(event)=='TD') checkAll('{$rowIdPrefix}');">
			<td align="center" rowspan="2"><input type="checkbox" name="row_id[]" value="{$result.f_id}"></td>
		{foreach from=$view->view_columns item=column name=columns}
			{if substr($column,0,3)=="cf_"}
				{include file="file:$core_tpl/internal/custom_fields/view/cell_renderer.tpl.php"}
			{elseif $column=="f_id"}
				<td>{$result.f_id}&nbsp;</td>
			{elseif $column=="a_email"}
				<td>
					{if !empty($result.a_email)}
						<a href="javascript:;" onclick="genericAjaxPanel('c=contacts&a=showAddressPeek&address_id={$result.f_quote_address_id}&view_id={$view->id}',this,false,'500px',ajax.cbAddressPeek);">{$result.a_email}</a>
					{else}
						<i>anonymous</i>
					{/if}
				</td>
			{elseif $column=="f_log_date"}
				<td title="{$result.f_log_date|devblocks_date}">{$result.f_log_date|devblocks_date:'EEE, MMM d Y'}&nbsp;</td>
			{elseif $column=="f_worker_id"}
				<td>{if isset($workers.$worker_id)}{$workers.$worker_id->getName()}{/if}&nbsp;</td>
			{elseif $column=="f_list_id"}
				<td>{if isset($lists.$list_id)}{$lists.$list_id->name}{/if}&nbsp;</td>
			{elseif $column=="f_quote_mood"}
				<td>
					{if 1==$result.$column}
						<span style="background-color:rgb(235, 255, 235);color:rgb(0, 180, 0);font-weight:bold;">Praise</span>
					{elseif 2==$result.$column}
						<span style="background-color: rgb(255, 235, 235);color: rgb(180, 0, 0);font-weight:bold;">Criticism</span>
					{else}
						Neutral
					{/if}
				</td>
			{elseif $column=="f_source_url"}
				<td><a href="{$result.f_source_url|escape}" target="_blank" title="{$result.f_source_url|escape}">{$result.f_source_url|truncate:35:'...':true:false}</a>&nbsp;</td>
			{else}
				<td>{$result.$column}&nbsp;</td>
			{/if}
		{/foreach}
		</tr>
		<tr class="{$tableRowBg}" id="{$rowIdPrefix}_s" onmouseover="toggleClass(this.id,'tableRowHover');toggleClass('{$rowIdPrefix}','tableRowHover');" onmouseout="toggleClass(this.id,'{$tableRowBg}');toggleClass('{$rowIdPrefix}','{$tableRowBg}');" onclick="if(getEventTarget(event)=='TD'||getEventTarget(event)=='DIV') checkAll('{$rowIdPrefix}');">
			<td colspan="{math equation="x" x=$smarty.foreach.headers.total}">
				<div id="subject_{$result.f_id}_{$view->id}" style="margin:5px;margin-left:10px;font-size:12px;">
					<img src="{devblocks_url}c=resource&p=cerberusweb.feedback&f=images/{if 1==$mood}bullet_ball_glass_green.png{elseif 2==$mood}bullet_ball_glass_red.png{else}bullet_ball_glass_grey.png{/if}{/devblocks_url}" align="top" title="{if 1==$mood}Praise{elseif 2==$mood}Criticism{else}Neutral{/if}"> 
					{$result.f_quote_text} 
					<a href="javascript:;" style="color:rgb(180,180,180);font-size:90%;" onclick="genericAjaxPanel('c=feedback&a=showEntry&id={$result.f_id}&view_id={$view->id}',this,false,'500px',function(o){literal}{{/literal} genericAjaxPostAfterSubmitEvent.subscribe(function(type,args){literal}{{/literal}genericAjaxGet('view{$view->id}','c=internal&a=viewRefresh&id={$view->id}');{literal}}{/literal}); {literal}}{/literal} );">(edit)</a>
				</div>
			</td>
		</tr>
		
	{/foreach}
	
</table>
<table cellpadding="2" cellspacing="0" border="0" width="100%" class="tableBg" id="{$view->id}_actions">
	{if $total}
	<tr>
		<td colspan="2">
			<button type="button" onclick="genericAjaxPanel('c=feedback&a=showBulkPanel&view_id={$view->id}&ids=' + Devblocks.getFormEnabledCheckboxValues('viewForm{$view->id}','row_id[]'),this,false,'500px');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/folder_gear.gif{/devblocks_url}" align="top"> bulk update</button>
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
