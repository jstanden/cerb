{$view_context = CerberusContexts::CONTEXT_ACTIVITY_LOG}
{$view_fields = $view->getColumnsAvailable()}
{$results = $view->getData()}
{$total = $results[1]}
{$data = $results[0]}

{include file="devblocks:cerberusweb.core::internal/views/view_marquee.tpl" view=$view}

<table cellpadding="0" cellspacing="0" border="0" class="worklist" width="100%" {if array_key_exists('header_color', $view->options) && $view->options.header_color}style="background-color:{$view->options.header_color};"{/if}>
	<tr>
		<td nowrap="nowrap"><span class="title">{$view->name}</span></td>
		<td nowrap="nowrap" align="right" class="title-toolbar">
			<a data-cerb-worklist-icon-search title="{'common.search'|devblocks_translate|capitalize}" class="minimal"><span class="glyphicons glyphicons-search"></span></a>
			<a data-cerb-worklist-icon-customize title="{'common.customize'|devblocks_translate|capitalize}" class="minimal"><span class="glyphicons glyphicons-cogwheel"></span></a>
			<a data-cerb-worklist-icon-subtotals title="{'common.subtotals'|devblocks_translate|capitalize}" class="minimal"><span class="glyphicons glyphicons-signal"></span></a>
			{if $active_worker->hasPriv("contexts.{$view_context}.export")}<a data-cerb-worklist-icon-export title="{'common.export'|devblocks_translate|capitalize}" class="minimal"><span class="glyphicons glyphicons-file-export"></span></a>{/if}
			<a data-cerb-worklist-icon-refresh title="{'common.refresh'|devblocks_translate|capitalize}" class="minimal"><span class="glyphicons glyphicons-refresh"></span></a>
			<input type="checkbox" class="select-all">
		</td>
	</tr>
</table>

<div id="{$view->id}_tips" class="block" style="display:none;margin:10px;padding:5px;">Loading...</div>
<form id="customize{$view->id}" name="customize{$view->id}" action="#"></form>
<form id="viewForm{$view->id}" name="viewForm{$view->id}" action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="view_id" value="{$view->id}">
<input type="hidden" name="context_id" value="{$view_context}">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="records">
<input type="hidden" name="action" value="">
<input type="hidden" name="explore_from" value="">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<table cellpadding="1" cellspacing="0" border="0" width="100%" class="worklistBody">

	{* Column Headers *}
	<thead>
	<tr>
		{foreach from=$view->view_columns item=header name=headers}
			{* start table header, insert column title and link *}
			<th class="{if array_key_exists('disable_sorting', $view->options) && $view->options.disable_sorting}no-sort{/if}">
			{if (!array_key_exists('disable_sorting', $view->options) || !$view->options.disable_sorting) && !empty($view_fields.$header->db_column)}
				<a data-cerb-worklist-sort="{$header}">{$view_fields.$header->db_label|capitalize}</a>
			{else}
				<a style="text-decoration:none;">{$view_fields.$header->db_label|capitalize}</a>
			{/if}
			
			{* add arrow if sorting by this column, finish table header tag *}
			{if $header==$view->renderSortBy}
				<span class="glyphicons {if $view->renderSortAsc}glyphicons-sort-by-attributes{else}glyphicons-sort-by-attributes-alt{/if}" style="font-size:14px;{if array_key_exists('disable_sorting', $view->options) && $view->options.disable_sorting}color:rgb(80,80,80);{else}color:rgb(39,123,213);{/if}"></span>
			{/if}
			</th>
		{/foreach}
	</tr>
	</thead>

	{$has_target_req = $view->findParam(SearchFields_ContextActivityLog::VIRTUAL_TARGET, $view->getParamsRequired())}

	{* Column Data *}
	{foreach from=$data item=result key=idx name=results}

	{if $smarty.foreach.results.iteration % 2}
		{$tableRowClass = "even"}
	{else}
		{$tableRowClass = "odd"}
	{/if}
	<tbody style="cursor:pointer;">
		<tr class="{$tableRowClass}">
			<td data-column="label" colspan="{$smarty.foreach.headers.total}" style="font-size:12px;padding:2px 0;">
				<input type="checkbox" name="row_id[]" value="{$result.c_id}" style="display:none;">
				<span class="subject">
				{* If we're looking at the target context, hide the text in the entry *}
				{$entry = Model_ContextActivityLogEntry::new(
					$result.c_activity_point,
					json_decode($result.c_entry_json,true),
					$result.c_actor_context,
					$result.c_actor_context_id,
					$result.c_target_context,
					$result.c_target_context_id
				)}
				{if $has_target_req}
					{$scrub = ['target']}
					{CerberusContexts::formatActivityLogEntry($entry,'html-cards',$scrub,true) nofilter}
				{else}
					{CerberusContexts::formatActivityLogEntry($entry,'html-cards',[],true) nofilter}
				{/if}
				</span>
			</td>
		</tr>
		
		<tr class="{$tableRowClass}">
		{foreach from=$view->view_columns item=column name=columns}
			{if DevblocksPlatform::strStartsWith($column, "cf_")}
				{include file="devblocks:cerberusweb.core::internal/custom_fields/view/cell_renderer.tpl"}
			{elseif $column=="c_created"}
				<td data-column="{$column}" title="{$result.$column|devblocks_date}">
					{if !empty($result.$column)}
						{$result.$column|devblocks_prettytime}&nbsp;
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
	{include file="devblocks:cerberusweb.core::internal/views/view_paging.tpl" view=$view}

	<div style="float:left;" id="{$view->id}_actions">
		{$view_toolbar = $view->getToolbar()}
		{include file="devblocks:cerberusweb.core::internal/views/view_toolbar.tpl" view_toolbar=$view_toolbar}
		{if $active_worker->is_superuser}
		<button type="button" class="action-delete"><span class="glyphicons glyphicons-circle-remove"></span> {'common.delete'|devblocks_translate|capitalize}</button>
		{/if}
	</div>
</div>
{/if}

<div style="clear:both;"></div>

</form>

{include file="devblocks:cerberusweb.core::internal/views/view_common_jquery_ui.tpl"}

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var $frm = $('#viewForm{$view->id}');
	var $actions = $('#{$view->id}_actions');

	{if $active_worker->is_superuser}
	$actions.find('.action-delete').on('click', function() {
		var formData = new FormData($frm[0]);
		formData.set('c','internal');
		formData.set('a','invoke');
		formData.set('module','records');
		formData.set('action','viewLogDelete');

		genericAjaxPost(
			formData,
			'view{$view->id}',
			null
		);
	});
	{/if}
});
</script>