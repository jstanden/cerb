{$view_context = 'cerb.contexts.agent.model.router'}
{$view_fields = $view->getColumnsAvailable()}
{$results = $view->getData()}
{$total = $results[1]}
{$data = $results[0]}

{include file="devblocks:cerberusweb.core::internal/views/view_marquee.tpl" view=$view}

<table cellpadding="0" cellspacing="0" border="0" class="worklist" width="100%" {if array_key_exists('header_color', $view->options) && $view->options.header_color}style="background-color:{$view->options.header_color};"{/if}>
	<tr>
		<td nowrap="nowrap"><span class="title">{$view->name}</span></td>
		<td nowrap="nowrap" align="right" class="title-toolbar">
			{{if $active_worker->is_superuser}}<a title="{'common.add'|devblocks_translate|capitalize}" class="minimal peek cerb-peek-trigger" data-context="{$view_context}" data-context-id="0"><span class="cerb-icons cerb-icon-circle-plus"></span></a>{/if}
			<a data-cerb-worklist-icon-search title="{'common.search'|devblocks_translate|capitalize}" class="minimal"><span class="cerb-icons cerb-icon-search"></span></a>
			<a data-cerb-worklist-icon-customize title="{'common.customize'|devblocks_translate|capitalize}" class="minimal"><span class="cerb-icons cerb-icon-gear"></span></a>
			<a data-cerb-worklist-icon-subtotals title="{'common.subtotals'|devblocks_translate|capitalize}" class="minimal"><span class="cerb-icons cerb-icon-signal"></span></a>
			{if $active_worker->hasPriv("contexts.{$view_context}.export")}<a data-cerb-worklist-icon-export title="{'common.export'|devblocks_translate|capitalize}" class="minimal"><span class="cerb-icons cerb-icon-file-export"></span></a>{/if}
			<a data-cerb-worklist-icon-refresh title="{'common.refresh'|devblocks_translate|capitalize}" class="minimal"><span class="cerb-icons cerb-icon-refresh"></span></a>
			<input type="checkbox" class="select-all">
		</td>
	</tr>
</table>

