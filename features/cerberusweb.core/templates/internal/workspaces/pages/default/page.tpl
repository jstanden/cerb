{$is_writeable = Context_WorkspacePage::isWriteableByActor($page, $active_worker)}
{if !$is_writeable && empty($page_tabs)}
	<div class="help-box">
		<h1 style="margin-bottom:5px;text-align:left;">This workspace is empty</h1>
		
		<p>
			This page has no content and you don't have permission to modify it.  You'll have to wait until someone else adds something.
		</p>
	</div>
{else}

{if array_key_exists('tab_style', $page->extension_params) && 'menu' == $page->extension_params.tab_style}
<h2 class="cerb-page-tab--title" style="display:inline-block;color:black;margin:-5px 0 0 0;padding:0;"><a href="javascript:;" style="text-decoration:none;"></a> <span class="glyphicons glyphicons-chevron-down" style="font-size:12px;vertical-align:middle;"></span></h2>

<ul class="cerb-tab-switcher-menu cerb-popupmenu cerb-float">
	{foreach from=$page_tabs item=tab name=page_tabs}
	<li>
		<a href="javascript:;" data-index="{$smarty.foreach.page_tabs.index}">{$tab->name}</a>
	</li>
	{/foreach}
</ul>
{/if}

<div id="pageTabs{$page->id}">
	{if array_key_exists('tab_style', $page->extension_params) && 'menu' == $page->extension_params.tab_style}
	<ul style="display:none;">
	{else}
	<ul>
	{/if}
		{$tabs = []}
		
		{foreach from=$page_tabs item=tab}
			{$tabs[] = "{$tab->name|lower|devblocks_permalink}"}
			<li class="drag" tab_id="{$tab->id}">
				<a href="{devblocks_url}ajax.php?c=pages&a=renderTab&point={$point}&id={$tab->id}&request={$response_uri|escape:'url'}{/devblocks_url}">
					{$tab->name}
				</a>
			</li>
		{/foreach}

		{if $is_writeable && $active_worker->hasPriv("contexts.{CerberusContexts::CONTEXT_WORKSPACE_TAB}.create")}
			<li><a href="{devblocks_url}ajax.php?c=pages&a=renderAddTabs&page_id={$page->id}{/devblocks_url}">&nbsp;<span class="glyphicons glyphicons-cogwheel"></span>&nbsp;</a></li>
		{/if}
	</ul>
</div>
{/if}
	
