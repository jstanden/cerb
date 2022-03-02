{$view_context = CerberusContexts::CONTEXT_AUTOMATION}
{$view_fields = $view->getColumnsAvailable()}
{$results = $view->getData()}
{$total = $results[1]}
{$data = $results[0]}
{$are_rows_two_lines = !in_array('a_name', $view->view_columns)}

{include file="devblocks:cerberusweb.core::internal/views/view_marquee.tpl" view=$view}

<table cellpadding="0" cellspacing="0" border="0" class="worklist" width="100%" {if array_key_exists('header_color', $view->options) && $view->options.header_color}style="background-color:{$view->options.header_color};"{/if}>
	<tr>
		<td nowrap="nowrap"><span class="title">{$view->name}</span></td>
		<td nowrap="nowrap" align="right" class="title-toolbar">
			{if $active_worker->is_superuser && $active_worker->hasPriv("contexts.{$view_context}.create")}<a href="javascript:;" title="{'common.add'|devblocks_translate|capitalize}" class="minimal peek cerb-peek-trigger" data-context="{$view_context}" data-context-id="0" data-width="90%"><span class="glyphicons glyphicons-circle-plus"></span></a>{/if}
			<a href="javascript:;" title="{'common.search'|devblocks_translate|capitalize}" class="minimal" onclick="genericAjaxPopup('search','c=internal&a=invoke&module=worklists&action=showQuickSearchPopup&view_id={$view->id}',null,false,'400');"><span class="glyphicons glyphicons-search"></span></a>
			<a href="javascript:;" title="{'common.customize'|devblocks_translate|capitalize}" class="minimal" onclick="genericAjaxGet('customize{$view->id}','c=internal&a=invoke&module=worklists&action=customize&id={$view->id}');toggleDiv('customize{$view->id}','block');"><span class="glyphicons glyphicons-cogwheel"></span></a>
			<a href="javascript:;" title="{'common.subtotals'|devblocks_translate|capitalize}" class="subtotals minimal"><span class="glyphicons glyphicons-signal"></span></a>
			{if $active_worker->hasPriv("contexts.{$view_context}.export")}<a href="javascript:;" title="{$translate->_('common.export')|capitalize}" class="minimal" onclick="genericAjaxGet('{$view->id}_tips','c=internal&a=invoke&module=worklists&action=renderExport&id={$view->id}');toggleDiv('{$view->id}_tips','block');"><span class="glyphicons glyphicons-file-export"></span></a>{/if}
			<a href="javascript:;" title="{$translate->_('common.copy')|capitalize}" onclick="genericAjaxGet('{$view->id}_tips','c=internal&a=invoke&module=worklists&action=renderCopy&view_id={$view->id}');toggleDiv('{$view->id}_tips','block');"><span class="glyphicons glyphicons-duplicate"></span></a>
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
<input type="hidden" name="module" value="automation">
<input type="hidden" name="action" value="">
<input type="hidden" name="explore_from" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<table cellpadding="1" cellspacing="0" border="0" width="100%" class="worklistBody">

	{* Column Headers *}
	<thead>
	<tr>
		{if !array_key_exists('disable_watchers', $view->options) || !$view->options.disable_watchers}
		<th class="no-sort" style="text-align:center;width:40px;padding-left:0;padding-right:0;" title="{'common.watchers'|devblocks_translate|capitalize}">
			<span class="glyphicons glyphicons-eye-open"></span>
		</th>
		{/if}

		{foreach from=$view->view_columns item=header name=headers}
			{* start table header, insert column title and link *}
			<th class="{if array_key_exists('disable_sorting', $view->options) && $view->options.disable_sorting}no-sort{/if}">
			{if (!array_key_exists('disable_sorting', $view->options) || !$view->options.disable_sorting) && !empty($view_fields.$header->db_column)}
				<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=invoke&module=worklists&action=sort&id={$view->id}&sortBy={$header}');">{$view_fields.$header->db_label|capitalize}</a>
			{else}
				<a href="javascript:;" style="text-decoration:none;">{$view_fields.$header->db_label|capitalize}</a>
			{/if}
			
			{* add arrow if sorting by this column, finish table header tag *}
			{if $header==$view->renderSortBy}
				<span class="glyphicons {if $view->renderSortAsc}glyphicons-sort-by-attributes{else}glyphicons-sort-by-attributes-alt{/if}" style="font-size:14px;{if array_key_exists('disable_sorting', $view->options) && $view->options.disable_sorting}color:rgb(80,80,80);{else}color:rgb(39,123,213);{/if}"></span>
			{/if}
			</th>
		{/foreach}
	</tr>
	</thead>

	{* Column Data *}
	{$object_watchers = DAO_ContextLink::getContextLinks($view_context, array_keys($data), CerberusContexts::CONTEXT_WORKER)}
	{foreach from=$data item=result key=idx name=results}

	{if $smarty.foreach.results.iteration % 2}
		{$tableRowClass = "even"}
	{else}
		{$tableRowClass = "odd"}
	{/if}

	{* This is used in two places depending on if the row is one or two lines *}
	{capture name="a_name"}
		<input type="checkbox" name="row_id[]" value="{$result.a_id}" style="display:none;">
		<a href="{devblocks_url}c=profiles&type=automation&id={$result.a_id}-{$result.a_name|devblocks_permalink}{/devblocks_url}" class="subject">{$result.a_name}</a>
		<button type="button" class="peek cerb-peek-trigger" data-context="{$view_context}" data-context-id="{$result.a_id}"><span class="glyphicons glyphicons-new-window-alt"></span></button>
	{/capture}
		
	<tbody style="cursor:pointer;">
		<tr class="{$tableRowClass}">
			<td data-column="*_watchers" align="center" {if $are_rows_two_lines}rowspan="2"{/if} nowrap="nowrap" style="padding-right:0;">
				{include file="devblocks:cerberusweb.core::internal/watchers/context_follow_button.tpl" context=$view_context context_id=$result.a_id}
			</td>

			{if !in_array('a_name',$view->view_columns)}
				<td data-column="label" colspan="{$smarty.foreach.headers.total}">
					{$smarty.capture.a_name nofilter}
				</td>
			{/if}

		{if $are_rows_two_lines}
		</tr>
		<tr class="{$tableRowClass}">
		{/if}
		
		{foreach from=$view->view_columns item=column name=columns}
			{if DevblocksPlatform::strStartsWith($column, "cf_")}
				{include file="devblocks:cerberusweb.core::internal/custom_fields/view/cell_renderer.tpl"}
			{elseif $column == "a_name"}
			<td>
				{$smarty.capture.a_name nofilter}
			</td>
			{elseif $column == "a_extension_id"}
			<td data-column="{$column}">
				{if isset($trigger_extensions.{$result.a_extension_id})}
					{$trigger_ext = $events.{$result.a_extension_id}}
					{$trigger_ext->name}
				{else}
					{$result.$column}
				{/if}
			</td>
			{elseif $column=="*_on"}
			<td data-column="{$column}">
				{$ctx = Extension_DevblocksContext::get($result.a_context)}
				{if is_object($ctx)}
				{$meta = $ctx->getMeta($result.a_context_id)}
				<span title="{$ctx->manifest->name}">
					{if $ctx->hasOption('cards')}
						<a href="javascript:;" class="cerb-peek-trigger" data-context="{$ctx->id}" data-context-id="{$meta.id}">{$meta.name|truncate:64}</a>
					{else}
						{$meta.name|truncate:64}
					{/if}
				</span>
				{/if}
			</td>
			{elseif $column == 'a_context'}
				<td>
					{$context_ext = $contexts.{$result.$column}}
					{if $context_ext}
						{$context_ext->name}
					{/if}
				</td>
			{elseif in_array($column, ["a_updated_at"])}
				<td>
					{if !empty($result.$column)}
						<abbr title="{$result.$column|devblocks_date}">{$result.$column|devblocks_prettytime}</abbr>
					{/if}
				</td>
			{else}
				<td data-column="{$column}">{$result.$column}</td>
			{/if}
		{/foreach}
		</tr>
	</tbody>
	{/foreach}