<div id="{$view->id}_tips" class="block" style="display:none;margin:10px;padding:5px;">Loading...</div>
<form id="customize{$view->id}" name="customize{$view->id}" action="#"></form>
<form id="viewForm{$view->id}" name="viewForm{$view->id}" action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="view_id" value="{$view->id}">
<input type="hidden" name="context_id" value="{$view_context}">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="agent_model_router">
<input type="hidden" name="action" value="">
<input type="hidden" name="explore_from" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<table cellpadding="1" cellspacing="0" border="0" width="100%" class="worklistBody">
	<thead>
	<tr>
		{if !array_key_exists('disable_watchers', $view->options) || !$view->options.disable_watchers}
		<th class="no-sort" style="text-align:center;width:40px;padding-left:0;padding-right:0;" title="{'common.watchers'|devblocks_translate|capitalize}">
			<span class="cerb-icons cerb-icon-eye-open"></span>
		</th>
		{/if}
		{foreach from=$view->view_columns item=header name=headers}
			<th class="{if array_key_exists('disable_sorting', $view->options) && $view->options.disable_sorting}no-sort{/if}">
			{if (!array_key_exists('disable_sorting', $view->options) || !$view->options.disable_sorting) && !empty($view_fields.$header->db_column)}
				{include file="devblocks:cerberusweb.core::internal/views/view_header_sort.tpl" view=$view header=$header}
				<a data-cerb-worklist-sort="{$header}">{$view_fields.$header->db_label|capitalize}</a>
			{else}
				<a style="text-decoration:none;">{$view_fields.$header->db_label|capitalize}</a>
			{/if}
			</th>
		{/foreach}
	</tr>
	</thead>

	{$object_watchers = DAO_ContextLink::getContextLinks($view_context, array_keys($data), CerberusContexts::CONTEXT_WORKER)}
	{foreach from=$data item=result key=idx name=results}

	{if $smarty.foreach.results.iteration % 2}
		{$tableRowClass = "even"}
	{else}
		{$tableRowClass = "odd"}
	{/if}
	{* `is_disabled` is in the DAO's fixed SELECT, not just the visible columns, so the row treatment holds even
	   when an operator drops the Disabled column from the worklist. *}
	<tbody style="cursor:pointer;" {if $result.a_is_disabled}class="cerb-worklist-row--disabled"{/if}>
		<tr class="{$tableRowClass}">
			<td data-column="*_watchers" align="center" nowrap="nowrap" style="padding:5px;">
				{include file="devblocks:cerberusweb.core::internal/watchers/context_follow_button.tpl" context=$view_context context_id=$result.a_id}
			</td>
		{foreach from=$view->view_columns item=column name=columns}
			{if DevblocksPlatform::strStartsWith($column, "cf_")}
				{include file="devblocks:cerberusweb.core::internal/custom_fields/view/cell_renderer.tpl"}
			{elseif $column == "a_name"}
			<td>
				<input type="checkbox" name="row_id[]" value="{$result.a_id}" style="display:none;">
				{* A router reads as a ROUTER, not as its initials -- the glyph is how you tell these apart from
				   the model records they contain. Color is derived per record so two routers stay distinct. *}
				<span data-cerb-agent-model-router-avatar
					style="vertical-align:middle;margin-right:0.4em;"
					data-avatar="{$result.a_name}"
					data-avatar-icon="{$view->getRowIcon($result)}"
					data-avatar-size="20"
					data-avatar-color="{$view->getRowIconColor($result)}"
				></span>
				<a href="{devblocks_url}c=profiles&type=agent_model_router&id={$result.a_id}-{$result.a_name|devblocks_permalink}{/devblocks_url}" class="subject">{$result.a_name}</a>
				{* The default router is the one that runs when nothing names one -- worth seeing at a glance,
				   and it's also in the fixed SELECT so it shows even without the Default column. *}
				{if $result.a_is_default}
					<span class="cerb-icons cerb-icon-star" style="vertical-align:middle;margin-left:0.3em;" title="{'common.default'|devblocks_translate|capitalize}"></span>
				{/if}
				{if $result.a_is_disabled}
					<span class="cerb-icons cerb-icon-ban" style="vertical-align:middle;margin-left:0.3em;" title="{'common.disabled'|devblocks_translate|capitalize}"></span>
				{/if}
				<button type="button" class="peek cerb-peek-trigger" data-context="{$view_context}" data-context-id="{$result.a_id}"><span class="cerb-icons cerb-icon-new-window"></span></button>
			</td>
			{elseif $column == "a_is_default"}
				{* The raw checkbox value renders as a bare 0/1. Show the STATE instead: a star on the default,
				   nothing on the rest -- there's only ever one, so it shouldn't be a column of "No". *}
				<td data-column="{$column}" style="text-align:center;">
					{if $result.$column}
						<span class="cerb-icons cerb-icon-star" title="{'common.default'|devblocks_translate|capitalize}"></span>
					{/if}
				</td>
			{elseif $column == "a_is_disabled"}
				<td data-column="{$column}" style="text-align:center;">
					{if $result.$column}
						<span class="cerb-icons cerb-icon-ban" title="{'common.disabled'|devblocks_translate|capitalize}"></span>
					{/if}
				</td>
			{elseif in_array($column, ["a_created_at", "a_updated_at"])}
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
	{include file="devblocks:cerberusweb.core::internal/views/view_paging.tpl" view=$view}

	<div style="float:left;" id="{$view->id}_actions">
		{$view_toolbar = $view->getToolbar()}
		{include file="devblocks:cerberusweb.core::internal/views/view_toolbar.tpl" view_toolbar=$view_toolbar}
		{if !$view_toolbar['explore']}<button type="button" class="action-always-show action-explore"><span class="cerb-icons cerb-icon-compass"></span> {'common.explore'|devblocks_translate|lower}</button>{/if}
	</div>
</div>
{/if}

<div style="clear:both;"></div>
</form>

{include file="devblocks:cerberusweb.core::internal/views/view_common_jquery_ui.tpl"}

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#viewForm{$view->id}');

	// Paint the per-row router marks. Scoped to this form and re-run on every worklist render (paging, sort,
	// filter all re-emit this template), so there's no global scan to keep in sync.
	if(window.CerbUI && CerbUI.Avatar)
		CerbUI.Avatar.enhance($frm[0], '[data-cerb-agent-model-router-avatar]');
});
</script>
