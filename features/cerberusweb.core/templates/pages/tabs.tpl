<form action="{devblocks_url}{/devblocks_url}" id="frmWorkspacePage{$page->id}" method="POST" style="margin-top:5px;">
	<input type="hidden" name="c" value="internal">
	<input type="hidden" name="a" value="">

	{$menu_json = DAO_WorkerPref::get($active_worker->id, 'menu_json', json_encode(array()))}
	{$menu = json_decode($menu_json, true)}
	{$in_menu = in_array($page->id, $menu)}
	
	<div style="float:left;">
		<h2>{$page->name}</h2>
	</div>

	<div style="float:right;">
		{if $page->isReadableByWorker($active_worker)}
			<button class="add toolbar-item" type="button" page_id="{$page->id}" page_label="{$page->name|lower}" page_url="{devblocks_url}c=pages&page={$page->id}-{$page->name|devblocks_permalink}{/devblocks_url}">{if $in_menu}<span class="cerb-sprite2 sprite-minus-circle"></span>{else}<span class="cerb-sprite2 sprite-plus-circle"></span>{/if} Menu</button>
		{/if}
	
		{if $page->isWriteableByWorker($active_worker)}
			<button type="button" class="edit toolbar-item"><span class="cerb-sprite2 sprite-ui-tab-content-gear"></span> Edit Page</button>
			&nbsp;
		{/if}
	</div>

	<div style="clear:both;"></div>
</form>

<div id="pageTabs">
	<ul>
		{$tabs = []}
		
		{foreach from=$page_tabs item=tab}
			{$tabs[] = 'w_'|cat:$tab->id}
			<li class="drag" tab_id="{$tab->id}"><a href="{devblocks_url}ajax.php?c=pages&a=showWorkspaceTab&point={$point}&id={$tab->id}&request={$response_uri|escape:'url'}{/devblocks_url}">{$tab->name}</a></li>
		{/foreach}

		{if $page->isWriteableByWorker($active_worker)}		
			<li><a href="{devblocks_url}ajax.php?c=pages&a=showAddTabs&page_id={$page->id}{/devblocks_url}">+</a></li>
		{/if}
	</ul>
</div> 
<br>

{include file="devblocks:cerberusweb.core::internal/whos_online.tpl"}

{$tab_selected_idx=0}
{foreach from=$tabs item=tab_label name=tabs}
	{if $tab_label==$selected_tab}{$tab_selected_idx = $smarty.foreach.tabs.index}{/if}
{/foreach}

<script type="text/javascript">
	$(function() {
		$tabs = $("#pageTabs");
		
		var tabs = $tabs.tabs( { 
			selected: {$tab_selected_idx}
		});
		
		$tabs.find('> ul').sortable({
			items:'> li.drag',
			forcePlaceholderWidth:true,
			update:function(e) {
				$tabs = $("#pageTabs");
				$page_tabs = $tabs.find('ul.ui-tabs-nav > li.drag[tab_id=*]');
				page_tab_ids = $page_tabs.map(function(e) {
					return $(this).attr('tab_id');
				}).get().join(',');
	
				genericAjaxGet('', 'c=pages&a=setTabOrder&page_id={$page->id}&tabs=' + page_tab_ids);
			}
		});
		
		$workspace = $('#frmWorkspacePage{$page->id}');
		
		// Edit workspace actions
		{if $page->isWriteableByWorker($active_worker)}
		$workspace.find('button.edit').click(function(e) {
			$popup = genericAjaxPopup('peek','c=internal&a=showEditWorkspacePage&id={$page->id}',null,true,'600');
			$popup.one('workspace_save',function(e) {
				window.location.href = '{devblocks_url}c=pages&id={$page->id}-{$page->name|devblocks_permalink}{/devblocks_url}';
			});
			$popup.one('workspace_delete',function(e) {
				window.location.href = '{devblocks_url}c=pages{/devblocks_url}';
			});
		});
		{/if}
		
		// Add/Remove in menu
		{if $page->isReadableByWorker($active_worker)}
		$workspace.find('button.add').click(function(e) {
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
					
					$this.html('<span class="cerb-sprite2 sprite-plus-circle"></span> Menu');
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
				
				$this.html('<span class="cerb-sprite2 sprite-minus-circle"></span> Menu');
		
				genericAjaxGet('', 'c=pages&a=doToggleMenuPageJson&page_id=' + $this.attr('page_id') + '&toggle=1');
			}
		});
		{/if}
		
	});
</script>
