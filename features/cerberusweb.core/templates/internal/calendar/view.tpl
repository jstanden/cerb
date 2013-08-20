{$view_context = CerberusContexts::CONTEXT_CALENDAR}
{$view_fields = $view->getColumnsAvailable()}
{assign var=results value=$view->getData()}
{assign var=total value=$results[1]}
{assign var=data value=$results[0]}

{include file="devblocks:cerberusweb.core::internal/views/view_marquee.tpl" view=$view}

<table cellpadding="0" cellspacing="0" border="0" class="worklist" width="100%">
	<tr>
		<td nowrap="nowrap"><span class="title">{$view->name}</span></td>
		<td nowrap="nowrap" align="right">
			<a href="javascript:;" title="{'common.add'|devblocks_translate|capitalize}" class="minimal" onclick="genericAjaxPopup('peek','c=internal&a=showPeekPopup&context={$view_context}&context_id=0&view_id={$view->id}&owner_context={$owner_context}&owner_context_id={$owner_context_id}',null,false,'500');"><span class="cerb-sprite2 sprite-plus-circle-frame"></span></a>
			<a href="javascript:;" title="{'common.search'|devblocks_translate|capitalize}" class="minimal" onclick="genericAjaxPopup('search','c=internal&a=viewShowQuickSearchPopup&view_id={$view->id}',this,false,'400');"><span class="cerb-sprite2 sprite-document-search-result"></span></a>
			<a href="javascript:;" title="{'common.customize'|devblocks_translate|capitalize}" class="minimal" onclick="genericAjaxGet('customize{$view->id}','c=internal&a=viewCustomize&id={$view->id}');toggleDiv('customize{$view->id}','block');"><span class="cerb-sprite2 sprite-gear"></span></a>
			<a href="javascript:;" title="Subtotals" class="subtotals minimal"><span class="cerb-sprite2 sprite-application-sidebar-list"></span></a>
			{*<a href="javascript:;" title="{$translate->_('common.import')|capitalize}" onclick="genericAjaxPopup('import','c=internal&a=showImportPopup&context={$view_context}&view_id={$view->id}',null,false,'500');"><span class="cerb-sprite2 sprite-application-import"></span></a>*}
			<a href="javascript:;" title="{$translate->_('common.export')|capitalize}" class="minimal" onclick="genericAjaxGet('{$view->id}_tips','c=internal&a=viewShowExport&id={$view->id}');toggleDiv('{$view->id}_tips','block');"><span class="cerb-sprite2 sprite-application-export"></span></a>
			<a href="javascript:;" title="{$translate->_('common.copy')|capitalize}" onclick="genericAjaxGet('{$view->id}_tips','c=internal&a=viewShowCopy&view_id={$view->id}');toggleDiv('{$view->id}_tips','block');"><span class="cerb-sprite2 sprite-applications"></span></a>
			<a href="javascript:;" title="{'common.refresh'|devblocks_translate|capitalize}" class="minimal" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewRefresh&id={$view->id}');"><span class="cerb-sprite2 sprite-arrow-circle-135-left"></span></a>
			<input type="checkbox" class="select-all">
		</td>
	</tr>
</table>

