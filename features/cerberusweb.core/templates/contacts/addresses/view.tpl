{$view_context = CerberusContexts::CONTEXT_ADDRESS}
{$view_fields = $view->getColumnsAvailable()}
{$results = $view->getData()}
{$total = $results[1]}
{$data = $results[0]}

{include file="devblocks:cerberusweb.core::internal/views/view_marquee.tpl" view=$view}

<table cellpadding="0" cellspacing="0" border="0" class="worklist" width="100%" {if array_key_exists('header_color', $view->options) && $view->options.header_color}style="background-color:{$view->options.header_color};"{/if}>
	<tr>
		<td nowrap="nowrap"><span class="title">{$view->name}</span></td>
		<td nowrap="nowrap" align="right" class="title-toolbar">
			{if $active_worker->hasPriv("contexts.{$view_context}.create")}<a title="{'common.add'|devblocks_translate|capitalize}" class="minimal cerb-peek-trigger" data-context="{$view_context}" data-context-id="0"><span class="cerb-icons cerb-icon-circle-plus"></span></a>{/if}
			<a data-cerb-worklist-icon-search title="{'common.search'|devblocks_translate|capitalize}" class="minimal"><span class="cerb-icons cerb-icon-search"></span></a>
			<a data-cerb-worklist-icon-customize title="{'common.customize'|devblocks_translate|capitalize}" class="minimal"><span class="cerb-icons cerb-icon-gear"></span></a>
			<a data-cerb-worklist-icon-subtotals title="{'common.subtotals'|devblocks_translate|capitalize}" class="minimal"><span class="cerb-icons cerb-icon-signal"></span></a>
			{if $active_worker->hasPriv("contexts.{$view_context}.import")}<a data-cerb-worklist-icon-import title="{'common.import'|devblocks_translate|capitalize}"><span class="cerb-icons cerb-icon-file-import"></span></a>{/if}
			{if $active_worker->hasPriv("contexts.{$view_context}.export")}<a data-cerb-worklist-icon-export title="{'common.export'|devblocks_translate|capitalize}" class="minimal"><span class="cerb-icons cerb-icon-file-export"></span></a>{/if}
			<a data-cerb-worklist-icon-refresh title="{'common.refresh'|devblocks_translate|capitalize}" class="minimal"><span class="cerb-icons cerb-icon-refresh"></span></a>
			<input type="checkbox" class="select-all">
		</td>
	</tr>
</table>

