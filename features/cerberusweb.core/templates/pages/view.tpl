{$view_fields = $view->getColumnsAvailable()}
{$results = $view->getData()}
{assign var=total value=$results[1]}
{assign var=data value=$results[0]}

<table cellpadding="0" cellspacing="0" border="0" class="worklist" width="100%">
	<tr>
		<td nowrap="nowrap"><span class="title">{$view->name}</span></td>
		<td nowrap="nowrap" align="right">
			<a href="javascript:;" title="{'common.add'|devblocks_translate|capitalize}" onclick="genericAjaxPopup('peek','c=pages&a=showEditWorkspacePage&id=0&view_id={$view->id}',null,true,'500');"><span class="cerb-sprite2 sprite-plus-circle-frame"></span></a>
			<a href="javascript:;" title="{'common.customize'|devblocks_translate|capitalize}" class="minimal" onclick="genericAjaxGet('customize{$view->id}','c=internal&a=viewCustomize&id={$view->id}');toggleDiv('customize{$view->id}','block');"><span class="cerb-sprite2 sprite-gear"></span></a>
			{*<a href="javascript:;" title="Subtotals" class="subtotals minimal"><span class="cerb-sprite2 sprite-application-sidebar-list"></span></a>*}
			<a href="javascript:;" title="{$translate->_('common.copy')|capitalize}" onclick="genericAjaxGet('{$view->id}_tips','c=internal&a=viewShowCopy&view_id={$view->id}');toggleDiv('{$view->id}_tips','block');"><span class="cerb-sprite2 sprite-applications"></span></a>
			<a href="javascript:;" title="{'common.refresh'|devblocks_translate|capitalize}" class="minimal" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewRefresh&id={$view->id}');"><span class="cerb-sprite2 sprite-arrow-circle-135-left"></span></a>
			<input type="checkbox" onclick="checkAll('view{$view->id}',this.checked);this.blur();$rows=$('#viewForm{$view->id}').find('table.worklistBody').find('tbody > tr');if($(this).is(':checked')) { $rows.addClass('selected'); } else { $rows.removeClass('selected'); }">
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
<table cellpadding="1" cellspacing="0" border="0" width="100%" class="worklistBody">

	{* Column Headers *}
	<thead>
	<tr>
		<th style="text-align:center;width:75px;">
			<a href="javascript:;">Menu</a>
		</th>
		
		{foreach from=$view->view_columns item=header name=headers}
			{* start table header, insert column title and link *}
			<th nowrap="nowrap">
			<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewSortBy&id={$view->id}&sortBy={$header}');">{$view_fields.$header->db_label|capitalize}</a>
			
			{* add arrow if sorting by this column, finish table header tag *}
			{if $header==$view->renderSortBy}
				{if $view->renderSortAsc}
					<span class="cerb-sprite sprite-sort_ascending"></span>
				{else}
					<span class="cerb-sprite sprite-sort_descending"></span>
				{/if}
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
		{assign var=tableRowClass value="even"}
	{else}
		{assign var=tableRowClass value="odd"}
	{/if}
	<tbody style="cursor:pointer;">
		<tr class="{$tableRowClass}">
			<td align="center" nowrap="nowrap" style="padding:5px;">
				<button class="add" type="button" page_id="{$result.w_id}" page_label="{$result.w_name|lower}" page_url="{devblocks_url}c=pages&page={$result.w_id}-{$result.w_name|devblocks_permalink}{/devblocks_url}">{if $in_menu}<span class="cerb-sprite2 sprite-minus-circle"></span>{else}<span class="cerb-sprite2 sprite-plus-circle"></span>{/if}</button>				
				<input type="checkbox" name="row_id[]" value="{$result.w_id}" style="display:none;">
			</td>
		{foreach from=$view->view_columns item=column name=columns}
			{if substr($column,0,3)=="cf_"}
				{include file="devblocks:cerberusweb.core::internal/custom_fields/view/cell_renderer.tpl"}
			{elseif $column=="w_name"}
			<td>
				<a href="{devblocks_url}c=pages&page={$result.w_id}-{$result.w_name|devblocks_permalink}{/devblocks_url}" class="subject">{if !empty($result.w_name)}{$result.w_name}{else}New Page{/if}</a>
				{*<button type="button" class="peek" style="visibility:hidden;padding:1px;margin:0px 5px;" onclick="$popup = genericAjaxPopup('peek','c=pages&a=showEditWorkspacePage&id={$result.w_id}',null,true,'550');"><span class="cerb-sprite2 sprite-document-search-result" style="margin-left:2px" title="{$translate->_('views.peek')}"></span></button>*}
			</td>
			{elseif $column=="*_owner"}
				{$owner_context = $result.w_owner_context}
				{$owner_context_id = $result.w_owner_context_id}
				{$owner_context_ext = Extension_DevblocksContext::get($owner_context)}
				<td>
					{if !is_null($owner_context_ext)}
						{$meta = $owner_context_ext->getMeta($owner_context_id)}
						{if !empty($meta)}
						{$meta.name} 
						{/if}
						({$owner_context_ext->manifest->name})
					{/if}
				</td>
			{else}
				<td>{$result.$column}</td>
			{/if}
		{/foreach}
		</tr>
	</tbody>
	{/foreach}
</table>

{if $total}
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
			<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewPage&id={$view->id}&page={$prevPage}');">&lt;{$translate->_('common.previous_short')|capitalize}</a>
		{/if}
		({'views.showing_from_to'|devblocks_translate:$fromRow:$toRow:$total})
		{if $toRow < $total}
			<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewPage&id={$view->id}&page={$nextPage}');">{$translate->_('common.next')|capitalize}&gt;</a>
			<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewPage&id={$view->id}&page={$lastPage}');">&gt;&gt;</a>
		{/if}
	</div>
	
	<div style="float:left;" id="{$view->id}_actions">
	</div>
</div>

<div style="clear:both;"></div>
{/if}

</form>

<script type="text/javascript">
$('#viewForm{$view->id}').find('button.add').click(function(e) {
	$this = $(this);

	$menu = $('BODY UL.navmenu:first');
	$item = $menu.find('li.drag[page_id="'+$this.attr('page_id')+'"]');
	
	// Remove
	if($item.length > 0) {
		// Is the page already in the menu?
		$item.css('visibility','hidden');
		
		if($item.length > 0) {
			$item.effect('transfer', { to:$this, className:'effects-transfer' }, 500, function() {
				$(this).remove();
			});
			
			$this.html('<span class="cerb-sprite2 sprite-plus-circle"></span>');
		}
		
		genericAjaxGet('', 'c=pages&a=doToggleMenuPageJson&page_id=' + $this.attr('page_id') + '&toggle=0');
		
	// Add
	} else {
		var $li = $('<li class="drag" page_id="'+$this.attr('page_id')+'"></li>');
		$li.append($('<a href="'+$this.attr('page_url')+'">'+$this.attr('page_label')+'</a>'));
		$li.css('visibility','hidden');
		
		$marker = $menu.find('li.add');

		if(0 == $marker.length) {
			$li.prependTo($menu);
			
		} else {
			$li.insertBefore($marker);
			
		}
		
		$this.effect('transfer', { to:$li, className:'effects-transfer' }, 500, function() {
			$li.css('visibility','visible');
		});
		
		$this.html('<span class="cerb-sprite2 sprite-minus-circle"></span>');

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