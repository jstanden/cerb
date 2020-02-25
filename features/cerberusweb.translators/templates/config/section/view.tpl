{$view_fields = $view->getColumnsAvailable()}
{$results = $view->getData()}
{$total = $results[1]}
{$data = $results[0]}
<table cellpadding="0" cellspacing="0" border="0" class="worklist" width="100%" {if $view->options.header_color}style="background-color:{$view->options.header_color};"{/if}>
	<tr>
		<td nowrap="nowrap"><span class="title">{$view->name}</span></td>
		<td nowrap="nowrap" align="right" class="title-toolbar">
			<a href="javascript:;" title="{'common.search'|devblocks_translate|capitalize}" class="minimal" onclick="genericAjaxPopup('search','c=internal&a=invoke&module=worklists&action=showQuickSearchPopup&view_id={$view->id}',null,false,'400');"><span class="glyphicons glyphicons-search"></span></a>
			<a href="javascript:;" title="{'common.customize'|devblocks_translate|capitalize}" class="minimal" onclick="genericAjaxGet('customize{$view->id}','c=internal&a=invoke&module=worklists&action=customize&id={$view->id}');toggleDiv('customize{$view->id}','block');"><span class="glyphicons glyphicons-cogwheel"></span></a>
			<a href="javascript:;" title="{'common.subtotals'|devblocks_translate|capitalize}" class="subtotals minimal"><span class="glyphicons glyphicons-signal"></span></a>
			{if $active_worker->hasPriv("contexts.{$view_context}.export")}<a href="javascript:;" title="{'common.export'|devblocks_translate|capitalize}" class="minimal" onclick="genericAjaxGet('{$view->id}_tips','c=internal&a=invoke&module=worklists&action=renderExport&id={$view->id}');toggleDiv('{$view->id}_tips','block');"><span class="glyphicons glyphicons-file-export"></span></a>{/if}
			<a href="javascript:;" title="{'common.refresh'|devblocks_translate|capitalize}" class="minimal" onclick="genericAjaxGet('view{$view->id}','c=internal&a=invoke&module=worklists&action=refresh&id={$view->id}');"><span class="glyphicons glyphicons-refresh"></span></a>
		</td>
	</tr>
</table>

<div id="{$view->id}_tips" class="block" style="display:none;margin:10px;padding:5px;">Analyzing...</div>
<form id="customize{$view->id}" name="customize{$view->id}" action="#" onsubmit="return false;" style="display:none;"></form>
<form id="viewForm{$view->id}" name="viewForm{$view->id}" action="{devblocks_url}{/devblocks_url}" method="POST">
<input type="hidden" name="view_id" value="{$view->id}">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="translations">
<input type="hidden" name="action" value="">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<table cellpadding="1" cellspacing="0" border="0" width="100%" class="worklistBody">

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
	
	{assign var=list_id value=$result.f_list_id}
	{assign var=worker_id value=$result.f_worker_id}
	{assign var=mood value=$result.f_quote_mood}
	<tbody style="cursor:pointer;">
		<tr class="{$tableRowClass}">
			<td data-column="label" colspan="{$smarty.foreach.headers.total}">
				<input type="checkbox" name="row_id[]" value="{$result.tl_id}" style="display:none;">
				<div id="subject_{$result.tl_id}_{$view->id}" style="margin:2px;font-size:12px;">
					<input type="hidden" name="row_ids[]" value="{$result.tl_id}">
					{assign var=lang_code value=$result.tl_lang_code}
					{assign var=string_id value=$result.tl_string_id}
					{assign var=english_string value=$english_map.$string_id}
					{if !empty($result.tl_string_default) || !empty($result.tl_string_override)}
						<b style="color:rgb(50,50,50);">{$langs.$lang_code}:</b><br>
						{if !empty($result.tl_string_default)}{* if official translation *}
						<div style="margin-top:5px;">
						<table cellpadding="0" cellspacing="0" style="border:1px dotted rgb(0,102,255);">
							<tr>
							<td style="padding:3px;color:rgb(0, 102, 255);font-size:10pt;">
								{$result.tl_string_default|escape|nl2br nofilter}
							</td>
							</tr>
						</table>
						</div>
						{else}{* If unofficial translation *}
							{if 'en_US' != $result.tl_lang_code}
							{if !empty($english_string)}
							<span style="color:rgb(50,50,50);">{'translators.config.translate_from'|devblocks_translate:$langs.en_US}</span><br>
							<table cellpadding="0" cellspacing="0" style="margin-top:5px;margin-bottom:5px;border:1px dotted rgb(0, 102, 255);">
							<tr>
							<td style="padding:3px;color:rgb(0, 102, 255);font-size:10pt;">
								{$english_string->string_default|escape|nl2br nofilter}
							</td>
							</tr>
							</table>
							{/if}
							{/if}
						{/if}
					{else}{* String not set *}
						{if 'en_US' != $result.tl_lang_code}
						{if !empty($english_string)}
						<b style="color:rgb(175,0,0);"><span class="glyphicons glyphicons-alert"></span> {$langs.$lang_code}</b><br>
						<span style="color:rgb(50,50,50);">{'translators.config.translate_from'|devblocks_translate:$langs.en_US}</span><br>
						<table cellpadding="0" cellspacing="0" style="margin-top:5px;margin-bottom:5px;border:1px dotted rgb(200,0,0);">
						<tr>
						<td style="padding:3px;color:rgb(50,50,50);font-size:10pt;">
							{$english_string->string_default|escape|nl2br nofilter}
						</td>
						</tr>
						</table>
						{/if}
						{/if}
					{/if}
				</div>
			</td>
		</tr>

		<tr class="{$tableRowClass}">
		{foreach from=$view->view_columns item=column name=columns}
			{assign var=lang_code value=$result.tl_lang_code}
		
			{if $column=="tl_id"}
				<td data-column="{$column}" valign="top">{$result.tl_id}&nbsp;</td>
			{elseif $column=="tl_string_override"}
				<td data-column="{$column}">
					{math assign=height equation="25+(25*floor(x/65))" x=$english_string->string_default|count_characters format="%d"}
					<textarea name="translations[]" style="width:98%;height:{$height}px;border:1px solid rgb(80,80,80);" rows="3" cols="45">{if !empty($result.$column)}{$result.$column}{/if}</textarea>
				</td>
			{elseif $column=="tl_string_id"}
				<td data-column="{$column}" valign="top">{$result.$column}&nbsp;</td>
			{elseif $column=="tl_lang_code"}
				<td data-column="{$column}" valign="top">
					{$langs.$lang_code}&nbsp;
				</td>
			{else}
				<td data-column="{$column}" valign="top">{$result.$column}&nbsp;</td>
			{/if}
		{/foreach}
		</tr>
	</tbody>
	{/foreach}
</table>

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
	
	{if $total}
	<div style="float:left;" id="{$view->id}_actions">
		<button type="button" class="action-always-show" onclick="$frm=$(this.form);$frm.find('input:hidden[name=action]').val('saveView');$frm.submit();"><span class="glyphicons glyphicons-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
		<button type="button" class="action-always-show" onclick="document.location.href = '{$smarty.const.DEVBLOCKS_WEBPATH}ajax.php?c=config&a=invoke&module=translations&action=exportTmx';"><span class="glyphicons glyphicons-file-export"></span> {'common.export'|devblocks_translate|capitalize}</button>
	</div>
	{/if}
</div>

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