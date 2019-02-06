{$view_context = CerberusContexts::CONTEXT_WORKSPACE_PAGE}
{$view_fields = $view->getColumnsAvailable()}
{$results = $view->getData()}
{$total = $results[1]}
{$data = $results[0]}

{include file="devblocks:cerberusweb.core::internal/views/view_marquee.tpl" view=$view}

<table cellpadding="0" cellspacing="0" border="0" class="worklist" width="100%" {if $view->options.header_color}style="background-color:{$view->options.header_color};"{/if}>
	<tr>
		<td nowrap="nowrap"><span class="title">{$view->name}</span></td>
		<td nowrap="nowrap" align="right" class="title-toolbar">
			{if $active_worker->hasPriv("contexts.{$view_context}.create")}<a href="javascript:;" title="{'common.add'|devblocks_translate|capitalize}" class="minimal peek cerb-peek-trigger" data-context="{$view_context}" data-context-id="0" data-width="75%"><span class="glyphicons glyphicons-circle-plus"></span></a>{/if}
			<a href="javascript:;" title="{'common.search'|devblocks_translate|capitalize}" class="minimal" onclick="genericAjaxPopup('search','c=internal&a=viewShowQuickSearchPopup&view_id={$view->id}',null,false,'400');"><span class="glyphicons glyphicons-search"></span></a>
			<a href="javascript:;" title="{'common.customize'|devblocks_translate|capitalize}" class="minimal" onclick="genericAjaxGet('customize{$view->id}','c=internal&a=viewCustomize&id={$view->id}');toggleDiv('customize{$view->id}','block');"><span class="glyphicons glyphicons-cogwheel"></span></a>
			<a href="javascript:;" title="{'common.subtotals'|devblocks_translate|capitalize}" class="subtotals minimal"><span class="glyphicons glyphicons-signal"></span></a>
			<a href="javascript:;" title="{'common.copy'|devblocks_translate|capitalize}" onclick="genericAjaxGet('{$view->id}_tips','c=internal&a=viewShowCopy&view_id={$view->id}');toggleDiv('{$view->id}_tips','block');"><span class="glyphicons glyphicons-duplicate"></span></a>
			<a href="javascript:;" title="{'common.refresh'|devblocks_translate|capitalize}" class="minimal" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewRefresh&id={$view->id}');"><span class="glyphicons glyphicons-refresh"></span></a>
			<input type="checkbox" class="select-all">
		</td>
	</tr>
</table>

