{$view_context = CerberusContexts::CONTEXT_NOTIFICATION}
{$view_fields = $view->getColumnsAvailable()}
<table cellpadding="0" cellspacing="0" border="0" class="worklist" width="100%" {if array_key_exists('header_color', $view->options) && $view->options.header_color}style="background-color:{$view->options.header_color};"{/if}>
	<tr>
		<td nowrap="nowrap"><span class="title">{$view->name}</span></td>
		<td nowrap="nowrap" align="right" class="title-toolbar">
			<a data-cerb-worklist-icon-search title="{'common.search'|devblocks_translate|capitalize}" class="minimal"><span class="glyphicons glyphicons-search"></span></a>
			<a data-cerb-worklist-icon-customize title="{'common.customize'|devblocks_translate|capitalize}" class="minimal"><span class="glyphicons glyphicons-cogwheel"></span></a>
			<a data-cerb-worklist-icon-subtotals title="{'common.subtotals'|devblocks_translate|capitalize}" class="minimal"><span class="glyphicons glyphicons-signal"></span></a>
			{if $active_worker->hasPriv("contexts.{$view_context}.export")}<a data-cerb-worklist-icon-export title="{'common.export'|devblocks_translate|capitalize}" class="minimal"><span class="glyphicons glyphicons-file-export"></span></a>{/if}
			<a data-cerb-worklist-icon-copy title="{'common.copy'|devblocks_translate|capitalize}"><span class="glyphicons glyphicons-duplicate"></span></a>
			<a data-cerb-worklist-icon-refresh title="{'common.refresh'|devblocks_translate|capitalize}" class="minimal"><span class="glyphicons glyphicons-refresh"></span></a>
			<input type="checkbox" class="select-all">
		</td>
	</tr>
</table>

<div id="{$view->id}_tips" class="block" style="display:none;margin:10px;padding:5px;">Loading...</div>
<form id="customize{$view->id}" name="customize{$view->id}" action="#"></form>
<form id="viewForm{$view->id}" name="viewForm{$view->id}" action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="view_id" value="{$view->id}">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="notifications">
<input type="hidden" name="action" value="viewExplore">
<input type="hidden" name="explore_from" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<table cellpadding="5" cellspacing="0" border="0" width="100%" class="worklistBody">

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

	{* Column Data *}
	{foreach from=$data item=result key=idx name=results}

	{if $smarty.foreach.results.iteration % 2}
		{$tableRowClass = "even"}
	{else}
		{$tableRowClass = "odd"}
	{/if}
	<tbody style="cursor:pointer;">
		<tr class="{$tableRowClass}">
			<td data-column="label" colspan="{$smarty.foreach.headers.total}" style="font-size:12px;padding:2px 0 2px 5px;">
				<input type="checkbox" name="row_id[]" value="{$result.we_id}" style="display:none;">
				{* If we're looking at the target context, hide the text in the entry *}
				{$entry = Model_ContextActivityLogEntry::new($result.we_activity_point, json_decode($result.we_entry_json,true))}
				{$params_req = $view->getParamsRequired()}
				{if $result.we_is_read}<span class="glyphicons glyphicons-circle-ok" style="font-size:16px;"></span> {/if}
				<span class="subject">{CerberusContexts::formatActivityLogEntry($entry,'html-cards',null,true) nofilter}</span>
			</td>
		</tr>
	
		<tr class="{$tableRowClass}">
		{foreach from=$view->view_columns item=column name=columns}
			{if $column=="we_id"}
				<td data-column="{$column}" valign="top">{$result.we_id}&nbsp;</td>
			{elseif $column=="we_created_date"}
				<td data-column="{$column}" valign="top" nowrap="nowrap"><abbr title="{$result.we_created_date|devblocks_date}">{$result.we_created_date|devblocks_prettytime}</abbr>&nbsp;</td>
			{elseif $column=="we_worker_id"}
				{assign var=worker_id value=$result.$column}
				<td data-column="{$column}">
					{if !empty($worker_id)}
						{$workers.$worker_id->getName()}
					{else}
						(auto)
					{/if}
					&nbsp;
				</td>
			{elseif $column=="we_is_read"}
				<td data-column="{$column}" valign="top">{if $result.$column}<span class="glyphicons glyphicons-circle-ok" style="font-size:16px;"></span>{/if}&nbsp;</td>
			{else}
				<td data-column="{$column}" valign="top">{$result.$column}&nbsp;</td>
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
		{if !$view_toolbar['explore']}<button type="button" class="action-always-show action-explore"><span class="glyphicons glyphicons-compass"></span> {'common.explore'|devblocks_translate|lower}</button>{/if}
		{if $active_worker->hasPriv("contexts.{$view_context}.update.bulk")}<button type="button" class="action-always-show action-bulkupdate" onclick="genericAjaxPopup('peek','c=internal&a=invoke&module=notifications&action=showBulkUpdatePanel&view_id={$view->id}&ids=' + Devblocks.getFormEnabledCheckboxValues('viewForm{$view->id}','row_id[]'),null,false,'50%');"><span class="glyphicons glyphicons-folder-closed"></span> {'common.bulk_update'|devblocks_translate|lower}</button>{/if}
		<button type="button" class="action-markread"><span class="glyphicons glyphicons-ok"></span> {'home.my_notifications.button.mark_read'|devblocks_translate|lower}</button>
	</div>
</div>
{/if}

<div style="clear:both;"></div>

</form>

{include file="devblocks:cerberusweb.core::internal/views/view_common_jquery_ui.tpl"}

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
var $frm = $('#viewForm{$view->id}');
var $view_actions = $('#{$view->id}_actions');

$view_actions.find('.action-markread').on('click', function() {
	var formData = new FormData($frm[0]);
	formData.set('c', 'internal');
	formData.set('a', 'invoke');
	formData.set('module', 'notifications');
	formData.set('action', 'viewMarkRead');

	genericAjaxPost(formData,'view{$view->id}',null);
});

{if $pref_keyboard_shortcuts}
$frm.bind('keyboard_shortcut',function(event) {
	//console.log("{$view->id} received " + (indirect ? 'indirect' : 'direct') + " keyboard event for: " + event.keypress_event.which);

	hotkey_activated = true;

	switch(event.keypress_event.which) {
		case 98: // (b) bulk update
			$btn = $view_actions.find('button.action-bulkupdate');

			if(event.indirect) {
				$btn.select().focus();

			} else {
				$btn.click();
			}
			break;

		case 99: // (c) mark read
			$btn = $view_actions.find('button.action-markread');

			if(!event.indirect) {
				$btn.click();
			}
			break;

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
});
</script>