{assign var=results value=$view->getData()}
{assign var=total value=$results[1]}
{assign var=data value=$results[0]}
<table cellpadding="0" cellspacing="0" border="0" class="tableBlue" width="100%">
	<tr>
		<td nowrap="nowrap" class="tableThBlue">{$view->name} {if $view->id == 'search'}<a href="#{$view->id}_actions" style="color:rgb(255,255,255);font-size:11px;">{$translate->_('views.jump_to_actions')}</a>{/if}</td>
		<td nowrap="nowrap" class="tableThBlue" align="right">
			<a href="javascript:;" onclick="genericAjaxGet('customize{$view->id}','c=internal&a=viewCustomize&id={$view->id}');toggleDiv('customize{$view->id}','block');" class="tableThLink">{$translate->_('common.customize')|lower}</a>
			{* {if $view->id != 'search'}<span style="font-size:12px"> | </span><a href="{devblocks_url}c=internal&a=searchview&id={$view->id}{/devblocks_url}" class="tableThLink">{$translate->_('common.search')|lower} list</a>{/if} *}
			<span style="font-size:12px"> | </span><a href="javascript:;" onclick="genericAjaxGet('{$view->id}_tips','c=internal&a=viewShowExport&id={$view->id}');toggleDiv('{$view->id}_tips','block');" class="tableThLink">{$translate->_('common.export')|lower}</a>
			<span style="font-size:12px"> | </span><a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewRefresh&id={$view->id}');" class="tableThLink"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/refresh.gif{/devblocks_url}" border="0" align="absmiddle" title="{$translate->_('common.refresh')|lower}" alt="{$translate->_('common.refresh')|lower}"></a>
		</td>
	</tr>
</table>

