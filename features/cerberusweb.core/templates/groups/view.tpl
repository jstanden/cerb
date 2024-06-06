{$view_context = CerberusContexts::CONTEXT_GROUP}
{$view_fields = $view->getColumnsAvailable()}
{$results = $view->getData()}
{$total = $results[1]}
{$data = $results[0]}

{include file="devblocks:cerberusweb.core::internal/views/view_marquee.tpl" view=$view}

<table cellpadding="0" cellspacing="0" border="0" class="worklist" width="100%" {if array_key_exists('header_color', $view->options) && $view->options.header_color}style="background-color:{$view->options.header_color};"{/if}>
	<tr>
		<td nowrap="nowrap"><span class="title">{$view->name}</span></td>
		<td nowrap="nowrap" align="right" class="title-toolbar">
			{if $active_worker->is_superuser}<a title="{'common.add'|devblocks_translate|capitalize}" class="minimal cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_GROUP}" data-context-id="0" data-edit="true"><span class="glyphicons glyphicons-circle-plus"></span></a>{/if}
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
<input type="hidden" name="context_id" value="{$view_context}">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="group">
<input type="hidden" name="action" value="">
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
	{*$object_watchers = DAO_ContextLink::getContextLinks($view_context, array_keys($data), CerberusContexts::CONTEXT_WORKER)*}
	{foreach from=$data item=result key=idx name=results}

	{if $smarty.foreach.results.iteration % 2}
		{$tableRowClass = "even"}
	{else}
		{$tableRowClass = "odd"}
	{/if}
	<tbody style="cursor:pointer;">
		<tr class="{$tableRowClass}">
		{foreach from=$view->view_columns item=column name=columns}
			{if DevblocksPlatform::strStartsWith($column, "cf_")}
				{include file="devblocks:cerberusweb.core::internal/custom_fields/view/cell_renderer.tpl"}
			{elseif $column=="g_name"}
			<td data-column="{$column}">
				<input type="checkbox" name="row_id[]" value="{$result.g_id}" style="display:none;">
				<img src="{devblocks_url}c=avatars&context=group&context_id={$result.g_id}{/devblocks_url}?v={$result.g_updated}" style="height:32px;width:32px;border-radius:16px;vertical-align:middle;margin-right:3px;">
				<a href="{devblocks_url}c=profiles&g=group&id={$result.g_id}-{$result.g_name|devblocks_permalink}{/devblocks_url}" class="subject">{$result.$column}</a>
				<button type="button" class="peek cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_GROUP}" data-context-id="{$result.g_id}"><span class="glyphicons glyphicons-new-window-alt"></span></button>
			</td>
			{elseif in_array($column, ["g_is_private", "g_is_default"])}
				<td data-column="{$column}">{if $result.$column}<span class="glyphicons glyphicons-circle-ok"></span>{else}{/if}</td>
			{elseif in_array($column, ["g_created", "g_updated"])}
				<td data-column="{$column}"><abbr title="{$result.$column|devblocks_date}">{$result.$column|devblocks_prettytime}</abbr>&nbsp;</td>
			{elseif $column == "g_reply_address_id"}
				{$replyto_address = $replyto_addresses.{$result.$column}}
				<td data-column="{$column}">
					{if $replyto_address}
					<img src="{devblocks_url}c=avatars&context=address&context_id={$replyto_address->id}{/devblocks_url}?v={$replyto_address->updated_at}" style="height:16px;width:16px;border-radius:16px;vertical-align:middle;margin-right:3px;">
					<a class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_ADDRESS}" data-context-id="{$result.$column}">{$replyto_address->email}</a>
					{/if}
				</td>
			{elseif $column == "g_reply_html_template_id"}
				{$html_template = $html_templates.{$result.$column}}
				<td data-column="{$column}">
					{if $html_template}
					<a class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_MAIL_HTML_TEMPLATE}" data-context-id="{$result.$column}">{$html_template->name}</a>
					{/if}
				</td>
			{elseif $column == "g_reply_signature_id"}
				{$signature = $signatures.{$result.$column}}
				<td data-column="{$column}">
					{if $signature}
					<a class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_EMAIL_SIGNATURE}" data-context-id="{$result.$column}">{$signature->name}</a>
					{/if}
				</td>
			{elseif $column == "g_reply_signing_key_id"}
				{$signing_key = $signing_keys.{$result.$column}}
				<td data-column="{$column}">
					{if $signing_key}
						<a class="cerb-peek-trigger" data-context="{Context_GpgPrivateKey::ID}" data-context-id="{$result.$column}">{$signing_key->name}</a>
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
		{if !$view_toolbar['explore']}<button type="button" class="action-always-show action-explore"><span class="glyphicons glyphicons-play-button"></span> {'common.explore'|devblocks_translate|lower}</button>{/if}
		{if $active_worker->is_superuser && $active_worker->hasPriv("contexts.{$view_context}.update.bulk")}<button type="button" class="action-always-show action-bulkupdate" onclick="genericAjaxPopup('peek','c=profiles&a=invoke&module=group&action=showBulkPopup&view_id={$view->id}&ids=' + Devblocks.getFormEnabledCheckboxValues('viewForm{$view->id}','row_id[]'),null,false,'50%');"><span class="glyphicons glyphicons-folder-closed"></span> {'common.bulk_update'|devblocks_translate|lower}</button>{/if}
	</div>
</div>
{/if}

<div style="clear:both;"></div>

</form>

{include file="devblocks:cerberusweb.core::internal/views/view_common_jquery_ui.tpl"}

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
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
	//console.log("{$view->id} received " + (indirect ? 'indirect' : 'direct') + " keyboard event for: " + event.keypress_event.which);
	
	var $view_actions = $('#{$view->id}_actions');
	var hotkey_activated = true;

	switch(event.keypress_event.which) {
		default:
			hotkey_activated = false;
			break;
	}

	if(hotkey_activated)
		event.preventDefault();
});
{/if}
</script>