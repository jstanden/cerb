{$view_context = 'cerb.contexts.agent.model'}
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
<input type="hidden" name="module" value="agent_model">
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
	{* `status` is in the DAO's fixed SELECT, not just the visible columns, so the row treatment holds
	   even when an operator drops the Status column from the worklist. Only Disabled dims -- an unlisted
	   model still runs when an automation names it. *}
	<tbody style="cursor:pointer;" {if $result.a_status == 2}class="cerb-worklist-row--disabled"{/if}>
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
				{* The vendor mark, so the list scans by brand rather than by reading provider+model strings.
				   The record's override else its provider's — a model reached over an OpenAI-compatible
				   endpoint (z.ai, Qwen, llama.cpp) can't be identified by `provider` alone. *}
				{$row_icon_color = $view->getRowIconColor($result)}
				<span data-cerb-agent-model-avatar
					style="vertical-align:middle;margin-right:0.4em;"
					data-avatar="{$result.a_name}"
					data-avatar-icon="{$view->getRowIcon($result)}"
					data-avatar-size="20"
					data-avatar-color="{if $row_icon_color}{$row_icon_color}{else}var(--cerb-color-background-contrast-180){/if}"
				></span>
				<a href="{devblocks_url}c=profiles&type=agent_model&id={$result.a_id}-{$result.a_name|devblocks_permalink}{/devblocks_url}" class="subject">{$result.a_name}</a>
				{* A bare `ban` glyph — the same mark the peek's status switcher uses for Disabled. No pill
				   chrome: the row's dimming already carries the state, and a filled badge on every disabled
				   row shouts louder than the model names it sits beside. The title carries the word. *}
				{if $result.a_status == 2}
					<span class="cerb-icons cerb-icon-ban" style="vertical-align:middle;margin-left:0.3em;" title="{'common.disabled'|devblocks_translate|capitalize}"></span>
				{elseif $result.a_status == 1}
					<span class="cerb-icons cerb-icon-lock" style="vertical-align:middle;margin-left:0.3em;" title="Unlisted"></span>
				{/if}
				<button type="button" class="peek cerb-peek-trigger" data-context="{$view_context}" data-context-id="{$result.a_id}"><span class="cerb-icons cerb-icon-new-window"></span></button>
			</td>
			{elseif $column == "a_status"}
				{* Available is the unremarkable case and stays blank so the exceptions stand out. *}
				<td data-column="{$column}" style="text-align:center;">
					{if $result.$column == 2}
						<span class="cerb-icons cerb-icon-ban" title="{'common.disabled'|devblocks_translate|capitalize}"></span>
					{elseif $result.$column == 1}
						<span class="cerb-icons cerb-icon-lock" title="Unlisted"></span>
					{/if}
				</td>
			{elseif $column == "a_has_thinking"}
				<td data-column="{$column}" style="text-align:center;">
					{if $result.$column}
						<span class="cerb-icons cerb-icon-brain" title="{'dao.agent_model.has_thinking'|devblocks_translate|capitalize}"></span>
					{/if}
				</td>
			{elseif in_array($column, ["a_rating_intelligence", "a_rating_speed", "a_rating_privacy", "a_rating_cost"])}
				{* The tier NAME, not the stored 10/20/30/40 -- the number is storage, the label is the interface. *}
				<td data-column="{$column}">
					{$tier = $result.$column}
					{if $tier && isset($rating_labels.$column.$tier)}{$rating_labels.$column.$tier|capitalize}{/if}
				</td>
			{elseif $column == "a_has_vision"}
				<td data-column="{$column}" style="text-align:center;">
					{if $result.$column}
						<span class="cerb-icons cerb-icon-eye-open" title="{'dao.agent_model.has_vision'|devblocks_translate|capitalize}"></span>
					{/if}
				</td>
			{elseif $column == "a_connected_account_id"}
				{* $connected_accounts is preloaded by View_AgentModel::render() -- never load records here *}
				<td data-column="{$column}">
					{$account_id = $result.$column}
					{if $account_id && isset($connected_accounts.$account_id)}
						<a class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_CONNECTED_ACCOUNT}" data-context-id="{$account_id}">{$connected_accounts.$account_id->name}</a>
					{elseif $account_id}
						{* A dangling id stays visible rather than blanking the cell *}
						{$account_id}
					{/if}
				</td>
			{elseif $column == "a_context_window"}
				{* `200K` scans; `200000` next to `1000000` and `32768` does not. The exact count rides
				   the tooltip so nothing is lost. *}
				<td data-column="{$column}">
					{if $result.$column}<span title="{$result.$column|number_format}">{$result.$column|devblocks_prettynumber}</span>{/if}
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
		{if $active_worker->is_superuser}<button data-cerb-worklist-action-bulk="agent_model" type="button" class="action-always-show action-bulkupdate"><span class="cerb-icons cerb-icon-bot-message"></span> {'common.bulk_update'|devblocks_translate|lower}</button>{/if}
	</div>
</div>
{/if}

<div style="clear:both;"></div>
</form>

{include file="devblocks:cerberusweb.core::internal/views/view_common_jquery_ui.tpl"}

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#viewForm{$view->id}');

	// Paint the per-row vendor marks. Scoped to this form and re-run on every worklist render (paging, sort,
	// filter all re-emit this template), so there's no global scan to keep in sync.
	if(window.CerbUI && CerbUI.Avatar)
		CerbUI.Avatar.enhance($frm[0], '[data-cerb-agent-model-avatar]');
});
</script>
