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
			<ul hidden data-cerb-navmenu-submenu>
				{foreach from=$menu_page.tabs item=menu_tab}
				<li data-href="{devblocks_url}c=pages&page={$menu_page.id}-{$menu_page.name|devblocks_permalink}&tab={$menu_tab->name|devblocks_permalink|lower}{/devblocks_url}">{$menu_tab->name}</li>
				{/foreach}
			</ul>
			{/if}
		</li>
	{/foreach}
	
	<li style="border-right:0;" class="add {if $page->id=='core.page.pages' && count($response_path)==1}selected{/if}">
		<a href="{devblocks_url}c=pages{/devblocks_url}"><span class="cerb-icons cerb-icon-chevron-down"></span></a>
	</li>
	
	{if $active_worker->is_superuser}
	<li class="tour-navmenu-setup{if $page->id=='core.page.configuration'} selected{/if}" style="float:right;">
		<a href="{devblocks_url}c=config{/devblocks_url}"><span class="cerb-icons cerb-icon-gear"></span> {'header.config'|devblocks_translate|lower}</a>
	</li>
	{/if}

	<li class="tour-navmenu-search{if $page->id=='core.page.search'} selected{/if}" style="float:right;">
		<a class="submenu" title="{{'common.search'|devblocks_translate|capitalize}} (/)"><span class="cerb-icons cerb-icon-search"></span><span class="cerb-icons cerb-icon-chevron-down"></span></a>
	</li>
</ul>
<div style="clear:both;" class="navmenu-submenu cerb-no-print"></div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	const $menu = $('UL.navmenu');
	
	{$user_agent = DevblocksPlatform::getClientUserAgent()}
	
	{if is_array($user_agent) && 0 != strcasecmp($user_agent.platform|default:'', 'Android')}
	// Each page's tab submenu opens on hover; only one is open at a time (shared hover group)
	$menu.find('> li.drag').each(function() {
		const submenu = this.querySelector('[data-cerb-navmenu-submenu]');

		if(!submenu)
			return;

		new CerbUI.Menu(submenu, {
			hoverTrigger: this,
			hoverGroup: 'navmenu',
			filter: true,             // start typing to filter long tab lists (auto-captures filter keys)
			maxHeight: 'viewport',    // grow into the available viewport height instead of a fixed cap
			onSelect: function(rendered, source, e) {
				const href = source.dataset.href;

				if(!href)
					return;

				if(e && e.metaKey) {
					// Cmd/Ctrl-click opens the page+tab in a new browser tab
					const a = document.createElement('a');
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
		});
	});

	if(window.CerbUI && CerbUI.Sortable)
		new CerbUI.Sortable($menu.get(0), {
			items: '> li.drag',
			distance: 20,
			onSorted:function() {
				const $pages = $menu.find('li.drag[data-page]');
				const page_ids = $pages.map(function() {
					return $(this).attr('data-page');
				}).get().join(',');

				const formData = new FormData();
				formData.set('c', 'pages');
				formData.set('a', 'setOrder');
				formData.set('pages', page_ids);

				genericAjaxPost(formData, null, null);
			}
		});
	
	$menu
		.find('> li.drag')
		.hoverIntent({
			sensitivity:10,
			interval:750,
			timeout:250,
			over:function(e) {
				const $this = $(this);
				$this.css('cursor', 'move');
				$this.children().css('cursor', 'move');
			},
			out:function(e) {
				const $this = $(this);
				$this.css('cursor', 'pointer');
				$this.children().css('cursor', 'pointer');
			}
		})
		;
	{/if}

	// Allow clicking anywhere in the menu item cell
	$menu.find('> li').click(function(e) {
		e.stopPropagation();

		const $target = $(e.target);

		if(!$target.is('li'))
			return;

		const $link = $target.find('a').first();
		
		if($link.length > 0 && $link.attr('href'))
			window.location.href = $link.attr('href');
	});

	const $search_button = $menu.find('> LI A.submenu');
	let searchMenu = null;
	let $searchUl = null;

	// Tear down the fetched source <ul> + refs whenever the dropdown closes
	const teardownSearchMenu = function() {
		if($searchUl) { $searchUl.remove(); $searchUl = null; }
		searchMenu = null;
	};

	// CerbUI.Menu rebuilds each row from its text label, so inject the record-type glyph from data-icon
	const renderSearchIcon = function(li, src) {
		const name = src.getAttribute('data-icon');
		if(!name) return;
		const ico = document.createElement('span');
		ico.className = (name.charAt(0) === '.') ? name.slice(1).split('.').join(' ') : ('cerb-icons cerb-icon-' + name);
		ico.setAttribute('aria-hidden', 'true');
		ico.style.marginRight = '0.5em';
		li.insertBefore(ico, li.firstChild);
	};

	$search_button
		.closest('li')
		.click(function(e) {
			e.stopPropagation();

			// Toggle: a second click on the button closes the open menu
			if(searchMenu && searchMenu.isOpen()) {
				searchMenu.close();
				return;
			}

			genericAjaxGet('', 'c=search&a=getSearchMenu', function(html) {
				if(typeof e == 'object' && e.status && 200 !== e.status)
					return;

				// Keep the fetched <ul> in the DOM (hidden) so its cerbBotTrigger handlers + data-* persist;
				// CerbUI.Menu renders its own floating panel from it.
				$searchUl = $(html).hide().appendTo('body');

				// Fire the record-type search interaction when a row is chosen (preserves the
				// open-search-popup-on-return flow)
				$searchUl.find('li.cerb-bot-trigger')
					.cerbBotTrigger({
						'width': '80%',
						'caller': {
							'name': 'cerb.toolbar.global.search',
							'params': { }
						},
						'done': function(e) {
							e.stopPropagation();

							if('object' !== typeof e || !e.hasOwnProperty('eventData'))
								return;

							const $target = e.trigger;

							if(!$target.is('.cerb-bot-trigger'))
								return;

							if (e.eventData.exit === 'error') {

							} else if(e.eventData.exit === 'return') {
								Devblocks.interactionWorkerPostActions(e.eventData);

								if(e.eventData.hasOwnProperty('return') && e.eventData.return.hasOwnProperty('record_type')) {
									const search_context = e.eventData.return.record_type;
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

				searchMenu = new CerbUI.Menu($searchUl[0], {
					filter: true,          // auto-captures filter keys so they don't leak to page hotkeys
					filterPlaceholder: 'Filter record types…',
					filterDedupe: true,    // a suggested type at the top is the same action as its "All record types" copy
					filterShowPath: false, // drop the "All record types ›" eyebrow on filtered matches
					maxHeight: 'viewport',
					onRenderItem: renderSearchIcon,
					onClose: teardownSearchMenu,
					onSelect: function(rendered, source, e) {
						if(source && source.getAttribute('data-interaction-uri'))
							jQuery(source).trigger('click');
					}
				});

				searchMenu.open($search_button.closest('li')[0]);
			});
		})
	;

	{if $pref_keyboard_shortcuts}
	$(document).keyup(function(e) {
		if(!(191 === e.which))
			return;

		const $target = $(e.target);

		if(!$target.is('BODY, .cerb-bot-interactions-menu'))
			return;

		e.preventDefault();
		e.stopPropagation();

		$search_button.closest('li').click();
	});
	{/if}
});
</script>
{/if}
