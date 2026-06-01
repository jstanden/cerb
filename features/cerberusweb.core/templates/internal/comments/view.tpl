{$view_context = CerberusContexts::CONTEXT_COMMENT}
{$view_fields = $view->getColumnsAvailable()}
{$total = $results[1]}
{$data = $results[0]}

{include file="devblocks:cerberusweb.core::internal/views/view_marquee.tpl" view=$view}

<table cellpadding="0" cellspacing="0" border="0" class="worklist" width="100%" {if array_key_exists('header_color', $view->options) && $view->options.header_color}style="background-color:{$view->options.header_color};"{/if}>
	<tr>
		<td nowrap="nowrap"><span class="title">{$view->name}</span></td>
		<td nowrap="nowrap" align="right" class="title-toolbar">
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
<input type="hidden" name="module" value="comment">
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
	{$object_watchers = DAO_ContextLink::getContextLinks($view_context, array_keys($data), CerberusContexts::CONTEXT_WORKER)}
	{foreach from=$data item=result key=idx name=results}

	{if $smarty.foreach.results.iteration % 2}
		{$tableRowClass = "even"}
	{else}
		{$tableRowClass = "odd"}
	{/if}
	<tbody style="cursor:pointer;">
		<tr class="{$tableRowClass}">
			<td data-column="label" colspan="{$smarty.foreach.headers.total}" style="padding:5px;">
				<input type="checkbox" name="row_id[]" value="{$result.c_id}" style="display:none;">
				
				<div style="float:left;margin:0px 5px;">
				{$owner_context = Extension_DevblocksContext::get($result.c_owner_context)}
				{if $owner_context}
					{$meta = $owner_context->getMeta($result.c_owner_context_id)}
					{if $meta}
						{if $owner_context->id == CerberusContexts::CONTEXT_APPLICATION}
						<img src="{devblocks_url}c=avatars&context=app&context_id=0{/devblocks_url}?v={$smarty.const.APP_BUILD}" style="height:32px;width:32px;vertical-align:middle;border-radius:32px;">
						{elseif $owner_context->id == CerberusContexts::CONTEXT_BOT}
						<img src="{devblocks_url}c=avatars&context=bot&context_id={$meta.id}{/devblocks_url}?v={$meta.updated}" style="height:32px;width:32px;vertical-align:middle;border-radius:32px;">
						{elseif $owner_context->id == CerberusContexts::CONTEXT_WORKER}
						<img src="{devblocks_url}c=avatars&context=worker&context_id={$meta.id}{/devblocks_url}?v={$meta.updated}" style="height:32px;width:32px;vertical-align:middle;border-radius:32px;">
						{elseif $owner_context->id == CerberusContexts::CONTEXT_CONTACT}
						<img src="{devblocks_url}c=avatars&context=contact&context_id={$meta.id}{/devblocks_url}?v={$meta.updated}" style="height:32px;width:32px;vertical-align:middle;border-radius:32px;">
						{elseif $owner_context->id == CerberusContexts::CONTEXT_ORG}
						<img src="{devblocks_url}c=avatars&context=org&context_id={$meta.id}{/devblocks_url}?v={$meta.updated}" style="height:32px;width:32px;vertical-align:middle;border-radius:32px;">
						{elseif $owner_context->id == CerberusContexts::CONTEXT_ADDRESS}
						<img src="{devblocks_url}c=avatars&context=address&context_id={$meta.id}{/devblocks_url}?v={$meta.updated}" style="height:32px;width:32px;vertical-align:middle;border-radius:32px;">
						{elseif $owner_context->id == CerberusContexts::CONTEXT_GROUP}
						<img src="{devblocks_url}c=avatars&context=group&context_id={$meta.id}{/devblocks_url}?v={$meta.updated}" style="height:32px;width:32px;vertical-align:middle;border-radius:32px;">
						{/if}
					{/if}
				{/if}
				</div>
				
				<div>
					<div style="margin-bottom:2px;">
						{if $owner_context->id == CerberusContexts::CONTEXT_APPLICATION}
						<b class="subject">{$meta.name}</b>
						{else}
						<a class="subject cerb-peek-trigger no-underline" data-context="{$result.c_owner_context}" data-context-id="{$result.c_owner_context_id}" data-profile-url="{$meta.permalink}">{$meta.name}</a>
						{/if}
						<button type="button" class="peek cerb-peek-trigger" data-context="{$view_context}" data-context-id="{$result.c_id}" data-profile-url="{devblocks_url}c=profiles&what=comment&id={$result.c_id}{/devblocks_url}"><span class="cerb-icons cerb-icon-new-window"></span></button>
					</div>
				
					<div>
						<pre class="emailbody" dir="auto">{$result.c_comment|trim|truncate:1000|escape|devblocks_hyperlinks nofilter}</pre>
					</div>
				</div>
			</td>
		</tr>
		<tr class="{$tableRowClass}">
		{foreach from=$view->view_columns item=column name=columns}
			{if DevblocksPlatform::strStartsWith($column, "cf_")}
				{include file="devblocks:cerberusweb.core::internal/custom_fields/view/cell_renderer.tpl"}
			{elseif in_array($column, ["c_created"])}
				<td data-column="{$column}">
					{if !empty($result.$column)}
						<abbr title="{$result.$column|devblocks_date}">{$result.$column|devblocks_prettytime}</abbr>
					{/if}
				</td>
			{elseif in_array($column, ["*_target"])}
				<td data-column="{$column}">
					{$target = $targets.{$result.c_context}.{$result.c_context_id}}
					{if $target}
						<a class="cerb-peek-trigger" data-context="{$target->_context}" data-context-id="{$target->id}" data-profile-url="{if isset($target->url)}{$target->url}{elseif isset($target->record_url)}{$target->record_url}{/if}">{$target->_label|truncate:64}</a>
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
		{if $active_worker->hasPriv("contexts.{$view_context}.update.bulk") && $active_worker->hasPriv("contexts.{$view_context}.delete")}<button data-cerb-worklist-action-bulk="comment" type="button" class="action-always-show action-bulkupdate"><span class="cerb-icons cerb-icon-folder"></span> {'common.bulk_update'|devblocks_translate|lower}</button>{/if}
	</div>
</div>
{/if}

<div style="clear:both;"></div>

</form>

{include file="devblocks:cerberusweb.core::internal/views/view_common_jquery_ui.tpl"}

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var $frm = $('#viewForm{$view->id}');
	
	{if $pref_keyboard_shortcuts}
	$frm.bind('keyboard_shortcut',function(event) {
		let $view_actions = $('#{$view->id}_actions');
		
		let hotkey_activated = true;
	
		switch(event.keypress_event.which) {
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