<script type="text/javascript">
$(function() {
	// Set the browser tab label to the record label
	document.title = "{$page->name|escape:'javascript' nofilter} - {$settings->get('cerberusweb.core','helpdesk_title')|escape:'javascript' nofilter}";
	
	var $tabs = $("#pageTabs{$page->id}");

	{if array_key_exists('tab_style', $page->extension_params) && 'menu' == $page->extension_params.tab_style}
	var $tab_switcher = $tabs.prevAll('h2.cerb-page-tab--title');
	var $tab_switcher_menu = $tab_switcher.next('.cerb-tab-switcher-menu').menu({
		select: function(event, ui) {
			var tab_index = ui.item.find('a').attr('data-index');
			$tabs.tabs('option', 'active', tab_index);
			$tab_switcher_menu.hide();
		}
	});
	
	$tab_switcher.on('click', function(e) {
		$tab_switcher_menu.toggle();
	});
	{/if}
	
	var tabOptions = Devblocks.getDefaultjQueryUiTabOptions();
	tabOptions.collapsible = true;
	tabOptions.active = false;
	
	var tabActiveIndex = 0;
	
	{if isset($tab_selected) && in_array($tab_selected, $tabs)}
	{$tab_idx = array_search($tab_selected, $tabs)}
	tabActiveIndex = {$tab_idx};
	Devblocks.setjQueryUiTabSelected('pageTabs{$page->id}', {$tab_idx});
	{else}
	tabActiveIndex = Devblocks.getjQueryUiTabSelected('pageTabs{$page->id}');
	{/if}
	
	tabOptions.create = function(e, ui) {
		var tab_id = $(ui.tab).attr('tab_id');
		
		var $frm = $('#frmWorkspacePage{$page->id}');
		var $menu = $frm.find('ul.cerb-popupmenu');
		
		if(undefined == tab_id) {
			$menu.find('a.edit-tab').attr('data-context-id', '');
			$menu.find('a.edit-tab').parent().hide();
			$menu.find('a.export-tab').parent().hide();
		} else {
			$menu.find('a.edit-tab').attr('data-context-id', tab_id);
			$menu.find('a.edit-tab').parent().show();
			$menu.find('a.export-tab').parent().show();
		}
	};
	
	tabOptions.activate = function(e, ui) {
		Devblocks.getDefaultjQueryUiTabOptions().activate(e, ui);
		
		var $new_tab = $(ui.newTab);
		var tab_id = $new_tab.attr('tab_id');
		
		var $frm = $('#frmWorkspacePage{$page->id}');
		var $menu = $frm.find('ul.cerb-popupmenu');
		
		if(undefined == tab_id) {
			$menu.find('a.edit-tab').attr('data-context-id', '');
			$menu.find('a.edit-tab').parent().hide();
			$menu.find('a.export-tab').parent().hide();
		} else {
			$menu.find('a.edit-tab').attr('data-context-id', tab_id);
			$menu.find('a.edit-tab').parent().show();
			$menu.find('a.export-tab').parent().show();
		}
		
		// Update the label
		{if array_key_exists('tab_style', $page->extension_params) && 'menu' == $page->extension_params.tab_style}
		$tab_switcher.find('> a').text($.trim($new_tab.text()));
		{/if}
	};
	
	var tabs = $tabs.tabs(tabOptions);
	
	$tabs.tabs('option', 'active', tabActiveIndex);
	
	{$user_agent = DevblocksPlatform::getClientUserAgent()}
	
	{if is_array($user_agent) && 0 != strcasecmp($user_agent.platform, 'Android')}
	$tabs.find('ul')
		.find('> li.drag')
		.hoverIntent({
			interval:750,
			timeout:250,
			over:function(e) {
				$(this).css('cursor', 'move');
				$(this).children().css('cursor', 'move');
			},
			out:function() {
				$(this).css('cursor', 'pointer');
				$(this).children().css('cursor', 'pointer');
			}
		})
		;
	
	$tabs.find('> ul').sortable({
		items:'> li.drag',
		distance: 20,
		forcePlaceholderWidth:true,
		stop:function(e) {
			e.stopPropagation();
			
			$tabs = $("#pageTabs{$page->id}");
			$page_tabs = $tabs.find('ul.ui-tabs-nav > li.drag[tab_id]');
			page_tab_ids = $page_tabs.map(function(e) {
				return $(this).attr('tab_id');
			}).get().join(',');

			var formData = new FormData();
			formData.set('c', 'pages');
			formData.set('a', 'setTabOrder');
			formData.set('page_id', '{$page->id}');
			formData.set('tabs', page_tab_ids);

			genericAjaxPost(formData, '', '');
		}
	});
	{/if}
	
	// Keyboard shortcuts
	
	$(document).keypress(function(event) {
		var is_control_character = (event.which == 9 || event.which == 10 || event.which == 13 || event.which == 32);
		
		if($(event.target).is('button') && is_control_character)
			return;
			
		if($(event.target).is(':input') && !$(event.target).is('button'))
			return;
		
		// Allow these special keys
		switch(event.which) {
			case 42: // (*)
			case 126: // (~)
				break;
			default:
				if(event.altKey || event.ctrlKey || event.shiftKey || event.metaKey)
					return;
				break;
		}
		
		// [TODO] Intercept 91,93 ([] -- tabs prev/next)
		
		// How many tabs are we showing?
		var num_tabs = $tabs.find('> ul > li').length;
		
		if(0 == num_tabs)
			return;
		
		// Which tab is selected?
		var tab_id = $tabs.tabs('option','active');
		
		// Find the worklists on this tab
		var $worklists = $tabs.find('div.ui-tabs-panel').eq(tab_id).find('TABLE.worklistBody').closest('FORM');
		var $worklist = $('');
		
		// Are we confident about the user's intentions with this keystroke?
		indirect = true; // by default, we're not
		
		// Try to find a selected row in the worklists
		var $selected_row = $worklists.find('TABLE.worklistBody > TBODY > TR.selected').first();

		if($selected_row.length > 0) {
			$worklist = $selected_row.closest('form');
			
			// Since there's a selected row, we *are* confident.
			indirect = false;
			
		} else {
			// If nothing is selected, try to find a row being hovered over
			$selected_row = $worklists.find('TABLE.worklistBody > TBODY > TR.hover').first();
			
			if($selected_row.length > 0) {
				$worklist = $selected_row.closest('form');
				
			} else {
				// Otherwise, just focus the first worklist
				$worklist = $worklists.first();
			}
		}
		
		if($worklist.length > 0) {
			$worklist.each(function(e) {
				var view_id = $(this).find('input:hidden[name=view_id]').val();
				var $view = $('#viewForm' + view_id);
				
				// Intercept global worklist keys
				
				var hotkey_activated = true;
				
				switch(event.which) {
					case 42: // (*) reset filters
						$('#viewCustomFilters' + view_id + ' TABLE TBODY.full TD:first FIELDSET SELECT[name=_preset]').val('reset').trigger('change');
						break;
						
					case 45: // (-) remove last filter
						$('#viewCustomFilters' + view_id + ' TABLE TBODY.summary UL.bubbles LI:last A.delete').click();
						break;
						
					case 96: // (`) focus first subtotal
						$('#view' + view_id + '_sidebar FIELDSET:first TABLE:first TD:first A:first').focus();
						break;
						
					case 97:  // (a) select all
						try {
							$('#view' + view_id + ' TABLE.worklist input:checkbox').data('view_id',view_id).each(function(e) {
								view_id = $(this).data('view_id');
								// Trigger event
								e = jQuery.Event('select_all');
								e.view_id = view_id;
								e.checked = !$(this).is(':checked')
								$('#view' + view_id).trigger(e);
							});
						} catch(e) { }
						break;
						
					case 126: // (~) show subtotals
						$('#view' + view_id + '_sidebar FIELDSET UL.cerb-popupmenu').toggle().find('a:first').focus();
						break;
						
					default:
						hotkey_activated = false;
						break;
				}
				
				if(hotkey_activated) {
					event.preventDefault();
					return;
				}
				
				if($view.length > 0) {
					// Trigger event
					var e = jQuery.Event('keyboard_shortcut');
					e.view_id = view_id;
					e.indirect = indirect;
					e.keypress_event = event;
					$view.trigger(e);
				}
			});
		}
	});
});
</script>