<div id="{$view->id}_tips" class="block" style="display:none;margin:10px;padding:5px;">Loading...</div>
<form id="customize{$view->id}" name="customize{$view->id}" action="#" onsubmit="return false;" style="display:none;"></form>
<form id="viewForm{$view->id}" name="viewForm{$view->id}" action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="view_id" value="{$view->id}">
<input type="hidden" name="context_id" value="{$view_context}">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="calendar">
<input type="hidden" name="action" value="">
<input type="hidden" name="explore_from" value="0">
<table cellpadding="1" cellspacing="0" border="0" width="100%" class="worklistBody">

	{* Column Headers *}
	<thead>
	<tr>
		<th style="text-align:center;width:75px;">
			<a href="javascript:;">{'common.watchers'|devblocks_translate|capitalize}</a>
		</th>
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
	</thead>

	{* Column Data *}
	{$object_watchers = DAO_ContextLink::getContextLinks($view_context, array_keys($data), CerberusContexts::CONTEXT_WORKER)}
	{foreach from=$data item=result key=idx name=results}

	{if $smarty.foreach.results.iteration % 2}
		{assign var=tableRowClass value="even"}
	{else}
		{assign var=tableRowClass value="odd"}
	{/if}
	<tbody style="cursor:pointer;">
		<tr class="{$tableRowClass}">
			<td align="center" rowspan="2" nowrap="nowrap" style="padding:5px;">
				<input type="checkbox" name="row_id[]" value="{$result.c_id}" style="display:none;">
				{include file="devblocks:cerberusweb.core::internal/watchers/context_follow_button.tpl" context=$view_context context_id=$result.c_id}
			</td>
		</tr>
		<tr class="{$tableRowClass}">
		{foreach from=$view->view_columns item=column name=columns}
			{if substr($column,0,3)=="cf_"}
				{include file="devblocks:cerberusweb.core::internal/custom_fields/view/cell_renderer.tpl"}
			{elseif $column == "c_name"}
			<td>
				<a href="{devblocks_url}c=profiles&type=calendar&id={$result.c_id}-{$result.c_name|devblocks_permalink}{/devblocks_url}" class="subject">{$result.c_name}</a>
				<button type="button" class="peek" style="visibility:hidden;padding:1px;margin:0px 5px;" onclick="genericAjaxPopup('peek','c=internal&a=showPeekPopup&context={$view_context}&context_id={$result.c_id}&view_id={$view->id}',null,false,'550');"><span class="cerb-sprite2 sprite-document-search-result" style="margin-left:2px" title="{$translate->_('views.peek')}"></span></button>
			</td>
			{elseif $column == "c_updated_at"}
				<td title="{$result.$column|devblocks_date}">
					{if !empty($result.$column)}
						{$result.$column|devblocks_prettytime}&nbsp;
					{/if}
				</td>
			{elseif $column=="*_owner"}
				{$owner_context = $result.c_owner_context}
				{$owner_context_id = $result.c_owner_context_id}
				{$owner_context_ext = Extension_DevblocksContext::get($owner_context)}
				<td>
					{if !is_null($owner_context_ext)}
						{$meta = $owner_context_ext->getMeta($owner_context_id)}
						{if !empty($meta)}
						{$meta.name} 
						{/if}
						({$owner_context_ext->manifest->name})
					{/if}
				</td>
			{else}
				<td>{$result.$column}</td>
			{/if}
		{/foreach}
		</tr>
	</tbody>
	{/foreach}
</table>

<div style="padding-top:5px;">
	<div style="float:right;">
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
	</div>
	
	{if $total}
	<div style="float:left;" id="{$view->id}_actions">
		<button type="button" class="action-always-show action-explore" onclick="this.form.explore_from.value=$(this).closest('form').find('tbody input:checkbox:checked:first').val();this.form.action.value='viewExplore';this.form.submit();"><span class="cerb-sprite sprite-media_play_green"></span> {'common.explore'|devblocks_translate|lower}</button>
		{*
		{if $active_worker->hasPriv('calls.actions.update_all')}<button type="button" class="action-always-show action-bulkupdate" onclick="genericAjaxPopup('peek','c=profiles&a=handleSectionAction¤ion=calendar&action=showBulkPanel&view_id={$view->id}&ids=' + Devblocks.getFormEnabledCheckboxValues('viewForm{$view->id}','row_id[]'),null,false,'500');"><span class="cerb-sprite2 sprite-folder-gear"></span> {'common.bulk_update'|devblocks_translate|lower}</button>{/if}
		*}
	</div>
	{/if}
</div>

<div style="clear:both;"></div>

</form>

{include file="devblocks:cerberusweb.core::internal/views/view_common_jquery_ui.tpl"}

<script type="text/javascript">
$frm = $('#viewForm{$view->id}');

{if $pref_keyboard_shortcuts}
$frm.bind('keyboard_shortcut',function(event) {
	$view_actions = $('#{$view->id}_actions');
	
	hotkey_activated = true;

	switch(event.keypress_event.which) {
		{*
		case 98: // (b) bulk update
			$btn = $view_actions.find('button.action-bulkupdate');
		
			if(event.indirect) {
				$btn.select().focus();
				
			} else {
				$btn.click();
			}
			break;
		*}
		
		case 101: // (e) explore
			$btn = $view_actions.find('button.action-explore');
		
			if(event.indirect) {
				$btn.select().focus();
				
			} else {
				$btn.click();
			}
			break;
			
		default:
			hotkey_activated = false;
			break;
	}

	if(hotkey_activated)
		event.preventDefault();
});
{/if}
</script>
