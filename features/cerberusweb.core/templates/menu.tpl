{if !empty($visit)}
<div id="tourHeaderMenu"></div>

<ul class="navmenu cerb-no-print">
	{foreach from=$pages_menu item=menu_page}
		{$is_selected = $page->id == 'core.page.pages' && isset($response_path[1]) && intval($response_path[1])==$menu_page.id}
		<li class="{if $is_selected}selected{/if} drag" data-page="{$menu_page.id}">
			<div>
				<a href="{devblocks_url}c=pages&page={$menu_page.id}-{$menu_page.name|devblocks_permalink}{/devblocks_url}">{$menu_page.name|lower}</a>
			</div>
				
			{if $menu_page.tabs}
			<ul class="cerb-float cerb-hidden" data-cerb-navmenu-submenu>
				{foreach from=$menu_page.tabs item=menu_tab}
				<li data-href="{devblocks_url}c=pages&page={$menu_page.id}-{$menu_page.name|devblocks_permalink}&tab={$menu_tab->name|devblocks_permalink|lower}{/devblocks_url}">
					<div>
						<b>{$menu_tab->name}</b>
					</div>
				</li>
				{/foreach}
			</ul>
			{/if}
		</li>
	{/foreach}
	
	<li style="border-right:0;" class="add {if $page->id=='core.page.pages' && count($response_path)==1}selected{/if}">
		<a href="{devblocks_url}c=pages{/devblocks_url}">{if $page->id=='core.page.pages' && count($response_path)==1}<span class="glyphicons glyphicons-chevron-down" style="font-size:12px;"></span>{else}<span class="glyphicons glyphicons-chevron-down" style="font-size:12px;"></span>{/if}</a>
	</li>
	
	{if $active_worker->is_superuser}
	<li class="{if $page->id=='core.page.configuration'}selected{/if}" style="float:right;">
		<a href="{devblocks_url}c=config{/devblocks_url}">{'header.config'|devblocks_translate|lower}</a>
	</li>
	{/if}

	<li class="tour-navmenu-search{if $page->id=='core.page.search'} selected{/if}" style="float:right;">
		<a href="javascript:;" class="submenu"><span class="glyphicons glyphicons-search"></span> <span class="glyphicons glyphicons-chevron-down" style="{if $page->id=='core.page.search'}color:white;{else}{/if}"></span></a>
	</li>
</ul>
<div style="clear:both;" class="navmenu-submenu cerb-no-print"></div>

<script type="text/javascript">
$(function() {
	var $menu = $('UL.navmenu');
	
	{$user_agent = DevblocksPlatform::getClientUserAgent()}
	
	{if is_array($user_agent) && 0 != strcasecmp($user_agent.platform|default:'', 'Android')}
	$menu.find('[data-cerb-navmenu-submenu]')
		.menu({
			select: function(event, ui) {
				event.stopPropagation();

				if(!ui.item.is('li'))
					return;
				
				let href = ui.item.attr('data-href');
				
				if(null == href)
					return;
				
				if(event.metaKey) {
					var a = document.createElement('a');
					a.style.display = 'none';
					document.body.appendChild(a);
					a.href = href;
					a.target = '_blank';
					a.click();
					a.remove();
					
				} else {
					window.location.href = href;
				}
			}
		})
	;
	
	$menu.sortable({
		items: '> li.drag',
		distance: 20,
		stop:function(e) {
			e.stopPropagation();
			
			var $pages = $(this).find('li.drag[data-page]');
			var page_ids = $pages.map(function(e) {
				return $(this).attr('data-page');
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
			function() {
				var $this = $(this);
				$this
					.find('[data-cerb-navmenu-submenu]')
					.show()
					.position({ my: "left top", at: "left bottom-2", of: $this, collision: "fit" })
				;
			},
			function() {
				var $this = $(this);
				$this.find('[data-cerb-navmenu-submenu]').hide();
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
		
		var $link = $target.find('a').first();
		
		if($link.length > 0)
			window.location.href = $link.attr('href');
	});

	var $search_button = $menu.find('> LI A.submenu');
	var $search_menu = null;
	
	$search_button
		.closest('li')
		.click(function(e) {
			e.stopPropagation();
			
			// Is the menu currently visible?
			if(null == $search_menu) {
				// If not, show a spinner and fetch it via Ajax
				genericAjaxGet('', 'c=search&a=getSearchMenu', function(html) {
					$search_menu = $(html)
						.hide()
						.insertAfter($search_button)
						.menu()
					;

					$search_menu.find('li.cerb-bot-trigger')
						.cerbBotTrigger({
							'width': '80%',
							'caller': {
								'name': 'cerb.toolbar.global.search',
								'params': { }
							},
							'start': function(formData) {
								$search_menu.empty().remove();
								$search_menu = null;
							},
							'done': function(e) {
								e.stopPropagation();
								// If the interaction rejected validation
								if(e.eventData.hasOwnProperty('exit') && 'return' === e.eventData.exit) {
									if(e.eventData.hasOwnProperty('return') && e.eventData.return.hasOwnProperty('record_type')) {
										var search_context = e.eventData.return.record_type;
										genericAjaxPopup('search' + Devblocks.uniqueId(),'c=search&a=openSearchPopup&context=' + encodeURIComponent(search_context) + '&q=*&qr=', null, false, '90%');
									}
								}
							},
							'reset': function(e) {
							},
							'error': function(e) {
							},
							'abort': function(e) {
							}
						})
					;

					$search_menu.show().position({ my: "right top", at: "right bottom", of: $search_button, collision: "fit" });
					
					$search_menu.focus().menu('focus', null, $search_menu.find('.ui-menu-item').first());
				});
				
			} else {
				// If so, close it
				$search_menu.empty().remove();
				$search_menu = null;
			}
		})
	;
});
</script>
{/if}