<div id="{$view->id}_tips" class="block" style="display:none;margin:10px;padding:5px;">Analyzing...</div>
<form id="customize{$view->id}" name="customize{$view->id}" action="#"></form>
<form id="viewForm{$view->id}" name="viewForm{$view->id}" action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="view_id" value="{$view->id}">
<input type="hidden" name="context_id" value="{$view_context}">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="address">
<input type="hidden" name="action" value="viewExplore">
<input type="hidden" name="explore_from" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<table cellpadding="1" cellspacing="0" border="0" width="100%" class="worklistBody">

	{* Column Headers *}
	<thead>
	<tr>
		{if !array_key_exists('disable_watchers', $view->options) || !$view->options.disable_watchers}
		<th class="no-sort" style="text-align:center;width:40px;padding-left:0;padding-right:0;" title="{'common.watchers'|devblocks_translate|capitalize}">
			<span class="cerb-icons cerb-icon-eye-open"></span>
		</th>
		{/if}

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
	{$object_watchers = DAO_ContextLink::getContextLinks(CerberusContexts::CONTEXT_ADDRESS, array_keys($data), CerberusContexts::CONTEXT_WORKER)}
	
	{* Bulk lazy load contacts for this page *}
	{$object_contacts = []}
	{if in_array('a_contact_id', $view->view_columns)}
		{$contact_ids = DevblocksPlatform::extractArrayValues($results, 'a_contact_id')}
		{$object_contacts = DAO_Contact::getIds($contact_ids)}
	{/if}
	
	{* Bulk lazy load orgs *}
	{$object_orgs = []}
	{if in_array('o_name', $view->view_columns)}
		{$org_ids = DevblocksPlatform::extractArrayValues($results, 'a_contact_org_id')}
		{$object_orgs = DAO_ContactOrg::getIds($org_ids)}
	{/if}
	
	{* Bulk lazy load workers *}
	{$object_workers = []}
	{if in_array('a_worker_id', $view->view_columns)}
		{$object_workers = DAO_Worker::getAll()}
	{/if}

	{foreach from=$data item=result key=idx name=results}

	{if $smarty.foreach.results.iteration % 2}
		{$tableRowClass = "even"}
	{else}
		{$tableRowClass = "odd"}
	{/if}
	<tbody style="cursor:pointer;">
		<tr class="{$tableRowClass}">
			<td data-column="*_watchers" align="center" rowspan="2" nowrap="nowrap" style="padding:5px;">
				{include file="devblocks:cerberusweb.core::internal/watchers/context_follow_button.tpl" context=CerberusContexts::CONTEXT_ADDRESS context_id=$result.a_id}
			</td>
			<td data-column="label" colspan="{$smarty.foreach.headers.total}">
				<input type="checkbox" name="row_id[]" value="{$result.a_id}" style="display:none;">
				<a href="{devblocks_url}c=profiles&type=address&id={$result.a_id}-{$result.a_email|devblocks_permalink}{/devblocks_url}" class="subject">{$result.a_email}</a>
				{if $result.a_is_banned}<span class="tag tag-red">banned</span> {/if}
				{if $result.a_is_defunct}<span class="tag tag-gray">defunct</span> {/if}
				<button type="button" class="peek cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_ADDRESS}" data-context-id="{$result.a_id}"><span class="cerb-icons cerb-icon-new-window"></span></button>
			</td>
		</tr>
		<tr class="{$tableRowClass}">
		{foreach from=$view->view_columns item=column name=columns}
			{if DevblocksPlatform::strStartsWith($column, "cf_")}
				{include file="devblocks:cerberusweb.core::internal/custom_fields/view/cell_renderer.tpl"}
			{elseif $column=="a_id"}
			<td data-column="{$column}">{$result.a_id}&nbsp;</td>
			{elseif $column=="a_contact_id"}
			<td data-column="{$column}">
				{if array_key_exists($result.a_contact_id, $object_contacts)}
				{$contact = $object_contacts.{$result.a_contact_id}}
				<a class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_CONTACT}" data-context-id="{$contact->id}">{$contact->getName()}</a>
				{/if}
			</td>
			{elseif $column=="o_name"}
			<td data-column="{$column}">
				{if array_key_exists($result.a_contact_org_id, $object_orgs)}
				{$org = $object_orgs.{$result.a_contact_org_id}}
				<a class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_ORG}" data-context-id="{$org->id}">{$org->name}</a>
				{/if}
			</td>
			{elseif in_array($column, ["a_is_banned", "a_is_defunct", "a_is_trusted"])}
			<td data-column="{$column}">
				{if $result.$column}<span class="cerb-icons cerb-icon-circle-ok"></span>{/if}&nbsp;
			</td>
			{elseif $column == "a_mail_transport_id"}
				{$mail_transport = $mail_transports.{$result.$column}}
				<td data-column="{$column}">
					{if $mail_transport}
					<a class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_MAIL_TRANSPORT}" data-context-id="{$result.$column}">{$mail_transport->name}</a>
					{/if}
				</td>
			{elseif $column == "a_worker_id"}
				<td data-column="{$column}">
					{if array_key_exists($result.a_worker_id, $object_workers)}
						{$worker = $object_workers.{$result.a_worker_id}}
						<a class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_WORKER}" data-context-id="{$result.$column}">{$worker->getName()}</a>
					{/if}
				</td>
			{elseif in_array($column, ["a_created_at","a_updated"])}
			<td data-column="{$column}">
				<abbr title="{$result.$column|devblocks_date}">{$result.$column|devblocks_prettytime}</abbr>
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
		{if $active_worker->hasPriv("contexts.{$view_context}.update.bulk")}<button data-cerb-worklist-action-bulk="address" type="button" class="action-always-show action-bulkupdate"><span class="cerb-icons cerb-icon-folder"></span> {'common.bulk_update'|devblocks_translate|lower}</button>{/if}
		{if $active_worker->hasPriv("contexts.{$view_context}.merge")}<button data-cerb-worklist-action-merge type="button"><span class="cerb-icons cerb-icon-merge"></span> {'common.merge'|devblocks_translate|lower}</button>{/if}
	</div>
</div>
{/if}

<div style="clear:both;"></div>

</form>

{include file="devblocks:cerberusweb.core::internal/views/view_common_jquery_ui.tpl"}

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
var $frm = $('#viewForm{$view->id}');
var $view = $('#view{$view->id}');

{if $pref_keyboard_shortcuts}
$frm.bind('keyboard_shortcut',function(event) {
	//console.log("{$view->id} received " + (indirect ? 'indirect' : 'direct') + " keyboard event for: " + event.keypress_event.which);
	
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