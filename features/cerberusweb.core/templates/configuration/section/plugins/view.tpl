{$app_version = DevblocksPlatform::strVersionToInt($smarty.const.APP_VERSION)}
{$view_fields = $view->getColumnsAvailable()}
{$results = $view->getData()}
{$total = $results[1]}
{$data = $results[0]}
<table cellpadding="0" cellspacing="0" border="0" class="worklist" width="100%" {if array_key_exists('header_color', $view->options) && $view->options.header_color}style="background-color:{$view->options.header_color};"{/if}>
	<tr>
		<td nowrap="nowrap"><span class="title">{$view->name}</span></td>
		<td nowrap="nowrap" align="right" class="title-toolbar">
			<a href="javascript:;" title="{'common.search'|devblocks_translate|capitalize}" class="minimal" onclick="genericAjaxPopup('search','c=internal&a=invoke&module=worklists&action=showQuickSearchPopup&view_id={$view->id}',null,false,'400');"><span class="glyphicons glyphicons-search"></span></a>
			<a href="javascript:;" title="{'common.customize'|devblocks_translate|capitalize}" class="minimal" onclick="genericAjaxGet('customize{$view->id}','c=internal&a=invoke&module=worklists&action=customize&id={$view->id}');toggleDiv('customize{$view->id}','block');"><span class="glyphicons glyphicons-cogwheel"></span></a>
			<a href="javascript:;" title="{'common.copy'|devblocks_translate|capitalize}" onclick="genericAjaxGet('{$view->id}_tips','c=internal&a=invoke&module=worklists&action=renderCopy&view_id={$view->id}');toggleDiv('{$view->id}_tips','block');"><span class="glyphicons glyphicons-duplicate"></span></a>
			<a href="javascript:;" title="{'common.refresh'|devblocks_translate|capitalize}" class="minimal" onclick="genericAjaxGet('view{$view->id}','c=internal&a=invoke&module=worklists&action=refresh&id={$view->id}');"><span class="glyphicons glyphicons-refresh"></span></a>
			<input type="checkbox" class="select-all">
		</td>
	</tr>
</table>

