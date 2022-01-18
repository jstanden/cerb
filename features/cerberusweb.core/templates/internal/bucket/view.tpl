{$view_context = CerberusContexts::CONTEXT_BUCKET}
{$view_fields = $view->getColumnsAvailable()}
{$results = $view->getData()}
{$total = $results[1]}
{$data = $results[0]}

{include file="devblocks:cerberusweb.core::internal/views/view_marquee.tpl" view=$view}

<table cellpadding="0" cellspacing="0" border="0" class="worklist" width="100%" {if $view->options.header_color}style="background-color:{$view->options.header_color};"{/if}>
	<tr>
		<td nowrap="nowrap"><span class="title">{$view->name}</span></td>
		<td nowrap="nowrap" align="right" class="title-toolbar">
			{if $active_worker->hasPriv("contexts.{$view_context}.create")}<a href="javascript:;" title="{'common.add'|devblocks_translate|capitalize}" class="minimal cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_BUCKET}" data-context-id="0" data-edit="true"><span class="glyphicons glyphicons-circle-plus"></span></a>{/if}
			<a href="javascript:;" title="{'common.search'|devblocks_translate|capitalize}" class="minimal" onclick="genericAjaxPopup('search','c=internal&a=invoke&module=worklists&action=showQuickSearchPopup&view_id={$view->id}',null,false,'400');"><span class="glyphicons glyphicons-search"></span></a>
			<a href="javascript:;" title="{'common.customize'|devblocks_translate|capitalize}" class="minimal" onclick="genericAjaxGet('customize{$view->id}','c=internal&a=invoke&module=worklists&action=customize&id={$view->id}');toggleDiv('customize{$view->id}','block');"><span class="glyphicons glyphicons-cogwheel"></span></a>
			<a href="javascript:;" title="{'common.subtotals'|devblocks_translate|capitalize}" class="subtotals minimal"><span class="glyphicons glyphicons-signal"></span></a>
			{*<a href="javascript:;" title="{$translate->_('common.import')|capitalize}" onclick="genericAjaxPopup('import','c=internal&a=invoke&module=worklists&action=renderImportPopup&context={$view_context}&view_id={$view->id}',null,false,'50%');"><span class="glyphicons glyphicons-file-import"></span></a>*}
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
<input type="hidden" name="module" value="bucket">
<input type="hidden" name="action" value="">
<input type="hidden" name="explore_from" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<table cellpadding="5" cellspacing="0" border="0" width="100%" class="worklistBody">

	{* Column Headers *}
	<thead>
	<tr>
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

	{* Column Data *}
	{foreach from=$data item=result key=idx name=results}

	{if $smarty.foreach.results.iteration % 2}
		{$tableRowClass = "even"}
	{else}
		{$tableRowClass = "odd"}
	{/if}
	<tbody style="cursor:pointer;">
		<tr class="{$tableRowClass}">
		{foreach from=$view->view_columns item=column name=columns}
			{if substr($column,0,3)=="cf_"}
				{include file="devblocks:cerberusweb.core::internal/custom_fields/view/cell_renderer.tpl"}
			{elseif $column == "b_name"}
			<td data-column="{$column}">
				<input type="checkbox" name="row_id[]" value="{$result.b_id}" style="display:none;">
				<a href="{devblocks_url}c=profiles&type=bucket&id={$result.b_id}-{$result.b_name|devblocks_permalink}{/devblocks_url}" class="subject">{$result.b_name}</a>
				<button type="button" class="cerb-peek-trigger peek" data-context="{$view_context}" data-context-id="{$result.b_id}"><span class="glyphicons glyphicons-new-window-alt" style="margin-left:2px" title="{$translate->_('views.peek')}"></span></button>
			</td>
			{elseif $column == "b_group_id"}
				<td data-column="{$column}">
					{$group = $groups.{$result.$column}}
					{if $group}
						<img src="{devblocks_url}c=avatars&context=group&context_id={$group->id}{/devblocks_url}?v={$group->updated}" style="height:24px;width:24px;border-radius:24px;vertical-align:middle;margin-right:3px;">
						<a href="javascript:;" class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_GROUP}" data-context-id="{$group->id}" data-profile-url="{devblocks_url}c=profiles&what=group&id={$group->id}{/devblocks_url}-{$group->name|devblocks_permalink}">{$group->name}</a>
					{/if}
				</td>
			{elseif $column == "b_is_default"}
				<td data-column="{$column}">
					{if $result.$column}<span class="glyphicons glyphicons-circle-ok" style="vertical-align:middle;font-size:120%;"></span>{/if}
				</td>
			{elseif $column == "b_reply_address_id"}
				{$replyto_address = $replyto_addresses.{$result.$column}}
				<td data-column="{$column}">
					{if $replyto_address}
					<img src="{devblocks_url}c=avatars&context=address&context_id={$replyto_address->id}{/devblocks_url}?v={$replyto_address->updated_at}" style="height:16px;width:16px;border-radius:16px;vertical-align:middle;margin-right:3px;">
					<a href="javascript:;" class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_ADDRESS}" data-context-id="{$result.$column}">{$replyto_address->email}</a>
					{/if}
				</td>
			{elseif $column == "b_reply_html_template_id"}
				{$html_template = $html_templates.{$result.$column}}
				<td data-column="{$column}">
					{if $html_template}
					<a href="javascript:;" class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_MAIL_HTML_TEMPLATE}" data-context-id="{$result.$column}">{$html_template->name}</a>
					{/if}
				</td>
			{elseif $column == "b_reply_signature_id"}
				{$signature = $signatures.{$result.$column}}
				<td data-column="{$column}">
					{if $signature}
					<a href="javascript:;" class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_EMAIL_SIGNATURE}" data-context-id="{$result.$column}">{$signature->name}</a>
					{/if}
				</td>
			{elseif $column == "b_reply_signing_key_id"}
				{$signing_key = $signing_keys.{$result.$column}}
				<td data-column="{$column}">
					{if $signing_key}
					<a href="javascript:;" class="cerb-peek-trigger" data-context="{Context_GpgPrivateKey::ID}" data-context-id="{$result.$column}">{$signing_key->name}</a>
					{/if}
				</td>
			{elseif $column == "b_updated_at"}
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
$frm = $('#viewForm{$view->id}');

{if $pref_keyboard_shortcuts}
$frm.bind('keyboard_shortcut',function(event) {
	$view_actions = $('#{$view->id}_actions');
	
	hotkey_activated = true;

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
</script>