<div id="{$view->id}_tips" class="block" style="display:none;margin:10px;padding:5px;">Loading...</div>
<form id="customize{$view->id}" name="customize{$view->id}" action="#" onsubmit="return false;" style="display:none;"></form>
<form id="viewForm{$view->id}" name="viewForm{$view->id}" action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="view_id" value="{$view->id}">
<input type="hidden" name="context_id" value="">
<input type="hidden" name="c" value="pages">
<input type="hidden" name="a" value="">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<table cellpadding="1" cellspacing="0" border="0" width="100%" class="worklistBody">

	{* Column Headers *}
	<thead>
	<tr>
		<th style="text-align:center;width:60px;">
			<a href="javascript:;">Menu</a>
		</th>
		
		{foreach from=$view->view_columns item=header name=headers}
			{* start table header, insert column title and link *}
			<th class="{if $view->options.disable_sorting}no-sort{/if}">
			{if !$view->options.disable_sorting && !empty($view_fields.$header->db_column)}
				<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewSortBy&id={$view->id}&sortBy={$header}');">{$view_fields.$header->db_label|capitalize}</a>
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
	{$menu_json = DAO_WorkerPref::get($active_worker->id, 'menu_json', json_encode(array()))}
	{$menu = json_decode($menu_json, true)}
	
	{foreach from=$data item=result key=idx name=results}
	
	{$in_menu = in_array($result.w_id, $menu)}

	{if $smarty.foreach.results.iteration % 2}
		{$tableRowClass = "even"}
	{else}
		{$tableRowClass = "odd"}
	{/if}
	<tbody style="cursor:pointer;">
		<tr class="{$tableRowClass}">
			<td data-column="button" align="center" nowrap="nowrap" style="padding:5px;">
				<button class="add" type="button" page_id="{$result.w_id}" page_label="{$result.w_name|lower}" page_url="{devblocks_url}c=pages&page={$result.w_id}-{$result.w_name|devblocks_permalink}{/devblocks_url}">{if $in_menu}<span class="glyphicons glyphicons-circle-minus" style="color:rgb(180,0,0);"></span>{else}<span class="glyphicons glyphicons-circle-plus" style="color:rgb(150,150,150);">{/if}</button>
				<input type="checkbox" name="row_id[]" value="{$result.w_id}" style="display:none;">
			</td>
		{foreach from=$view->view_columns item=column name=columns}
			{if substr($column,0,3)=="cf_"}
				{include file="devblocks:cerberusweb.core::internal/custom_fields/view/cell_renderer.tpl"}
			{elseif $column=="w_name"}
			<td data-column="{$column}">
				<a href="{devblocks_url}c=pages&page={$result.w_id}-{$result.w_name|devblocks_permalink}{/devblocks_url}" class="subject">{if !empty($result.w_name)}{$result.w_name}{else}New Page{/if}</a>
				<button type="button" class="peek cerb-peek-trigger" data-context="{$view_context}" data-context-id="{$result.w_id}"><span class="glyphicons glyphicons-new-window-alt"></span></button>
			</td>
			{elseif in_array($column,["w_extension_id"])}
				<td data-column="{$column}">
					{$page_extension = $page_extensions[$result.$column]}
					{$page_extension->params.label|devblocks_translate|capitalize}
				</td>
			{elseif in_array($column,["w_updated_at"])}
				<td data-column="{$column}" title="{$result.$column|devblocks_date}">
					{if !empty($result.$column)}
						{$result.$column|devblocks_prettytime}&nbsp;
					{/if}
				</td>
			{elseif $column=="*_owner"}
				{$owner_context = $result.w_owner_context}
				{$owner_context_id = $result.w_owner_context_id}
				{$owner_context_ext = Extension_DevblocksContext::get($owner_context)}
				
				<td data-column="{$column}">
					{if $owner_context_ext instanceof Extension_DevblocksContext}
						{$meta = $owner_context_ext->getMeta($owner_context_id)}
						{if !empty($meta)}
							<img src="{devblocks_url}c=avatars&context={$owner_context_ext->id}&context_id={$owner_context_id}{/devblocks_url}?v={$meta.updated_at}" style="height:1.5em;width:1.5em;border-radius:0.75em;vertical-align:middle;">
							{if $owner_context_id}
							<a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{$owner_context}" data-context-id="{$owner_context_id}">{$meta.name}</a>
							{else}
							{$meta.name}
							{/if}
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
			<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewPage&id={$view->id}&page=0');">&lt;&lt;</a>
			<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewPage&id={$view->id}&page={$prevPage}');">&lt;{'common.previous_short'|devblocks_translate|capitalize}</a>
		{/if}
		({'views.showing_from_to'|devblocks_translate:$fromRow:$toRow:$total})
		{if $toRow < $total}
			<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewPage&id={$view->id}&page={$nextPage}');">{'common.next'|devblocks_translate|capitalize}&gt;</a>
			<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewPage&id={$view->id}&page={$lastPage}');">&gt;&gt;</a>
		{/if}
	</div>
	
	{if $total}
	<div style="float:left;" id="{$view->id}_actions">
	</div>
	{/if}
</div>

<div style="clear:both;"></div>

</form>

<script type="text/javascript">
$('#viewForm{$view->id}').find('button.add').click(function(e) {
	var $this = $(this);

	var $menu = $('BODY UL.navmenu:first');
	var $item = $menu.find('li.drag[page_id="'+$this.attr('page_id')+'"]');
	
	// Remove
	if($item.length > 0) {
		// Is the page already in the menu?
		$item.css('visibility','hidden');
		
		if($item.length > 0) {
			$item.effect('transfer', { to:$this, className:'effects-transfer' }, 500, function() {
				$(this).remove();
			});
			
			$this.html('<span class="glyphicons glyphicons-circle-plus" style="color:rgb(150,150,150);">');
		}
		
		genericAjaxGet('', 'c=pages&a=doToggleMenuPageJson&page_id=' + $this.attr('page_id') + '&toggle=0');
		
	// Add
	} else {
		var $li = $('<li class="drag"/>').attr('page_id',$this.attr('page_id'));
		$li.append($('<a/>').attr('href',$this.attr('page_url')).text($this.attr('page_label')));
		$li.css('visibility','hidden');
		
		var $marker = $menu.find('li.add');

		if(0 == $marker.length) {
			$li.prependTo($menu);
			
		} else {
			$li.insertBefore($marker);
			
		}
		
		$this.effect('transfer', { to:$li, className:'effects-transfer' }, 500, function() {
			$li.css('visibility','visible');
		});
		
		$this.html('<span class="glyphicons glyphicons-circle-minus" style="color:rgb(180,0,0);">');

		genericAjaxGet('', 'c=pages&a=doToggleMenuPageJson&page_id=' + $this.attr('page_id') + '&toggle=1');
	}
});
</script>

{include file="devblocks:cerberusweb.core::internal/views/view_common_jquery_ui.tpl"}

<script type="text/javascript">
$frm = $('#viewForm{$view->id}');

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