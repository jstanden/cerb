<form action="{devblocks_url}{/devblocks_url}" id="frmWorkspacePage{$page->id}" method="POST" style="margin-top:5px;">
	<input type="hidden" name="c" value="internal">
	<input type="hidden" name="a" value="">
	<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

	{$menu = DAO_WorkerPref::getAsJson($active_worker->id, 'menu_json', '[]')}
	{$in_menu = in_array($page->id, $menu)}
	
	<div style="float:left;">
		<h2>{$page->name}</h2>
	</div>
	
	<div style="float:right;">
		{$page_owner_meta = $page->getOwnerMeta()}
		{if !empty($page_owner_meta)}
			<div style="display:inline-block;margin-right:5px;vertical-align:middle;">
				Managed by
				<img src="{devblocks_url}c=avatars&context={$page->owner_context}&context_id={$page->owner_context_id}{/devblocks_url}?v={$page_owner_meta.updated}" style="height:1.2em;width:1.2em;border-radius:0.75em;vertical-align:middle;">
				<b>
				{if $page->owner_context_id} 
				<a class="cerb-peek-trigger no-underline" data-context="{$page->owner_context}" data-context-id="{$page->owner_context_id}">{$page_owner_meta.name}</a>
				{else}
				{$page_owner_meta.name}
				{/if}
				</b>
			</div>
		{/if}
	
		<button class="add" type="button" page_id="{$page->id}" page_label="{$page->name|lower}" page_url="{devblocks_url}c=pages&page={$page->id}-{$page->name|devblocks_permalink}{/devblocks_url}">{if $in_menu}<span class="cerb-icons cerb-icon-circle-minus"></span>{else}<span class="cerb-icons cerb-icon-circle-plus"></span>{/if} Menu</button>

		{if CerberusContexts::isWriteableByActor(CerberusContexts::CONTEXT_WORKSPACE_PAGE, $page, $active_worker)}
		<div style="display:inline-block;vertical-align:middle;">
			<button class="config-page" type="button"><span class="cerb-icons cerb-icon-gear"></span><span class="cerb-icons cerb-icon-chevron-down"></span></button>
			<ul hidden data-cerb-config-menu>
				{if CerberusContexts::isWriteableByActor(CerberusContexts::CONTEXT_WORKSPACE_PAGE, $page, $active_worker)}
					{if $active_worker->hasPriv("contexts.{CerberusContexts::CONTEXT_WORKSPACE_PAGE}.update")}<li data-icon="edit"><a class="edit-page" data-context="{CerberusContexts::CONTEXT_WORKSPACE_PAGE}" data-context-id="{$page->id}" data-edit="true">Edit Page</a></li>{/if}
					{if $page->extension_id == 'core.workspace.page.workspace' && $active_worker->hasPriv("contexts.{CerberusContexts::CONTEXT_WORKSPACE_TAB}.update")}<li data-icon="edit"><a class="edit-tab" data-context="{CerberusContexts::CONTEXT_WORKSPACE_TAB}" data-context-id="" data-edit="true">Edit Tab</a></li>{/if}
				{/if}
				{if $active_worker->hasPriv("contexts.{CerberusContexts::CONTEXT_WORKSPACE_PAGE}.export")}<li data-icon="upload"><a class="export-page">Export Page</a></li>{/if}
				{if $page->extension_id == 'core.workspace.page.workspace' && $active_worker->hasPriv("contexts.{CerberusContexts::CONTEXT_WORKSPACE_TAB}.export")}<li data-icon="upload"><a class="export-tab">Export Tab</a></li>{/if}
			</ul>
		</div>
		{/if}
	</div>

	<div style="clear:both;"></div>
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

				const $tabs = $("#pageTabs{$page->id}");
				let $selected_tab = $tabs.find('li.ui-tabs-active').first();

				if(0 == $selected_tab.length)
					return;

				const tab_id = $selected_tab.attr('data-tab-id');

				// On this page
				if(e.page_id == {$page->id}) {
					if(0 != $tabs) {
						const selected_idx = $tabs.tabs('option','active');
						$tabs.tabs('load', selected_idx);

						if(null != e.label) {
							$selected_tab = $tabs.find('> ul > li.ui-tabs-active');
							$selected_tab.find('a').text(e.label);
						}
					}

				} else { // If moved to another page, remove the tab
					const evt = jQuery.Event('cerb-peek-deleted');
					evt.id = e.id;
					evt.label = e.label;
					$(this).trigger(evt);
				}

			})
			.on('cerb-peek-deleted', function(e) {
				e.stopPropagation();

				const $tabs = $("#pageTabs{$page->id}");
				const $selected_tab = $tabs.find('li.ui-tabs-active').first();

				if(0 == $selected_tab.length)
					return;

				const tab_id = $selected_tab.attr('data-tab-id');

				if(0 != $tabs.length) {
					const tab = $tabs.find('.ui-tabs-nav li:eq(' + $tabs.tabs('option','active') + ')').remove();
					const panelId = tab.attr('aria-controls');
					$('#' + panelId).remove();
					$tabs.tabs('refresh');
				}
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
		
		let $tabs = $("#pageTabs{$page->id}");
		let $selected_tab = $tabs.find('li.ui-tabs-active').first();
		
		if(0 == $selected_tab.length)
			return;
		
		let tab_id = $selected_tab.attr('data-tab-id');
		
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
			
			$item.effect('transfer', { to:$this, className:'effects-transfer' }, 500, function() {
				$(this).remove();
			});
			
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
			
			$this.effect('transfer', { to:$li, className:'effects-transfer' }, 500, function() {
				$li.css('visibility','visible');
			});
			
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