<div id="{$view->id}_tips" class="block" style="display:none;margin:10px;padding:5px;">Analyzing...</div>
<form id="customize{$view->id}" name="customize{$view->id}" action="#" onsubmit="return false;" style="display:none;"></form>
<form id="viewForm{$view->id}" name="viewForm{$view->id}" action="#">
<input type="hidden" name="id" value="{$view->id}">
<input type="hidden" name="c" value="time">
<input type="hidden" name="a" value="">
<table cellpadding="1" cellspacing="0" border="0" width="100%" class="tableRowBg">

	{* Column Headers *}
	<tr class="tableTh">
		<th style="text-align:center">&nbsp;{*<input type="checkbox" onclick="checkAll('view{$view->id}',this.checked);">*}</th>
		{foreach from=$view->view_columns item=header name=headers}
			{* start table header, insert column title and link *}
			<th nowrap="nowrap">
			{if $header=="x"}<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewSortBy&id={$view->id}&sortBy=a_id');">{$translate->_('timetracking_entry.id')|capitalize}</a>
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

	{assign var=rowIdPrefix value="row_"|cat:$view->id|cat:"_"|cat:$result.tt_id}
	{if $smarty.foreach.results.iteration % 2}
		{assign var=tableRowBg value="tableRowBg"}
	{else}
		{assign var=tableRowBg value="tableRowAltBg"}
	{/if}
	
	{assign var=worker_id value=$result.tt_worker_id}
	{assign var=activity_id value=$result.tt_activity_id}
	
	{assign var=generic_worker value='timetracking.ui.generic_worker'|devblocks_translate}
	{if isset($workers.$worker_id)}
		{assign var=worker_name value=$workers.$worker_id->getName()}
	{else}
		{assign var=worker_name value=$generic_worker}
	{/if}


		<tr class="{$tableRowBg}" id="{$rowIdPrefix}" onmouseover="toggleClass(this.id,'tableRowHover');toggleClass('{$rowIdPrefix}_s','tableRowHover');" onmouseout="toggleClass(this.id,'{$tableRowBg}');toggleClass('{$rowIdPrefix}_s','{$tableRowBg}');" onclick="if(getEventTarget(event)=='TD') checkAll('{$rowIdPrefix}_s');">
		<td align="center" rowspan="2">{*<input type="checkbox" name="row_id[]" value="{$result.a_id}">*}</td>
		{foreach from=$view->view_columns item=column name=columns}
			{if substr($column,0,3)=="cf_"}
				{assign var=col value=$column|explode:'_'}
				{assign var=col_id value=$col.1}
				{assign var=col value=$custom_fields.$col_id}
				
				{if $col->type=='S'}
				<td>{$result.$column}</td>
				{elseif $col->type=='T'}
				<td title="{$result.$column|escape}">{$result.$column|truncate:32}</td>
				{elseif $col->type=='D'}
				<td>{$result.$column}</td>
				{elseif $col->type=='E'}
				<td><abbr title="{$result.$column|devblocks_date}">{$result.$column|devblocks_prettytime}</abbr></td>
				{elseif $col->type=='C'}
				<td>{if '1'==$result.$column}Yes{elseif '0'==$result.$column}No{/if}</td>
				{/if}
			{elseif $column=="tt_id"}
			<td>{$result.tt_id}&nbsp;</td>
			{elseif $column=="o_name"}
			<td>
				{if !empty($result.o_name)}
				<a href="javascript:;" onclick="genericAjaxPanel('c=contacts&a=showOrgPeek&id={$result.tt_debit_org_id}&view_id={$view->id}',this,false,'500px',ajax.cbOrgCountryPeek);">{$result.o_name}</a>
				{/if}
			</td>
			{elseif $column=="tt_log_date"}
			<td title="{$result.tt_log_date|devblocks_date}">{$result.tt_log_date|devblocks_date:'EEE, MMM d Y'}&nbsp;</td>
			{elseif $column=="tt_worker_id"}
				<td>{if isset($workers.$worker_id)}{$workers.$worker_id->getName()}{/if}&nbsp;</td>
			{elseif $column=="tt_activity_id"}
				<td>{if isset($activities.$activity_id)}{$activities.$activity_id->name}{if $activities.$activity_id->rate > 0} ($){/if}{/if}&nbsp;</td>
			{elseif $column=="tt_source_extension_id"}
				{assign var=source_ext_id value=$result.tt_source_extension_id}
				{assign var=source_id value=$result.tt_source_id}
				<td>{if isset($sources.$source_ext_id)}
					{assign var=source value=$sources.$source_ext_id}
					<a href="{$source->getLink($source_id)}">{$source->getLinkText($source_id)}</a>{/if}&nbsp;
				</td>
			{else}
			<td>{$result.$column}&nbsp;</td>
			{/if}
		{/foreach}
		</tr>
		<tr class="{$tableRowBg}" id="{$rowIdPrefix}_s" onmouseover="toggleClass(this.id,'tableRowHover');toggleClass('{$rowIdPrefix}','tableRowHover');" onmouseout="toggleClass(this.id,'{$tableRowBg}');toggleClass('{$rowIdPrefix}','{$tableRowBg}');" onclick="if(getEventTarget(event)=='TD') checkAll('{$rowIdPrefix}_s');">
			<td colspan="{math equation="x" x=$smarty.foreach.headers.total}">
			<div id="subject_{$result.f_id}_{$view->id}" style="margin:2px;margin-left:10px;font-size:12px;">
				<a href="javascript:;" style="color:rgb(75,75,75);font-size:12px;" onclick="genericAjaxPanel('c=timetracking&a=showEntry&id={$result.tt_id}&view_id={$view->id}',this,false,'500px',function(o){literal}{{/literal} ajax.cbAddressPeek(); genericAjaxPostAfterSubmitEvent.subscribe(function(type,args){literal}{{/literal} genericAjaxGet('view{$view->id}','c=internal&a=viewRefresh&id={$view->id}');{literal}}{/literal}); {literal}}{/literal} );">
				<b id="subject_{$result.tt_id}_{$view->id}">
					{'timetracking.ui.tracked_desc'|devblocks_translate:$worker_name:$result.tt_time_actual_mins:$activities.$activity_id->name}
				</b>
				</a>
				<br>
				{if !empty($result.tt_notes)}{$result.tt_notes}{/if}
			</div>
			</td>
		</tr>
	{/foreach}
	
</table>
<table cellpadding="2" cellspacing="0" border="0" width="100%" class="tableBg" id="{$view->id}_actions">
	{if $total}
	<tr>
		<td colspan="2">
			{*<button type="button" onclick="ajax.showAddressBatchPanel('{$view->id}',this);"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/folder_gear.gif{/devblocks_url}" align="top"> bulk update</button>*}
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
