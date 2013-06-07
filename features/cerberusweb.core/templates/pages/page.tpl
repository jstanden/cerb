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
			<button class="add" type="button" page_id="{$page->id}" page_label="{$page->name|lower}" page_url="{devblocks_url}c=pages&page={$page->id}-{$page->name|devblocks_permalink}{/devblocks_url}">{if $in_menu}<span class="cerb-sprite2 sprite-minus-circle"></span>{else}<span class="cerb-sprite2 sprite-plus-circle"></span>{/if} Menu</button>
		{/if}
	
		<div style="display:inline-block;">
			<button class="config-page split-left" type="button"><span class="cerb-sprite2 sprite-gear"></span></button><!--
			--><button class="config-page split-right" type="button"><span class="cerb-sprite sprite-arrow-down-white"></span></button>
			<ul class="cerb-popupmenu cerb-float">
				{if $page->isWriteableByWorker($active_worker)}
					<li><a href="javascript:;" class="edit-page">Edit Page</a></li>
					{if $page->extension_id == 'core.workspace.page.workspace'}<li><a href="javascript:;" class="edit-tab">Edit Tab</a></li>{/if}
				{/if}
				<li><a href="javascript:;" class="export-page">Export Page</a></li>
				{if $page->extension_id == 'core.workspace.page.workspace'}<li><a href="javascript:;" class="export-tab">Export Tab</a></li>{/if}
			</ul>
		</div>
	</div>

	<div style="clear:both;"></div>
</form>

<div style="margin-top:5px;">
	{if $page_extension instanceof Extension_WorkspacePage}
		{$page_extension->renderPage($page)}
	{/if}
</div>

<script type="text/javascript">
	$(function() {
		var $workspace = $('#frmWorkspacePage{$page->id}');
		var $frm = $('form#frmWorkspacePage{$page->id}');
		var $menu = $frm.find('ul.cerb-popupmenu');
		
		// Menu
		
		$menu
			.hover(
				function() {
				},
				function() {
					$(this).hide();
				}
			)
			;
		
		$menu.find('> li').click(function(e) {
			if($(e.target).is('a'))
				return;
			
			e.stopPropagation();
			$(this).find('> a').click();
		});
		
		$menu.siblings('button.config-page').click(function(e) {
			var $menu = $(this).siblings('ul.cerb-popupmenu');
			$menu.toggle();
			
			if($menu.is(':visible')) {
				var $div = $menu.closest('div');
				$menu.css('left', $div.position().left + $div.outerWidth() - $menu.outerWidth());
			}
		});
		
		// Edit workspace actions
		
		{if $page->isWriteableByWorker($active_worker)}
			// Edit page
			$workspace.find('a.edit-page').click(function(e) {
				e.stopPropagation();
				
				$popup = genericAjaxPopup('peek','c=pages&a=showEditWorkspacePage&id={$page->id}',null,true,'600');
				$popup.one('workspace_save',function(e) {
					window.location.href = '{devblocks_url}c=pages&id={$page->id}-{$page->name|devblocks_permalink}{/devblocks_url}';
				});
				$popup.one('workspace_delete',function(e) {
					window.location.href = '{devblocks_url}c=pages{/devblocks_url}';
				});
			});
			
			// Edit tab
			$workspace.find('a.edit-tab').click(function(e) {
				e.stopPropagation();
				
				$tabs = $("#pageTabs");
				$selected_tab = $tabs.find('li.ui-tabs-active').first();
				
				if(0 == $selected_tab.length)
					return;
				
				tab_id = $selected_tab.attr('tab_id');
				
				if(null == tab_id)
					return;
				
				$popup = genericAjaxPopup('peek','c=pages&a=showEditWorkspaceTab&id=' + tab_id,null,true,'600');
				
				$popup.one('workspace_save',function(json) {
					$tabs = $("#pageTabs");
					if(0 != $tabs) {
						selected_idx = $tabs.tabs('option','selected');
						$tabs.tabs('load', selected_idx);
						
						if(null != json.name) {
							$selected_tab = $tabs.find('> ul > li.ui-tabs-active');
							$selected_tab.find('a').html(json.name);
						}
					}
				});
				
				$popup.one('workspace_delete',function(e) {
					$tabs = $("#pageTabs");
					if(0 != $tabs) {
						$tabs.tabs('remove', $tabs.tabs('option','selected'));
					}
				});
			});
		{/if}
		
		// Export page
		$workspace.find('a.export-page').click(function(e) {
			e.stopPropagation();
			$popup = genericAjaxPopup('peek','c=pages&a=showExportWorkspacePage&id={$page->id}',null,true,'600');
		});
		
		// Export tab
		$workspace.find('a.export-tab').click(function(e) {
			e.stopPropagation();
			
			$tabs = $("#pageTabs");
			$selected_tab = $tabs.find('li.ui-tabs-active').first();
			
			if(0 == $selected_tab.length)
				return;
			
			tab_id = $selected_tab.attr('tab_id');
			
			if(null == tab_id)
				return;
			
			$popup = genericAjaxPopup('peek','c=pages&a=showExportWorkspaceTab&id=' + encodeURIComponent(tab_id),null,true,'600');
		});
		
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