<div id="{$view->id}_tips" class="block" style="display:none;margin:10px;padding:5px;">Analyzing...</div>
<form id="customize{$view->id}" name="customize{$view->id}" action="#" onsubmit="return false;" style="display:none;"></form>
<form id="viewForm{$view->id}" name="viewForm{$view->id}" action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="">
<input type="hidden" name="a" value="">
<input type="hidden" name="view_id" value="{$view->id}">
<input type="hidden" name="context_id" value="">
<input type="hidden" name="id" value="{$view->id}">
<input type="hidden" name="explore_from" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<table cellpadding="1" cellspacing="0" border="0" width="100%" class="worklistBody">

	{* Column Headers *}
	<thead>
	<tr>
		<th style="width:106px;"></th>
		{foreach from=$view->view_columns item=header name=headers}
			{* start table header, insert column title and link *}
			<th class="{if array_key_exists('disable_sorting', $view->options) && $view->options.disable_sorting}no-sort{/if}">
			{if (!array_key_exists('disable_sorting', $view->options) || !$view->options.disable_sorting) && !empty($view_fields.$header->db_column)}
				<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=invoke&module=worklists&action=sort&id={$view->id}&sortBy={$header}');">{$view_fields.$header->db_label|capitalize}</a>
			{else}
				<a href="javascript:;" style="text-decoration:none;">{$view_fields.$header->db_label|capitalize}</a>
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
	{$plugin = $plugins.{$result.c_id}}
	{if !empty($plugin)}
		{$meets_requirements = $plugin->checkRequirements()}
	{/if}

	{if $smarty.foreach.results.iteration % 2}
		{$tableRowClass = "even"}
	{else}
		{$tableRowClass = "odd"}
	{/if}
	<tbody style="cursor:pointer;">
		<tr class="{$tableRowClass}">
			<td data-column="icon" rowspan="4" align="center">
				<div style="margin:0px 5px 5px 5px;position:relative;">
					{if !empty($plugin) && isset($plugin->manifest_cache.plugin_image) && !empty($plugin->manifest_cache.plugin_image)}
						<img src="{devblocks_url}c=resource&p={$plugin->id}&f={$plugin->manifest_cache.plugin_image}{/devblocks_url}" width="100" height="100" style="border:1px solid var(--cerb-color-background-contrast-200);">
					{else}
						<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/wgm/plugin_code_gray.gif{/devblocks_url}" width="100" height="100" style="border:1px solid var(--cerb-color-background-contrast-200);">
					{/if}
					{if !$result.c_enabled}
						<span class="plugin_icon_overlay_disabled"></span>
					{/if}
				</div>
			</td>
		</tr>
		<tr class="{$tableRowClass}">
			<td data-column="label" colspan="{$smarty.foreach.headers.total}">
				<div style="padding-bottom:2px;">
					<input type="checkbox" name="row_id[]" value="{$result.c_id}" style="display:none;">
					<b class="subject">{$result.c_name}</b>
				</div>
			</td>
		</tr>
		<tr class="{$tableRowClass}">
		{foreach from=$view->view_columns item=column name=columns}
		
			{if DevblocksPlatform::strStartsWith($column, "cf_")}
				{include file="devblocks:cerberusweb.core::internal/custom_fields/view/cell_renderer.tpl"}
			{elseif $column=="c_updated"}
				<td data-column="{$column}"><abbr title="{$result.$column|devblocks_date}">{$result.c_updated|devblocks_prettytime}</abbr>&nbsp;</td>
			{elseif $column=="c_version"}
				<td data-column="{$column}">
					{if isset($plugin->version) && $plugin->version < $result.c_version}
						<div class="badge" style="font-weight:bold;">{DevblocksPlatform::intVersionToStr($result.$column)}</div>
					{else}
						{DevblocksPlatform::intVersionToStr($result.$column)}
					{/if}
				</td>
			{elseif $column=="c_link"}
				<td data-column="{$column}">
					<a href="{$result.$column}" target="_blank" rel="noopener noreferrer">{$result.$column}</a>
				</td>
			{elseif $column=="c_enabled"}
				<td data-column="{$column}">
					{if !$result.c_enabled}
						<a href="javascript:;" onclick="" style="color:var(--cerb-color-error-text);text-decoration:none;font-weight:bold;">{'common.no'|devblocks_translate}</a>
					{else}
						<a href="javascript:;" onclick="" style="color:var(--cerb-color-text);text-decoration:none;font-weight:normal;">{'common.yes'|devblocks_translate}</a>
					{/if}
				</td>
			{elseif $column=="c_author"}
				<td data-column="{$column}">
					{if !empty($column.c_link)}
						<a href="{$result.c_link}" target="_blank" rel="noopener noreferrer">{$result.$column}</a>
					{else}
						{$result.$column}
					{/if}
				</td>
			{else}
				<td data-column="{$column}">{$result.$column}</td>
			{/if}
		{/foreach}
		</tr>
		<tr class="{$tableRowClass}">
			<td colspan="{$smarty.foreach.headers.total}" valign="top">
				<div style="padding:5px;">
					{$result.c_description}
				</div>
				<div style="margin:5px;">
					{if !empty($plugin)}
						<div class="badge badge-lightgray" style="padding:3px;"><a href="javascript:;" onclick="genericAjaxPopup('peek','c=config&a=invoke&module=plugins&action=showPopup&plugin_id={$result.c_id}&view_id={$view->id}',null,true,'550');" style="color:var(--cerb-color-text);text-decoration:none;font-weight:bold;">Configure &#x25be;</a></div>
					{/if}
					
					{if !$meets_requirements && !empty($plugin)}
						{$errors = $plugin->getRequirementsErrors()}
						{if !empty($errors)}
						<div style="padding:5px;color:rgb(150,0,0);">
							<b>Missing requirements:</b>
							<ul style="margin:0;">
							{foreach from=$errors item=error name=errors}
								<li>{$error}</li>
							{/foreach}
							</ul>
						</div>
						{/if}
					{/if}
				</div>
			</td>
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
			<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=invoke&module=worklists&action=page&id={$view->id}&page={$prevPage}');">&lt;{'common.previous_short'|devblocks_translate|capitalize}</a>
		{/if}
		({'views.showing_from_to'|devblocks_translate:$fromRow:$toRow:$total})
		{if $toRow < $total}
			<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=invoke&module=worklists&action=page&id={$view->id}&page={$nextPage}');">{'common.next'|devblocks_translate|capitalize}&gt;</a>
			<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=invoke&module=worklists&action=page&id={$view->id}&page={$lastPage}');">&gt;&gt;</a>
		{/if}
	</div>
	
	<div style="float:left;" id="{$view->id}_actions">
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
	//console.log("{$view->id} received " + (indirect ? 'indirect' : 'direct') + " keyboard event for: " + event.keypress_event.which);
	
	$view_actions = $('#{$view->id}_actions');
	
	hotkey_activated = true;

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