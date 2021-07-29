{if !empty($visit)}
<div id="tourHeaderMenu"></div>

<ul class="navmenu cerb-no-print">
	{foreach from=$pages_menu item=menu_page}
		{$is_selected = $page->id == 'core.page.pages' && isset($response_path[1]) && intval($response_path[1])==$menu_page.id}
		<li class="{if $is_selected}selected{/if} drag" page_id="{$menu_page.id}" style="position:relative;">
			<a href="{devblocks_url}c=pages&page={$menu_page.id}-{$menu_page.name|devblocks_permalink}{/devblocks_url}">{$menu_page.name|lower}</a>
			
			{if $menu_page.tabs}
			<ul class="cerb-popupmenu cerb-float">
				{foreach from=$menu_page.tabs item=menu_tab}
				<li>
					<a href="{devblocks_url}c=pages&page={$menu_page.id}-{$menu_page.name|devblocks_permalink}&tab={$menu_tab->name|devblocks_permalink|lower}{/devblocks_url}">{$menu_tab->name}</a>
				</li>
				{/foreach}
			</ul>
			{/if}
		</li>
	{/foreach}
	
	<li style="border-right:0;" class="add {if $page->id=='core.page.pages' && count($response_path)==1}selected{/if}">
		<a href="{devblocks_url}c=pages{/devblocks_url}" style="font-weight:normal;text-decoration:none;">{if $page->id=='core.page.pages' && count($response_path)==1}<span class="glyphicons glyphicons-chevron-down" style="font-size:12px;color:white;"></span>{else}<span class="glyphicons glyphicons-chevron-down" style="font-size:12px;color:rgb(50,50,50);"></span>{/if}</a>
	</li>
	
	{if $active_worker->is_superuser}
	<li class="{if $page->id=='core.page.configuration'}selected{/if}" style="float:right;">
		<a href="{devblocks_url}c=config{/devblocks_url}">{'header.config'|devblocks_translate|lower}</a>
	</li>
	{/if}

	<li class="tour-navmenu-search{if $page->id=='core.page.search'} selected{/if}" style="float:right;">
		<a href="javascript:;" class="submenu"><span class="glyphicons glyphicons-search"></span> <span class="glyphicons glyphicons-chevron-down" style="{if $page->id=='core.page.search'}color:white;{else}{/if}"></span></a>
		<ul class="cerb-popupmenu cerb-float">
			{$is_search_collapsed = false}
			{foreach from=$search_menu item=search_item}
				<li style="{if !$search_item.visible}display:none;{$is_search_collapsed=true}{/if}"><a href="javascript:;" data-context="{$search_item.context}">{$search_item.label|capitalize}</a></li>
			{/foreach}
			{if $is_search_collapsed}
			<li>
				<a href="javascript:;" class="cerb-show-all" style="font-weight:normal;">show all &raquo;</a>
			</li>
			{/if}
		</ul>
	</li>
</ul>
<div style="clear:both;background-color:rgb(100,135,225);height:5px;" class="cerb-no-print"></div>

<script type="text/javascript">
$(function() {
	var $menu = $('UL.navmenu');
	
	{$user_agent = DevblocksPlatform::getClientUserAgent()}
	
	{if is_array($user_agent) && 0 != strcasecmp($user_agent.platform, 'Android')}
	$menu.find('> li.drag .cerb-popupmenu')
		.menu()
		;
	
	$menu.sortable({
		items:'> li.drag',
		distance: 20,
		stop:function(e) {
			e.stopPropagation();
			
			var $pages = $(this).find('li.drag[page_id]');
			var page_ids = $pages.map(function(e) {
				return $(this).attr('page_id');
			}).get().join(',');

			var formData = new FormData();
			formData.set('c', 'pages');
			formData.set('a', 'setOrder');
			formData.set('pages', page_ids);

			genericAjaxPost(formData, null, null);
		}
	});
	
	$menu
		.find('> li.drag')
		.hover(
			function(e) {
				var $this = $(this);
				$this.find('.cerb-popupmenu').show();
			},
			function(e) {
				var $this = $(this);
				$this.find('.cerb-popupmenu').hide();
			}
		)
		;
	
	$menu
		.find('> li.drag')
		.hoverIntent({
			sensitivity:10,
			interval:750,
			timeout:250,
			over:function(e) {
				var $this = $(this);
				$this.css('cursor', 'move');
				$this.children().css('cursor', 'move');
			},
			out:function(e) {
				var $this = $(this);
				$this.css('cursor', 'pointer');
				$this.children().css('cursor', 'pointer');
			}
		})
		;
	{/if}

	// Allow clicking anywhere in the menu item cell
	$menu.find('> li').click(function(e) {
		e.stopPropagation();
		
		var $target = $(e.target);
		
		if(!$target.is('li'))
			return;
		
		$link = $target.find('> a');
		
		if($link.length > 0)
			window.location.href = $link.attr('href');
	});

	var $search_button = $menu.find('> LI A.submenu');
	var $search_menu = $search_button.next('ul.cerb-popupmenu');
	var $show_all = $search_menu.find('a.cerb-show-all');
	
	$search_button
		.closest('li')
		.hoverIntent({
			sensitivity:10,
			interval:100,
			over:function(e) {
				$menu = $(this).find('ul:first');
				$menu
					.show()
					.trigger('cerb-refresh')
				;
			},
			timeout:500,
			out:function(e) {
				$(this).find('ul:first').hide();
			}
		})
		.click(function(e) {
			$menu = $(this).find('ul:first');
			$menu
				.show()
				.trigger('cerb-refresh')
			;
			$menu.find('li:first a')
				.focus()
				;
		})
	;
	
	$search_menu
		.css('margin-top', '-2px')
		.find('> li')
			.click(function(e) {
				e.stopPropagation();

				var $this = $(this);
				var $search_menu = $this.closest('.cerb-popupmenu');
				var $link = $this.find('> a');
				
				if($link.length == 0)
					return;
				
				var search_context = $link.attr('data-context');
				
				if(!search_context || search_context.length == 0)
					return;
				
				var $window = genericAjaxPopup('search' + Devblocks.uniqueId(),'c=search&a=openSearchPopup&context=' + encodeURIComponent(search_context) + '&q=*&qr=', null, false, '90%');
				
				$search_menu.hide();
			})
		;
	
	$search_menu
		.on('cerb-refresh', function(e) {
			var items = $search_menu.find('a[data-context]:visible').length;
			
			if(items > 20) {
				var cols = Math.min(Math.floor(items / 20) + 1, 4);
				var width = (185 * cols);
				
				if(width > window.innerWidth)
					width = window.innerWidth - 50;
				
				$search_menu
					.css('width', '' + width + 'px')
					.css('column-width', '175px')
					.css('column-count', 'auto')
					.css('column-gap', '10px')
					;
			} else {
				$search_menu
					.css('width', null)
					.css('column-width', null)
					.css('column-count', null)
					.css('column-gap', null)
					;
			}
			
			$search_menu.position({ my: "right top", at: "right bottom", of: $search_button, collision: "fit" })
		})
		.trigger('cerb-refresh')
	;
	
	$show_all.closest('li').on('click', function(e) {
		e.preventDefault();
		
		var $a = $(this);
		
		$search_menu.find('> li').show();
		
		$a.hide();
		
		$search_menu.trigger('cerb-refresh');
	});
	
});
</script>
{/if}
