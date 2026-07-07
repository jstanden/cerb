<form action="{devblocks_url}{/devblocks_url}" id="frmWorkspacePage{$page->id}" method="POST" style="margin-top:5px;">
	<input type="hidden" name="c" value="internal">
	<input type="hidden" name="a" value="">
	<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

	{$menu = DAO_WorkerPref::getAsJson($active_worker->id, 'menu_json', '[]')}
	{$in_menu = in_array($page->id, $menu)}

	<div class="cerb-ui-header cerb-ui-header--tight cerb-u-items-center">
		<div>
			<div class="cerb-ui-header--title">{$page->name}</div>
		</div>
		<div class="cerb-ui-header--right">
			{$page_owner_meta = $page->getOwnerMeta()}
			{if !empty($page_owner_meta)}
				<div class="cerb-ui-pill cerb-u-text-muted" title="Managed by {$page_owner_meta.name}">
					<span class="cerb-icons cerb-icon-tag"></span>
					<span class="cerb-font-bold">
						{if $page->owner_context_id}
							<a class="cerb-peek-trigger cerb-u-text-muted cerb-u-underline-hover" data-context="{$page->owner_context}" data-context-id="{$page->owner_context_id}">{$page_owner_meta.name}</a>
						{else}
							{$page_owner_meta.name}
						{/if}
					</span>
				</div>
			{/if}

			<div class="cerb-ui-toolbar-strip">
				<button class="add cerb-ui-toolbar-button" type="button" page_id="{$page->id}" page_label="{$page->name|lower}" page_url="{devblocks_url}c=pages&page={$page->id}-{$page->name|devblocks_permalink}{/devblocks_url}">{if $in_menu}<span class="cerb-icons cerb-icon-circle-minus"></span>{else}<span class="cerb-icons cerb-icon-circle-plus"></span>{/if} Menu</button>

				{if CerberusContexts::isWriteableByActor(CerberusContexts::CONTEXT_WORKSPACE_PAGE, $page, $active_worker)}
					<button class="config-page cerb-ui-toolbar-button" type="button"><span class="cerb-icons cerb-icon-gear"></span><span class="cerb-icons cerb-icon-chevron-down cerb-ui-toolbar--caret"></span></button>
					<ul hidden data-cerb-config-menu>
						{if CerberusContexts::isWriteableByActor(CerberusContexts::CONTEXT_WORKSPACE_PAGE, $page, $active_worker)}
							{if $active_worker->hasPriv("contexts.{CerberusContexts::CONTEXT_WORKSPACE_PAGE}.update")}<li data-icon="edit"><a class="edit-page" data-context="{CerberusContexts::CONTEXT_WORKSPACE_PAGE}" data-context-id="{$page->id}" data-edit="true">Edit Page</a></li>{/if}
							{if $page->extension_id == 'core.workspace.page.workspace' && $active_worker->hasPriv("contexts.{CerberusContexts::CONTEXT_WORKSPACE_TAB}.update")}<li data-icon="edit"><a class="edit-tab" data-context="{CerberusContexts::CONTEXT_WORKSPACE_TAB}" data-context-id="" data-edit="true">Edit Tab</a></li>{/if}
						{/if}
						{if $active_worker->hasPriv("contexts.{CerberusContexts::CONTEXT_WORKSPACE_PAGE}.export")}<li data-icon="upload"><a class="export-page">Export Page</a></li>{/if}
						{if $page->extension_id == 'core.workspace.page.workspace' && $active_worker->hasPriv("contexts.{CerberusContexts::CONTEXT_WORKSPACE_TAB}.export")}<li data-icon="upload"><a class="export-tab">Export Tab</a></li>{/if}
					</ul>
				{/if}
			</div>
		</div>
	</div>
</form>

