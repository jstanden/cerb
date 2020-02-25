{$view_context = CerberusContexts::CONTEXT_WORKER}
{$view_fields = $view->getColumnsAvailable()}
{$results = $view->getData()}
{$total = $results[1]}
{$data = $results[0]}
{$data_count = count($data)}
{$avatar_limit = 50}

{include file="devblocks:cerberusweb.core::internal/views/view_marquee.tpl" view=$view}

<table cellpadding="0" cellspacing="0" border="0" class="worklist" width="100%" {if $view->options.header_color}style="background-color:{$view->options.header_color};"{/if}>
	<tr>
		<td nowrap="nowrap"><span class="title">{$view->name}</span></td>
		<td nowrap="nowrap" align="right" class="title-toolbar">
			{if $active_worker->is_superuser}<a href="javascript:;" title="{'common.add'|devblocks_translate|capitalize}" class="minimal cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_WORKER}" data-context-id="0"><span class="glyphicons glyphicons-circle-plus"></span></a>{/if}
			<a href="javascript:;" title="{'common.search'|devblocks_translate|capitalize}" class="minimal" onclick="genericAjaxPopup('search','c=internal&a=invoke&module=worklists&action=showQuickSearchPopup&view_id={$view->id}',null,false,'400');"><span class="glyphicons glyphicons-search"></span></a>
			<a href="javascript:;" title="{'common.customize'|devblocks_translate|capitalize}" class="minimal" onclick="genericAjaxGet('customize{$view->id}','c=internal&a=invoke&module=worklists&action=customize&id={$view->id}');toggleDiv('customize{$view->id}','block');"><span class="glyphicons glyphicons-cogwheel"></span></a>
			<a href="javascript:;" title="{'common.subtotals'|devblocks_translate|capitalize}" class="subtotals minimal"><span class="glyphicons glyphicons-signal"></span></a>
			{if $active_worker->hasPriv("contexts.{$view_context}.export")}<a href="javascript:;" title="{'common.export'|devblocks_translate|capitalize}" class="minimal" onclick="genericAjaxGet('{$view->id}_tips','c=internal&a=invoke&module=worklists&action=renderExport&id={$view->id}');toggleDiv('{$view->id}_tips','block');"><span class="glyphicons glyphicons-file-export"></span></a>{/if}
			<a href="javascript:;" title="{'common.copy'|devblocks_translate|capitalize}" onclick="genericAjaxGet('{$view->id}_tips','c=internal&a=invoke&module=worklists&action=renderCopy&view_id={$view->id}');toggleDiv('{$view->id}_tips','block');"><span class="glyphicons glyphicons-duplicate"></span></a>
			<a href="javascript:;" title="{'common.refresh'|devblocks_translate|capitalize}" class="minimal" onclick="genericAjaxGet('view{$view->id}','c=internal&a=invoke&module=worklists&action=refresh&id={$view->id}');"><span class="glyphicons glyphicons-refresh"></span></a>
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
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="worker">
<input type="hidden" name="action" value="">
<input type="hidden" name="explore_from" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<table cellpadding="2" cellspacing="0" border="0" width="100%" class="worklistBody">

	{* Column Headers *}
	<thead>
	<tr>
		{if $data_count <= $avatar_limit}
		<th class="no-sort" style="text-align:center;width:40px;padding-left:0;padding-right:0;" title="{'common.photo'|devblocks_translate|capitalize}">
			<span class="glyphicons glyphicons-camera" style="color:rgb(80,80,80);"></span>
		</th>
		{/if}
	
		{foreach from=$view->view_columns item=header name=headers}
			{* start table header, insert column title and link *}
			<th class="{if $view->options.disable_sorting}no-sort{/if}">
			{if !$view->options.disable_sorting && !empty($view_fields.$header->db_column)}
				<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=invoke&module=worklists&action=sort&id={$view->id}&sortBy={$header}');">{$view_fields.$header->db_label|capitalize}</a>
			{else}
				<a href="javascript:;" style="text-decoration:none;">{$view_fields.$header->db_label|capitalize}</a>
			{/if}
			
			{* add arrow if sorting by this column, finish table header tag *}
			{if $header==$view->renderSortBy}
				<span class="glyphicons {if $view->renderSortAsc}glyphicons-sort-by-attributes{else}glyphicons-sort-by-attributes-alt{/if}" style="font-size:14px;{if $view->options.disable_sorting}color:rgb(80,80,80);{else}color:rgb(39,123,213);{/if}"></span>
			{/if}
			</th>
		{/foreach}
	</tr>
	</thead>

	{* Bulk lazy load addresses for this page *}
	{$object_addys = []}
	{if in_array(SearchFields_Worker::EMAIL_ADDRESS, $view->view_columns)}
		{$addy_ids = DevblocksPlatform::extractArrayValues($results, 'w_email_id')}
		{$object_addys = DAO_Address::getIds($addy_ids)}
	{/if}
	
	{* Column Data *}
	{foreach from=$data item=result key=idx name=results}

	{if $smarty.foreach.results.iteration % 2}
		{$tableRowClass = "even"}
	{else}
		{$tableRowClass = "odd"}
	{/if}
	<tbody style="cursor:pointer;">
		<tr class="{$tableRowClass}">
			{if $data_count <= $avatar_limit}
			<td data-column="*_image" align="center" rowspan="2" nowrap="nowrap" style="padding:5px;">
				<div style="position:relative;">
					<img src="{devblocks_url}c=avatars&context=worker&context_id={$result.w_id}{/devblocks_url}?v={$result.w_updated}" style="height:32px;width:32px;border-radius:16px;vertical-align:middle;">
					{if $result.w_is_disabled}<span class="plugin_icon_overlay_disabled" style="background-size:32px 32px;"></span>{/if}
				</div>
			</td>
			{/if}
			<td data-column="label" colspan="{$smarty.foreach.headers.total}">
				{$worker_name = "{$result.w_first_name}{if !empty($result.w_last_name)} {$result.w_last_name}{/if}"}
				<input type="checkbox" name="row_id[]" value="{$result.w_id}" style="display:none;">
				<a href="{devblocks_url}c=profiles&a=worker&id={$result.w_id}-{$worker_name|devblocks_permalink}{/devblocks_url}" class="subject">{$worker_name}</a>
				<button type="button" class="peek cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_WORKER}" data-context-id="{$result.w_id}"><span class="glyphicons glyphicons-new-window-alt"></span></button>
			</td>
		</tr>
		<tr class="{$tableRowClass}">
		{foreach from=$view->view_columns item=column name=columns}
			{if substr($column,0,3)=="cf_"}
				{include file="devblocks:cerberusweb.core::internal/custom_fields/view/cell_renderer.tpl"}
			{elseif in_array($column, ['w_is_disabled','w_is_mfa_required','w_is_password_disabled','w_is_superuser'])}
				<td data-column="{$column}">{if $result.$column}{'common.yes'|devblocks_translate|capitalize}{else}{'common.no'|devblocks_translate|capitalize}{/if}</td>
			{elseif $column=="a_address_email"}
				{$addy = $object_addys.{$result.w_email_id}}
				<td data-column="{$column}"><a href="javascript:;" class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_ADDRESS}" data-context-id="{$addy->id}" title="{$addy->email}">{$addy->email|truncate:64:'...':true:true}</a></td>
			{elseif $column == SearchFields_Worker::GENDER}
			<td data-column="{$column}">
				{if $result.$column == 'M'}
				<span class="glyphicons glyphicons-male"></span>
				{elseif $result.$column == 'F'}
				<span class="glyphicons glyphicons-female"></span>
				{else}
				{/if}
			</td>
			{elseif $column=="w_calendar_id"}
				<td data-column="{$column}">
				{if $result.$column}
					{$calendar = DAO_Calendar::get($result.$column)}
					{if $calendar}
						<a href="javascript:;" class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_CALENDAR}" data-context-id="{$calendar->id}" title="{$calendar->name}">{$calendar->name|truncate:64:'...':true:true}</a>
					{/if}
				{/if}
				</td>
			{elseif in_array($column, ['w_updated'])}
				{if !empty($result.$column)}
				<td data-column="{$column}" title="{$result.$column|devblocks_date}">{$result.$column|devblocks_prettytime}</td>
				{else}
				<td data-column="{$column}">{'common.never'|devblocks_translate|lower}</td>
				{/if}
			{else}
			<td data-column="{$column}">{$result.$column}</td>
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
			<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=invoke&module=worklists&action=page&id={$view->id}&page=0');">&lt;&lt;</a>
			<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=invoke&module=worklists&action=page&id={$view->id}&page={$prevPage}');">&lt;{'common.previous_short'|devblocks_translate|capitalize}</a>
		{/if}
		({'views.showing_from_to'|devblocks_translate:$fromRow:$toRow:$total})
		{if $toRow < $total}
			<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=invoke&module=worklists&action=page&id={$view->id}&page={$nextPage}');">{'common.next'|devblocks_translate|capitalize}&gt;</a>
			<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=invoke&module=worklists&action=page&id={$view->id}&page={$lastPage}');">&gt;&gt;</a>
		{/if}
	</div>
	
	{if $total}
	<div style="float:left;" id="{$view->id}_actions">
		<button type="button" class="action-always-show action-explore"><span class="glyphicons glyphicons-play-button"></span> {'common.explore'|devblocks_translate|lower}</button>
		{if $active_worker && $active_worker->is_superuser}
			<button type="button" class="action-always-show action-bulkupdate" onclick="genericAjaxPopup('peek','c=profiles&a=invoke&module=worker&action=showBulkPopup&view_id={$view->id}&ids=' + Devblocks.getFormEnabledCheckboxValues('viewForm{$view->id}','row_id[]'),null,false,'50%');"><span class="glyphicons glyphicons-folder-closed"></span></a> bulk update</button>
		{/if}
	</div>
	{/if}
</div>

<div style="clear:both;"></div>

</form>

{include file="devblocks:cerberusweb.core::internal/views/view_common_jquery_ui.tpl"}

<script type="text/javascript">
$(function() {
	var $frm = $('#viewForm{$view->id}');
	var $frm_actions = $('#{$view->id}_actions');
	
	$frm_actions.find('button.action-explore').click(function() {
		var checkedId = $frm.find('tbody input:checkbox:checked:first').val();
		$frm.find('input:hidden[name=explore_from]').val(checkedId);
		
		$frm.find('input:hidden[name=action]').val('viewExplore');
		$frm.submit();
	});
	
	{if $pref_keyboard_shortcuts}
	$frm.bind('keyboard_shortcut',function(event) {
		var $view_actions = $('#{$view->id}_actions');
		var hotkey_activated = true;
	
		switch(event.keypress_event.which) {
			case 98: // (b) bulk update
				var $btn = $view_actions.find('button.action-bulkupdate');
			
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
});
</script>