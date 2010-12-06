{$view_fields = $view->getColumnsAvailable()}
{assign var=results value=$view->getData()}
{assign var=total value=$results[1]}
{assign var=data value=$results[0]}
<table cellpadding="0" cellspacing="0" border="0" class="worklist" width="100%">
	<tr>
		<td nowrap="nowrap"><span class="title">{$view->name}</span> {if $view->id == 'search'}<a href="#{$view->id}_actions">{$translate->_('views.jump_to_actions')}</a>{/if}</td>
		<td nowrap="nowrap" align="right">
			<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewRefresh&id={$view->id}');">{$translate->_('common.refresh')|lower}</a>
			{* {if $view->id != 'search'} | <a href="{devblocks_url}c=internal&a=searchview&id={$view->id}{/devblocks_url}">{$translate->_('common.search')|lower} list</a>{/if} *}
			 | <a href="javascript:;" onclick="genericAjaxGet('customize{$view->id}','c=internal&a=viewCustomize&id={$view->id}');toggleDiv('customize{$view->id}','block');">{$translate->_('common.customize')|lower}</a>
		</td>
	</tr>
</table>

<form id="customize{$view->id}" name="customize{$view->id}" action="#" onsubmit="return false;" style="display:none;"></form>
<form id="viewForm{$view->id}" name="viewForm{$view->id}" action="{devblocks_url}{/devblocks_url}" method="POST">
<input type="hidden" name="view_id" value="{$view->id}">
<input type="hidden" name="c" value="translators">
<input type="hidden" name="a" value="">

<table cellpadding="1" cellspacing="0" border="0" width="100%" class="worklistBody">

	{* Column Headers *}
	<tr>
		<th style="text-align:center">{*<input type="checkbox" onclick="checkAll('view{$view->id}',this.checked);">*}</th>
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

	{if $smarty.foreach.results.iteration % 2}
		{assign var=tableRowClass value="even"}
	{else}
		{assign var=tableRowClass value="odd"}
	{/if}
	
	{assign var=list_id value=$result.f_list_id}
	{assign var=worker_id value=$result.f_worker_id}
	{assign var=mood value=$result.f_quote_mood}
	<tbody onmouseover="$(this).find('tr').addClass('hover');" onmouseout="$(this).find('tr').removeClass('hover');">
		<tr class="{$tableRowClass}">
			<td align="center" rowspan="2">{*<input type="checkbox" name="row_id[]" value="{$result.tl_id}">*}</td>
			<td colspan="{math equation="x" x=$smarty.foreach.headers.total}">
				<div id="subject_{$result.tl_id}_{$view->id}" style="margin:2px;font-size:12px;">
					<input type="hidden" name="row_ids[]" value="{$result.tl_id}">
					{assign var=lang_code value=$result.tl_lang_code}
					{assign var=string_id value=$result.tl_string_id}
					{assign var=english_string value=$english_map.$string_id}
					{if !empty($result.tl_string_default) || !empty($result.tl_string_override)}
						<b style="color:rgb(50,50,50);">{$langs.$lang_code}:</b><br>
						{if !empty($result.tl_string_default)}{* if official translation *}
						<div style="margin-top:5px;">
						<table cellpadding="0" cellspacing="0" style="border:1px dotted rgb(0,102,255);">
							<tr>
							<td style="padding:3px;color:rgb(0, 102, 255);font-size:10pt;">
								{$result.tl_string_default|nl2br}
							</td>
							</tr>
						</table>
						</div>
						{else}{* If unofficial translation *}
							{if 'en_US' != $result.tl_lang_code}
							{if !empty($english_string)}
							<span style="color:rgb(50,50,50);">{'translators.config.translate_from'|devblocks_translate:$langs.en_US}</span><br>
							<table cellpadding="0" cellspacing="0" style="margin-top:5px;margin-bottom:5px;border:1px dotted rgb(0, 102, 255);">
							<tr>
							<td style="padding:3px;color:rgb(0, 102, 255);font-size:10pt;">
								{$english_string->string_default|nl2br}
							</td>
							</tr>
							</table>
							{/if}
							{/if}
						{/if}
					{else}{* String not set *}
						{if 'en_US' != $result.tl_lang_code}
						{if !empty($english_string)}
						<img src="{devblocks_url}c=resource&p=cerberusweb.translators&f=images/16x16/warning.png{/devblocks_url}" align="top"> 
						<b style="color:rgb(175,0,0);">{$langs.$lang_code}</b><br>
						<span style="color:rgb(50,50,50);">{'translators.config.translate_from'|devblocks_translate:$langs.en_US}</span><br>
						<table cellpadding="0" cellspacing="0" style="margin-top:5px;margin-bottom:5px;border:1px dotted rgb(200,0,0);">
						<tr>
						<td style="padding:3px;color:rgb(50,50,50);font-size:10pt;">
							{$english_string->string_default|nl2br}
						</td>
						</tr>
						</table>
						{/if}
						{/if}
					{/if}
				</div>
			</td>
		</tr>

		<tr class="{$tableRowClass}">
		{foreach from=$view->view_columns item=column name=columns}
			{assign var=lang_code value=$result.tl_lang_code}
		
			{if $column=="tl_id"}
				<td valign="top">{$result.tl_id}&nbsp;</td>
			{elseif $column=="tl_string_override"}
				<td>
					{math assign=height equation="25+(25*floor(x/65))" x=$english_string->string_default|count_characters format="%d"}
					<textarea name="translations[]" style="width:98%;height:{$height}px;border:1px solid rgb(80,80,80);" rows="3" cols="45">{if !empty($result.$column)}{$result.$column|escape}{/if}</textarea>
				</td>
			{elseif $column=="tl_string_id"}
				<td valign="top">{$result.$column}&nbsp;</td>
			{elseif $column=="tl_lang_code"}
				<td valign="top">
					{$langs.$lang_code}&nbsp;
				</td>
			{else}
				<td valign="top">{$result.$column}&nbsp;</td>
			{/if}
		{/foreach}
		</tr>
	</tbody>
	{/foreach}
</table>
<table cellpadding="2" cellspacing="0" border="0" width="100%" id="{$view->id}_actions">
	{if $total}
	<tr>
		<td colspan="2">
			<button type="button" onclick="this.form.a.value='saveView';this.form.submit();"><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')|capitalize}</button>
			<button type="button" onclick="document.location.href='{devblocks_url}c=translators&a=exportTmx{/devblocks_url}';"><img src="{devblocks_url}c=resource&p=cerberusweb.translators&f=images/16x16/document_down.png{/devblocks_url}" align="top"> {$translate->_('common.export')|capitalize}</button>
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

{include file="devblocks:cerberusweb.core::internal/views/view_common_jquery_ui.tpl"}