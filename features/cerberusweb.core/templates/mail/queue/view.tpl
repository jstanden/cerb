{$view_context = CerberusContexts::CONTEXT_DRAFT}
{$view_fields = $view->getColumnsAvailable()}
{$results = $view->getData()}
{$total = $results[1]}
{$data = $results[0]}
<table cellpadding="0" cellspacing="0" border="0" class="worklist" width="100%" {if array_key_exists('header_color', $view->options) && $view->options.header_color}style="background-color:{$view->options.header_color};"{/if}>
	<tr>
		<td nowrap="nowrap"><span class="title">{$view->name}</span></td>
		<td nowrap="nowrap" align="right" class="title-toolbar">
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
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="draft">
<input type="hidden" name="action" value="">
<input type="hidden" name="explore_from" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<table cellpadding="1" cellspacing="0" border="0" width="100%" class="worklistBody">

	{* Column Headers *}
	<thead>
	<tr>
		{foreach from=$view->view_columns item=header name=headers}
			{* start table header, insert column title and link *}
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

	{* Column Data *}
	{foreach from=$data item=result key=idx name=results}

	{if $smarty.foreach.results.iteration % 2}
		{$tableRowClass = "even"}
	{else}
		{$tableRowClass = "odd"}
	{/if}
	<tbody style="cursor:pointer;">
		<tr class="{$tableRowClass}">
			<td data-column="label" colspan="{$smarty.foreach.headers.total}">
				<input type="checkbox" name="row_id[]" value="{$result.m_id}" style="display:none;">
				<a class="subject peek cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_DRAFT}" data-context-id="{$result.m_id}" data-view-id="{$view->id}">{if empty($result.m_name)}(no subject){else}{$result.m_name}{/if}</a>
			</td>
		</tr>
		<tr class="{$tableRowClass}">
		{foreach from=$view->view_columns item=column name=columns}
			{if DevblocksPlatform::strStartsWith($column, "cf_")}
				{include file="devblocks:cerberusweb.core::internal/custom_fields/view/cell_renderer.tpl"}
			{elseif $column=="m_updated" || $column=="m_queue_delivery_date"}
			<td data-column="{$column}">
				<abbr title="{$result.$column|devblocks_date}">{$result.$column|devblocks_prettytime}</abbr>
			</td>
			{elseif $column=="m_worker_id"}
			<td data-column="{$column}">
				{if !isset($workers)}
					{$workers = DAO_Worker::getAll()}
				{/if}
				{if array_key_exists($result.$column, $workers)}
					{$worker = $workers.{$result.$column}}
					{$worker->getName()}
				{/if}
			</td>
			{elseif $column=="m_is_queued"}
			<td data-column="{$column}">
				{if $result.$column}
					{'common.yes'|devblocks_translate|capitalize}
				{else}
					{'common.no'|devblocks_translate|capitalize}
				{/if}
			</td>
			{elseif $column=="m_type"}
			<td data-column="{$column}">
				{$label_key = $result.$column}
				{if isset($label_map_type.$label_key)}
					{$label_map_type.$label_key}
				{else}
					{$label_key}
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
		{if $active_worker->hasPriv("contexts.{$view_context}.update.bulk")}<button data-cerb-worklist-action-bulk="draft" type="button" class="action-always-show action-bulkupdate"><span class="cerb-icons cerb-icon-folder"></span> {'common.bulk_update'|devblocks_translate|lower}</button>{/if}
	</div>
</div>
{/if}

<div style="clear:both;"></div>

</form>

{include file="devblocks:cerberusweb.core::internal/views/view_common_jquery_ui.tpl"}

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#viewForm{$view->id}');

	{if $pref_keyboard_shortcuts}
	$frm.bind('keyboard_shortcut',function(event) {
		let $view_actions = $('#{$view->id}_actions');
		let hotkey_activated = true;

		switch(event.keypress_event.which) {
			case 98: // (b) bulk update
				let $btn = $view_actions.find('button.action-bulkupdate');

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