<div style="margin-top:5px;">
	{if is_a($page_extension, 'Extension_WorkspacePage')}
		{$page_extension->renderPage($page)}
	{/if}
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	const $workspace = $('#frmWorkspacePage{$page->id}');
	const $frm = $('form#frmWorkspacePage{$page->id}');

	// Form

	$frm.find('.cerb-peek-trigger')
		.cerbPeekTrigger()
		;

	// Config menu

	const configMenuEl = $frm.find('ul[data-cerb-config-menu]')[0];

	if(configMenuEl) {
		const configMenu = new CerbUI.Menu(configMenuEl, {
			onRenderItem: function(rendered, source) {
				const icon = source.dataset.icon;
				if(icon) {
					const ico = document.createElement('span');
					ico.className = 'cerb-icons cerb-icon-' + icon;
					ico.setAttribute('aria-hidden', 'true');
					ico.style.marginRight = '0.5em';
					rendered.insertBefore(ico, rendered.firstChild);
				}
			}
			// default onSelect clicks the source <a> -> fires the cerbPeekTrigger / export handlers bound below
		});

		$frm.find('button.config-page').on('click', function(e) {
			e.stopPropagation();
			configMenu.isOpen() ? configMenu.close() : configMenu.open(this);
		});
	}
	
	// Edit workspace actions
	
	{if CerberusContexts::isWriteableByActor(CerberusContexts::CONTEXT_WORKSPACE_PAGE, $page, $active_worker)}
		// Edit page
		$workspace.find('a.edit-page')
			.cerbPeekTrigger()
			.on('cerb-peek-saved', function() {
				window.location.href = '{devblocks_url}c=pages&id={$page->id}-{$page->name|devblocks_permalink}{/devblocks_url}';
			})
			.on('cerb-peek-deleted', function() {
				window.location.href = '{devblocks_url}c=pages{/devblocks_url}';
			})
			;
		
		// Edit tab
		$workspace.find('a.edit-tab')
			.cerbPeekTrigger()
			.on('cerb-peek-saved', function(e) {
				e.stopPropagation();

				const cerbTabs = window.CerbUI?.Tabs?.from(document.getElementById('pageTabs{$page->id}'));

				if(!cerbTabs || !cerbTabs.activeTab)
					return;

				// On this page
				if(e.page_id == {$page->id}) {
					cerbTabs.refresh();

					if(null != e.label)
						$(cerbTabs.activeTab.li).find('a').text(e.label);

				} else { // If moved to another page, remove the tab
					const evt = jQuery.Event('cerb-peek-deleted');
					evt.id = e.id;
					evt.label = e.label;
					$(this).trigger(evt);
				}
			})
			.on('cerb-peek-deleted', function(e) {
				e.stopPropagation();

				const cerbTabs = window.CerbUI?.Tabs?.from(document.getElementById('pageTabs{$page->id}'));

				if(!cerbTabs || !cerbTabs.activeTab)
					return;

				// Remove the active tab; sync() drops its auto-created panel and activates a sibling
				$(cerbTabs.activeTab.li).remove();
				cerbTabs.sync();
			})
			;
	{/if}
	
	// Export page
	$workspace.find('a.export-page').click(function(e) {
		e.stopPropagation();
		CerbUI.Dialog.fromAjax('c=pages&a=renderExport&id={$page->id}', { modal: true, width: 600, title: 'Export Page' });
	});
	
	// Export tab
	$workspace.find('a.export-tab').click(function(e) {
		e.stopPropagation();
		
		const cerbTabs = window.CerbUI?.Tabs?.from(document.getElementById('pageTabs{$page->id}'));
		const tab_id = (cerbTabs && cerbTabs.activeTab) ? $(cerbTabs.activeTab.li).attr('data-tab-id') : null;

		if(null == tab_id)
			return;

		CerbUI.Dialog.fromAjax('c=pages&a=renderExportTab&id=' + encodeURIComponent(tab_id), { modal: true, width: 600, title: 'Export Tab' });
	});
	
	// Add/Remove in menu
	$workspace.find('button.add').click(function(e) {
		const $this = $(this);
		const $menu = $('BODY UL.navmenu:first');
		const $item = $menu.find('li.drag[data-page="'+$this.attr('page_id')+'"]');
		
		// Remove
		if(1 == $item.length) {
			// Is the page already in the menu?
			$item.css('visibility','hidden');
			
			if(window.CerbUI && CerbUI.effects)
				CerbUI.effects.transfer($item[0], $this[0], { onEnd: function() { $item.remove(); } });
			else
				$item.remove();

			$this.html('<span class="cerb-icons cerb-icon-circle-plus"></span> Menu');

			const formData = new FormData();
			formData.set('c', 'pages');
			formData.set('a', 'toggleMenuPageJson');
			formData.set('page_id', $this.attr('page_id'));
			formData.set('toggle', '0');
			genericAjaxPost(formData);

		// Add
		} else {
			// Add the menu item if it doesn't exist (e.g. removed on this page cycle)
			const $li = $('<li class="drag"/>').attr('data-page',$this.attr('page_id'));
			$li.append($('<a/>').attr('href',$this.attr('page_url')).text($this.attr('page_label')));

			$li
				.css('visibility','hidden')
				.addClass('selected')
				;

			const $marker = $menu.find('li.add');
			
			if(0 == $marker.length) {
				$li.prependTo($menu);
				
			} else {
				$li.insertBefore($marker);
				
			}
			
			if(window.CerbUI && CerbUI.effects)
				CerbUI.effects.transfer($this[0], $li[0], { onEnd: function() { $li.css('visibility','visible'); } });
			else
				$li.css('visibility','visible');

			$this.html('<span class="cerb-icons cerb-icon-circle-minus"></span> Menu');

			const formData = new FormData();
			formData.set('c', 'pages');
			formData.set('a', 'toggleMenuPageJson');
			formData.set('page_id', $this.attr('page_id'));
			formData.set('toggle', '1');
			genericAjaxPost(formData);
		}
	});
	
});
</script>