</table>

{if $total >= 0}
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
			<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=invoke&module=worklists&action=page&id={$view->id}&page={$prevPage}');">&lt;{$translate->_('common.previous_short')|capitalize}</a>
		{/if}
		({'views.showing_from_to'|devblocks_translate:$fromRow:$toRow:$total})
		{if $toRow < $total}
			<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=invoke&module=worklists&action=page&id={$view->id}&page={$nextPage}');">{$translate->_('common.next')|capitalize}&gt;</a>
			<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=invoke&module=worklists&action=page&id={$view->id}&page={$lastPage}');">&gt;&gt;</a>
		{/if}
	</div>
	
	<div style="float:left;" id="{$view->id}_actions">
		<button type="button" class="action-always-show action-explore" onclick="this.form.explore_from.value=$(this).closest('form').find('tbody input:checkbox:checked:first').val();this.form.action.value='viewExplore';this.form.submit();"><span class="glyphicons glyphicons-play-button"></span> {'common.explore'|devblocks_translate|lower}</button>
	</div>
</div>
{/if}

<div style="clear:both;"></div>

</form>

{include file="devblocks:cerberusweb.core::internal/views/view_common_jquery_ui.tpl"}

<script type="text/javascript">
$(function() {
	var $frm = $('#viewForm{$view->id}');
	
	{if $pref_keyboard_shortcuts}
	$frm.bind('keyboard_shortcut',function(event) {
		var $view_actions = $('#{$view->id}_actions');
		var hotkey_activated = true;
	
		switch(event.keypress_event.which) {
			case 101: // (e) explore
				var $btn = $view_actions.find('button.action-explore');
			
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
