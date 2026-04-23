{$view_context = CerberusContexts::CONTEXT_BEHAVIOR_SCHEDULED}
{$view_fields = $view->getColumnsAvailable()}
{$results = $view->getData()}
{$total = $results[1]}
{$data = $results[0]}

{include file="devblocks:cerberusweb.core::internal/views/view_marquee.tpl" view=$view}

<table cellpadding="0" cellspacing="0" border="0" class="worklist" width="100%" {if array_key_exists('header_color', $view->options) && $view->options.header_color}style="background-color:{$view->options.header_color};"{/if}>
	<tr>
		<td nowrap="nowrap"><span class="title">{$view->name}</span></td>
		<td nowrap="nowrap" align="right" class="title-toolbar">
			{if $active_worker->hasPriv("contexts.{$view_context}.create")}<a title="{'common.add'|devblocks_translate|capitalize}" class="minimal peek cerb-peek-trigger" data-context="{$view_context}" data-context-id="0"><span class="glyphicons glyphicons-circle-plus"></span></a>{/if}
			<a data-cerb-worklist-icon-search title="{'common.search'|devblocks_translate|capitalize}" class="minimal"><span class="glyphicons glyphicons-search"></span></a>
			<a data-cerb-worklist-icon-customize title="{'common.customize'|devblocks_translate|capitalize}" class="minimal"><span class="glyphicons glyphicons-cogwheel"></span></a>
			<a data-cerb-worklist-icon-subtotals title="{'common.subtotals'|devblocks_translate|capitalize}" class="minimal"><span class="glyphicons glyphicons-signal"></span></a>
			<a data-cerb-worklist-icon-refresh title="{'common.refresh'|devblocks_translate|capitalize}" class="minimal"><span class="glyphicons glyphicons-refresh"></span></a>
		</td>
	</tr>
</table>

<div id="{$view->id}_tips" class="block" style="display:none;margin:10px;padding:5px;">Loading...</div>
<form id="customize{$view->id}" name="customize{$view->id}" action="#"></form>
<form id="viewForm{$view->id}" name="viewForm{$view->id}" action="{devblocks_url}{/devblocks_url}" method="POST">
<input type="hidden" name="view_id" value="{$view->id}">
<input type="hidden" name="context_id" value="cerberusweb.contexts.behavior.scheduled">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="scheduled_behavior">
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
			{elseif $column=="b_behavior_name"}
			<td data-column="{$column}">
				<input type="checkbox" name="row_id[]" value="{$result.c_id}" style="display:none;">
				<a href="{devblocks_url}c=profiles&what=scheduled_behavior&id={$result.c_id}&name={$result.$column|devblocks_permalink}{/devblocks_url}" class="subject">{$result.$column}</a>
				<button type="button" class="peek cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_BEHAVIOR_SCHEDULED}" data-context-id="{$result.c_id}" data-profile-url="{devblocks_url}c=profiles&what=scheduled_behavior&id={$result.c_id}&name={$result.$column|devblocks_permalink}{/devblocks_url}"><span class="glyphicons glyphicons-new-window-alt"></span></button>
			</td>
			{elseif $column=="c_run_date"}
			<td data-column="{$column}" style="width:100px;">
				{if $result.$column <= time()}
				now
				{else}
				<abbr title="{$result.$column|devblocks_date}">{$result.$column|devblocks_prettytime}</abbr>
				{/if}
			</td>
			{elseif $column=="b_behavior_bot_id"}
			<td data-column="{$column}">
				{$ctx = Extension_DevblocksContext::get(CerberusContexts::CONTEXT_BOT)}
				{if is_object($ctx)}
				{$meta = $ctx->getMeta($result.$column)}
				<span title="{$ctx->manifest->name}">
					<a class="cerb-peek-trigger" data-context="{$ctx->id}" data-context-id="{$meta.id}">{$meta.name|truncate:64}</a>
				</span>
				{/if}
			</td>
			{elseif $column=="*_target"}
			<td data-column="{$column}">
				{$ctx = Extension_DevblocksContext::get($result.c_context|default:'')}
				{if is_a($ctx, 'Extension_DevblocksContext')}
				{$meta = $ctx->getMeta($result.c_context_id)}
				<span title="{$ctx->manifest->name}">
					{if $ctx->hasOption('cards')}
						<a class="cerb-peek-trigger" data-context="{$ctx->id}" data-context-id="{$meta.id}">{$meta.name|truncate:64}</a>
					{else}
						{$meta.name|truncate:64}
					{/if}
				</span>
				{/if}
			</td>
			{elseif $column=="c_repeat_json"}
			<td data-column="{$column}">
				{if !empty($result.$column)}
					{$repeat = json_decode($result.$column, true)}
					{if $repeat.freq == 'interval'}
						every 
						{$repeat.options.every_n}
					{else}
						{$repeat.freq}
					{/if}
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
		{if !$view_toolbar['explore']}<button type="button" class="action-always-show action-explore"><span class="glyphicons glyphicons-compass"></span> {'common.explore'|devblocks_translate|lower}</button>{/if}
	</div>
</div>
{/if}

<div style="clear:both;"></div>

</form>

{include file="devblocks:cerberusweb.core::internal/views/view_common_jquery_ui.tpl"}

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$frm = $('#viewForm{$view->id}');

{if $pref_keyboard_shortcuts}
$frm.bind('keyboard_shortcut',function(event) {
	let $view_actions = $('#{$view->id}_actions');
	let hotkey_activated = true;

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